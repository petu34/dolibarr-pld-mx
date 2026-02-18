# Schemas - Esquemas XSD del SAT

## Descripción

Esquemas XSD oficiales publicados por el **SAT** para validación de archivos XML del portal SPPLD.

## Archivos Esperados

```
/schemas/
├── sppld_sat.xsd              # Esquema principal SPPLD
├── catalogos_sat.xsd          # Catálogos (actividades vulnerables, formas de pago, etc.)
└── README.md
```

## Fuente Oficial

Los esquemas XSD deben descargarse del portal oficial del SAT:

**URL**: https://www.sat.gob.mx/aplicacion/operacion/...

**IMPORTANTE**: Estos archivos NO se incluyen en el repositorio por defecto. Deben descargarse de la fuente oficial del SAT.

## Uso

Los esquemas se utilizan en:

1. **Validación automática** (pytest):
   ```python
   xsd_doc = etree.XMLSchema(file='schemas/sppld_sat.xsd')
   xml_doc = etree.parse('xml-samples/aviso_test.xml.sample')
   assert xsd_doc.validate(xml_doc)
   ```

2. **Generación de XML** (PHP):
   - Verificar campos obligatorios
   - Validar tipos de datos
   - Verificar estructura jerárquica

## Actualización

Los esquemas XSD del SAT se actualizan periódicamente. Verificar versión vigente antes de cada envío oficial.
