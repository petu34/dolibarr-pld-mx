# Plan Fase 1: PLD Vehículos - Implementación de Extrafields en Dolibarr

## 🎯 Objetivo de la Fase 1

Agregar los campos mínimos necesarios mediante **extrafields** (campos adicionales) en Dolibarr para cumplir con los requerimientos de la **Fracción VIII del Art. 17 LFPIORPI** (Compra y venta de vehículos), sin crear tablas nuevas.

---

## 📊 Alcance de la Fase 1

### Actividad Vulnerable Específica
**Fracción VIII - Comercialización o distribución habitual o profesional de vehículos**

**Umbrales 2026:**
- **Identificación**: 3,220 UMAs = $377,778.20 MXN
- **Aviso**: 3,220 UMAs = $377,778.20 MXN
- **Restricción efectivo**: 3,100 UMAs = $363,661 MXN

### Entidades Dolibarr Afectadas
1. ✅ **llx_societe** (Terceros/Clientes)
2. ✅ **llx_socpeople** (Contactos - representantes legales)
3. ✅ **llx_product** (Vehículos como productos)
4. ✅ **llx_facture** (Facturas de venta)
5. ✅ **llx_paiement** (Formas de pago)
6. ✅ **llx_commande** (Pedidos - opcional para seguimiento)

---

## 📋 PARTE 1: Extrafields para llx_societe (Clientes)

### 1.1 Identificación Básica del Cliente

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_tipo_persona** | select | - | ✅ Sí | Física / Moral / Fideicomiso |
| **pld_curp** | varchar | 18 | ⚠️ Si es PF | CURP (18 caracteres) |
| **pld_rfc_validado** | varchar | 13 | ✅ Sí | RFC validado contra SAT |
| **pld_fecha_nacimiento** | date | - | ⚠️ Si es PF | Fecha nacimiento (PF) |
| **pld_fecha_constitucion** | date | - | ⚠️ Si es PM | Fecha constitución (PM) |
| **pld_nacionalidad** | varchar | 50 | ✅ Sí | Nacionalidad principal |
| **pld_pais_nacimiento** | varchar | 50 | ⚠️ Si es PF | País nacimiento |
| **pld_estado_nacimiento** | varchar | 50 | ⚠️ Si es PF | Entidad federativa nacimiento |

### 1.2 Domicilio Fiscal Detallado

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_calle** | varchar | 100 | ✅ Sí | Nombre de la calle |
| **pld_numero_exterior** | varchar | 10 | ✅ Sí | Número exterior |
| **pld_numero_interior** | varchar | 10 | ❌ No | Número interior |
| **pld_colonia** | varchar | 100 | ✅ Sí | Colonia (catálogo SEPOMEX) |
| **pld_codigo_postal** | varchar | 5 | ✅ Sí | CP (5 dígitos) |
| **pld_municipio** | varchar | 100 | ✅ Sí | Municipio/Alcaldía |
| **pld_estado** | select | - | ✅ Sí | Entidad federativa (catálogo) |
| **pld_pais** | select | - | ✅ Sí | País (por defecto: México) |

### 1.3 Actividad Económica

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_actividad_economica** | varchar | 10 | ✅ Sí | Clave actividad SAT |
| **pld_giro_mercantil** | text | - | ✅ Sí | Descripción del giro |
| **pld_ocupacion** | varchar | 100 | ⚠️ Si es PF | Profesión u ocupación |

### 1.4 Datos Constitutivos (Personas Morales)

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_numero_escritura** | varchar | 20 | ⚠️ Si es PM | Escritura constitutiva |
| **pld_fecha_escritura** | date | - | ⚠️ Si es PM | Fecha escritura |
| **pld_notario_numero** | int | - | ⚠️ Si es PM | Número de notario |
| **pld_notario_nombre** | varchar | 150 | ⚠️ Si es PM | Nombre del notario |
| **pld_notario_estado** | varchar | 50 | ⚠️ Si es PM | Estado del notario |
| **pld_folio_mercantil** | varchar | 50 | ⚠️ Si es PM | Folio RPP |

### 1.5 Control PLD

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_cliente_identificado** | boolean | - | ✅ Sí | Ya se identificó |
| **pld_fecha_identificacion** | date | - | ✅ Sí | Fecha identificación |
| **pld_expediente_completo** | boolean | - | ✅ Sí | Expediente completo |
| **pld_es_pep** | boolean | - | ✅ Sí | Persona Expuesta Políticamente |
| **pld_relacion_pep** | varchar | 200 | ❌ No | Parentesco con PEP |
| **pld_tiene_beneficiario** | boolean | - | ✅ Sí | Tiene beneficiario controlador |
| **pld_observaciones** | text | - | ❌ No | Observaciones generales |

---

## 👤 PARTE 2: Extrafields para llx_socpeople (Contactos)

### 2.1 Datos Personales Completos

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_apellido_paterno** | varchar | 50 | ✅ Sí | Apellido paterno |
| **pld_apellido_materno** | varchar | 50 | ✅ Sí | Apellido materno |
| **pld_nombre_completo** | varchar | 150 | ✅ Sí | Nombre(s) completo(s) |
| **pld_curp** | varchar | 18 | ✅ Sí | CURP del contacto |
| **pld_rfc** | varchar | 13 | ✅ Sí | RFC del contacto |
| **pld_fecha_nacimiento** | date | - | ✅ Sí | Fecha nacimiento |
| **pld_nacionalidad** | varchar | 50 | ✅ Sí | Nacionalidad |

### 2.2 Identificación Oficial

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_tipo_identificacion** | select | - | ✅ Sí | INE/IFE/Pasaporte/FM3/Cédula |
| **pld_numero_identificacion** | varchar | 20 | ✅ Sí | Número de identificación |
| **pld_vigencia_identificacion** | date | - | ✅ Sí | Fecha vigencia |
| **pld_autoridad_emite** | varchar | 100 | ✅ Sí | Autoridad emisora |
| **pld_clave_elector** | varchar | 18 | ⚠️ Si INE | Clave de elector (INE) |

### 2.3 Representación Legal

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_es_representante_legal** | boolean | - | ✅ Sí | Es representante legal |
| **pld_tipo_representacion** | select | - | ⚠️ Si rep | Poder general/especial/ambos |
| **pld_escritura_poder** | varchar | 20 | ⚠️ Si rep | Escritura del poder |
| **pld_fecha_poder** | date | - | ⚠️ Si rep | Fecha del poder |
| **pld_notario_poder** | varchar | 150 | ⚠️ Si rep | Notario que dio fe |

---

## 🚗 PARTE 3: Extrafields para llx_product (Vehículos)

### 3.1 Identificación del Vehículo

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_tipo_vehiculo** | select | - | ✅ Sí | Terrestre/Aéreo/Marítimo |
| **pld_subtipo** | varchar | 50 | ✅ Sí | Auto/Camión/Motocicleta/Avión/etc |
| **pld_marca** | varchar | 50 | ✅ Sí | Marca del vehículo |
| **pld_submarca** | varchar | 50 | ❌ No | Submarca/línea |
| **pld_modelo** | varchar | 50 | ✅ Sí | Modelo |
| **pld_anio_modelo** | int | 4 | ✅ Sí | Año modelo |
| **pld_version** | varchar | 100 | ❌ No | Versión específica |
| **pld_color** | varchar | 30 | ✅ Sí | Color del vehículo |

### 3.2 Números de Serie e Identificadores

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_numero_serie_vin** | varchar | 17 | ✅ Sí | VIN (17 caracteres) |
| **pld_numero_motor** | varchar | 30 | ✅ Sí | Número de motor |
| **pld_numero_pedimento** | varchar | 20 | ⚠️ Importado | Pedimento aduanal |
| **pld_placas** | varchar | 10 | ❌ No | Placas actuales (si aplica) |
| **pld_numero_registro** | varchar | 30 | ⚠️ Aéreo/Mar | Matrícula aérea/marítima |

### 3.3 Origen y Estado

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_origen** | select | - | ✅ Sí | Nacional/Importado |
| **pld_pais_origen** | varchar | 50 | ⚠️ Importado | País fabricación |
| **pld_estado_vehiculo** | select | - | ✅ Sí | Nuevo/Usado/Seminuevo |
| **pld_kilometraje** | int | - | ⚠️ Si usado | Kilometraje actual |
| **pld_uso_destino** | select | - | ✅ Sí | Particular/Comercial/Transporte |

### 3.4 Documentación Legal

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_numero_factura_original** | varchar | 30 | ⚠️ Si usado | Factura original |
| **pld_fecha_factura_original** | date | - | ⚠️ Si usado | Fecha factura original |
| **pld_propietario_anterior** | varchar | 200 | ⚠️ Si usado | Nombre propietario anterior |
| **pld_tarjeta_circulacion** | varchar | 20 | ❌ No | Número tarjeta circulación |

### 3.5 Valores

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_valor_factura** | decimal | (15,2) | ✅ Sí | Valor facturado |
| **pld_valor_comercial** | decimal | (15,2) | ✅ Sí | Valor comercial / avalúo |
| **pld_valor_libro_azul** | decimal | (15,2) | ❌ No | Valor libro azul |

---

## 💰 PARTE 4: Extrafields para llx_facture (Facturas)

### 4.1 Control PLD de la Operación

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_es_actividad_vulnerable** | boolean | - | ✅ Sí | Es actividad vulnerable |
| **pld_tipo_actividad** | varchar | 5 | ✅ Sí | "VIII" (Fracción LFPIORPI) |
| **pld_supera_umbral_id** | boolean | - | ✅ Sí | Supera umbral identificación |
| **pld_supera_umbral_aviso** | boolean | - | ✅ Sí | Supera umbral aviso |
| **pld_requiere_aviso** | boolean | - | ✅ Sí | Requiere aviso SAT |
| **pld_tipo_aviso** | select | - | ⚠️ Si req | mensual/24hrs/acumulado |

### 4.2 Datos de la Operación

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_fecha_operacion** | date | - | ✅ Sí | Fecha real operación |
| **pld_descripcion_operacion** | text | - | ✅ Sí | Descripción detallada |
| **pld_razon_operacion** | text | - | ✅ Sí | Justificación/motivo |
| **pld_monto_moneda_nacional** | decimal | (15,2) | ✅ Sí | Monto en MXN |
| **pld_tipo_cambio_aplicado** | decimal | (10,4) | ⚠️ Si extranjera | Tipo de cambio |

### 4.3 Acumulación de Operaciones

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_es_operacion_acumulada** | boolean | - | ✅ Sí | Parte de acumulación 6 meses |
| **pld_fecha_inicio_acumulacion** | date | - | ⚠️ Si acum | Inicio período |
| **pld_fecha_fin_acumulacion** | date | - | ⚠️ Si acum | Fin período |
| **pld_monto_acumulado_total** | decimal | (15,2) | ⚠️ Si acum | Total acumulado |

### 4.4 Control de Avisos

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_aviso_presentado** | boolean | - | ✅ Sí | Ya se presentó aviso |
| **pld_fecha_presentacion** | date | - | ⚠️ Si pres | Fecha presentación SAT |
| **pld_folio_aviso** | varchar | 50 | ⚠️ Si pres | Folio del aviso SAT |
| **pld_mes_reportado** | varchar | 6 | ⚠️ Si pres | YYYYMM reportado |
| **pld_acuse_sat** | text | - | ⚠️ Si pres | Acuse digital SAT |

### 4.5 Alertas y Excepciones

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_genera_alerta** | boolean | - | ✅ Sí | Genera alerta interna |
| **pld_tipo_alerta** | select | - | ⚠️ Si alerta | inusual/lista/patron/otro |
| **pld_motivo_alerta** | text | - | ⚠️ Si alerta | Razón de la alerta |
| **pld_requiere_aviso_24hrs** | boolean | - | ✅ Sí | Requiere aviso 24 horas |
| **pld_razon_24hrs** | text | - | ⚠️ Si 24h | Justificación aviso urgente |

---

## 💳 PARTE 5: Extrafields para llx_paiement (Pagos)

### 5.1 Forma de Pago Detallada

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_forma_pago** | select | - | ✅ Sí | Efectivo/Transfer/Cheque/Tarjeta/Otro |
| **pld_monto_efectivo** | decimal | (15,2) | ✅ Sí | Monto en efectivo |
| **pld_monto_transferencia** | decimal | (15,2) | ✅ Sí | Monto por transferencia |
| **pld_monto_cheque** | decimal | (15,2) | ✅ Sí | Monto por cheque |
| **pld_monto_tarjeta** | decimal | (15,2) | ✅ Sí | Monto por tarjeta |
| **pld_monto_otros** | decimal | (15,2) | ✅ Sí | Otros medios de pago |

### 5.2 Datos Bancarios (Transferencia)

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_banco_origen** | varchar | 100 | ⚠️ Si trans | Institución financiera origen |
| **pld_cuenta_origen** | varchar | 4 | ⚠️ Si trans | Últimos 4 dígitos cuenta |
| **pld_clabe_origen** | varchar | 18 | ⚠️ Si trans | CLABE origen (si disponible) |
| **pld_banco_destino** | varchar | 100 | ⚠️ Si trans | Banco destino |
| **pld_cuenta_destino** | varchar | 4 | ⚠️ Si trans | Últimos 4 dígitos destino |
| **pld_numero_autorizacion** | varchar | 20 | ⚠️ Si trans | Número autorización |
| **pld_fecha_transferencia** | date | - | ⚠️ Si trans | Fecha de transferencia |

### 5.3 Datos del Cheque

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_banco_cheque** | varchar | 100 | ⚠️ Si cheque | Banco emisor |
| **pld_numero_cheque** | varchar | 20 | ⚠️ Si cheque | Número de cheque |
| **pld_cuenta_cheque** | varchar | 4 | ⚠️ Si cheque | Últimos 4 dígitos cuenta |
| **pld_fecha_cheque** | date | - | ⚠️ Si cheque | Fecha del cheque |
| **pld_librador_cheque** | varchar | 200 | ⚠️ Si cheque | Nombre del librador |

### 5.4 Datos de Tarjeta

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_tipo_tarjeta** | select | - | ⚠️ Si tarjeta | Débito/Crédito |
| **pld_emisor_tarjeta** | varchar | 100 | ⚠️ Si tarjeta | Banco emisor |
| **pld_ultimos_digitos** | varchar | 4 | ⚠️ Si tarjeta | Últimos 4 dígitos |
| **pld_numero_autorizacion_tarjeta** | varchar | 20 | ⚠️ Si tarjeta | Autorización de compra |

### 5.5 Control de Efectivo

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_supera_limite_efectivo** | boolean | - | ✅ Sí | Supera $363,661 MXN |
| **pld_alerta_efectivo** | boolean | - | ✅ Sí | Alerta por uso de efectivo |
| **pld_justificacion_efectivo** | text | - | ⚠️ Si alto | Justificación uso efectivo |

---

## 🔄 PARTE 6: Extrafields para llx_commande (Pedidos - Opcional)

### 6.1 Pre-validación PLD

| Campo Extrafield | Tipo | Longitud | Obligatorio | Descripción |
|------------------|------|----------|-------------|-------------|
| **pld_preventa_identificada** | boolean | - | ❌ No | Cliente ya identificado |
| **pld_anticipo_estimado** | decimal | (15,2) | ❌ No | Anticipo aproximado |
| **pld_forma_pago_planeada** | select | - | ❌ No | Forma pago esperada |
| **pld_alerta_previa** | boolean | - | ❌ No | Alerta en cotización |

---

## 🛠️ PARTE 7: Plan de Implementación Técnica

### 7.1 Herramientas Necesarias

**Módulo Dolibarr:**
```
htdocs/custom/pldvehiculos/
  ├── core/
  │   └── modules/
  │       └── modPLDVehiculos.class.php
  ├── admin/
  │   ├── setup.php
  │   └── about.php
  ├── sql/
  │   ├── llx_societe_extrafields.sql
  │   ├── llx_socpeople_extrafields.sql
  │   ├── llx_product_extrafields.sql
  │   ├── llx_facture_extrafields.sql
  │   └── llx_paiement_extrafields.sql
  ├── langs/
  │   ├── es_MX/
  │   │   └── pldvehiculos.lang
  │   └── en_US/
  │       └── pldvehiculos.lang
  └── class/
      └── pldvehiculos.class.php
```

### 7.2 Scripts SQL para Extrafields

**Ejemplo: llx_societe_extrafields**
```sql
-- Tipo de persona
INSERT INTO llx_extrafields (name, label, type, pos, entity, elementtype, enabled, required)
VALUES ('pld_tipo_persona', 'Tipo de Persona', 'select', 100, 1, 'societe', 1, 1);

INSERT INTO llx_extrafields_values (fk_object, name, value)
VALUES 
  (LAST_INSERT_ID(), 'options', 'fisica:Persona Física\nmoral:Persona Moral\nfideicomiso:Fideicomiso');

-- CURP
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, enabled, required, list, help)
VALUES ('pld_curp', 'CURP', 'varchar', '18', 101, 1, 'societe', 1, 0, 1, 
  'Clave Única de Registro de Población (18 caracteres). Obligatorio para personas físicas.');

-- RFC validado
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, enabled, required, list)
VALUES ('pld_rfc_validado', 'RFC Validado', 'varchar', '13', 102, 1, 'societe', 1, 1, 1);

-- ... continuar con todos los campos
```

### 7.3 Archivo de Idioma (es_MX)

```ini
# langs/es_MX/pldvehiculos.lang

Module100001Name=PLD Vehículos
Module100001Desc=Módulo para cumplimiento LFPIORPI en compra-venta de vehículos

# Terceros
PLDTipoPersona=Tipo de Persona
PLDCURP=CURP
PLDRFCValidado=RFC Validado
PLDFechaNacimiento=Fecha de Nacimiento
PLDNacionalidad=Nacionalidad

# Vehículos
PLDTipoVehiculo=Tipo de Vehículo
PLDMarca=Marca
PLDModelo=Modelo
PLDAnioModelo=Año Modelo
PLDNumeroSerieVIN=VIN (Número de Serie)

# Pagos
PLDFormaPago=Forma de Pago
PLDMontoEfectivo=Monto en Efectivo
PLDMontoTransferencia=Monto Transferencia
```

### 7.4 Cronograma de Implementación

#### Semana 1-2: Preparación
- [ ] Crear estructura del módulo
- [ ] Definir scripts SQL para extrafields
- [ ] Crear archivos de idioma
- [ ] Configurar módulo descriptor

#### Semana 3: Implementación Terceros
- [ ] Agregar extrafields a llx_societe
- [ ] Agregar extrafields a llx_socpeople
- [ ] Crear formularios personalizados
- [ ] Validaciones en frontend

#### Semana 4: Implementación Productos
- [ ] Agregar extrafields a llx_product
- [ ] Crear fichas de vehículos
- [ ] Integrar con catálogos externos (marcas/modelos)

#### Semana 5: Implementación Facturación
- [ ] Agregar extrafields a llx_facture
- [ ] Agregar extrafields a llx_paiement
- [ ] Crear lógica de umbrales
- [ ] Alertas automáticas

#### Semana 6: Validaciones y Testing
- [ ] Validación de CURP
- [ ] Validación de RFC
- [ ] Validación de VIN
- [ ] Pruebas de cálculo de umbrales
- [ ] Pruebas de alertas

---

## ✅ Checklist de Completitud

### Datos Mínimos para Operar Legalmente

**Al registrar un cliente nuevo:**
- [ ] Tipo de persona identificado
- [ ] RFC capturado y validado
- [ ] CURP capturado (si PF)
- [ ] Domicilio completo con colonia
- [ ] Actividad económica registrada
- [ ] Identificación oficial escaneada

**Al registrar un vehículo:**
- [ ] VIN capturado
- [ ] Marca, modelo, año
- [ ] Número de motor
- [ ] Origen (nacional/importado)
- [ ] Estado (nuevo/usado)
- [ ] Valor comercial

**Al facturar:**
- [ ] Validar si supera umbrales
- [ ] Marcar como operación vulnerable
- [ ] Registrar forma de pago detallada
- [ ] Validar límite de efectivo
- [ ] Generar alerta si corresponde

---

## 📊 Reportes Necesarios Fase 1

### 1. Reporte de Operaciones Pendientes de Aviso
```
Facturas que superan umbral y no tienen aviso presentado
```

### 2. Reporte de Clientes Sin Identificar
```
Clientes con operaciones > umbral identificación sin expediente completo
```

### 3. Reporte Mensual de Operaciones
```
Todas las operaciones vulnerables del mes para generar XML
```

### 4. Reporte de Alertas Generadas
```
Operaciones que generaron alerta interna (posibles 24 horas)
```

### 5. Dashboard de Cumplimiento
```
- Operaciones del mes
- Avisos pendientes
- Clientes sin identificar
- Límites de efectivo rebasados
```

---

## 🚨 Validaciones Automáticas Requeridas

### En Alta de Cliente:
```javascript
// Validar CURP (si PF)
if (tipo_persona == 'fisica' && !validarCURP(curp)) {
  error("CURP inválido");
}

// Validar RFC
if (!validarRFC(rfc)) {
  error("RFC inválido");
}

// Validar edad mínima (18 años)
if (fecha_nacimiento && edad < 18) {
  error("Cliente debe ser mayor de edad");
}
```

### En Alta de Vehículo:
```javascript
// Validar VIN (17 caracteres alfanuméricos)
if (!validarVIN(vin)) {
  error("VIN debe tener 17 caracteres");
}

// Validar año modelo
if (anio_modelo < 1900 || anio_modelo > año_actual + 1) {
  error("Año modelo inválido");
}
```

###