# Plan Fase 4: Soporte Multi-Actividad Vulnerable

> **Creado:** 2026-03-19
> **Estado:** 🔴 NO INICIADA — prerequisito: Fase 3 completada y validada en producción
> **Prerequisito técnico:** Al menos una segunda actividad vulnerable operativa en el cliente

---

## Contexto y motivación

La Fase 3 implementó `PLDXMLGenerator` como una clase **monolítica exclusiva para
Fracción VIII (vehículos / `veh.xsd`)**. Este diseño es funcional para el caso de
uso actual pero no es extensible a otras actividades vulnerables sin modificación
de la clase base.

Las fracciones declaradas en scope del módulo son:

| Fracción | Actividad | XSD SAT | Estado |
|----------|-----------|---------|--------|
| V | Transmisión de inmuebles (inmobiliarias) | `inmu.xsd` | ⏳ Fase 4 |
| VIII | Compraventa de vehículos | `veh.xsd` | ✅ Fase 3 |
| XI | Fedatarios públicos (notarios) | `not.xsd` | ⏳ Fase 4 |
| XII | Transmisión/custodia de fondos | `fp.xsd` | ⏳ Fase 4 |
| XIII | Blindaje de vehículos | `blin.xsd` | ⏳ Fase 4 |
| XV | Servicios profesionales | `ssprof2.xsd` | ⏳ Fase 4 |

---

## Deuda técnica heredada de Fase 3

### `PLDXMLGenerator` — limitaciones actuales

1. **Namespace hardcodeado** a `http://www.uif.shcp.gob.mx/recepcion/veh`
2. **XSD hardcodeado** a `veh.xsd`
3. **`crearActoOperacion()`** genera siempre el nodo `<vehiculo>` con campos
   específicos de VEH (VIN, marca, modelo, clase, origen, uso)
4. **Catálogos** (`mapTipoVehiculo`, `mapClaseVehiculo`, `mapOrigen`, `mapUso`)
   son específicos de vehículos
5. **`getOperacionesMes()`** filtra por `requiere_aviso = 1` sin distinguir
   el tipo de actividad cuando hay múltiples en la misma entidad
6. **Extrafields fuente** asumidos son exclusivamente de `llx_product_extrafields`
   (vehículo). Para notarios la fuente es `llx_societe` + datos del instrumento;
   para inmobiliarias es `llx_product` con campos completamente distintos.

---

## Arquitectura objetivo: clase abstracta + subclases por XSD

```
PLDXMLGeneratorBase  (abstract)
│
│  # Métodos comunes — igual para todos los XSD
│  + generarXMLMensual(mes): string
│  + crearSujetoObligado(dom): DOMElement
│  + crearPersonaAviso(dom, operacion): DOMElement
│  + crearPersonaFisica(dom, cliente): DOMElement
│  + crearPersonaMoral(dom, cliente): DOMElement
│  + crearDomicilio(dom, cliente): DOMElement
│  + crearBeneficiario(dom, operacion): DOMElement
│  + guardarXML(content, mes, user): string
│  + cleanXML(text): string
│
│  # Métodos abstractos — cada subclase implementa
│  + abstract getNamespace(): string
│  + abstract getXSDLocation(): string
│  + abstract getTipoActividad(): string       ← 'VIII', 'V', 'XI'...
│  + abstract crearActoOperacion(dom, op): DOMElement
│  + abstract getOperacionesMes(mes): array    ← puede filtrar por actividad
│
PLDXMLGeneratorVeh   → veh.xsd    Fracción VIII  (migrar desde Fase 3)
PLDXMLGeneratorInmu  → inmu.xsd   Fracción V
PLDXMLGeneratorNot   → not.xsd    Fracción XI
PLDXMLGeneratorFP    → fp.xsd     Fracción XII
PLDXMLGeneratorBlin  → blin.xsd   Fracción XIII
PLDXMLGeneratorSS    → ssprof2.xsd Fracción XV
```

### Factory para instanciar el generador correcto

```php
// class/pldxmlgeneratorfactory.class.php
class PLDXMLGeneratorFactory
{
    public static function build(string $fraccion, $db): PLDXMLGeneratorBase
    {
        $map = array(
            'V'    => 'PLDXMLGeneratorInmu',
            'VIII' => 'PLDXMLGeneratorVeh',
            'XI'   => 'PLDXMLGeneratorNot',
            'XII'  => 'PLDXMLGeneratorFP',
            'XIII' => 'PLDXMLGeneratorBlin',
            'XV'   => 'PLDXMLGeneratorSS',
        );
        if (!isset($map[$fraccion])) {
            throw new InvalidArgumentException("Fracción $fraccion no soportada");
        }
        $class = $map[$fraccion];
        require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/'.strtolower($class).'.class.php';
        return new $class($db);
    }
}
```

---

## Pasos de implementación

### Paso 1 — Refactorizar `PLDXMLGenerator` → `PLDXMLGeneratorBase`

- Extraer los métodos comunes a la clase abstracta
- Mover la lógica VEH a `PLDXMLGeneratorVeh` (subclase)
- La clase `PLDXMLGenerator` existente puede quedar como alias o eliminarse
- Actualizar `xml_generator.php` para usar la factory

> **Sin regresión:** `PLDXMLGeneratorVeh` debe producir XML idéntico al de Fase 3.

### Paso 2 — Extrafields y tablas por actividad nueva

Cada actividad requiere sus propios extrafields en las tablas correspondientes.
Ejemplo para Fracción XI (notarios):

- `llx_product_extrafields`: campos del instrumento notarial (escritura, folio, notario, tipo acto)
- `llx_societe_extrafields`: ya existen campos PLD genéricos
- Nueva migración SQL: `migration_009_extrafields_notaria.sql`

### Paso 3 — Subclase por actividad

Implementar `crearActoOperacion()` para cada XSD. Referencia de nodos:

| Actividad | Nodo principal | Campos clave |
|-----------|---------------|--------------|
| Inmobiliaria (V) | `<inmueble>` | superficie, tipo_inmueble, clave_catastral, ubicacion |
| Notarios (XI) | `<instrumento_notarial>` | numero_escritura, tipo_instrumento, folio_real |
| Fondos (XII) | `<datos_transferencia>` | cuenta_origen, cuenta_destino, institucion |
| Blindaje (XIII) | `<vehiculo_blindado>` | nivel_blindaje + campos VEH |
| Servicios prof. (XV) | `<servicio_profesional>` | tipo_servicio, descripcion |

### Paso 4 — UI multi-actividad en `xml_generator.php`

Agregar selector de fracción/actividad antes del mes reportado. La factory
instancia el generador correcto en tiempo de ejecución.

### Paso 5 — Tests de validación XSD

Para cada actividad, validar el XML generado contra el XSD oficial del SAT
antes de permitir el envío al portal SPPLD.

```php
// Validación contra XSD
$dom = new DOMDocument();
$dom->loadXML($xml_content);
if (!$dom->schemaValidate($xsd_local_path)) {
    // libxml_get_errors() para detalle
}
```

---

## Notas para el implementador

- Descargar los XSD oficiales desde el portal SPPLD y guardarlos en
  `htdocs/custom/modulecompliancepld/xsd/` para validación offline.
- La cadena original para la firma e.firma varía por XSD; verificar el
  Anexo Técnico de cada resolución SAT antes de implementar
  `generarCadenaOriginal()` en cada subclase.
- `PLDEFirmaIntegration` es reutilizable sin cambios — la firma es
  independiente del tipo de actividad.
