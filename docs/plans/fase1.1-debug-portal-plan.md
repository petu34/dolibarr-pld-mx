# Plan Fase 1.1: Verificación y Debug del Módulo en Portal Dolibarr

## Objetivo

Verificar que el módulo CompliancePLD funciona correctamente en el portal Dolibarr local (Docker), corrigiendo los errores encontrados durante la prueba visual antes de hacer PR a `main`.

---

## Contexto

### Entorno de prueba

| Parámetro | Valor |
|---|---|
| Contenedor Docker | `doli20` (solo app web) |
| IP interna | `172.18.0.2` |
| Puerto externo | `8088` |
| Volumen custom | `doli20_dolibarr_custom` → `/var/www/html/custom` |
| Dolibarr | 20.0.4 |
| Motor BD | **PostgreSQL 16** (Postgres.app en host macOS) |
| BD nombre | `doli20_db` |
| BD usuario | `doli20_user` |
| BD acceso desde Docker | `host.docker.internal:5432` |
| Usuario portal | `doli20_user` |

> **Importante:** El volumen NO es bind mount. Cada cambio en el repo requiere:
> ```bash
> docker cp htdocs/custom/modulecompliancepld/ doli20:/var/www/html/custom/
> ```
> Seguido de desactivar + reactivar el módulo en Admin → Módulos para que `init()` corra.

---

## Regla: BD solo lectura — toda corrección vía módulo custom

> **La base de datos NO se modifica directamente (INSERT, UPDATE, DELETE).**
>
> - Las consultas `SELECT` contra `doli20_db` son **permitidas** para diagnóstico y verificación de bugs.
> - Toda corrección de datos en BD se hace **exclusivamente** a través del módulo custom:
>   1. Corregir el código PHP en `htdocs/custom/modulecompliancepld/`
>   2. Copiar al contenedor: `docker cp ... doli20:/var/www/html/custom/`
>   3. Desactivar + reactivar el módulo en Admin → Módulos (para que `init()` corra `addExtraField()`)
>   4. Verificar el resultado con `SELECT` en BD
> - Si tras reactivar el módulo la BD sigue inconsistente, **no corregir manualmente** — iterar sobre el código PHP hasta que `init()` produzca el estado correcto.
> - Excepción: Un `UPDATE` o `DELETE` directo en BD solo se permite con **aprobación explícita del humano**, documentando el motivo y el SQL ejecutado en este plan.

---

## Bug Encontrado: `$computed` recibía la clave de ayuda

### Síntoma

- Campos `pld_tipo_persona`, `pld_curp`, `pld_rfc_validado` mostraban **"calculado automáticamente"** y no eran editables
- Error en pantalla: `Exception during evaluation: PLDCURPHelp`

### Causa raíz

La firma real de `addExtraField()` en Dolibarr 20 (`extrafields.class.php:143`):
```php
addExtraField($attrname, $label, $type, $pos, $size, $elementtype,
  $unique, $required, $default_value, $param, $alwayseditable,
  $perms, $list, $help, $computed, $entity, $langfile, $enabled, ...)
```

El código generado tenía los args desalineados — la clave i18n de ayuda (`'PLDCURPHelp'`) caía en `$computed`, que Dolibarr evalúa como PHP.

### Fix aplicado — Commit `49c6cb3`

Dos `replace_all` sobre las 143 llamadas en `modModulecompliancepld.class.php`:
- **OP1:** `'', $e, ` → `'',` (elimina `$e` mal colocado en posición `$list`)
- **OP2:** `'', $l, $e);` → `'', '', $l, $e);` (agrega `$entity=''` faltante)

### Estado al cerrar sesión

Error **sigue presente en portal** — el fix fue commiteado pero no verificado en BD porque la sesión terminó antes de reactivar el módulo.

---

## Pasos Pendientes

### Paso 1 — Copiar y reactivar módulo

```bash
# 1. Copiar descriptor corregido al contenedor
docker cp htdocs/custom/modulecompliancepld/core/modules/modModulecompliancepld.class.php \
  doli20:/var/www/html/custom/modulecompliancepld/core/modules/modModulecompliancepld.class.php

# 2. En el portal: Admin → Módulos → buscar "compliance" → Desactivar → Reactivar
# URL directa: http://172.18.0.2/admin/modules.php?search_keyword=compliance&search_status=1
```

### Paso 2 — Verificar en BD si $computed quedó limpio

Si el error persiste tras reactivar, revisar directamente en PostgreSQL (Postgres.app en el host):

```bash
psql -h localhost -p 5432 -U doli20_user -d doli20_db -c \
  "SELECT name, fieldcomputed FROM llx_extrafields
   WHERE elementtype='thirdparty' AND name LIKE 'pld_%' AND fieldcomputed IS NOT NULL AND fieldcomputed != '';"
```

> **Nota:** La columna se llama `fieldcomputed` (no `computed`). Ver DDL real de `llx_extrafields` más abajo.

Si devuelve filas, los extrafields en BD tienen `fieldcomputed` sucio del primer `init()` (antes del fix). `addExtraField()` en PostgreSQL usa `INSERT ... ON CONFLICT DO UPDATE` pero puede que NO actualice la columna `fieldcomputed` si el campo ya existe.

### Paso 3 — Si fieldcomputed sigue sucio: corregir vía módulo

Si `addExtraField()` no limpió `fieldcomputed` al reactivar, el fix debe ir en el código PHP:

1. **Verificar** que `modModulecompliancepld.class.php` pasa `''` (string vacío) en la posición `$computed` de cada llamada a `addExtraField()`.
2. Si el argumento ya es correcto pero Dolibarr no actualiza la columna existente, agregar una llamada explícita en `init()` **antes** de los `addExtraField()`:
   ```php
   // Limpiar fieldcomputed residual de init() previos con args desalineados
   $this->db->query("UPDATE ".MAIN_DB_PREFIX."extrafields SET fieldcomputed = NULL WHERE name LIKE 'pld_%' AND fieldcomputed LIKE 'PLD%'");
   ```
   > Esto sigue siendo corrección vía módulo — el SQL lo ejecuta Dolibarr en `init()`, no el agente directamente.
3. Copiar al contenedor → desactivar → reactivar → verificar con SELECT (Paso 2).

Verificar que **todas** las elementtype quedaron limpias:
```bash
psql -h localhost -p 5432 -U doli20_user -d doli20_db -c \
  "SELECT elementtype, name, fieldcomputed FROM llx_extrafields
   WHERE name LIKE 'pld_%' AND fieldcomputed IS NOT NULL AND fieldcomputed != '';"
```
> Resultado esperado: 0 filas.

### Paso 4 — Verificar campos en cards de Dolibarr

Una vez limpio, navegar a:

| URL | Qué verificar |
|---|---|
| `/societe/card.php?action=create` | Campos PLD en alta de tercero — tipo persona, RFC, CURP editables |
| `/contact/card.php?action=create` | Campos PLD en alta de contacto |
| `/product/card.php?action=create&type=0` | Campos PLD en alta de producto/vehículo |

Confirmar que:
- [x] `pld_tipo_persona` muestra dropdown editable (PF/PM/Fideicomiso)
- [x] `pld_curp` muestra campo texto editable (no "calculado automáticamente")
- [x] `pld_rfc_validado` muestra campo texto editable
- [x] No aparece ningún `Exception during evaluation`
- [ ] Pestaña "Datos PLD" visible en cada card — **No verificado**: los campos PLD aparecen inline en el formulario de alta. Verificar en card de registro existente.

**Resultado Paso 4** (23 feb 2026):
- **Tercero** (`/societe/card.php?action=create`): ✅ 33 campos PLD visibles y editables. Sin errores.
- **Contacto** (`/contact/card.php?action=create`): ✅ ~20 campos PLD visibles y editables. Sin errores.
- **Producto** (`/product/card.php?action=create&type=0`): ⚠️ "Acceso denegado" — el módulo Productos/Servicios no está habilitado en esta instancia de Dolibarr. No es bug PLD.

### Paso 5 — Verificar menú PLD

- [x] Menú superior "PLDMenu" visible en la barra de navegación
- [ ] Submenús: Dashboard, Operaciones, Avisos, Alertas, Reportes, Configuración

**Resultado Paso 5** (23 feb 2026):
- PLDMenu aparece en la barra de navegación. ✅
- Al hacer clic → **404 Not Found** en `/modulecompliancepld/index.php`. ❌
- **Causa**: El archivo existe como `modulecompliancepldindex.php` (nombre concatenado del ModuleBuilder), no como `index.php`.
- **Acción**: Requiere fix separado (renombrar archivos de página o ajustar rutas en menús). Será un bug de Fase 1.2 o un nuevo plan.

### Paso 6 — Actualizar plan y hacer PR

- [x] Actualizar este plan con resultado de pruebas
- [ ] Commit final: `[HUMAN] PLAN FASE 1.1 — verificación completa`
- [ ] Abrir PR de `fase1/extrafields` → `main`

---

## Notas técnicas

### Credenciales
```
Portal Dolibarr:
  Usuario:        doli20_user
  Contraseña:     tirejkandani
  URL:            http://localhost:8088

PostgreSQL (Postgres.app — host macOS):
  Host:           localhost (desde Mac) / host.docker.internal (desde Docker)
  Puerto:         5432
  BD nombre:      doli20_db
  BD usuario:     doli20_user
  BD contraseña:  tirejkandani
```

> Verificar credenciales de BD desde conf de Dolibarr:
> ```bash
> docker exec doli20 grep -E "^\\\$dolibarr_main_db" /var/www/html/conf/conf.php
> ```
>
> Conexión directa a BD desde el host:
> ```bash
> psql -h localhost -p 5432 -U doli20_user -d doli20_db
> ```

### Estructura real de llx_extrafields (PostgreSQL 16)

> **Referencia:** Columnas clave que difieren de la documentación MySQL genérica de Dolibarr:
>
> | Columna real (PostgreSQL) | Nombre asumido en docs MySQL | Notas |
> |---|---|---|
> | `name` | `attrname` | Nombre técnico del extrafield |
> | `fieldcomputed` | `computed` | Expresión PHP evaluada por Dolibarr |
> | `fielddefault` | `default_value` | Valor por defecto |
> | `fieldunique` | `unique` | Restricción de unicidad (0/1) |
> | `fieldrequired` | `required` | Campo obligatorio (0/1) |
> | `pos` | `position` | Posición de orden en el formulario |
>
> Usar siempre los nombres de columna reales (columna izquierda) en las consultas.

### Comportamiento de addExtraField() en actualizaciones

`addExtraField()` en Dolibarr con PostgreSQL internamente hace `INSERT ... ON CONFLICT DO UPDATE`. **Confirmado (23 feb 2026):** `addExtraField()` NO actualiza `fieldcomputed` en registros existentes al reactivar. Fue necesario agregar la query de cleanup en `init()` (ver Paso 3). La corrección se ejecuta vía módulo, nunca como SQL directo contra la BD.

---

### Pendiente: Actualizar AGENTS.md

> El archivo `AGENTS.md` contiene referencias incorrectas que deben corregirse para reflejar PostgreSQL:
> - Sección 1 "Entorno técnico" dice "Base de datos: MySQL/MariaDB" → cambiar a **PostgreSQL 16 (Postgres.app)**
> - Sección 4.2 ejemplo SQL usa `attrname` → debe ser `name`
> - Sección 4.2 ejemplo SQL usa `position` → debe ser `pos`
> - Sección 6.2 `ExtrafieldsTest.php` usa `attrname` → debe ser `name`
> - Sección 5.2 query ejemplo en Paso 2 del plan anterior usaba `computed` → es `fieldcomputed`

### Bugs nuevos encontrados (fuera de alcance de Fase 1.1)

> 1. **Páginas PLD 404**: Los archivos de página del módulo tienen nombres incorrectos (`modulecompliancepldindex.php` en vez de `index.php`). Requiere renombrar archivos o ajustar definición de menús en el descriptor. **Prioridad media.**
> 2. **Módulo Productos deshabilitado**: No se pudo verificar `product/card.php`. Requiere habilitar módulo Productos/Servicios en Admin → Módulos antes de verificar extrafields de vehículos. **Prioridad baja** (configuración, no bug).

*Plan Fase 1.1 v1.3 — Debug portal Dolibarr (PostgreSQL) | 23 de febrero de 2026*
