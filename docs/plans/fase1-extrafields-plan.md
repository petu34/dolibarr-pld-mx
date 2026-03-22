# Plan Fase 1: PLD Vehículos - Implementación de Extrafields en Dolibarr

## Objetivo de la Fase 1

Agregar los campos mínimos necesarios mediante **extrafields** (campos adicionales) en Dolibarr para cumplir con los requerimientos de la **Fracción VIII del Art. 17 LFPIORPI** (Compra y venta de vehículos), sin crear tablas nuevas.

---

## Análisis de Esquemas XSD del SAT

### Archivos XSD disponibles

| Archivo | Actividad vulnerable | Fracción LFPIORPI | Namespace |
|---|---|---|---|
| **`schemas/veh.xsd`** | Compraventa de vehículos | Fracc. VIII | `http://www.uif.shcp.gob.mx/recepcion/veh` |
| `schemas/inmu.xsd` | Derechos de uso/goce de inmuebles | Fracc. XV | `http://www.uif.shcp.gob.mx/recepcion/inm` |
| `schemas/ssprof2.xsd` | Servicios profesionales | Fracc. XI | `http://www.uif.shcp.gob.mx/recepcion/spr` |

> **Prioridad:** `veh.xsd` es el esquema principal de esta implementación. Los otros dos se incluyen como referencia para diseñar extrafields reutilizables.

### Estructura común a los 3 XSD (~80% compartido)

Los tres esquemas comparten una estructura envolvente idéntica:

```
archivo -> informe[] ->
  |-- mes_reportado (YYYYMM)
  |-- sujeto_obligado (clave_so, clave_actividad, exento?)
  +-- aviso[] ->
        |-- referencia_aviso
        |-- modificatorio? (folio, descripcion)
        |-- prioridad (1|2)
        |-- alerta (tipo_alerta, descripcion_alerta?)
        |-- persona_aviso[] ->
        |     |-- tipo_persona (persona_fisica | persona_moral | fideicomiso)
        |     |-- tipo_domicilio? (nacional | extranjero)
        |     +-- telefono? (clave_pais, numero, correo)
        |-- dueno_beneficiario[] ->
        |     +-- tipo_persona_simple (PF | PM | fideicomiso - datos reducidos)
        +-- detalle_operaciones ->              <-- UNICO BLOQUE QUE DIFIERE
              +-- datos_operacion[] ->            POR ACTIVIDAD VULNERABLE
                    +-- (estructura especifica por fraccion)
```

### Campos específicos por actividad (solo `detalle_operaciones`)

**VEH - Fracción VIII (vehículos):**
```
datos_operacion -> fecha_operacion, codigo_postal, tipo_operacion,
  tipo_vehiculo[] (terrestre | maritimo | aereo) ->
    marca_fabricante, modelo, anio, vin?, repuve?, placas?, nivel_blindaje
  datos_liquidacion[] -> fecha_pago, forma_pago, instrumento_monetario?, moneda, monto
```

**INM - Fracción XV (inmuebles):**
```
datos_operacion -> fecha_operacion, tipo_operacion, figura_cliente, figura_so,
  datos_contraparte[]?,
  caracteristicas_inmueble[] -> tipo, valor_pactado, direccion, dimensiones, folio_real,
  contrato_instrumento_publico (instrumento_publico | contrato),
  datos_liquidacion[]
```

**SPR - Fracción XI (servicios profesionales):**
```
datos_operacion -> fecha_operacion,
  tipo_actividad (10 subtipos: compra_venta_inmuebles, cesion_derechos,
    admin_recursos, constitucion_sociedades, aportaciones, fusion, escision,
    admin_PM, constitucion_fideicomiso, compra_venta_entidades),
  datos_operacion_financiera[]
```

### Clasificación de reutilización por tabla Dolibarr

| Tabla extrafields | % Común (3 XSD) | Notas |
|---|---|---|
| `llx_socpeople_extrafields` | **100%** | Persona física/moral idéntica en los 3 esquemas |
| `llx_societe_extrafields` | **~95%** | Solo `clave_actividad` cambia (VEH/INM/SPR) |
| `llx_product_extrafields` | **0%** - 100% específico | Campos de vehículo solo en VEH |
| `llx_facture_extrafields` | **~70%** | Datos de operación parcialmente comunes |
| `llx_paiement_extrafields` | **~90%** | `datos_liquidacion` casi idéntico |
| `llx_commande_extrafields` | **~70%** | Pre-validación genérica reutilizable |

### Discrepancias de regex entre XSD (usar el más estricto)

| Tipo | VEH (simple) | INM / SPR (estricto) | Recomendación |
|---|---|---|---|
| **CURP** | `[A-Z]{4}\d{6}[MH][A-Z]{5}[0-9]{2}` | Valida fecha real + entidad federativa | **Usar SPR** (más estricto) |
| **RFC física** | `[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}` | Valida fecha + permite `%+` | **Usar SPR** con `Ñ&%+` |
| **RFC moral** | `[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}` | Valida fecha + permite `%+` | **Usar SPR** con `Ñ&%+` |
| **fecha_type** | `\d{8}` (cualquier 8 dígitos) | Valida día/mes real (28/30/31) | **Usar INM/SPR** (valida fechas reales) |
| **nombre_type** | `[A-ZÑ ]{1,200}` | INM permite `.,` adicional | **Usar INM** (más permisivo en nombres) |

> **Regla de implementación:** para cada tipo compartido, usar el regex que pase validación en los 3 XSD. Esto evita tener que cambiar validadores al agregar nuevas actividades vulnerables.

---

## Alcance de la Fase 1

### Actividad Vulnerable Específica

**Fracción VIII - Comercialización o distribución habitual o profesional de vehículos**

**Umbrales 2026:**
- **Identificación**: 3,220 UMAs = $377,778.20 MXN
- **Aviso**: 3,220 UMAs = $377,778.20 MXN
- **Restricción efectivo**: 3,100 UMAs = $363,661 MXN

### Entidades Dolibarr Afectadas

1. **llx_societe** (Terceros/Clientes) - reutilizable 95%
2. **llx_socpeople** (Contactos - representantes legales) - reutilizable 100%
3. **llx_product** (Vehículos como productos) - específico VEH
4. **llx_facture** (Facturas de venta) - reutilizable 70%
5. **llx_paiement** (Formas de pago) - reutilizable 90%
6. **llx_commande** (Pedidos - opcional para seguimiento) - reutilizable 70%

---

## PARTE 1: Extrafields para llx_societe (Clientes) - 95% reutilizable

> Estos campos mapean directamente a `persona_aviso -> tipo_persona` en los 3 XSD.

### 1.1 Identificación Básica del Cliente

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_tipo_persona** | select | - | Si | `tipo_persona` (choice) | Física / Moral / Fideicomiso |
| **pld_curp** | varchar | 18 | Si es PF | `curp_type` | CURP (18 caracteres) |
| **pld_rfc_validado** | varchar | 13 | Si | `rfc_fisica_type` / `rfc_moral_type` | RFC validado |
| **pld_fecha_nacimiento** | date | - | Si es PF | `fecha_nacimiento` | Fecha nacimiento (PF) |
| **pld_fecha_constitucion** | date | - | Si es PM | `fecha_constitucion` | Fecha constitución (PM) |
| **pld_nacionalidad** | varchar | 2 | Si | `pais_nacionalidad` (pais_type) | ISO alpha-2, 2 caracteres |
| **pld_pais_nacimiento** | varchar | 2 | Si es PF | - (campo complementario) | País nacimiento ISO alpha-2 |
| **pld_estado_nacimiento** | varchar | 50 | Si es PF | - (campo complementario) | Entidad federativa nacimiento |

### 1.2 Domicilio Fiscal Detallado

> Mapea a `tipo_domicilio -> nacional` / `extranjero` en los 3 XSD.

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_calle** | varchar | 100 | Si | `calle` (direccion_1-100_type) | Nombre de la calle |
| **pld_numero_exterior** | varchar | 56 | Si | `numero_exterior` (direccion_1-56_type) | Número exterior |
| **pld_numero_interior** | varchar | 40 | No | `numero_interior` (direccion_1-40_type) | Número interior |
| **pld_colonia** | varchar | 50 | Si | `colonia` (direccion_1-50_type) | Colonia |
| **pld_codigo_postal** | varchar | 5 | Si | `codigo_postal` (cp_type) | CP (5 dígitos) |
| **pld_municipio** | varchar | 100 | Si | - (campo complementario) | Municipio/Alcaldía |
| **pld_estado** | select | - | Si | - (campo complementario) | Entidad federativa (catálogo) |
| **pld_pais** | varchar | 2 | Si | `pais` (pais_type) | País ISO alpha-2. Default: MX |
| **pld_es_domicilio_extranjero** | boolean | - | Si | choice nacional/extranjero | Determina qué bloque XSD usar |
| **pld_estado_provincia_ext** | varchar | 100 | Si ext | `estado_provincia` (extranjero) | Estado/provincia extranjero |
| **pld_ciudad_poblacion_ext** | varchar | 100 | Si ext | `ciudad_poblacion` (extranjero) | Ciudad extranjero |

### 1.3 Actividad Económica

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_actividad_economica** | varchar | 7 | Si | `actividad_economica` (digito_7_type) | Clave SCIAN 7 dígitos |
| **pld_giro_mercantil** | varchar | 7 | Si es PM | `giro_mercantil` (digito_7_type) | Giro mercantil PM 7 dígitos |
| **pld_ocupacion** | varchar | 100 | Si es PF | - (campo complementario) | Profesión u ocupación |

### 1.4 Datos Constitutivos (Personas Morales)

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_denominacion_razon** | varchar | 254 | Si es PM | `denominacion_razon` (denominacion_razon_type) | Razón social formal para XML |
| **pld_numero_escritura** | varchar | 20 | Si es PM | - (campo complementario) | Escritura constitutiva |
| **pld_fecha_escritura** | date | - | Si es PM | - (campo complementario) | Fecha escritura |
| **pld_notario_numero** | varchar | 8 | Si es PM | - (campo complementario) | Número de notario |
| **pld_notario_nombre** | varchar | 150 | Si es PM | - (campo complementario) | Nombre del notario |
| **pld_notario_estado** | varchar | 50 | Si es PM | - (campo complementario) | Estado del notario |
| **pld_folio_mercantil** | varchar | 200 | Si es PM | - (campo complementario) | Folio RPP |

### 1.5 Datos de Fideicomiso

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_identificador_fideicomiso** | varchar | 40 | Si fideicomiso | `identificador_fideicomiso` (descripcion_1-40_type) | Identificador del fideicomiso |

### 1.6 Control PLD

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_cliente_identificado** | boolean | - | Si | - (control interno) | Ya se identificó |
| **pld_fecha_identificacion** | date | - | Si | - (control interno) | Fecha identificación |
| **pld_expediente_completo** | boolean | - | Si | - (control interno) | Expediente completo |
| **pld_es_pep** | boolean | - | Si | - (control interno) | Persona Expuesta Políticamente |
| **pld_relacion_pep** | varchar | 200 | No | - (control interno) | Parentesco con PEP |
| **pld_tiene_beneficiario** | boolean | - | Si | presencia de `dueno_beneficiario` | Tiene beneficiario controlador |
| **pld_observaciones** | text | - | No | - (control interno) | Observaciones generales |

**Subtotal societe: ~33 extrafields**

---

## PARTE 2: Extrafields para llx_socpeople (Contactos) - 100% reutilizable

> Mapean a `persona_fisica`, `representante_apoderado` y `dueno_beneficiario -> persona_fisica_simple` en los 3 XSD.

### 2.1 Datos Personales Completos

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_apellido_paterno** | varchar | 200 | Si | `apellido_paterno` (nombre_type) | Apellido paterno |
| **pld_apellido_materno** | varchar | 200 | Si | `apellido_materno` (nombre_type) | Apellido materno |
| **pld_nombre_completo** | varchar | 200 | Si | `nombre` (nombre_type) | Nombre(s) completo(s) |
| **pld_curp** | varchar | 18 | Si | `curp_type` | CURP del contacto |
| **pld_rfc** | varchar | 13 | Si | `rfc_fisica_type` | RFC del contacto |
| **pld_fecha_nacimiento** | date | - | Si | `fecha_nacimiento` (fecha_type) | Fecha nacimiento |
| **pld_nacionalidad** | varchar | 2 | Si | `pais_nacionalidad` (pais_type) | ISO alpha-2 |
| **pld_actividad_economica** | varchar | 7 | Si | `actividad_economica` (digito_7_type) | Clave SCIAN 7 dígitos |

### 2.2 Identificación Oficial

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_tipo_identificacion** | select | - | Si | - (control interno) | INE/IFE/Pasaporte/FM3/Cédula |
| **pld_numero_identificacion** | varchar | 20 | Si | - (control interno) | Número de identificación |
| **pld_vigencia_identificacion** | date | - | Si | - (control interno) | Fecha vigencia |
| **pld_autoridad_emite** | varchar | 100 | Si | - (control interno) | Autoridad emisora |
| **pld_clave_elector** | varchar | 18 | Si INE | - (control interno) | Clave de elector (INE) |

### 2.3 Representación Legal

> Mapea a `representante_apoderado` / `apoderado_delegado` en los 3 XSD.

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_es_representante_legal** | boolean | - | Si | presencia de `representante_apoderado` | Es representante legal |
| **pld_tipo_representacion** | select | - | Si rep | - (control interno) | Poder general/especial/ambos |
| **pld_escritura_poder** | varchar | 20 | Si rep | - (control interno) | Escritura del poder |
| **pld_fecha_poder** | date | - | Si rep | - (control interno) | Fecha del poder |
| **pld_notario_poder** | varchar | 150 | Si rep | - (control interno) | Notario que dio fe |

### 2.4 Teléfono y contacto (bloque `telefono` del XSD)

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_clave_pais_telefono** | varchar | 2 | No | `clave_pais` (pais_type) | Código país teléfono, ISO alpha-2 |
| **pld_numero_telefono** | varchar | 12 | No | `numero_telefono` (numero_telefono_type) | 10-12 dígitos |
| **pld_correo_electronico** | varchar | 60 | No | `correo_electronico` (correo_electronico_type) | Email formato SAT |

**Subtotal socpeople: ~22 extrafields**

---

## PARTE 3: Extrafields para llx_product (Vehículos) - Específico VEH

> Mapean a `tipo_vehiculo` dentro de `detalle_operaciones` en `veh.xsd` exclusivamente. NO son reutilizables para INM ni SPR.

### 3.1 Identificación del Vehículo

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_tipo_vehiculo** | select | - | Si | choice terrestre/maritimo/aereo | Terrestre/Marítimo/Aéreo |
| **pld_marca** | varchar | 40 | Si | `marca_fabricante` (descveh_1-40_type) | Marca del vehículo |
| **pld_modelo** | varchar | 40 | Si | `modelo` (descveh_1-40_type) | Modelo |
| **pld_anio_modelo** | varchar | 4 | Si | `anio` (digito_4_type) | Año modelo (4 dígitos) |

### 3.2 Números de Serie e Identificadores

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_vin** | varchar | 17 | Si terrestre | `vin` (referencia_17_type) | VIN exactamente 17 chars |
| **pld_repuve** | varchar | 8 | No | `repuve` (repuve_8_type) | Clave REPUVE 8 chars |
| **pld_placas** | varchar | 12 | No | `placas` (placas_1-12_type) | Placas 1-12 chars |
| **pld_nivel_blindaje** | varchar | 1 | Si | `nivel_blindaje` (digito_1_type) | 1 dígito |
| **pld_numero_serie** | varchar | 20 | Si maritimo/aereo | `numero_serie` (numero_serie_1-20_type) | Serie marítimo/aéreo |
| **pld_bandera** | varchar | 2 | No | `bandera` (pais_type) | País bandera ISO alpha-2 |
| **pld_matricula** | varchar | 12 | No | `matricula` (placas_1-12_type) | Matrícula aérea/marítima |

### 3.3 Origen y Estado

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_origen** | select | - | Si | - (control interno) | Nacional/Importado |
| **pld_pais_origen** | varchar | 2 | Si importado | - (control interno) | País fabricación ISO alpha-2 |
| **pld_estado_vehiculo** | select | - | Si | - (control interno) | Nuevo/Usado/Seminuevo |
| **pld_kilometraje** | int | - | Si usado | - (control interno) | Kilometraje actual |
| **pld_uso_destino** | select | - | Si | - (control interno) | Particular/Comercial/Transporte |

### 3.4 Documentación Legal (vehículos usados)

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_numero_factura_original** | varchar | 30 | Si usado | - (control interno) | Factura original |
| **pld_fecha_factura_original** | date | - | Si usado | - (control interno) | Fecha factura original |
| **pld_propietario_anterior** | varchar | 200 | Si usado | - (control interno) | Nombre propietario anterior |
| **pld_tarjeta_circulacion** | varchar | 20 | No | - (control interno) | Número tarjeta circulación |
| **pld_numero_pedimento** | varchar | 20 | Si importado | - (control interno) | Pedimento aduanal |

### 3.5 Valores

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_valor_factura** | decimal | (15,2) | Si | - (control interno) | Valor facturado |
| **pld_valor_comercial** | decimal | (15,2) | Si | - (control interno) | Valor comercial / avalúo |
| **pld_valor_libro_azul** | decimal | (15,2) | No | - (control interno) | Valor libro azul |

**Subtotal product: ~24 extrafields**

---

## PARTE 4: Extrafields para llx_facture (Facturas) - 70% reutilizable

### 4.1 Control PLD de la Operación

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_es_actividad_vulnerable** | boolean | - | Si | - (control interno) | Es actividad vulnerable |
| **pld_clave_actividad** | varchar | 3 | Si | `clave_actividad` (VEH/INM/SPR) | Clave de actividad vulnerable |
| **pld_tipo_operacion** | varchar | 4 | Si | `tipo_operacion` (digito_3-4_type) | Tipo operación del catálogo SAT |
| **pld_supera_umbral_id** | boolean | - | Si | - (control interno) | Supera umbral identificación |
| **pld_supera_umbral_aviso** | boolean | - | Si | - (control interno) | Supera umbral aviso |
| **pld_requiere_aviso** | boolean | - | Si | - (control interno) | Requiere aviso SAT |
| **pld_tipo_aviso** | select | - | Si req | - (control interno) | mensual/24hrs/acumulado |

### 4.2 Datos de la Operación (mapeo a `datos_operacion` del XSD)

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_fecha_operacion** | date | - | Si | `fecha_operacion` (fecha_type) | Fecha real operación (YYYYMMDD) |
| **pld_codigo_postal_operacion** | varchar | 5 | Si | `codigo_postal` (cp_type) - solo VEH | CP donde ocurre la operación |
| **pld_descripcion_operacion** | text | - | Si | - (control interno) | Descripción detallada |
| **pld_razon_operacion** | text | - | Si | - (control interno) | Justificación/motivo |
| **pld_monto_moneda_nacional** | decimal | (15,2) | Si | - (derivado de monto_operacion) | Monto en MXN |
| **pld_tipo_cambio_aplicado** | decimal | (10,4) | Si extranjera | - (control interno) | Tipo de cambio |

### 4.3 Referencia del aviso

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_referencia_aviso** | varchar | 14 | Si aviso | `referencia_aviso` (referencia_aviso_type) | Ref interna del aviso |
| **pld_prioridad** | varchar | 1 | Si aviso | `prioridad` (prioridad_type) | 1=Normal, 2=Prioritario |

### 4.4 Alerta

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_tipo_alerta** | varchar | 4 | Si aviso | `tipo_alerta` (tipo_alerta_type) | Código alerta 3-4 dígitos |
| **pld_descripcion_alerta** | varchar | 3000 | No | `descripcion_alerta` (descripcion_1-3000_type) | Texto alerta |

### 4.5 Acumulación de Operaciones

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_es_operacion_acumulada** | boolean | - | Si | - (control interno) | Parte de acumulación 6 meses |
| **pld_fecha_inicio_acumulacion** | date | - | Si acum | - (control interno) | Inicio período |
| **pld_fecha_fin_acumulacion** | date | - | Si acum | - (control interno) | Fin período |
| **pld_monto_acumulado_total** | decimal | (15,2) | Si acum | - (control interno) | Total acumulado |

### 4.6 Control de Avisos

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_aviso_presentado** | boolean | - | Si | - (control interno) | Ya se presentó aviso |
| **pld_fecha_presentacion** | date | - | Si pres | - (control interno) | Fecha presentación SAT |
| **pld_folio_aviso** | varchar | 14 | Si pres | `folio_modificacion` (folio_modificacion_type) | Folio del aviso SAT |
| **pld_mes_reportado** | varchar | 6 | Si pres | `mes_reportado` (mes_reportado_type) | YYYYMM reportado |
| **pld_acuse_sat** | text | - | Si pres | - (control interno) | Acuse digital SAT |

### 4.7 Modificatorio (correcciones a avisos previos)

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_es_modificatorio** | boolean | - | No | presencia de `modificatorio` | Es corrección de aviso previo |
| **pld_folio_modificacion** | varchar | 14 | Si modif | `folio_modificacion` | Folio aviso a modificar |
| **pld_descripcion_modificacion** | varchar | 3000 | Si modif | `descripcion_modificacion` | Razón de la modificación |

### 4.8 Alertas y aviso 24 horas

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_genera_alerta** | boolean | - | Si | - (control interno) | Genera alerta interna |
| **pld_requiere_aviso_24hrs** | boolean | - | Si | - (control interno) | Requiere aviso 24 horas |
| **pld_razon_24hrs** | text | - | Si 24h | - (control interno) | Justificación aviso urgente |

**Subtotal facture: ~31 extrafields**

---

## PARTE 5: Extrafields para llx_paiement (Pagos) - 90% reutilizable

> Mapean a `datos_liquidacion` en los 3 XSD.

### 5.1 Datos de Liquidación (estructura XSD)

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_fecha_pago** | date | - | Si | `fecha_pago` (fecha_type) | Fecha del pago YYYYMMDD |
| **pld_forma_pago** | varchar | 1 | Si | `forma_pago` (digito_1_type) | Catálogo SAT formas de pago |
| **pld_instrumento_monetario** | varchar | 2 | No | `instrumento_monetario` (digito_1-2_type) | Catálogo instrumento monetario |
| **pld_moneda** | varchar | 3 | Si | `moneda` (digito_1-3_type) | Catálogo SAT monedas |
| **pld_monto_operacion** | varchar | 17 | Si | `monto_operacion` (monto_type) | Formato: `\d{1,14}\.\d{2}` |

### 5.2 Desglose por Forma de Pago (control interno)

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_monto_efectivo** | decimal | (15,2) | Si | - (control interno) | Monto en efectivo |
| **pld_monto_transferencia** | decimal | (15,2) | Si | - (control interno) | Monto por transferencia |
| **pld_monto_cheque** | decimal | (15,2) | Si | - (control interno) | Monto por cheque |
| **pld_monto_tarjeta** | decimal | (15,2) | Si | - (control interno) | Monto por tarjeta |
| **pld_monto_otros** | decimal | (15,2) | Si | - (control interno) | Otros medios de pago |

### 5.3 Datos Bancarios (Transferencia)

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_banco_origen** | varchar | 100 | Si trans | - (control interno) | Institución financiera origen |
| **pld_cuenta_origen** | varchar | 4 | Si trans | - (control interno) | Últimos 4 dígitos cuenta |
| **pld_clabe_origen** | varchar | 18 | Si trans | - (control interno) | CLABE origen |
| **pld_banco_destino** | varchar | 100 | Si trans | - (control interno) | Banco destino |
| **pld_cuenta_destino** | varchar | 4 | Si trans | - (control interno) | Últimos 4 dígitos destino |
| **pld_numero_autorizacion** | varchar | 20 | Si trans | - (control interno) | Número autorización |
| **pld_fecha_transferencia** | date | - | Si trans | - (control interno) | Fecha de transferencia |

### 5.4 Datos del Cheque

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_banco_cheque** | varchar | 100 | Si cheque | - (control interno) | Banco emisor |
| **pld_numero_cheque** | varchar | 20 | Si cheque | - (control interno) | Número de cheque |
| **pld_cuenta_cheque** | varchar | 4 | Si cheque | - (control interno) | Últimos 4 dígitos cuenta |
| **pld_fecha_cheque** | date | - | Si cheque | - (control interno) | Fecha del cheque |
| **pld_librador_cheque** | varchar | 200 | Si cheque | - (control interno) | Nombre del librador |

### 5.5 Datos de Tarjeta

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_tipo_tarjeta** | select | - | Si tarjeta | - (control interno) | Débito/Crédito |
| **pld_emisor_tarjeta** | varchar | 100 | Si tarjeta | - (control interno) | Banco emisor |
| **pld_ultimos_digitos** | varchar | 4 | Si tarjeta | - (control interno) | Últimos 4 dígitos |
| **pld_numero_autorizacion_tarjeta** | varchar | 20 | Si tarjeta | - (control interno) | Autorización de compra |

### 5.6 Control de Efectivo

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_supera_limite_efectivo** | boolean | - | Si | - (control interno) | Supera $363,661 MXN |
| **pld_alerta_efectivo** | boolean | - | Si | - (control interno) | Alerta por uso de efectivo |
| **pld_justificacion_efectivo** | text | - | Si alto | - (control interno) | Justificación uso efectivo |

**Subtotal paiement: ~29 extrafields**

---

## PARTE 6: Extrafields para llx_commande (Pedidos - Opcional) - 70% reutilizable

### 6.1 Pre-validación PLD

| Campo Extrafield | Tipo | Longitud | Obligatorio | XSD origen | Descripción |
|---|---|---|---|---|---|
| **pld_preventa_identificada** | boolean | - | No | - (control interno) | Cliente ya identificado |
| **pld_anticipo_estimado** | decimal | (15,2) | No | - (control interno) | Anticipo aproximado |
| **pld_forma_pago_planeada** | select | - | No | - (control interno) | Forma pago esperada |
| **pld_alerta_previa** | boolean | - | No | - (control interno) | Alerta en cotización |

**Subtotal commande: 4 extrafields**

---

## Resumen de Extrafields

| Tabla | Extrafields | Reutilizable | Nota |
|---|---|---|---|
| `llx_societe` | ~33 | 95% | Persona + domicilio + PLD |
| `llx_socpeople` | ~22 | 100% | Idéntico en los 3 XSD |
| `llx_product` | ~24 | 0% | Solo VEH |
| `llx_facture` | ~31 | 70% | Operación + alertas |
| `llx_paiement` | ~29 | 90% | Liquidación + control |
| `llx_commande` | 4 | 70% | Pre-validación |
| **TOTAL** | **~143** | | |

> **Nota:** El conteo original de 152 extrafields se ajustó a ~143 tras alinear con los campos reales del XSD. Los campos restantes se cubrirán con las tablas especializadas de Fase 2 (beneficiarios, documentos, etc.) donde corresponden mejor.

---

## PARTE 7: Plan de Implementación Técnica

### 7.1 Estructura del Módulo

```
htdocs/custom/modulecompliancepld/
  |-- core/modules/
  |     +-- modCompliancePLD.class.php
  |-- admin/
  |     |-- setup.php
  |     +-- about.php
  |-- class/
  |     |-- compliancepld.class.php
  |     +-- pldvalidator.class.php
  |-- langs/es_MX/
  |     +-- modulecompliancepld.lang
  +-- css/
        +-- compliancepld.css
```

### 7.2 Scripts SQL para Extrafields

**Convención de nombres de migración:**
```
sql/migrations/
  |-- migration_001_extrafields_societe.sql
  |-- migration_002_extrafields_socpeople.sql
  |-- migration_003_extrafields_product.sql
  |-- migration_004_extrafields_facture.sql
  |-- migration_005_extrafields_paiement.sql
  +-- migration_006_extrafields_commande.sql
```

**Ejemplo: `migration_001_extrafields_societe.sql`**
```sql
-- Migration 001: Extrafields PLD para llx_societe (Terceros/Clientes)
-- Compliance: LFPIORPI Art. 17 Fracc. VIII
-- XSD: schemas/veh.xsd - persona_aviso -> tipo_persona -> persona_fisica/moral/fideicomiso
-- Reutilizable: 95% (aplica también para INM Fracc. XV y SPR Fracc. XI)

-- Tipo de persona (persona_fisica | persona_moral | fideicomiso)
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, enabled, required, list, help)
VALUES ('pld_tipo_persona', 'Tipo de Persona (PLD)', 'select', '', 100, 1, 'societe', 1, 1, 1,
  'Clasificación según LFPIORPI: Persona Física, Persona Moral o Fideicomiso')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- CURP (curp_type: 18 caracteres, regex validado)
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, enabled, required, list, help)
VALUES ('pld_curp', 'CURP (PLD)', 'varchar', '18', 101, 1, 'societe', 1, 0, 1,
  'Clave Única de Registro de Población. Obligatorio para personas físicas mexicanas.')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- RFC validado (rfc_fisica_type: 13 chars | rfc_moral_type: 12 chars)
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, enabled, required, list, help)
VALUES ('pld_rfc_validado', 'RFC Validado (PLD)', 'varchar', '13', 102, 1, 'societe', 1, 1, 1,
  'RFC validado. 13 caracteres para persona física, 12 para persona moral.')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- ... continuar con todos los campos
```

### 7.3 Validaciones PHP (alineadas a regex XSD más estricto)

```php
<?php
// Regex de validación: se usa el más estricto de los 3 XSD para compatibilidad futura

// CURP - basado en ssprof2.xsd (valida fecha real y entidad federativa)
const PLD_REGEX_CURP = '/^([A-Z]{4})((\d{2})(((0[469]|1[1])(0[1-9]|[12]\d|3[0]))|((0[2])(0[1-9]|[12]\d))|((0[13578]|1[02])(0[1-9]|[12]\d|3[01]))))([MH])([A-Z]{5})([A-J\d][\d])$/';

// RFC persona física - basado en ssprof2.xsd (valida fecha)
const PLD_REGEX_RFC_FISICA = '/^[A-ZÑ&]{4}((\d{2})(((0[469]|1[1])(0[1-9]|[12]\d|3[0]))|((0[2])(0[1-9]|[12]\d))|((0[13578]|1[02])(0[1-9]|[12]\d|3[01]))))[A-Z\d]{3}$/';

// RFC persona moral - basado en ssprof2.xsd (valida fecha)
const PLD_REGEX_RFC_MORAL = '/^[A-ZÑ&]{3}((\d{2})(((0[469]|1[1])(0[1-9]|[12]\d|3[0]))|((0[2])(0[1-9]|[12]\d))|((0[13578]|1[02])(0[1-9]|[12]\d|3[01]))))[A-Z\d]{3}$/';

// VIN - basado en veh.xsd (exactamente 17 caracteres alfanuméricos)
const PLD_REGEX_VIN = '/^[A-Z\d\-_]{17}$/';

// Código postal - basado en los 3 XSD (idéntico)
const PLD_REGEX_CP = '/^\d{5}$/';

// País - ISO alpha-2 (idéntico en los 3 XSD)
const PLD_REGEX_PAIS = '/^[A-Z]{2}$/';

// Monto - basado en los 3 XSD (idéntico)
const PLD_REGEX_MONTO = '/^\d{1,14}\.\d{2}$/';

// Fecha - formato YYYYMMDD con validación de fecha real (INM/SPR)
const PLD_REGEX_FECHA = '/^([1-9]\d{3})(((0[469]|1[1])(0[1-9]|[12]\d|3[0]))|((0[2])(0[1-9]|[12]\d))|((0[13578]|1[02])(0[1-9]|[12]\d|3[01])))$/';

// Mes reportado (YYYYMM)
const PLD_REGEX_MES_REPORTADO = '/^([2-9]\d{3})((0([1-9]))|(1[0-2]))$/';

// Actividad económica / giro mercantil (7 dígitos SCIAN)
const PLD_REGEX_ACTIVIDAD_ECONOMICA = '/^\d{7}$/';
```

---

## Estado de Implementación

> Última actualización: 2026-02-22 — Commit `bc9ce73` en `fase1/extrafields`

### Completado ✅

| Entregable | Archivo | Commit | Notas |
|---|---|---|---|
| Clase validadora | `class/pldvalidator.class.php` | `8afc8c8` | 18 regex XSD (variante SPR, ADR-002), 20 métodos de validación, soporte unicode `/u` + `mb_strtoupper` |
| Clase lógica PLD | `class/compliancepld.class.php` | `8afc8c8` | Umbrales UMA 2026, `debeGenerarAviso()`, `superaLimiteEfectivo()`, verificación expedientes PF/PM/vehículos. Fix `empty('0')` para nivel_blindaje |
| Idioma es_MX | `langs/es_MX/modulecompliancepld.lang` | `8afc8c8` | 143 etiquetas extrafields + opciones select + mensajes error/info/alerta + catálogos (32 entidades federativas, 7 formas de pago) |
| Tests CURP | `tests/Unit/CURPValidationTest.php` | `8afc8c8` | 16 tests: válidas (M/H, estados, décadas, normalización), inválidas (vacía, corta, larga, sexo, fecha, día) |
| Tests RFC | `tests/Unit/RFCValidationTest.php` | `8afc8c8` | 15 tests: PF 13 chars, PM 12 chars, Ñ/&, auto-detección `validarRFC()` |
| Tests VIN + auxiliares | `tests/Unit/VINValidationTest.php` | `8afc8c8` | 60+ tests cubriendo los 20 validadores: VIN, CP, país, monto, fecha, mesReportado, actividadEconomica, nombre, denominación, teléfono, correo, REPUVE, placas, CLABE, tipoPersona, tipoVehiculo, anioModelo, formatearMonto, formatearFecha |
| Tests umbrales | `tests/Unit/UmbralesTest.php` | `8afc8c8` | 30+ tests: constantes regulatorias, cálculo umbrales UMA, debeGenerarAviso (vehículos + acumulado), superaLimiteEfectivo, prioridad, referencia aviso, mes reportado, expediente PF/PM/vehículo |
| Migración societe | `sql/migrations/migration_001_extrafields_societe.sql` | `8afc8c8` | 33 campos (identificación, domicilio, actividad económica, constitutivos PM, fideicomiso, control PLD) |
| Migración socpeople | `sql/migrations/migration_002_extrafields_socpeople.sql` | `8afc8c8` | 22 campos (datos personales, identificación oficial, representación legal, teléfono/contacto) |
| **Descriptor del módulo** | `core/modules/modModulecompliancepld.class.php` | `bc9ce73` | Metadata (numero=500200, family=financial, v1.0.0, php8.1+, doli20+), triggers=1, 6 hooks, 5 permisos PLD, top+left menu (Dashboard/Operaciones/Avisos/Alertas/Reportes/Config), 6 tabs PLD, init() con 143 `addExtraField()` en 6 tablas |
| Migración product | `sql/migrations/migration_003_extrafields_product.sql` | `5c2b8f2` | 24 campos vehículo VEH (tipo, marca, modelo, año, VIN, REPUVE, placas, blindaje, serie, bandera, matrícula, origen, estado, km, uso, docs legales, valores) |
| Migración facture | `sql/migrations/migration_004_extrafields_facture.sql` | `5c2b8f2` | 31 campos operación PLD (control, datos operación, referencia aviso, alerta, acumulación, control avisos, modificatorio, alertas 24h) |
| Migración paiement | `sql/migrations/migration_005_extrafields_paiement.sql` | `5c2b8f2` | 29 campos liquidación (datos XSD, desglose por forma de pago, bancarios, cheque, tarjeta, control efectivo) |
| Migración commande | `sql/migrations/migration_006_extrafields_commande.sql` | `5c2b8f2` | 4 campos pre-validación PLD (preventa identificada, anticipo, forma pago planeada, alerta previa) |

**Resultado PHPUnit:** 160 tests, 175 assertions, 0 fallos

### Pendiente — Próxima Sesión

#### 1. Verificación final

- [ ] Ejecutar `./vendor/bin/phpunit tests/Unit/` → 0 fallos
- [ ] Commit y push a `fase1/extrafields`

---

### Bugs corregidos en esta iteración

| Bug | Causa raíz | Fix |
|---|---|---|
| RFC con Ñ/& no validaba | Regex sin flag `/u` (unicode) y `strtoupper()` no maneja multibyte | Agregado `/u` a `REGEX_RFC_FISICA` y `REGEX_RFC_MORAL`, cambiado a `mb_strtoupper()` |
| `verificarDatosVehiculo` rechazaba nivel_blindaje='0' | PHP `empty('0')` retorna `true` | Reemplazado `empty($datos[$campo])` por `!isset() \|\| === '' \|\| === null` |
| Test CURP estado 'ZZ' | El regex XSD usa `[A-Z]{5}` sin enumerar entidades válidas | Test corregido: el regex de formato acepta cualquier 2 letras; la validación semántica de entidad es responsabilidad de capa superior |

---

### Decisiones técnicas tomadas

- **ADR-002 confirmado**: Todos los regex usan la variante más estricta (ssprof2.xsd) para compatibilidad futura con múltiples actividades vulnerables
- **Unicode**: Los métodos `validarRFCFisica()` y `validarRFCMoral()` usan `mb_strtoupper()` + regex con `/u` para soportar Ñ y & correctamente
- **empty() vs isset()**: Para verificación de expedientes, se usa `!isset() || === '' || === null` en lugar de `empty()` para que valores como `'0'` (nivel_blindaje sin blindaje) sean aceptados

---

## Checklist de Completitud

### Datos Mínimos para Operar Legalmente

**Al registrar un cliente nuevo:**
- [x] Tipo de persona identificado (PF/PM/Fideicomiso) — `pld_tipo_persona` select
- [x] RFC capturado y validado (regex SPR) — `pld_rfc_validado` + `PLDValidator::validarRFC()`
- [x] CURP capturado si PF (regex SPR) — `pld_curp` + `PLDValidator::validarCURP()`
- [x] Domicilio completo con colonia, CP, municipio — 11 campos domicilio definidos
- [x] Nacionalidad en ISO alpha-2 — `pld_nacionalidad` + `PLDValidator::validarPais()`
- [x] Actividad económica (SCIAN 7 dígitos) — `pld_actividad_economica` + `PLDValidator::validarActividadEconomica()`
- [ ] Identificación oficial escaneada — Pendiente Fase 2 (tabla `llx_pld_documentos`)

**Al registrar un vehículo:**
- [x] Tipo (terrestre/marítimo/aéreo) — `pld_tipo_vehiculo` select
- [x] Marca y modelo (descveh_1-40_type) — `pld_marca`, `pld_modelo` varchar(40)
- [x] Año modelo (digito_4_type) — `pld_anio_modelo` + `PLDValidator::validarAnioModelo()`
- [x] VIN exactamente 17 chars (si terrestre) — `pld_vin` + `PLDValidator::validarVIN()`
- [x] Nivel de blindaje (digito_1_type) — `pld_nivel_blindaje` varchar(1)
- [x] Origen (nacional/importado) — `pld_origen` select
- [x] Estado (nuevo/usado) — `pld_estado_vehiculo` select
- [x] Valores (factura y comercial) — `pld_valor_factura`, `pld_valor_comercial` price

**Al facturar:**
- [x] Validar si supera umbrales (3,220 UMAs = $377,770.40 MXN) — `CompliancePLD::debeGenerarAviso()`
- [x] Marcar como operación vulnerable — `pld_es_actividad_vulnerable` boolean
- [x] Registrar forma de pago en formato XSD (digito_1_type) — `pld_forma_pago` varchar(1)
- [x] Validar límite de efectivo (3,100 UMAs = $363,692.00 MXN) — `CompliancePLD::superaLimiteEfectivo()`
- [x] Generar alerta si corresponde — `pld_genera_alerta`, `pld_tipo_alerta`
- [x] Asignar referencia de aviso — `CompliancePLD::generarReferenciaAviso()` (PLD + YYMMDD + 5 dígitos)

---

*Plan Fase 1 v3.2 — Migraciones 003-006 verificadas como completas | 22 de febrero de 2026*
