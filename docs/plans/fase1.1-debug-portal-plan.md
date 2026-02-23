# Plan Fase 1.1: Verificación y Debug del Módulo en Portal Dolibarr

## Objetivo

Verificar que el módulo CompliancePLD funciona correctamente en el portal Dolibarr local (Docker), corrigiendo los errores encontrados durante la prueba visual antes de hacer PR a `main`.

---

## Contexto

### Entorno de prueba

| Parámetro | Valor |
|---|---|
| Contenedor Docker | `doli20` |
| IP interna | `172.18.0.2` |
| Puerto externo | `8088` |
| Volumen custom | `doli20_dolibarr_custom` → `/var/www/html/custom` |
| Dolibarr | 20.0.4 |
| Usuario portal | `doli20_user` |

> **Importante:** El volumen NO es bind mount. Cada cambio en el repo requiere:
> ```bash
> docker cp htdocs/custom/modulecompliancepld/ doli20:/var/www/html/custom/
> ```
> Seguido de desactivar + reactivar el módulo en Admin → Módulos para que `init()` corra.

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

Si el error persiste tras reactivar, revisar directamente en la BD del contenedor:

```bash
docker exec doli20 mysql -u dolibarr -pdolibarr dolibarr -e \
  "SELECT name, computed FROM llx_extrafields
   WHERE elementtype='thirdparty' AND name LIKE 'pld_%' AND computed != '';"
```

Si devuelve filas, los extrafields en BD tienen `computed` sucio del primer `init()` (antes del fix). `addExtraField()` usa `INSERT ... ON DUPLICATE KEY UPDATE` pero puede que NO actualice la columna `computed` si el campo ya existe.

### Paso 3 — Si computed sigue sucio: limpiar manualmente

```bash
docker exec doli20 mysql -u dolibarr -pdolibarr dolibarr -e \
  "UPDATE llx_extrafields SET computed = ''
   WHERE name LIKE 'pld_%' AND computed LIKE 'PLD%';"
```

Verificar también las otras tablas:
```bash
docker exec doli20 mysql -u dolibarr -pdolibarr dolibarr -e \
  "SELECT elementtype, name, computed FROM llx_extrafields
   WHERE name LIKE 'pld_%' AND computed != '';"
```

### Paso 4 — Verificar campos en cards de Dolibarr

Una vez limpio, navegar a:

| URL | Qué verificar |
|---|---|
| `/societe/card.php?action=create` | Campos PLD en alta de tercero — tipo persona, RFC, CURP editables |
| `/contact/card.php?action=create` | Campos PLD en alta de contacto |
| `/product/card.php?action=create&type=0` | Campos PLD en alta de producto/vehículo |

Confirmar que:
- [ ] `pld_tipo_persona` muestra dropdown editable (PF/PM/Fideicomiso)
- [ ] `pld_curp` muestra campo texto editable (no "calculado automáticamente")
- [ ] `pld_rfc_validado` muestra campo texto editable
- [ ] No aparece ningún `Exception during evaluation`
- [ ] Pestaña "Datos PLD" visible en cada card

### Paso 5 — Verificar menú PLD

- [ ] Menú superior "PLDMenu" visible
- [ ] Submenús: Dashboard, Operaciones, Avisos, Alertas, Reportes, Configuración

### Paso 6 — Actualizar plan y hacer PR

Una vez verificado sin errores:
- [ ] Actualizar este plan con resultado de pruebas
- [ ] Commit final: `[HUMAN] PLAN FASE 1.1 — verificación completa`
- [ ] Abrir PR de `fase1/extrafields` → `main`

---

## Notas técnicas

### Credenciales Docker
```
Usuario portal:   doli20_user
Contraseña:       tirejkandani
BD usuario:       dolibarr
BD contraseña:    dolibarr (verificar en conf/conf.php si falla)
BD nombre:        dolibarr
```

> Verificar credenciales reales de BD:
> ```bash
> docker exec doli20 grep -E "^\\$dolibarr_main_db" /var/www/html/conf/conf.php
> ```

### Comportamiento de addExtraField() en actualizaciones

`addExtraField()` internamente hace `INSERT ... ON DUPLICATE KEY UPDATE`. Es probable que actualice `computed` al valor correcto (`''`) al reactivar. Si no lo hace, el UPDATE manual del Paso 3 es suficiente — no es necesario DROP ni recrear las columnas.

---

*Plan Fase 1.1 v1.0 — Debug portal Dolibarr | 22 de febrero de 2026*
