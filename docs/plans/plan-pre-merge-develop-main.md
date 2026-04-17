# Plan Pre-Merge: develop → main

**Fecha:** 2026-04-17
**Estado:** pendiente
**Objetivo:** Resolver los issues críticos e importantes identificados en la revisión de PR antes de hacer merge de `develop` a `main`.

---

## Veredicto de la revisión

**NO hacer merge todavía.** Se encontraron 7 issues críticos y 7 importantes en tres dimensiones: código/SQL, errores silenciosos (riesgo regulatorio), y diseño de tipos.

---

## Paso 1 — Críticos bloqueantes (hacer hoy)

### C1 — `SUBSTRING_INDEX` / `CAST AS UNSIGNED` rompen PostgreSQL

- **Archivo:** `class/repository/PLDOperacionRepository.php:79-85`
- **Método:** `getUltimoFolioConsecutivo()`
- **Fix:** Eliminar el SQL con `SUBSTRING_INDEX`. Ejecutar un SELECT simple con `LIKE 'PLD-YYYY-MM-%'`, traer los folios a PHP y parsear el consecutivo con `explode('-', $folio)`.

### C2 — `DECIMAL(15,2)` en importes monetarios (viola CLAUDE.md)

- **Archivos:**
  - `sql/llx_pld_operacion.sql` — campo `monto_mxn`
  - `sql/llx_pld_aviso.sql` — campo `monto_total_operaciones`
  - `sql/migrations/migration_007_fase2_tablas_pld.sql` — mismos campos
- **Fix:** Cambiar `DECIMAL(15,2)` → `double(24,8)` en los campos de importe monetario. `porcentaje_participacion DECIMAL(5,2)` en `llx_pld_beneficiario.sql` es aceptable (no es importe).

### C3 — `PLDOperacionRepository` retorna valores neutros en fallo de BD sin log

- **Archivo:** `class/repository/PLDOperacionRepository.php`
- **Métodos afectados:** `getAcumuladoSeisMeses()`, `getUltimoFolioConsecutivo()`, `fetchBeneficiarioIds()`, `fetchFormasPago()`, `hidratar()`
- **Riesgo regulatorio:** `getAcumuladoSeisMeses()` retorna `0.0` en error → sistema calcula que el cliente no acumula operaciones → omisión silenciosa de aviso obligatorio al SAT.
- **Fix:**
  - Agregar propiedad `public string $error = '';` a la clase.
  - En cada método: si `$db->query()` falla, asignar `$this->error = $this->db->lasterror()`, llamar `dol_syslog()` y retornar valor distinguible (`-1.0` para floats, `-1` para ints, `null` para arrays).
  - En `evaluarUmbral()` (consumidor): verificar `$acumulado < 0` y forzar aviso conservador ante error.

### C4 — `hidratar()` descarta operaciones fallidas silenciosamente

- **Archivo:** `class/repository/PLDOperacionRepository.php::hidratar()`
- **Fix:**
  - Loggear con `dol_syslog()` cuando `$operacion->fetch()` falla (en lugar de solo `continue`).
  - Verificar retornos de `fetchCliente()`, `fetchVehiculo()`, `fetchBeneficiarios()`, `fetchFormasPago()` — si alguno falla, loggear y decidir si abortar o continuar con advertencia.

### C5 — `$aviso->update()` sin verificar retorno en `PLDAvisoService`

- **Archivo:** `class/services/PLDAvisoService.php:1510`
- **Fix:**
  ```php
  $updateResult = $aviso->update($user);
  if ($updateResult < 0) {
      $this->error = 'XML generado en '.$ruta.' pero no se pudo actualizar el aviso en BD: '.implode(', ', $aviso->errors);
      dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
      return false;
  }
  ```

### C6 — `getOperacionesDisponibles()` oculta error de BD

- **Archivo:** `class/services/PLDAvisoService.php::getOperacionesDisponibles()`
- **Fix:** Si `$db->query()` falla, asignar `$this->error`, llamar `dol_syslog()` y retornar `[]`. La clase ya tiene `$error`/`$errors` — usarlas.

### C7 — `CURP::from()` no aplica `checkdate()` — acepta fechas imposibles

- **Archivo:** `class/vo/CURP.php::from()`
- **Fix:** Después del `preg_match`, extraer año/mes/día del CURP y validar con `checkdate()`:
  ```php
  $anio2d = (int) substr($clean, 4, 2);
  $mes    = (int) substr($clean, 6, 2);
  $dia    = (int) substr($clean, 8, 2);
  $anio   = $anio2d <= 30 ? 2000 + $anio2d : 1900 + $anio2d;
  if (!checkdate($mes, $dia, $anio)) {
      throw new \InvalidArgumentException("CURP inválido (fecha imposible): '{$raw}'");
  }
  ```
  Alternativa: hacer que `CURP::from()` delegue a `PLDValidator::validarCURP()` para no duplicar lógica.

---

## Paso 2 — Críticos regulatorios (antes del merge)

### C8 — `migration_010` usa `ADD COLUMN IF NOT EXISTS` (MySQL < 8.0.31 no lo soporta)

- **Archivo:** `sql/migrations/migration_010_bugfix_alpha.sql`
- **Fix:** Usar el patrón Dolibarr estándar: intentar el `ALTER` sin `IF NOT EXISTS`. O verificar la existencia de la columna consultando `information_schema.COLUMNS` antes de ejecutar el `ALTER`.

---

## Paso 3 — Issues importantes (pueden ir en PR de seguimiento inmediato)

| # | Archivo | Acción |
|---|---------|--------|
| I1 | `PLDAvisoService.php::generarXML()` — bloque firma | Si `$firma === false`, siempre loggear error aunque `$efirma->error` esté vacío. XML sin firma no debe presentarse como éxito silencioso. |
| I2 | `PLDOperacion.php::fetchBeneficiarios()` | Loggear con `dol_syslog()` cuando `$ben->fetch($id)` falla. Retornar valor diferenciado si alguno falla. |
| I3 | `PLDXMLGenerator.php::generarXMLMensual()` | Incluir `$e->getFile().':'.$e->getLine()` en el mensaje del catch. |
| I4 | `PLDXMLGenerator.php::guardarXML()` | Verificar retorno de `dol_mkdir()` + agregar `dol_syslog()` en el bloque de error de `file_put_contents`. |
| I5 | `VIN.php` | Corregir REGEX a `/^[A-HJ-NPR-Z\d]{17}$/` (ISO 3779, excluye I/O/Q y no acepta `-_`), o documentar explícitamente en el docblock por qué se aceptan si el XSD lo permite. |
| I6 | `PLDFormValidator.php` | Cambiar `GETPOST($field, 'alpha')` a `'alphanohtml'` o `'nohtml'` para campos que pueden contener `Ñ`/`&` (RFC, nombres). |
| I7 | `PLDReporteService.php::countQuery()/fetchAll()` | Agregar `dol_syslog()` cuando `$db->query()` falla. Considerar agregar `$error`/`$errors` a la clase para consistencia con los otros servicios. |

---

## Deuda técnica (post-merge, no urgente)

- **Dos capas de validación desincronizadas:** Los VOs (`CURP`, `RFC`, `VIN`) usan `preg_match` directo con constantes REGEX, mientras `PLDValidator` tiene validaciones adicionales que los VOs ignoran. Objetivo: los VOs deben ser la fuente de verdad; `PLDValidator` debe delegar a `CURP::tryFrom()` etc., no reimplementar.
- **`datec` vs `date_creation`:** Las tablas usan `datec DATETIME NOT NULL`; CLAUDE.md especifica `date_creation datetime`. Alinear o documentar la decisión en `docs/architecture/DECISIONS.md`.
- **`RFC::tipo()` retorna `string`:** Exponer constantes `RFC::TIPO_FISICA` y `RFC::TIPO_MORAL` para que el código consumidor no dependa de literales implícitos.
- **Race condition en folio consecutivo:** La secuencia leer-incrementar-insertar en `PLDOperacionService::crearOperacion()` no usa transacción ni `SELECT FOR UPDATE`. Si el campo `folio_interno` tiene `UNIQUE`, el duplicado falla con error de BD (controlado). Si no tiene `UNIQUE`, agregar constraint o transacción.

---

## Fortalezas del código (no requieren acción)

- Sin FK duras en DDL (cumple CLAUDE.md)
- Sin SQL injection — `$db->escape()` y casting `(int)` consistentes
- `ENGINE=InnoDB` y `AUTO_INCREMENT` en todos los DDL (cumple CLAUDE.md)
- `$db->ifsql()` usado correctamente
- Service Layer, Repository Pattern y Strategy/Composite bien estructurados para la complejidad del dominio
- Value Objects con constructor privado, `final`, inmutables y `from()`/`tryFrom()` — patrón correcto
- Migraciones documentadas con contexto de decisión
