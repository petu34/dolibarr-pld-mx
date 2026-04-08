# XML Samples - Ejemplos de Avisos SAT

## Descripción

Ejemplos de archivos XML para avisos al portal **SPPLD del SAT**.

## Convenciones

- Todos los archivos deben tener sufijo `.sample`
- **NUNCA** incluir datos reales de RFC, CURP o información personal
- Codificación: `UTF-8`
- Formato de fechas: ISO 8601 (`YYYY-MM-DDTHH:MM:SS`)

## Datos de Prueba Autorizados

```
RFC empresa:  TEST010101ABC
CURP cliente: TESE010101MDFSTR00
RFC cliente:  TESR010101ABC
```

## Estructura XML Esperada

```xml
<?xml version="1.0" encoding="UTF-8"?>
<Aviso>
  <SujetoObligado>
    <RFC>TEST010101ABC</RFC>
    <RazonSocial>Empresa de Prueba SA de CV</RazonSocial>
  </SujetoObligado>
  
  <Cliente>
    <Nombre>Juan Pérez García</Nombre>
    <CURP>TESE010101MDFSTR00</CURP>
    <RFC>TESR010101ABC</RFC>
    ...
  </Cliente>
  
  <Operacion>
    <TipoActividad>808</TipoActividad>
    <Monto>250000</Monto>
    <Moneda>MXN</Moneda>
    ...
  </Operacion>
</Aviso>
```

## Validación

Todos los XML deben validarse contra el esquema XSD del SAT antes de envío:

```bash
pytest tests/XML/test_xml_schema.py -v
```
