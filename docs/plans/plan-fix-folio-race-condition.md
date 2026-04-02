# Plan: Corregir Race Condition en `generarFolioInterno()`

**Estado:** Pendiente  
**Prioridad:** Crítica — cumplimiento normativo SAT  
**Archivo principal:** `htdocs/custom/modulecompliancepld/class/pldoperacion.class.php`

---

## Problema

`generarFolioInterno()` abre una transacción, ejecuta un `SELECT MAX(...)` para obtener el último consecutivo, calcula `$this->folio_interno` y hace commit — todo antes de que se ejecute el `INSERT` en `create()`.

```
generarFolioInterno()          create()
  begin()                        begin()
  SELECT MAX → ultimo=5            INSERT (folio=PLD-2026-04-0006)
  folio = PLD-2026-04-0006        commit()
  commit()    ← tx ya cerrada
```

Dos requests concurrentes pueden leer el mismo `ultimo` y generar el mismo `folio_interno`. Reportar al SAT con folios duplicados invalida el XML ante el SPPLD.

---

## Solución elegida: mover generación de folio dentro de `create()`

Unificar ambas operaciones en una sola transacción. El folio se calcula y se inserta atómicamente.

### Ventajas
- Sin dependencias de DB adicionales (no requiere tabla de secuencias aparte)
- Compatible con DoliDB/PostgreSQL — sigue las reglas DDL del proyecto
- `SELECT ... FOR UPDATE` en PostgreSQL (vía DoliDB) bloquea la fila del MAX hasta el commit

### Por qué no otras alternativas
- **Tabla de secuencias separada**: overhead innecesario para un campo de negocio
- **Columna `AUTO_INCREMENT` como folio**: el folio tiene formato compuesto (`PLD-YYYY-MM-NNNN`) que no puede ser un auto-increment directo
- **UUID**: el SAT requiere folios secuenciales y predecibles por mes

---

## Pasos de implementación

### 1. Convertir `generarFolioInterno()` en método privado de cálculo puro

Renombrar a `calcularFolioInterno()`. Eliminar `begin()/commit()`. Solo devuelve el string calculado dado un resultset ya obtenido:

```php
private function calcularFolioInterno(int $ultimo): string
{
    $prefix = 'PLD';
    $year   = date('Y');
    $month  = date('m');
    return sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $ultimo + 1);
}
```

### 2. Integrar el SELECT dentro de `create()`, dentro de su transacción existente

En `create()` (línea ~77), la transacción ya existe. Antes del INSERT principal, agregar:

```php
// Dentro de $this->db->begin() ... ya existente en create()

$year  = date('Y');
$month = date('m');
$mes   = $this->db->escape($year.$month);

$sql_folio = "SELECT MAX(CAST("
    .$this->db->ifsql(
        "folio_interno LIKE 'PLD-{$year}-{$month}-%'",
        "SUBSTRING_INDEX(folio_interno, '-', -1)",
        "0"
    )." AS UNSIGNED)) as ultimo"
    ." FROM ".MAIN_DB_PREFIX.$this->table_element
    ." WHERE mes_reportado = '".$mes."'"
    ." FOR UPDATE";   // bloqueo de lectura — evita race condition

$resql_folio = $this->db->query($sql_folio);
if (!$resql_folio) {
    $this->db->rollback();
    $this->error = $this->db->lasterror();
    return -1;
}
$obj_folio   = $this->db->fetch_object($resql_folio);
$this->folio_interno = $this->calcularFolioInterno((int)($obj_folio->ultimo ?? 0));
```

> **Nota DoliDB**: `FOR UPDATE` funciona en PostgreSQL. En MySQL también. DoliDB no interfiere con esta cláusula SQL estándar.

### 3. Eliminar la llamada externa en `operacion.php`

```php
// Antes (operacion.php:65)
$object->generarFolioInterno();
$result = $object->create($user);

// Después — solo create(), el folio se genera dentro
$result = $object->create($user);
```

El método público `generarFolioInterno()` puede eliminarse o dejarse como deprecated si `scripts/seed_datos_prueba.php` lo usa.

### 4. Actualizar `scripts/seed_datos_prueba.php`

`seed_datos_prueba.php:519` llama directamente a `generarFolioInterno()`. Como el seed no opera en contexto concurrente, puede simplificarse a:

```php
// Reemplazar generarFolioInterno() por create() directo
// (create() ya asignará el folio internamente)
$op->create($userobj);
```

### 5. Verificar constraint de unicidad en la tabla

Revisar `sql/llx_pld_operacion.sql`. Si no existe, agregar:

```sql
ALTER TABLE llx_pld_operacion ADD UNIQUE KEY uk_pld_operacion_folio (folio_interno);
```

Esto actúa como red de seguridad: si por cualquier razón el bloqueo falla, la DB rechaza el duplicado con error que `create()` ya maneja.

---

## Prueba de la corrección

1. Abrir dos tabs del navegador simultáneamente con el form de nueva operación
2. Completar ambos forms con el mismo mes
3. Hacer submit al mismo tiempo (o usar dos curl concurrentes al endpoint)
4. Verificar que los `folio_interno` asignados son consecutivos únicos: `PLD-2026-04-0001` y `PLD-2026-04-0002`
5. Confirmar que no hay duplicados en `llx_pld_operacion`

---

## Archivos a modificar

| Archivo | Cambio |
|---------|--------|
| `class/pldoperacion.class.php` | Eliminar `generarFolioInterno()` público; agregar `calcularFolioInterno()` privado; integrar lógica en `create()` |
| `operacion.php` | Eliminar llamada a `generarFolioInterno()` (línea 65) |
| `scripts/seed_datos_prueba.php` | Eliminar llamada a `generarFolioInterno()` (línea 519) |
| `sql/llx_pld_operacion.sql` | Verificar/agregar `UNIQUE KEY uk_pld_operacion_folio` |
