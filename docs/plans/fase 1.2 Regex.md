# Plan de Implementación — Fase 1.2: Integración PLDValidator con Dolibarr

> **Versión:** 1.1 | **Fecha:** 27 de febrero de 2026
> **Generado por:** Sisyphus (Claude Code) + Plan Agent
> **Compliance:** LFPIORPI Art. 17 Fracc. VIII — PLD México

---

## Estado de Avance (actualizado 27 de febrero de 2026)

| Fase | Descripción | Estado | Commit | Notas |
|------|-------------|--------|--------|-------|
| **Fase 1** | Corregir Regex CURP + `strict_types` + Tests | ✅ Completada | `c428dbc` | 163 tests / 178 assertions pasan |
| **Fase 2** | Implementar Trigger Handlers | ✅ Completada | `91db595` | 4 handlers + 2 helpers privados |
| **Fase 3** | Implementar Hook Class (formularios) | ✅ Completada | `91db595` + `9d1e500` | `doActions()` real + boilerplate limpio |
| **Fase 4** | Agregar Entradas de Idioma | ⚠️ Parcial | `91db595` | Faltan: `PLDErrorTelefonoInvalido`, `PLDErrorCorreoInvalido`, `PLDJSError*`, `PLDTrigger*` |
| **Fase 5** | Validación JS Client-Side | ❌ Pendiente | — | Archivo JS sigue siendo boilerplate |
| **Fase 6** | Verificación Integral | ❌ Pendiente | — | Depende de Fases 4-5 |

### Detalle de lo pendiente en Fase 4

Las siguientes keys de idioma son referenciadas en código PHP pero **no existen** en `modulecompliancepld.lang`:

| Key faltante | Usada por | Archivo |
|---|---|---|
| `PLDErrorTelefonoInvalido` | Hook `validarFormularioContacto()` | `actions_modulecompliancepld.class.php:341` |
| `PLDErrorCorreoInvalido` | Hook `validarFormularioContacto()` | `actions_modulecompliancepld.class.php:347` |
| `PLDJSErrorCURPFormato` | JS client-side (Fase 5) | `modulecompliancepld.js.php` |
| `PLDJSErrorRFCFormato` | JS client-side (Fase 5) | `modulecompliancepld.js.php` |
| `PLDJSErrorCPFormato` | JS client-side (Fase 5) | `modulecompliancepld.js.php` |
| `PLDJSErrorTelefonoFormato` | JS client-side (Fase 5) | `modulecompliancepld.js.php` |
| `PLDJSErrorCorreoFormato` | JS client-side (Fase 5) | `modulecompliancepld.js.php` |
| `PLDJSCampoValido` | JS client-side (Fase 5) | `modulecompliancepld.js.php` |

---

## Resumen Ejecutivo

La clase `PLDValidator` existe y funciona en aislamiento (tests PHPUnit pasan), pero está **completamente desconectada de Dolibarr**. Este plan implementa la integración en 6 fases de 15-25 minutos cada una.

### Problemas diagnosticados

1. **Trigger es boilerplate** — Todas las acciones comentadas, no llama a `PLDValidator`
2. **No existe hook class funcional** — No intercepta formularios de contacto/empresa
3. **Regex CURP demasiado permisivo** — No valida vocal en pos. 2, ni entidades federativas, ni consonantes
4. **Falta `declare(strict_types=1)`** — Requerido por AGENTS.md para PHP 8.1+
5. **Sin mensajes de error en `$langs`** — No usa `setEventMessages()` de Dolibarr

### Decisiones del usuario

| Decisión | Respuesta |
|---|---|
| Incluir JS client-side (Fase 5) | Sí — las 6 fases completas |
| Alcance de validación en hook | CURP, RFC, CP, teléfono, correo |
| Alcance de `declare(strict_types=1)` | Todos los archivos PLD |

### Arquitectura de defensa (dos capas)

| Capa | Mecanismo | Cuándo se dispara | ¿Bloquea guardado? |
|---|---|---|---|
| Hook (`doActions`) | Antes de procesar la acción | Usuario envía formulario | Sí — limpia `$action`, muestra errores |
| Trigger (`runTrigger`) | Dentro de transacción DB | Durante `create()`/`update()` | Sí — retorna `-1`, causa rollback |

---

## Mapa de Extrafields (referencia para todas las fases)

| Contexto | Campo | Extrafield attr | Nombre GETPOST | Método validador |
|---|---|---|---|---|
| `contactcard` | CURP | `pld_curp` | `options_pld_curp` | `validarCURP()` |
| `contactcard` | RFC | `pld_rfc` | `options_pld_rfc` | `validarRFC()` |
| `contactcard` | Teléfono | `pld_numero_telefono` | `options_pld_numero_telefono` | `validarTelefono()` |
| `contactcard` | Correo | `pld_correo_electronico` | `options_pld_correo_electronico` | `validarCorreo()` |
| `thirdpartycard` | CURP | `pld_curp` | `options_pld_curp` | `validarCURP()` |
| `thirdpartycard` | RFC | `pld_rfc_validado` | `options_pld_rfc_validado` | `validarRFC()` |
| `thirdpartycard` | CP | `pld_codigo_postal` | `options_pld_codigo_postal` | `validarCodigoPostal()` |

Fuente: `modModulecompliancepld.class.php` líneas 414-493.

---

## Grafo de Dependencias

```
Fase 1 (CURP regex + strict_types + tests)
    │
    ├──→ Fase 2 (Trigger handlers)
    │        └──→ Fase 4 (Lang entries para triggers)
    │
    ├──→ Fase 3 (Hook class)
    │        └──→ Fase 4 (Lang entries para hooks)
    │
    └──→ Fase 5 (Client-side JS)
              └──→ Fase 4 (Lang entries para JS)
                        │
                        └──→ Fase 6 (Verificación integral)
```

**Orden de ejecución requerido**: 1 → 2 → 3 → 4 → 5 → 6

---

## Fase 1: Corregir Regex CURP + `declare(strict_types=1)` + Actualizar Tests ✅

**Estado**: Completada — commit `c428dbc`
**Tiempo real**: ~20 minutos
**Dependencias**: Ninguna (primera fase)

### Archivos modificados

| # | Archivo | Acción |
|---|---|---|
| 1 | `htdocs/custom/modulecompliancepld/class/pldvalidator.class.php` | Modificar |
| 2 | `tests/Unit/CURPValidationTest.php` | Modificar |

### Tarea 1.1 — Agregar `declare(strict_types=1)` a `pldvalidator.class.php`

Insertar `declare(strict_types=1);` inmediatamente después de `<?php` (línea 1), antes del docblock.

**Antes** (línea 1):
```php
<?php
/**
```

**Después**:
```php
<?php
declare(strict_types=1);

/**
```

### Tarea 1.2 — Reemplazar constante `REGEX_CURP`

Reemplazar la constante en línea 36 con la especificación de AGENTS.md (sección 5.3):

**Antes** (líneas 33-36):
```php
const REGEX_CURP = '/^([A-Z]{4})((\d{2})(((0[469]|1[1])(0[1-9]|[12]\d|3[0]))|((0[2])(0[1-9]|[12]\d))|((0[13578]|1[02])(0[1-9]|[12]\d|3[01]))))([MH])([A-Z]{5})([A-J\d][\d])$/';
```

**Después**:
```php
const REGEX_CURP = '/^[A-Z]{1}[AEIOU]{1}[A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1])[HM]{1}(AS|BC|BS|CC|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TL|TS|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]{1}[0-9]{1}$/';
```

Cambios:
- Posiciones 1-2: `[A-Z]{4}` → `[A-Z]{1}[AEIOU]{1}[A-Z]{2}` — impone vocal en posición 2
- Fecha: validación por mes → `(0[1-9]|1[0-2])(0[1-9]|...|3[0-1])` más simple, delega precisión a `checkdate()`
- Entidad: `[A-Z]{5}` → `(AS|BC|BS|...NE)[B-DF-HJ-NP-TV-Z]{3}` — enumera 31 códigos válidos + solo consonantes
- Últimos 2: `([A-J\d][\d])` → `[0-9A-Z]{1}[0-9]{1}` — más permisivo en posición 17

### Tarea 1.3 — Actualizar método `validarCURP()` con `checkdate()`

El nuevo regex es más simple en fechas (permite día 31 en cualquier mes). Agregar `checkdate()` para atrapar fechas imposibles como feb 30 o abr 31.

**Antes** (líneas 186-193):
```php
public function validarCURP(string $curp): bool
{
    $curp = strtoupper(trim($curp));
    if (strlen($curp) !== 18) {
        return false;
    }
    return (bool) preg_match(self::REGEX_CURP, $curp);
}
```

**Después**:
```php
public function validarCURP(string $curp): bool
{
    $curp = strtoupper(trim($curp));
    if (strlen($curp) !== 18) {
        return false;
    }
    if (!preg_match(self::REGEX_CURP, $curp)) {
        return false;
    }
    // Validación adicional de fecha real (el regex permite día 31 en cualquier mes)
    $anio_2d = (int) substr($curp, 4, 2);
    $mes     = (int) substr($curp, 6, 2);
    $dia     = (int) substr($curp, 8, 2);
    // CURP usa año de 2 dígitos; asumir 2000+ para ≤30, 1900+ para >30
    $anio = $anio_2d <= 30 ? 2000 + $anio_2d : 1900 + $anio_2d;
    return checkdate($mes, $dia, $anio);
}
```

### Tarea 1.4 — Cambiar test de entidad no estándar en `CURPValidationTest.php`

El test `testCURPEstadoNoEstandarAceptadaPorRegex` (línea 150) actualmente usa `assertTrue` para CURP con entidad `ZZ`. El nuevo regex enumera entidades válidas, así que `ZZ` debe ser rechazada.

**Antes** (líneas 146-156):
```php
/**
 * CURP con código de entidad no estándar — el regex XSD acepta [A-Z]{5}
 * sin enumerar entidades, por lo que 'ZZ' pasa la validación de formato.
 * La validación semántica de entidad se hará en una capa superior.
 */
public function testCURPEstadoNoEstandarAceptadaPorRegex(): void
{
    $this->assertTrue(
        $this->validator->validarCURP('GOGA850315HZZNZR07'),
        'CURP con entidad ZZ pasa validación de formato (regex XSD no enumera entidades)'
    );
}
```

**Después**:
```php
/**
 * CURP inválida — código de entidad no válido (ZZ no está en las 31 entidades)
 * El regex AGENTS.md enumera las 31 entidades federativas válidas.
 */
public function testCURPInvalidaEntidadNoValida(): void
{
    $this->assertFalse(
        $this->validator->validarCURP('GOGA850315HZZNZR07'),
        'CURP con entidad ZZ debe ser rechazada (no es entidad válida)'
    );
}
```

### Tarea 1.5 — Agregar tests nuevos para validación más estricta

Agregar después de los tests existentes (antes del `}` final) en `CURPValidationTest.php`:

```php
/**
 * CURP inválida — posición 2 no es vocal
 * El regex AGENTS.md requiere [AEIOU] en posición 2.
 */
public function testCURPInvalidaSinVocalEnPosicion2(): void
{
    // GBGA... — B no es vocal
    $this->assertFalse(
        $this->validator->validarCURP('GBGA850315HDFNZR07'),
        'CURP sin vocal en posición 2 debe ser rechazada'
    );
}

/**
 * CURP inválida — vocal en posición consonante (14-16)
 * Posiciones 14-16 requieren [B-DF-HJ-NP-TV-Z] (solo consonantes).
 */
public function testCURPInvalidaVocalEnPosicionConsonante(): void
{
    // ...HDFAZR07 — A es vocal, no consonante
    $this->assertFalse(
        $this->validator->validarCURP('GOGA850315HDFAZR07'),
        'CURP con vocal en posición de consonante (14) debe ser rechazada'
    );
}

/**
 * CURP inválida — febrero 29 en año no bisiesto
 * El regex permite día 29 en cualquier mes; checkdate lo valida.
 */
public function testCURPInvalidaFeb29AnioNoBisiesto(): void
{
    // 850229 = 29 feb 1985 — 1985 no es bisiesto
    $this->assertFalse(
        $this->validator->validarCURP('GOGA850229HDFNZR07'),
        'CURP con 29 de febrero en año no bisiesto debe ser rechazada'
    );
}
```

### Verificación Fase 1

1. `php -l htdocs/custom/modulecompliancepld/class/pldvalidator.class.php` — sin errores de sintaxis
2. `./vendor/bin/phpunit tests/Unit/CURPValidationTest.php` — **todos los tests pasan** (17 total)
3. `./vendor/bin/phpunit tests/Unit/RFCValidationTest.php` — **todos pasan** (sin cambios, verificar que `strict_types` no rompe)
4. `./vendor/bin/phpunit tests/Unit/VINValidationTest.php` — **todos pasan** (sin cambios)
5. `./vendor/bin/phpunit tests/Unit/` — **suite completa en verde**

### Gotchas Fase 1

- El método `formatearMonto()` (línea 485) acepta `$monto` sin type hint. Con `strict_types=1` esto es seguro porque no hay declaración de tipo en el parámetro.
- La heurística de año `checkdate()` (`≤30 → 2000s, >30 → 1900s`) refleja la convención de RENAPO y coincide con todos los datos de test.

---

## Fase 2: Implementar Trigger Handlers ✅

**Estado**: Completada — commit `91db595`
**Tiempo real**: ~25 minutos
**Dependencias**: Fase 1 (necesita `PLDValidator` con regex CURP corregido)

### Archivos modificados

| # | Archivo | Acción |
|---|---|---|
| 1 | `htdocs/custom/modulecompliancepld/core/triggers/interface_99_modModulecompliancepld_ModulecompliancepldTriggers.class.php` | Modificar |

### Tarea 2.1 — Agregar `declare(strict_types=1)` después de `<?php`

Insertar al inicio del archivo (línea 1), antes del comentario de licencia.

### Tarea 2.2 — Actualizar metadatos del constructor

**Antes** (líneas 50-53):
```php
$this->family = "demo";
$this->description = "Modulecompliancepld triggers.";
```

**Después**:
```php
$this->family = "compliance";
$this->description = "Validación PLD en contactos y empresas — LFPIORPI Art. 17";
```

### Tarea 2.3 — Eliminar todos los switch cases comentados

Eliminar líneas 90-309 (el bloque switch completo con 200+ cases comentados). Reemplazar con un switch limpio:

```php
switch ($action) {
    default:
        dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
        break;
}
```

El manejo real lo hacen los métodos camelCase despachados por el dispatcher existente (líneas 79-87).

### Tarea 2.4 — Agregar `require_once` para PLDValidator

Después del `require_once` existente para `dolibarrtriggers.class.php` (línea 34):

```php
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldvalidator.class.php';
```

### Tarea 2.5 — Agregar método `contactCreate()`

El dispatcher existente convierte `CONTACT_CREATE` → `contactCreate`:

```php
/**
 * Validación PLD al crear un contacto
 *
 * @param string       $action  Acción (CONTACT_CREATE)
 * @param CommonObject $object  Objeto Contact
 * @param User         $user    Usuario que ejecuta
 * @param Translate    $langs   Traducciones
 * @param Conf         $conf    Configuración
 * @return int -1 si validación falla (rollback), 1 si OK
 */
public function contactCreate($action, $object, User $user, Translate $langs, Conf $conf): int
{
    return $this->validarCamposPLDContacto($object, $langs);
}
```

### Tarea 2.6 — Agregar método `contactModify()`

```php
/**
 * Validación PLD al modificar un contacto
 */
public function contactModify($action, $object, User $user, Translate $langs, Conf $conf): int
{
    return $this->validarCamposPLDContacto($object, $langs);
}
```

### Tarea 2.7 — Agregar método `companyCreate()`

```php
/**
 * Validación PLD al crear una empresa
 */
public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf): int
{
    return $this->validarCamposPLDEmpresa($object, $langs);
}
```

### Tarea 2.8 — Agregar método `companyModify()`

```php
/**
 * Validación PLD al modificar una empresa
 */
public function companyModify($action, $object, User $user, Translate $langs, Conf $conf): int
{
    return $this->validarCamposPLDEmpresa($object, $langs);
}
```

### Tarea 2.9 — Agregar helper privado `validarCamposPLDContacto()`

```php
/**
 * Valida campos PLD de un contacto (socpeople)
 *
 * @param CommonObject $object Contacto con array_options cargado
 * @param Translate    $langs  Traducciones
 * @return int 1 si válido, -1 si inválido (provoca rollback)
 */
private function validarCamposPLDContacto($object, Translate $langs): int
{
    $langs->load('modulecompliancepld@modulecompliancepld');
    $validator = new PLDValidator();

    $curp = $object->array_options['options_pld_curp'] ?? '';
    if ($curp !== '' && !$validator->validarCURP($curp)) {
        $this->errors[] = $langs->trans('PLDErrorCURPInvalida');
        dol_syslog("PLD Trigger: CURP inválida para contacto id=".$object->id." curp=".$curp, LOG_WARNING);
        return -1;
    }

    $rfc = $object->array_options['options_pld_rfc'] ?? '';
    if ($rfc !== '' && !$validator->validarRFC($rfc)) {
        $this->errors[] = $langs->trans('PLDErrorRFCInvalido');
        dol_syslog("PLD Trigger: RFC inválido para contacto id=".$object->id." rfc=".$rfc, LOG_WARNING);
        return -1;
    }

    $telefono = $object->array_options['options_pld_numero_telefono'] ?? '';
    if ($telefono !== '' && !$validator->validarTelefono($telefono)) {
        $this->errors[] = $langs->trans('PLDErrorTelefonoInvalido');
        dol_syslog("PLD Trigger: Teléfono inválido para contacto id=".$object->id, LOG_WARNING);
        return -1;
    }

    $correo = $object->array_options['options_pld_correo_electronico'] ?? '';
    if ($correo !== '' && !$validator->validarCorreo($correo)) {
        $this->errors[] = $langs->trans('PLDErrorCorreoInvalido');
        dol_syslog("PLD Trigger: Correo inválido para contacto id=".$object->id, LOG_WARNING);
        return -1;
    }

    dol_syslog("PLD Trigger: Contacto id=".$object->id." validado correctamente", LOG_INFO);
    return 1;
}
```

### Tarea 2.10 — Agregar helper privado `validarCamposPLDEmpresa()`

```php
/**
 * Valida campos PLD de una empresa (societe/thirdparty)
 *
 * @param CommonObject $object Empresa con array_options cargado
 * @param Translate    $langs  Traducciones
 * @return int 1 si válido, -1 si inválido (provoca rollback)
 */
private function validarCamposPLDEmpresa($object, Translate $langs): int
{
    $langs->load('modulecompliancepld@modulecompliancepld');
    $validator = new PLDValidator();

    $curp = $object->array_options['options_pld_curp'] ?? '';
    if ($curp !== '' && !$validator->validarCURP($curp)) {
        $this->errors[] = $langs->trans('PLDErrorCURPInvalida');
        dol_syslog("PLD Trigger: CURP inválida para empresa id=".$object->id." curp=".$curp, LOG_WARNING);
        return -1;
    }

    $rfc = $object->array_options['options_pld_rfc_validado'] ?? '';
    if ($rfc !== '' && !$validator->validarRFC($rfc)) {
        $this->errors[] = $langs->trans('PLDErrorRFCInvalido');
        dol_syslog("PLD Trigger: RFC inválido para empresa id=".$object->id." rfc=".$rfc, LOG_WARNING);
        return -1;
    }

    $cp = $object->array_options['options_pld_codigo_postal'] ?? '';
    if ($cp !== '' && !$validator->validarCodigoPostal($cp)) {
        $this->errors[] = $langs->trans('PLDErrorCPInvalido');
        dol_syslog("PLD Trigger: CP inválido para empresa id=".$object->id." cp=".$cp, LOG_WARNING);
        return -1;
    }

    dol_syslog("PLD Trigger: Empresa id=".$object->id." validada correctamente", LOG_INFO);
    return 1;
}
```

### Verificación Fase 2

1. `php -l` del archivo trigger — sin errores de sintaxis
2. `./vendor/bin/phpunit tests/Unit/` — **suite completa en verde** (triggers no se llaman desde unit tests)
3. Verificar que no quedan switch cases comentados
4. Verificar `declare(strict_types=1)` presente

### Gotchas Fase 2

- **Nombre de campos diferente entre tablas**: RFC en empresa es `options_pld_rfc_validado`, en contacto es `options_pld_rfc`
- **Null coalescing**: Siempre usar `?? ''` en lookups de `$object->array_options`
- **Retorno**: `1` = OK, `0` = no se ejecutó trigger, `-1` = error (rollback)
- El `require_once` para PLDValidator usa `DOL_DOCUMENT_ROOT.'/custom/...'`

---

## Fase 3: Implementar Hook Class (Validación en Formularios) ✅

**Estado**: Completada — commits `91db595` + `9d1e500` (cleanup boilerplate)
**Tiempo real**: ~25 minutos
**Dependencias**: Fase 1 (necesita `PLDValidator`)

### Archivos modificados

| # | Archivo | Acción |
|---|---|---|
| 1 | `htdocs/custom/modulecompliancepld/class/actions_modulecompliancepld.class.php` | Modificar |

### Tarea 3.1 — Agregar `declare(strict_types=1)` después de `<?php`

### Tarea 3.2 — Agregar `require_once` para PLDValidator

Después del `require_once` existente para `commonhookactions.class.php`:

```php
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldvalidator.class.php';
```

### Tarea 3.3 — Reescribir método `doActions()`

Reemplazar el método `doActions()` completo con lógica real de validación:

```php
/**
 * Validación PLD al enviar formularios de contacto o empresa
 *
 * Intercepta las acciones 'add' y 'update' en contactcard y thirdpartycard
 * para validar campos PLD antes de que Dolibarr procese la acción.
 *
 * @param array         $parameters  Metadatos del hook (context, etc.)
 * @param CommonObject  $object      Objeto en proceso
 * @param string        $action      Acción actual ('add', 'update', etc.)
 * @param HookManager   $hookmanager Gestor de hooks
 * @return int -1 si error de validación, 0 si OK
 */
public function doActions($parameters, &$object, &$action, $hookmanager)
{
    global $conf, $user, $langs;

    if (!isModEnabled('modulecompliancepld')) {
        return 0;
    }

    if (!in_array($action, array('add', 'update'))) {
        return 0;
    }

    $langs->load('modulecompliancepld@modulecompliancepld');
    $context = $parameters['currentcontext'];
    $error = 0;

    if ($context === 'contactcard') {
        $error = $this->validarFormularioContacto();
    } elseif ($context === 'thirdpartycard') {
        $error = $this->validarFormularioEmpresa();
    }

    if ($error > 0) {
        $action = '';
        return -1;
    }

    return 0;
}
```

### Tarea 3.4 — Agregar método privado `validarFormularioContacto()`

```php
/**
 * Valida campos PLD del formulario de contacto (socpeople)
 *
 * Campos validados: CURP, RFC, teléfono, correo electrónico
 *
 * @return int Número de errores encontrados
 */
private function validarFormularioContacto(): int
{
    global $langs;
    $validator = new PLDValidator();
    $error = 0;

    $curp = GETPOST('options_pld_curp', 'alpha');
    if ($curp !== '' && !$validator->validarCURP($curp)) {
        setEventMessages($langs->trans('PLDErrorCURPInvalida'), null, 'errors');
        $error++;
    }

    $rfc = GETPOST('options_pld_rfc', 'alpha');
    if ($rfc !== '' && !$validator->validarRFC($rfc)) {
        setEventMessages($langs->trans('PLDErrorRFCInvalido'), null, 'errors');
        $error++;
    }

    $telefono = GETPOST('options_pld_numero_telefono', 'alpha');
    if ($telefono !== '' && !$validator->validarTelefono($telefono)) {
        setEventMessages($langs->trans('PLDErrorTelefonoInvalido'), null, 'errors');
        $error++;
    }

    $correo = GETPOST('options_pld_correo_electronico', 'alpha');
    if ($correo !== '' && !$validator->validarCorreo($correo)) {
        setEventMessages($langs->trans('PLDErrorCorreoInvalido'), null, 'errors');
        $error++;
    }

    return $error;
}
```

### Tarea 3.5 — Agregar método privado `validarFormularioEmpresa()`

```php
/**
 * Valida campos PLD del formulario de empresa (thirdparty/societe)
 *
 * Campos validados: CURP, RFC, código postal
 *
 * @return int Número de errores encontrados
 */
private function validarFormularioEmpresa(): int
{
    global $langs;
    $validator = new PLDValidator();
    $error = 0;

    $curp = GETPOST('options_pld_curp', 'alpha');
    if ($curp !== '' && !$validator->validarCURP($curp)) {
        setEventMessages($langs->trans('PLDErrorCURPInvalida'), null, 'errors');
        $error++;
    }

    $rfc = GETPOST('options_pld_rfc_validado', 'alpha');
    if ($rfc !== '' && !$validator->validarRFC($rfc)) {
        setEventMessages($langs->trans('PLDErrorRFCInvalido'), null, 'errors');
        $error++;
    }

    $cp = GETPOST('options_pld_codigo_postal', 'alpha');
    if ($cp !== '' && !$validator->validarCodigoPostal($cp)) {
        setEventMessages($langs->trans('PLDErrorCPInvalido'), null, 'errors');
        $error++;
    }

    return $error;
}
```

### Tarea 3.6 — Limpiar boilerplate en `doMassActions()`

Eliminar `$this->resprints = 'A text to show';` (línea 150) y `$this->results = array('myreturn' => 999);` que mostraría texto en cada página.

### Verificación Fase 3

1. `php -l` del archivo hook — sin errores de sintaxis
2. `./vendor/bin/phpunit tests/Unit/` — **suite completa en verde**
3. No quedan strings `somecontext1` o `'A text to show'` en el archivo
4. `declare(strict_types=1)` presente
5. Nombres de GETPOST coinciden con mapa de extrafields

### Gotchas Fase 3

- **`GETPOST()` retorna `''` para campos vacíos**, no `null`
- **`$action = ''`** es la convención Dolibarr para bloquear procesamiento (no `$action = 'create'`)
- **Doble validación**: Hook (UX) + Trigger (integridad). Si alguien bypasea el form (API), el trigger aún lo atrapa
- **`GETPOST()` requiere runtime Dolibarr** — no disponible en PHPUnit unit tests

---

## Fase 4: Agregar Entradas de Idioma ⚠️ PARCIAL

**Estado**: Parcialmente completada — faltan 8 keys de idioma (ver tabla en Estado de Avance)
**Tiempo estimado restante**: ~10 minutos
**Dependencias**: Fases 2-3 (necesita saber qué keys de lang se referencian)

### Archivos modificados

| # | Archivo | Acción |
|---|---|---|
| 1 | `htdocs/custom/modulecompliancepld/langs/es_MX/modulecompliancepld.lang` | Modificar |

### Tarea 4.1 — Verificar mensajes existentes

Estas keys ya existen en el archivo lang y son usadas por las Fases 2-3:

| Key | Usada por |
|---|---|
| `PLDErrorCURPInvalida` | Hook + Trigger |
| `PLDErrorRFCInvalido` | Hook + Trigger |
| `PLDErrorCPInvalido` | Hook + Trigger |

**No necesitan cambios.**

### Tarea 4.2 — Agregar mensajes faltantes

Agregar después de la sección de errores existente:

```lang
# Mensajes de validación — formularios PLD (hook doActions)
PLDErrorTelefonoInvalido = El número de teléfono PLD debe tener entre 10 y 12 dígitos
PLDErrorCorreoInvalido = El correo electrónico PLD no tiene un formato válido (máximo 60 caracteres)

# Mensajes de log — triggers PLD
PLDTriggerContactoValidado = Contacto %s validado correctamente por módulo PLD
PLDTriggerContactoError = Error de validación PLD en contacto %s: %s
PLDTriggerEmpresaValidada = Empresa %s validada correctamente por módulo PLD
PLDTriggerEmpresaError = Error de validación PLD en empresa %s: %s
```

### Tarea 4.3 — Agregar mensajes para JS (Fase 5)

```lang
# Mensajes de validación — JavaScript (client-side)
PLDJSErrorCURPFormato = Formato CURP inválido: debe ser 18 caracteres (letra, vocal, 2 letras, fecha, sexo, entidad, 3 consonantes, dígito verificador)
PLDJSErrorRFCFormato = Formato RFC inválido: persona física 13 caracteres, persona moral 12 caracteres
PLDJSErrorCPFormato = Formato de código postal inválido: debe ser exactamente 5 dígitos
PLDJSErrorTelefonoFormato = Formato de teléfono inválido: debe tener entre 10 y 12 dígitos
PLDJSErrorCorreoFormato = Formato de correo electrónico inválido
PLDJSCampoValido = Formato válido
```

### Verificación Fase 4

1. Sin errores de formato (cada línea es `Key = Value` o `# Comentario`)
2. Sin keys duplicadas en el archivo
3. Cada key referenciada en trigger (Fase 2) existe en el archivo
4. Cada key referenciada en hook (Fase 3) existe en el archivo
5. `./vendor/bin/phpunit tests/Unit/` — **sigue en verde**

### Gotchas Fase 4

- Formato: Sin comillas alrededor de valores. `Key = Value` con exactamente un espacio a cada lado
- Los `%s` se llenan con `$langs->trans('Key', $param1, $param2)` en PHP
- Codificación UTF-8 sin BOM

---

## Fase 5: Validación JS Client-Side ❌ PENDIENTE

**Estado**: Pendiente — archivo JS sigue siendo boilerplate del scaffold Dolibarr
**Tiempo estimado**: ~20 minutos
**Dependencias**: Fase 4 (necesita keys de lang JS), Fase 1 (regex CURP correcto a replicar)

### Archivos modificados

| # | Archivo | Acción |
|---|---|---|
| 1 | `htdocs/custom/modulecompliancepld/js/modulecompliancepld.js.php` | Modificar |

### Tarea 5.1 — Agregar bloque PHP para traducir mensajes a variables JS

```php
<?php
// Cargar traducciones para JavaScript
$langs->load('modulecompliancepld@modulecompliancepld');
?>

/* Mensajes de validación PLD para cliente */
var pldMessages = {
    curpError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorCURPFormato")); ?>',
    rfcError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorRFCFormato")); ?>',
    cpError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorCPFormato")); ?>',
    telefonoError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorTelefonoFormato")); ?>',
    correoError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorCorreoFormato")); ?>',
    valido: '<?php echo dol_escape_js($langs->trans("PLDJSCampoValido")); ?>'
};
```

### Tarea 5.2 — Agregar constantes regex espejo de PHP

```javascript
/* Regex de validación PLD — espejo de PLDValidator */
var pldRegex = {
    curp: /^[A-Z]{1}[AEIOU]{1}[A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1])[HM]{1}(AS|BC|BS|CC|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TL|TS|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]{1}[0-9]{1}$/,
    rfcFisica: /^[A-ZÑ&]{4}[0-9]{6}[A-Z0-9]{3}$/,
    rfcMoral: /^[A-ZÑ&]{3}[0-9]{6}[A-Z0-9]{3}$/,
    cp: /^[0-9]{5}$/,
    telefono: /^[0-9]{10,12}$/,
    correo: /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,60}$/
};
```

### Tarea 5.3 — Agregar función helper de validación visual

```javascript
/**
 * Muestra u oculta mensaje de error junto a un campo
 */
function pldValidateField($field, isValid, errorMsg) {
    var $msg = $field.next('.pld-validation-msg');

    if ($msg.length === 0) {
        $field.after('<span class="pld-validation-msg" style="margin-left:8px;font-size:0.85em;"></span>');
        $msg = $field.next('.pld-validation-msg');
    }

    if (isValid) {
        $field.css('border-color', '');
        $msg.css('color', '#28a745').text(pldMessages.valido);
        setTimeout(function() { $msg.text(''); }, 2000);
    } else {
        $field.css('border-color', '#dc3545');
        $msg.css('color', '#dc3545').text(errorMsg);
    }
}
```

### Tarea 5.4 — Agregar event handlers en `$(document).ready()`

```javascript
$(document).ready(function() {
    /* Validación CURP al perder foco */
    $('input[name="options_pld_curp"]').on('blur', function() {
        var val = $(this).val().toUpperCase().trim();
        if (val === '') return;
        pldValidateField($(this), pldRegex.curp.test(val), pldMessages.curpError);
    });

    /* Validación RFC al perder foco */
    $('input[name="options_pld_rfc"], input[name="options_pld_rfc_validado"]').on('blur', function() {
        var val = $(this).val().toUpperCase().trim();
        if (val === '') return;
        var isValid = false;
        if (val.length === 13) {
            isValid = pldRegex.rfcFisica.test(val);
        } else if (val.length === 12) {
            isValid = pldRegex.rfcMoral.test(val);
        }
        pldValidateField($(this), isValid, pldMessages.rfcError);
    });

    /* Validación Código Postal al perder foco */
    $('input[name="options_pld_codigo_postal"]').on('blur', function() {
        var val = $(this).val().trim();
        if (val === '') return;
        pldValidateField($(this), pldRegex.cp.test(val), pldMessages.cpError);
    });

    /* Validación Teléfono al perder foco */
    $('input[name="options_pld_numero_telefono"]').on('blur', function() {
        var val = $(this).val().replace(/[\s\-\(\)]/g, '').trim();
        if (val === '') return;
        pldValidateField($(this), pldRegex.telefono.test(val), pldMessages.telefonoError);
    });

    /* Validación Correo al perder foco */
    $('input[name="options_pld_correo_electronico"]').on('blur', function() {
        var val = $(this).val().trim();
        if (val === '') return;
        pldValidateField($(this), pldRegex.correo.test(val) && val.length <= 60, pldMessages.correoError);
    });

    /* Auto-uppercase para CURP y RFC al escribir */
    $('input[name="options_pld_curp"], input[name="options_pld_rfc"], input[name="options_pld_rfc_validado"]').on('input', function() {
        var pos = this.selectionStart;
        $(this).val($(this).val().toUpperCase());
        this.setSelectionRange(pos, pos);
    });
});
```

### Verificación Fase 5

1. `php -l` del archivo JS — sin errores PHP
2. `./vendor/bin/phpunit tests/Unit/` — **suite completa en verde**
3. Regex CURP en JS coincide exactamente con constante PHP `REGEX_CURP`
4. Selectores jQuery coinciden con atributos `name` reales de extrafields Dolibarr

### Gotchas Fase 5

- El archivo `.js.php` carga `main.inc.php` de Dolibarr — `$langs` disponible pero requiere `$langs->load()`
- **`dol_escape_js()`** obligatorio al output PHP strings a JS (previene XSS)
- **JS no puede hacer `checkdate()`** — permite feb 30 etc. Aceptable porque server-side lo atrapa
- **Dolibarr usa jQuery** — `$(document).ready()` disponible globalmente

---

## Fase 6: Verificación Integral ❌ PENDIENTE

**Estado**: Pendiente — depende de Fases 4 y 5
**Tiempo estimado**: ~10 minutos
**Dependencias**: Todas las fases anteriores

### Archivos a verificar

| # | Archivo | Verificación |
|---|---|---|
| 1 | `class/pldvalidator.class.php` | `php -l`, `strict_types`, regex CURP nuevo |
| 2 | `core/triggers/...Triggers.class.php` | `php -l`, `strict_types`, 4 handlers, sin cases comentados |
| 3 | `class/actions_modulecompliancepld.class.php` | `php -l`, `strict_types`, `doActions()` real |
| 4 | `langs/es_MX/modulecompliancepld.lang` | Todas las keys referenciadas existen, sin duplicados |
| 5 | `js/modulecompliancepld.js.php` | `php -l`, regex CURP coincide con PHP |

### Tarea 6.1 — PHP lint en todos los archivos modificados

```bash
php -l htdocs/custom/modulecompliancepld/class/pldvalidator.class.php
php -l htdocs/custom/modulecompliancepld/core/triggers/interface_99_modModulecompliancepld_ModulecompliancepldTriggers.class.php
php -l htdocs/custom/modulecompliancepld/class/actions_modulecompliancepld.class.php
php -l htdocs/custom/modulecompliancepld/js/modulecompliancepld.js.php
```

Todos deben retornar `No syntax errors detected`.

### Tarea 6.2 — Ejecutar suite completa PHPUnit

```bash
./vendor/bin/phpunit tests/Unit/
```

Resultado esperado: **todos los tests pasan**.
- `CURPValidationTest`: 17 tests (15 originales − 1 cambiado + 3 nuevos)
- `RFCValidationTest`: 15 tests (sin cambios)
- `VINValidationTest`: ~50 tests (sin cambios)

### Tarea 6.3 — Verificar cobertura de lang keys

Grep de cada `$langs->trans('PLD...)` en archivos PHP y verificar que cada key existe en el lang file.

### Tarea 6.4 — Verificar consistencia de regex CURP

El regex CURP debe ser idéntico en 3 lugares:
1. `PLDValidator::REGEX_CURP` (PHP)
2. `pldRegex.curp` (JS)
3. AGENTS.md sección 5.3 (referencia)

### Tarea 6.5 — Verificar `declare(strict_types=1)` en todos los archivos PLD

```bash
grep -L 'declare(strict_types=1)' \
  htdocs/custom/modulecompliancepld/class/pldvalidator.class.php \
  htdocs/custom/modulecompliancepld/core/triggers/...Triggers.class.php \
  htdocs/custom/modulecompliancepld/class/actions_modulecompliancepld.class.php
```

Esperado: sin output (todos contienen la declaración).

---

## Evaluación de Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| `strict_types` rompe código existente | Baja | Media | Type hints ya usados; `formatearMonto()` sin type hint es seguro |
| Regex CURP rechaza CURPs válidas en producción | Baja | Alta | Las 5 CURPs válidas de test pasan; regex coincide con AGENTS.md |
| Hook bloquea guardados legítimos por mismatch de nombres GETPOST | Media | Alta | Mapa de extrafields verificado contra `modModulecompliancepld.class.php` |
| Trigger rollback causa pérdida de datos | Baja | Alta | Solo revierte la transacción actual; usuario ve error y puede corregir |
| JS da falsos negativos (rechaza input válido) | Baja | Baja | JS es solo advisory; server-side es autoritativo |
| `$object->array_options` no poblado en trigger | Baja | Media | Null coalescing `?? ''` maneja keys faltantes |
