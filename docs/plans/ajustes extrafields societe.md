**Estado: COMPLETADO — commit `2eda1e7` rama `reglamento-dof-2026` (2026-03-31)**

Aquí está el plan detallado para que Claude Code lo implemente en `/Users/austria/Documents/dlbrpld/dolibarr-pld-mx/`:

---

## Plan de correcciones al módulo `modulecompliancepld`

### Problema 1 — Validación incorrecta de campos Boolean en el formulario de terceros

**Síntoma:** Al crear un tercero, el servidor rechaza el formulario si los checkboxes `¿Domicilio Extranjero?`, `¿Persona Expuesta Políticamente?` y `¿Tiene Beneficiario Controlador?` están **desmarcados**, aunque sí envían el campo oculto `_boolean=1`.

**Causa raíz:** El módulo tiene un hook de validación de terceros (probablemente en `core/modules/modModuleCompliancePld.class.php` o en un hook de `Societe` registrado en `core/triggers/` o `core/hooks/`) que verifica si el valor POST del campo está presente, pero para campos Boolean de Dolibarr, un checkbox desmarcado solo envía `options_pld_XXX_boolean=1` sin enviar `options_pld_XXX`. El código del módulo erróneamente trata eso como "campo vacío".

**Archivos a modificar:**
- Buscar en `core/triggers/interface_99_modModuleCompliancePld_*.class.php` o en el módulo principal el hook `formObjectOptions` o la función de validación que itera campos requeridos PLD.
- En la lógica de validación de campos obligatorios, cambiar la condición: para campos de tipo `boolean`, el valor válido incluye tanto `1` (marcado) como `0`/vacío (desmarcado). La presencia del campo `_boolean=1` debe ser suficiente para que pase la validación de "campo rellenado".
- Edita el help text para que describa qué implica marcar el campo (sí) o dejarlo desmarcado (no)

**Cambio concreto:** En el código de validación, donde se hace algo como:
```php
if (empty(GETPOST('options_pld_es_pep'))) { // ERROR: siempre vacío si unchecked
    $error++;
}
```
Cambiarlo a:
```php
if (!isset($_POST['options_pld_es_pep_boolean'])) { // Correcto: detecta presencia
    $error++;
}
```
O bien, marcar esos tres campos como **no obligatorios** en la definición del extrafield (en el instalador del módulo), ya que un checkbox que se puede dejar en blanco semánticamente equivale a "No".

---

### Problema 2 — El selector "Nivel de Riesgo" muestra `/custom` en el texto de opciones

**Síntoma:** Cuando el extrafield `pld_nivel_riesgo` tiene opciones del tipo `bajo:Bajo`, el select muestra el texto como `Bajo: /custom` en lugar de solo `Bajo`. Esto ocurre porque el campo tiene configurado el atributo `langfile = 'modulecompliancepld@modulecompliancepld'`, y Dolibarr interpreta el formato `key:Label` concatenado con la ruta del langfile, resultando en `key:Label,/path/to/langfile`.

**Archivos a modificar:**
- En el archivo de instalación o actualización del módulo (probablemente `modulecompliancepld.class.php` en la raíz o en `core/modules/`), donde se definen los extrafields en `$this->module_parts` o en el método `init()` mediante `insertExtraFields`, buscar la definición del campo `pld_nivel_riesgo`.
- Eliminar o dejar vacío el parámetro `langfile` de ese campo, **o bien** cambiar el formato de las opciones para que usen solo claves sin prefijo de traducción.

**Cambio concreto:** En la definición del extrafield `pld_nivel_riesgo`, cambiar:
```php
'langfile' => 'modulecompliancepld@modulecompliancepld',
'param'    => ['options' => ['bajo' => 'NivelBajo', 'medio' => 'NivelMedio', 'alto' => 'NivelAlto']],
```
A (usando etiquetas literales sin archivo de idioma):
```php
'langfile' => '',
'param'    => ['options' => ['bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto']],
```
O bien, si se quiere mantener i18n, agregar las claves `NivelBajo`, `NivelMedio`, `NivelAlto` al archivo de idioma del módulo en `langs/es_MX/modulecompliancepld.lang`.

---

### Problema 3 — Valor almacenado con formato incorrecto en `pld_nivel_riesgo`

**Síntoma derivado:** Como el selector guarda `medio:Medio` como valor (el `value` del `<option>` es la clave compuesta), en base de datos se almacenará `medio:Medio` en lugar de solo `medio`. Esto causará problemas al leer el valor posteriormente.

**Cambio concreto:** Asegurarse de que los `<option value="...">` del selector solo contengan la clave corta (`bajo`, `medio`, `alto`), no la clave compuesta. Esto se resuelve al corregir el Problema 2 (definición del extrafield sin langfile).

---

### Resumen de archivos a revisar/modificar:

1. **`modulecompliancepld.class.php`** (o el archivo de instalación del módulo) — definición del extrafield `pld_nivel_riesgo`: corregir `langfile` y `param['options']`.
2. **Hook o trigger de validación de terceros** (en `core/triggers/` o `core/hooks/`) — lógica que valida campos PLD obligatorios: corregir la detección de valor para campos Boolean.
3. **`langs/es_MX/modulecompliancepld.lang`** (opcional) — agregar traducciones para las claves del nivel de riesgo si se decide mantener i18n.

---

## Implementación realizada

**Archivos modificados:**

- `htdocs/custom/modulecompliancepld/core/modules/modModulecompliancepld.class.php`
  - `pld_es_domicilio_extranjero`, `pld_es_pep`, `pld_tiene_beneficiario`: `required` 1 → 0
  - `$optNivelRiesgo`: cambiado a `array('options' => [...])` — formato correcto para `select`

- `htdocs/custom/modulecompliancepld/langs/es_MX/modulecompliancepld.lang`
  - Help texts de los 3 campos boolean actualizados con semántica marcado/sin marcar y refs legales

**Nota:** Desactivar y reactivar el módulo PLD desde Configuración → Módulos para que los cambios de `required` se apliquen en BD.