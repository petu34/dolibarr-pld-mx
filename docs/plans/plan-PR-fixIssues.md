# Plan Fase 3.2: Issues de Seguridad y Calidad — Pendientes post-PR

> **Creado:** 2026-03-22
> **Estado:** ✅ COMPLETADO — todos los issues resueltos en commit `af64abb` (2026-03-25)

---

## Contexto

Durante el code review previo al PR `fase1/extrafields → develop` se identificaron issues.
Los **3 críticos** ya fueron corregidos y committeados. Este plan registra los **5 importantes** restantes.

---

## Issues importantes — Checklist

### [x] 1. XSS: `$_SERVER['PHP_SELF']` sin escapar en `action=` del form

**Archivos:** `xml_generator.php:205`, `admin/setup.php:94`

`$_SERVER['PHP_SELF']` puede contener fragmentos inyectados vía URL (e.g. `?x="/onsubmit="alert(1)`), lo que permite XSS reflejado en el atributo `action` del form.

**Fix:**
```php
// Antes
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';

// Después
print '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
```

Aplica también en `admin/setup.php:94`.

---

### [x] 2. CSRF token no validado en `action=descargar` (GET)

**Archivo:** `xml_generator.php:151`

El token CSRF se incluye en la URL del enlace de descarga (línea 239) pero nunca se valida antes de servir el archivo. En Dolibarr los tokens se validan automáticamente en POST; para GET se debe hacer explícitamente.

**Fix:**
```php
if ($action == 'descargar' && GETPOST('filepath', 'nohtml')) {
    if (!checkToken()) {
        accessforbidden('Invalid token');
    }
    // ... resto del código
}
```

---

### [x] 3. Race condition en `generarFolioInterno()` — folios duplicados bajo concurrencia

**Archivo:** `pldoperacion.class.php:320–336`

`COUNT(*) + 1` para generar el número secuencial produce colisiones si dos requests llegan simultáneamente. Al ser el folio la referencia del aviso SAT, un duplicado puede causar rechazo del portal SPPLD.

**Fix:** Cambiar a `MAX()` sobre el último segmento numérico del folio, o usar una secuencia en tabla separada con lock:
```php
$sql = "SELECT MAX(CAST(SUBSTRING_INDEX(folio_interno, '-', -1) AS UNSIGNED)) as ultimo"
    . " FROM ".MAIN_DB_PREFIX."pld_operacion"
    . " WHERE mes_reportado = '".$db->escape($year.$month)."'";
// siguiente = $ultimo + 1
```

También considerar envolver en transacción con `$db->begin()` / `$db->commit()`.

---

### [x] 4. Falta `$db->escape()` en `generarFolioInterno()`

**Archivo:** `pldoperacion.class.php:326`

```php
// Antes (sin escape)
$sql .= " WHERE mes_reportado = '".$year.$month."'";

// Después
$sql .= " WHERE mes_reportado = '".$db->escape($year.$month)."'";
```

Aunque `$year` y `$month` vienen de `date()` en el flujo normal, el patrón viola las guías del proyecto (CLAUDE.md: "Usar `$db->escape()` para todo input").

---

### [x] 5. Instancia sin usar en `about.php:108`

**Archivo:** `admin/about.php:108`

```php
$efirma = new PLDEFirmaIntegration($db);  // nunca se usa — eliminar
```

Eliminar la línea y el `require_once` de `pldefirmaintegration.class.php` en `about.php` si no hay otro uso.

---

### [x] 6. Validación explícita de campos obligatorios veh.xsd en `pldxmlgenerator.class.php`

**Archivo:** `class/pldxmlgenerator.class.php` — método `crearDomicilio()`

Actualmente los campos nulos del domicilio nacional se reemplazan por `''` (fix temporal con `?? ''`). Según `veh.xsd`, los campos `colonia`, `calle`, `numero_exterior`, `codigo_postal`, `municipio` y `entidad_federativa` son **obligatorios** para domicilio nacional. Generar XML con campos vacíos resultará en rechazo del portal SAT.

**Fix:**
```php
// Lanzar Exception descriptiva si campo requerido es null
$required = ['colonia', 'calle', 'numero_exterior', 'codigo_postal', 'municipio', 'estado'];
foreach ($required as $field) {
    if (empty($cliente->$field)) {
        throw new Exception("Campo '$field' es obligatorio según veh.xsd para domicilio nacional (operación ID: {$cliente->rowid})");
    }
}
```

La excepción debe ser capturada en `generarXMLMensual()` y devuelta como error con el ID de la operación problemática para que el usuario la corrija antes de regenerar.

Revertir el comentario TODO en `crearDomicilio()` una vez implementado.

---

## Orden de atención recomendado

1. **#1 XSS** — fácil, 2 líneas, alta visibilidad
2. **#4 escape()** — trivial, 1 línea
3. **#5 instancia sin usar** — trivial, eliminar
4. **#2 CSRF GET** — requiere conocer API `checkToken()` de Dolibarr
5. **#6 validación veh.xsd** — requiere conocer el XSD completo; impacto en calidad del XML SAT
6. **#3 race condition** — más complejo; baja probabilidad en instalaciones de un solo usuario pero necesario para producción

---

## Archivos a modificar

| Archivo | Issues |
|---|---|
| `xml_generator.php` | #1 XSS, #2 CSRF |
| `admin/setup.php` | #1 XSS |
| `admin/about.php` | #5 instancia sin usar |
| `class/pldoperacion.class.php` | #3 race condition, #4 escape |
| `class/pldxmlgenerator.class.php` | #6 validación campos veh.xsd |
