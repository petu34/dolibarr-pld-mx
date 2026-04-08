# Plan: Interruptor de datos seed en admin/setup.php

## Contexto

`scripts/seed_datos_prueba.php` carga 30 operaciones PLD de prueba (societes,
productos, facturas, pagos, `llx_pld_operacion`). Actualmente solo se ejecuta
manualmente desde CLI. El objetivo es exponerlo como un toggle booleano en
`admin/setup.php`: encender ejecuta el seed; apagar borra todos los registros
generados por el seed.

El seed **no marca** sus registros con ningún campo trazable (`import_key`,
`note_private`, etc.). Eso es el problema central: sin marca no hay forma de
identificar qué borrar. La solución es añadir el marcado antes de implementar
el borrado.

---

## Estrategia de marcado — `import_key = 'pld_seed_v1'`

Dolibarr tiene el campo estándar `import_key varchar(14)` en todas las tablas
principales. Se usará `'pld_seed_v1'` (14 chars exactos) como marca de
origen en cada registro que el seed crea:

| Tabla | Columna marca | Valor |
|---|---|---|
| `llx_societe` | `import_key` | `'pld_seed_v1'` |
| `llx_product` | `import_key` | `'pld_seed_v1'` |
| `llx_facture` | `import_key` | `'pld_seed_v1'` |
| `llx_paiement` | `note` | `'pld_seed_v1'` (no tiene import_key) |
| `llx_pld_operacion` | `import_key` | `'pld_seed_v1'` |

> `llx_paiement` no tiene `import_key`, pero sí tiene `num_paiement varchar(50)`
> que actualmente el seed deja en `''`. Se usará ese campo como marca alternativa:
> `num_paiement = 'SEED-PLD-v1'`.

---

## Orden de borrado (integridad referencial soft)

```
1. llx_paiement_extrafields   WHERE fk_object IN (pagos marcados)
2. llx_paiement_facture        WHERE fk_paiement IN (pagos marcados)
3. llx_paiement                WHERE num_paiement = 'SEED-PLD-v1'
4. llx_facture_det             WHERE fk_facture IN (facturas marcadas)
5. llx_factureline_extrafields WHERE fk_object  IN (facturas marcadas)  [si existe]
6. llx_facture                 WHERE import_key = 'pld_seed_v1'
7. llx_pld_operacion           WHERE import_key = 'pld_seed_v1'
8. llx_product                 WHERE import_key = 'pld_seed_v1'
9. llx_societe_extrafields     WHERE fk_object IN (societes marcadas)
10. llx_societe                WHERE import_key = 'pld_seed_v1'
```

> Las facturas de Dolibarr tienen estado — hay que setear `statut = 0` (brouillon)
> antes de borrar para evitar restricciones lógicas del ORM; o borrar directo
> con SQL (más simple para seed de prueba).

---

## Constante de control

```
MODULECOMPLIANCEPLD_SEED_ACTIVO   int  (0 = apagado, 1 = encendido)
```

Guardada con `dolibarr_set_const()` igual que el resto de params del setup.

---

## Archivos a crear / modificar

| Archivo | Cambio |
|---|---|
| `scripts/seed_datos_prueba.php` | +marcado `import_key`/`num_paiement` en cada INSERT |
| `scripts/seed_purge.php` | CREAR — script CLI que borra los registros marcados |
| `admin/setup.php` | +sección "Datos de Prueba" con toggle + acción `toggle_seed` |
| `langs/es_MX/modulecompliancepld.lang` | +5 claves |

---

## Detalle por archivo

### 1. `scripts/seed_datos_prueba.php` — añadir marcas

En cada función de creación añadir antes del `create()` / `INSERT`:

```php
// societes
$soc->import_key = 'pld_seed_v1';

// products
$prod->import_key = 'pld_seed_v1';

// facturas
$factura->import_key = 'pld_seed_v1';

// pld_operacion (en el INSERT directo o en el objeto PLDOperacion)
$op->import_key = 'pld_seed_v1';

// paiement — en el INSERT directo cambiar num_paiement de '' a 'SEED-PLD-v1'
```

El script ya es idempotente (chequea existencia por RFC/VIN antes de insertar),
por lo que los registros previos sin marca **no serán re-insertados**. La marca
solo aplica a ejecuciones futuras del seed.

> **Nota importante:** los registros que ya existen en BD (creados antes de
> este cambio) **no tendrán marca** y no serán borrados por el purge. Para
> limpiar una BD con seed viejo el usuario deberá hacerlo manualmente o
> volver a instalar. Documentar esto en el setup.

### 2. `scripts/seed_purge.php` — CREAR

Script CLI (mismo patrón de bootstrap que `seed_datos_prueba.php`):

```
1. Bootstrap Dolibarr (NOSESSION + NOLOGIN)
2. $user->fetch(1); $user->getrights();
3. Ejecutar las 10 sentencias DELETE en orden (ver sección "Orden de borrado")
4. Imprimir conteo de filas borradas por tabla
5. Retornar exit(0) si ok, exit(1) si error
```

### 3. `admin/setup.php` — nueva sección

Nueva sección al final del formulario, antes del botón Save, titulada
`$langs->trans('SeccionDatosPrueba')`:

```
┌─────────────────────────────────────────────────────────────────┐
│ DATOS DE PRUEBA                                                 │
├──────────────────────┬──────────────┬───────────────────────────┤
│ Datos seed activos   │ [ON / OFF]   │ Al activar: ejecuta seed. │
│                      │              │ Al desactivar: borra datos │
│                      │              │ marcados con pld_seed_v1. │
└──────────────────────┴──────────────┴───────────────────────────┘
```

El toggle **no forma parte del `action=update`** habitual. Usa una acción
separada `action=toggle_seed` con su propio `<form>` y botón dedicado para
que el usuario tenga control explícito y consciente (encender/apagar el seed
es destructivo).

**Lógica de `toggle_seed`:**

```php
if ($action == 'toggle_seed' && $user->admin) {
    $nuevo_estado = (int) GETPOST('seed_activo', 'int');
    if ($nuevo_estado == 1) {
        // Ejecutar seed
        $cmd = 'docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/seed_datos_prueba.php 2>&1';
        exec($cmd, $output, $retcode);
        if ($retcode === 0) {
            dolibarr_set_const($db, 'MODULECOMPLIANCEPLD_SEED_ACTIVO', 1, 'int', 0, '', $conf->entity);
            setEventMessages($langs->trans('SeedActivado'), null, 'mesgs');
        } else {
            setEventMessages($langs->trans('SeedError').': '.implode("\n", array_slice($output, -5)), null, 'errors');
        }
    } else {
        // Ejecutar purge
        $cmd = 'docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/seed_purge.php 2>&1';
        exec($cmd, $output, $retcode);
        if ($retcode === 0) {
            dolibarr_set_const($db, 'MODULECOMPLIANCEPLD_SEED_ACTIVO', 0, 'int', 0, '', $conf->entity);
            setEventMessages($langs->trans('SeedDesactivado'), null, 'mesgs');
        } else {
            setEventMessages($langs->trans('SeedPurgeError').': '.implode("\n", array_slice($output, -5)), null, 'errors');
        }
    }
}
```

> `exec()` corre en el servidor web, no en Docker. Si Dolibarr corre **dentro**
> de Docker (como en este proyecto), `docker exec` no es accesible desde el
> proceso PHP del container. La alternativa es incluir directamente el script
> con `require_once` o con `passthru('php ...')` dentro del mismo container.
> Ver "Decisión de ejecución" abajo.

### Decisión de ejecución — `require_once` vs `exec`

Dado que Dolibarr corre **dentro** de `doli20`:

```
PHP (Apache en doli20) → exec('docker exec ...') → ✗ no funciona
PHP (Apache en doli20) → require_once script      → ✓ mismo proceso
```

Se usará `require_once` directamente. Los scripts deben adaptarse para
poder ser incluidos (no solo ejecutados por CLI):

- Cambiar el guard de `php_sapi_name()` por una constante opcional
  `SEED_CLI_MODE` que solo define el script cuando corre directo en CLI.
- Definir una función principal `runSeed($db, $user)` y `runSeedPurge($db, $user)`
  que devuelvan `array('ok' => bool, 'log' => string[])`.
- El bootstrap CLI llama a esa función; el setup.php también la llama directo.

### 4. Claves de idioma

```
SeccionDatosPrueba = Datos de prueba
SeedActivoLabel    = Datos seed activos
SeedActivoDesc     = Al activar ejecuta el script seed (30 operaciones de prueba). Al desactivar borra los registros marcados con import_key=pld_seed_v1.
SeedActivado       = Datos de prueba cargados correctamente
SeedDesactivado    = Datos de prueba eliminados correctamente
SeedError          = Error al ejecutar el seed
SeedPurgeError     = Error al purgar datos seed
SeedAvisoSinMarca  = Advertencia: registros creados antes de esta versión no tienen marca y no serán borrados automáticamente
```

---

## Flujo visual en setup.php

```
Estado actual: [● ACTIVO]

[Desactivar y borrar datos seed]   ← botón rojo, requiere confirmación JS

─────── ó ───────

Estado actual: [○ INACTIVO]

[Activar y cargar datos seed]      ← botón verde
```

Confirmación JS antes de desactivar:
```js
confirm('¿Eliminar todos los registros seed marcados con pld_seed_v1? Esta acción no puede deshacerse.')
```

---

## Verificación

1. Setup → activar seed → avisar "Cargado". Ir a `operaciones_list.php` → ver 30 ops.
2. Setup → desactivar → confirmar JS → avisar "Eliminado". Volver a lista → 0 ops seed.
3. Verificar que societes/facturas/pagos seed también desaparecen.
4. Verificar que registros **sin** marca (data real) no son borrados.

---

## Limitaciones conocidas

- Registros seed creados **antes** de añadir el marcado no son borrables con purge.
- Si el seed falla a mitad de ejecución, `MODULECOMPLIANCEPLD_SEED_ACTIVO` no se
  actualiza a 1 (el toggle permanece en 0 para evitar inconsistencia).
- El purge borra con SQL directo (sin pasar por ORM ni triggers), lo cual es
  aceptable para datos de prueba pero debe documentarse.
