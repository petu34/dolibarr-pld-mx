# Schemas - Esquemas XSD del SAT (UIF/SHCP)

## Descripción

Esquemas XSD oficiales publicados por la **UIF (Unidad de Inteligencia Financiera)** de la SHCP para validación de archivos XML del portal SPPLD.

## Archivos Disponibles

```
/schemas/
├── veh.xsd         # Fracción VIII  - Compraventa de vehículos (PRIORIDAD)
├── inmu.xsd        # Fracción XV    - Derechos de uso/goce de inmuebles
├── ssprof2.xsd     # Fracción XI    - Servicios profesionales
└── README.md
```

| Archivo | Actividad vulnerable | Fracción LFPIORPI | Namespace | Estado |
|---|---|---|---|---|
| **veh.xsd** | Compraventa de vehículos | Fracc. VIII | `http://www.uif.shcp.gob.mx/recepcion/veh` | Activo - Fase 1 |
| inmu.xsd | Derechos uso/goce inmuebles | Fracc. XV | `http://www.uif.shcp.gob.mx/recepcion/inm` | Referencia futura |
| ssprof2.xsd | Servicios profesionales | Fracc. XI | `http://www.uif.shcp.gob.mx/recepcion/spr` | Referencia futura |

## Estructura Común (~80% compartido entre los 3 XSD)

Los tres esquemas comparten la misma estructura envolvente (persona_aviso, domicilio, telefono, beneficiario, liquidacion). Solo difiere el bloque `detalle_operaciones`, que es específico de cada actividad vulnerable. Ver análisis completo en `docs/plans/fase1-extrafields-plan.md`.

## Uso

Los esquemas se utilizan en:

1. **Validación automática** (pytest):
   ```python
   xsd_doc = etree.XMLSchema(file='schemas/veh.xsd')
   xml_doc = etree.parse('xml-samples/aviso_veh_test.xml.sample')
   assert xsd_doc.validate(xml_doc)
   ```

2. **Generación de XML** (PHP):
   - Verificar campos obligatorios
   - Validar tipos de datos y regex (usar el más estricto de los 3 XSD)
   - Verificar estructura jerárquica

## Actualización

Los esquemas XSD de la UIF se actualizan periódicamente. Verificar versión vigente antes de cada envío oficial.
