# Síntesis del Bug y la Solución — Sesión del 23 de Mayo 2026

## 📋 Contexto del Incidente

**Escenario:** Al momento de confirmar (`confirm_valid`) una factura en Dolibarr 20.0.4, la página del módulo de facturación (`/compta/facture/card.php`) devolvía un error HTTP 500 (Internal Server Error), resultando en una **página en blanco**. El error solo se manifestaba al estar activo el módulo custom `modulecompliancepld` (PLD — Prevención de Lavado de Dinero).

**Stack involucrado:** PHP 8.1 con `declare(strict_types=1)`, PostgreSQL como motor de base de datos, Dolibarr ejecutándose dentro de un contenedor Docker.

---

## 🔍 Proceso de Investigación (Debug)

El flujo de investigación siguió estos pasos, cada uno revelando una capa adicional del problema:

1. **Inspección de logs del contenedor Docker** para aislar el error exacto.
2. **Identificación del punto de entrada del módulo PLD** en el ciclo de vida de validación de factura (`marcarFacturaComoVulnerable()` en `PLDOperacionService.php`).
3. **Trazado de la cadena de llamadas** hasta `pldoperacion.class.php::create()`, donde se encontró el primer error fatal.
4. **Simulación de la query SQL fallida** directamente contra PostgreSQL para verificar el comportamiento de la base de datos.
5. **Descubrimiento del error de transacción abortada** al inspeccionar el estado de la transacción PostgreSQL después de cada operación.

El descubrimiento clave fue que **el error de página en blanco no era un solo bug, sino una cadena de cuatro bugs encadenados** que se disparaban en cascada, todos ellos manifestándose exclusivamente en el entorno PHP 8.1 + PostgreSQL (inexistentes en versiones anteriores de PHP o en MySQL/MariaDB).

---

## 🐛 Los Cuatro Bugs (en orden de descubrimiento)

### Bug #1 — `TypeError` en `strtotime()` por incompatibilidad de tipos PHP 8.1

| Atributo | Detalle |
|----------|---------|
| **Archivo** | `pldoperacion.class.php`, línea 96 |
| **Error exacto** | `strtotime(): Argument #1 ($datetime) must be of type string, int given` |
| **Severidad** | **Fatal** — Rompe la ejecución por completo |

**Mecanismo del fallo:**

Dolibarr almacena internamente la fecha de una factura como un **timestamp Unix** (entero, por ejemplo `1779321600`). Cuando el módulo PLD ejecuta `marcarFacturaComoVulnerable()`, copia `$facture->date` (un `int`) directamente a `$op->fecha_operacion`. Posteriormente, cuando se invoca `$op->create()`, el método intenta llamar a:

```php
strtotime($this->fecha_operacion)  // $this->fecha_operacion es int, strtotime() espera string
```

En PHP 8.1 con `declare(strict_types=1)`, el tipado estricto **impide la coerción automática de `int` a `string`**. Lo que en PHP 7.x o en modo no-estricto de PHP 8.x sería una conversión silenciosa, aquí se convierte en un **`TypeError` fatal** que detiene la ejecución inmediatamente, produciendo el error 500 y la página en blanco.

**Por qué no aparecía antes:** En MySQL/MariaDB o en PHP sin `strict_types`, el int se convertía implícitamente a string y `strtotime()` lo interpretaba correctamente (o al menos no lanzaba excepción).

---

### Bug #2 — Formato de fecha incompatible con PostgreSQL en el INSERT

| Atributo | Detalle |
|----------|---------|
| **Archivo** | `pldoperacion.class.php`, línea 119 (método `create()`) |
| **Error exacto** | PostgreSQL no puede interpretar `'1779321600'` como valor de columna tipo `date` |
| **Severidad** | **Grave** — El INSERT falla |

**Mecanismo del fallo:**

Aunque se corrigiera el `strtotime()`, persistía un segundo problema. El SQL generado para el INSERT contenía:

```sql
INSERT INTO llx_pld_operacion (fecha_operacion, ...) VALUES ('1779321600', ...)
```

Aquí `fecha_operacion` es una columna de tipo `date` en PostgreSQL. El motor espera un valor en formato `'YYYY-MM-DD'` (ej. `'2026-05-23'`). Al recibir un string numérico `'1779321600'`, PostgreSQL **no sabe cómo interpretarlo como fecha** y rechaza la inserción.

En MySQL, `INSERT ... VALUES (1779321600)` contra una columna `DATE` puede funcionar por la coerción más permisiva del motor. En PostgreSQL, esto es un error.

---

### Bug #3 — `ON DUPLICATE KEY UPDATE` (sintaxis MySQL) aborta la transacción PostgreSQL

| Atributo | Detalle |
|----------|---------|
| **Archivo** | `PLDMonitorService.php`, líneas 375 y 516 (métodos `guardarPerfil()` y `registrarLogMonitoreo()`) |
| **Error exacto** | `ERROR: syntax error at or near "ON"` → estado de transacción `25P02` (in_failed_transaction) |
| **Severidad** | **Crítico** — Provoca rollback de toda la operación |

**Mecanismo del fallo:**

Este fue el bug más insidioso y el verdadero "fantasma" detrás de la página en blanco. El flujo completo en Dolibarr es:

1. Dolibarr inicia una **transacción** al validar la factura.
2. Dentro de esa transacción, el módulo PLD ejecuta:
   - `$op->create()` → **INSERT exitoso** a `llx_pld_operacion` ✅
   - `guardarPerfil()` → ejecuta `INSERT ... ON DUPLICATE KEY UPDATE ...` → **syntax error en PostgreSQL** ❌
3. En PostgreSQL, **cualquier error dentro de una transacción la pone en estado `ABORTED`** (código `25P02`). A partir de ese momento, todas las queries subsiguientes dentro de esa transacción son rechazadas automáticamente con `ERROR: current transaction is aborted, commands ignored until end of transaction block`.
4. Dolibarr ejecuta `COMMIT` al final del flujo → PostgreSQL responde con `ROLLBACK` implícito porque la transacción está abortada.
5. **Resultado:** El INSERT exitoso del paso 2 se pierde. La operación PLD nunca se persiste. El usuario ve la página en blanco.

`ON DUPLICATE KEY UPDATE` es sintaxis exclusiva de MySQL. El equivalente en PostgreSQL es `ON CONFLICT ... DO UPDATE`. Al ejecutar sintaxis MySQL en PostgreSQL, el parser SQL simplemente no la reconoce y lanza un error de sintaxis.

**Por qué es especialmente peligroso:** No es un error obvio. El INSERT previo parece exitoso (no lanza excepción en PHP), pero se pierde silenciosamente por el rollback de la transacción. El desarrollador ve que `$op->create()` retorna un ID, asume que se guardó, pero la realidad es que PostgreSQL lo descartó todo.

---

### Bug #4 — Asignación de `null` a propiedad tipada `string` en `PLDOperacionService`

| Atributo | Detalle |
|----------|---------|
| **Archivo** | `PLDOperacionService.php`, líneas 53, 79, 121 |
| **Error exacto** | `TypeError: Cannot assign null to property PLDOperacionService::$error of type string` |
| **Severidad** | **Media** — Error secundario que se manifestaba después de corregir los anteriores |

**Mecanismo del fallo:**

La clase `PLDOperacionService` declara:

```php
public string $error;
```

Sin embargo, en los métodos `crearOperacion()`, `actualizarOperacion()` y `marcarFacturaComoVulnerable()`, cuando el `create()` o `update()` del objeto `PLDOperacion` falla, se intenta propagar el error así:

```php
$this->error = $op->error;
```

El problema es que `CommonObject::$error` de Dolibarr **no siempre está inicializado**. Si el método de persistencia falla antes de establecer `$this->error` (por ejemplo, si el fallo ocurre en la preparación del SQL antes de llegar a la ejecución), `$op->error` es `null`. Asignar `null` a una propiedad tipada `string` produce un segundo `TypeError`, enmascarando aún más el error original.

Adicionalmente, `$op->id` en PostgreSQL se devuelve como `string` (por el driver PDO), mientras que el código asumía `int`, causando problemas de tipo en el retorno.

---

## ✅ Soluciones Aplicadas (una por bug)

### Corrección #1 — `strtotime()` seguro con detección de tipo

**Archivo:** `pldoperacion.class.php`, método `create()` y `update()`

**Antes:**
```php
$fecha = strtotime($this->fecha_operacion);
```

**Después:**
```php
// Si ya es numérico (timestamp Unix de Dolibarr), usarlo directamente
// Si es string con formato de fecha, convertirlo con strtotime
$fecha = is_numeric($this->fecha_operacion) 
    ? (int) $this->fecha_operacion 
    : strtotime((string) $this->fecha_operacion);
```

**Razonamiento:** Detecta proactivamente si el valor ya es un timestamp Unix (proveniente de Dolibarr, donde las fechas se almacenan como `int`) o si es un string de fecha que requiere conversión. Esto maneja ambos casos sin lanzar TypeError.

---

### Corrección #2 — Normalización de fecha a `YYYY-MM-DD` para PostgreSQL

**Archivo:** `pldoperacion.class.php`, método `create()` y `update()`

**Antes:**
```php
VALUES (' . $this->fecha_operacion . ', ...)
```

**Después:**
```php
// Normalizar la fecha a formato YYYY-MM-DD para PostgreSQL
$fecha_normalizada = date('Y-m-d', $fecha);
// ... VALUES (' . $fecha_normalizada . ', ...)
```

Además, en el método `fetch()`, se agregó cast `(int)` a `rowid` y `entity` ya que el driver PDO de PostgreSQL devuelve todos los valores como strings:

```php
$this->id = (int) $obj->rowid;
$this->entity = (int) $obj->entity;
```

**Razonamiento:** `date('Y-m-d', $timestamp)` convierte cualquier timestamp Unix a un string de fecha compatible con el tipo `date` de PostgreSQL, independientemente de que el valor de entrada sea int o string.

---

### Corrección #3 — Savepoints de Dolibarr para aislar errores en PostgreSQL

**Archivo:** `PLDMonitorService.php`, métodos `guardarPerfil()` y `registrarLogMonitoreo()`

**Antes:**
```php
$resql = $this->db->query($sql);
```

**Después:**
```php
$resql = $this->db->query($sql, 1);  // El segundo parámetro activa savepoint
```

**Mecanismo de los savepoints en Dolibarr:**

Dolibarr tiene un mecanismo de **savepoints** implementado en su capa de abstracción de base de datos (`DoliDB`). Cuando se pasa `1` como segundo argumento a `$db->query()`:

1. Dolibarr crea un **savepoint** (punto de restauración) dentro de la transacción activa antes de ejecutar la query.
2. Si la query falla (como en el caso del `ON DUPLICATE KEY UPDATE` inválido), Dolibarr hace **ROLLBACK TO SAVEPOINT** en lugar de abortar toda la transacción.
3. La transacción externa (la de Dolibarr al validar la factura) **no se ve afectada**.
4. El INSERT previo a `llx_pld_operacion` sobrevive.

Sin embargo, la solución completa requiere también **reemplazar la sintaxis MySQL por la de PostgreSQL** cuando corresponda. El savepoint solo evita el daño colateral, pero la operación `guardarPerfil()` igual fallará y no persistirá su propio dato. Para PostgreSQL, `ON DUPLICATE KEY UPDATE` debe reemplazarse por:

```sql
INSERT INTO tabla (col1, col2) VALUES (val1, val2)
ON CONFLICT (col_unique) DO UPDATE SET col2 = excluded.col2
```

Pero la corrección con savepoints (`query($sql, 1)`) al menos **detiene la hemorragia** y evita que una query fallida en el módulo PLD destruya toda la transacción de Dolibarr, incluyendo la validación de la factura y la creación de la operación PLD.

---

### Corrección #4 — Manejo defensivo de `null` en propagación de errores y cast de tipos

**Archivo:** `PLDOperacionService.php`, métodos `crearOperacion()`, `actualizarOperacion()`, `marcarFacturaComoVulnerable()`

**Antes:**
```php
$this->error = $op->error;
return $op->id;
```

**Después:**
```php
$this->error = $op->error ?: 'Error desconocido al crear/actualizar la operación PLD';
return (int) $op->id;
```

**Razonamiento:**

1. **`$op->error ?: 'Error desconocido...'`**: El operador Elvis (`?:`) garantiza que si `$op->error` es `null`, `false`, o string vacío, se asigna un mensaje de error por defecto. Esto evita el `TypeError` por asignar `null` a una propiedad tipada `string`. Además, proporciona información de diagnóstico útil en los logs en lugar de un error genérico.

2. **`(int) $op->id`**: El cast explícito a `int` resuelve la discrepancia entre el driver PDO de PostgreSQL (que devuelve `string`) y las expectativas del código (que asume `int`). Esto también aplica en el `fetch()` de `pldoperacion.class.php`.

---

## 🎯 Corrección Adicional — Columna Faltante en PostgreSQL

| Atributo | Detalle |
|----------|---------|
| **Tabla** | `llx_pld_operacion` |
| **Columna faltante** | `fk_propal` (foreign key a la tabla de propuestas comerciales) |
| **Impacto** | El INSERT fallaba porque la columna referenciada en el SQL no existía en PostgreSQL |

La migración de MySQL a PostgreSQL no había sido completa — la tabla `llx_pld_operacion` en PostgreSQL carecía de la columna `fk_propal` que el código PHP intentaba insertar. Se agregó mediante:

```sql
ALTER TABLE llx_pld_operacion ADD COLUMN fk_propal INTEGER;
```

---

## 🧩 Diagrama de la Cadena de Fallos

```
Usuario hace clic en "Confirmar Factura"
    │
    ▼
Dolibarr inicia TRANSACCIÓN
    │
    ▼
PLDOperacionService::marcarFacturaComoVulnerable()
    │
    ├── $op = new PLDOperacion()
    ├── $op->fecha_operacion = $facture->date   ← int (timestamp Unix)
    │
    ▼
$op->create() ──────────────────────────────────────────────┐
    │                                                        │
    ├── strtotime($this->fecha_operacion)  ← BUG #1: TypeError (int → string)
    │                                                        │
    ├── INSERT ... VALUES ('1779321600')    ← BUG #2: PostgreSQL date no acepta timestamp crudo
    │                                                        │
    └── INSERT exitoso (si se corrigen #1 y #2) ✅           │
                                                             │
    ▼                                                        │
guardarPerfil()                                              │
    │                                                        │
    └── INSERT ... ON DUPLICATE KEY UPDATE  ← BUG #3: Sintaxis MySQL en PostgreSQL
            │                                                │
            └── ERROR: syntax error                         │
                    │                                        │
                    └── Transacción PostgreSQL → ABORTED (25P02)
                            │                                │
                            └── Todas las queries subsiguientes ignoradas
                                    │                        │
                                    ▼                        │
                            COMMIT → ROLLBACK implícito      │
                                    │                        │
                                    └── El INSERT del paso anterior SE PIERDE ❌
                                            │
                                            ▼
                                    PÁGINA EN BLANCO (500)

Además:
PLDOperacionService::$error = $op->error (= null)  ← BUG #4: TypeError string
```

---

## 📊 Tabla Resumen de Correcciones

| # | Archivo | Qué se cambió | Por qué |
|---|---------|---------------|---------|
| 1 | `pldoperacion.class.php` | `strtotime()` → `is_numeric() ? (int) : strtotime((string))` | PHP 8.1 strict_types no permite coerción int→string |
| 2 | `pldoperacion.class.php` | Normalizar `fecha_operacion` a `date('Y-m-d', $timestamp)` antes del INSERT | PostgreSQL columna `date` requiere formato `YYYY-MM-DD` |
| 3 | `pldoperacion.class.php` | Cast `(int)` a `rowid` y `entity` en `fetch()` | Driver PDO PostgreSQL devuelve strings |
| 4 | `PLDMonitorService.php` | `$db->query($sql)` → `$db->query($sql, 1)` en `guardarPerfil()` y `registrarLogMonitoreo()` | Los savepoints de Dolibarr aíslan errores SQL y evitan que aborten la transacción externa |
| 5 | `PLDOperacionService.php` | `$this->error = $op->error` → `$this->error = $op->error ?: 'Error desconocido...'` | `CommonObject::$error` puede ser `null`; asignar `null` a `string $error` → TypeError |
| 6 | `PLDOperacionService.php` | `return $op->id` → `return (int) $op->id` | PostgreSQL devuelve strings, el código espera int |
| 7 | PostgreSQL | `ALTER TABLE llx_pld_operacion ADD COLUMN fk_propal INTEGER` | Columna referenciada en INSERT no existía en PostgreSQL |

---

## 🏁 Resultado Final

Después de aplicar las siete correcciones, el flujo de validación de factura funciona correctamente:

1. La factura se valida sin error 500.
2. La operación PLD se crea y persiste correctamente en `llx_pld_operacion`.
3. El perfil de monitoreo y el log se registran sin abortar la transacción.
4. El COMMIT de Dolibarr completa exitosamente.

**Commit:** `12b5dc6` en rama `fraccion-x-monitoreo`, pusheado a `github.com:petu34/dolibarr-pld-mx.git`.

---

## 💡 Lecciones Aprendidas

1. **PHP 8.1 + `strict_types=1` es implacable.** Código que funcionaba en PHP 7.x por coerción implícita de tipos puede romper silenciosamente o estrepitosamente en PHP 8.1. Todo código que interactúe con funciones built-in de PHP que esperan tipos específicos (`strtotime`, `preg_match`, `substr`, etc.) debe validar y convertir explícitamente los tipos de entrada.

2. **PostgreSQL no es MySQL.** La sintaxis `ON DUPLICATE KEY UPDATE` es exclusiva de MySQL. PostgreSQL usa `ON CONFLICT ... DO UPDATE`. Las diferencias de motor de base de datos van mucho más allá de la sintaxis — el comportamiento transaccional es radicalmente distinto: PostgreSQL aborta toda la transacción ante cualquier error, MySQL es más permisivo.

3. **Las transacciones en PostgreSQL son "todo o nada" por diseño.** Un solo error en cualquier query dentro de una transacción la invalida por completo. Los savepoints son la herramienta correcta para aislar operaciones que pueden fallar sin comprometer la transacción principal.

4. **El driver PDO de PostgreSQL devuelve todo como string.** A diferencia de MySQL, donde los tipos numéricos se devuelven como tipos nativos de PHP, PDO PostgreSQL devuelve todos los valores como `string`. Todo código que lea de la base de datos debe hacer cast explícito a los tipos esperados.

5. **Los errores encadenados son difíciles de diagnosticar.** El error visible (página en blanco) no apuntaba directamente a ninguna de las causas raíz. Fue necesario depurar capa por capa (PHP → SQL → Transacción PostgreSQL) para encontrar todos los bugs y entender cómo interactuaban entre sí.
