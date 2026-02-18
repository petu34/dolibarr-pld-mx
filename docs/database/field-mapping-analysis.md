# Mapeo de Campos: Dolibarr vs XML PLD/LFPIORPI México

## Análisis Comparativo de Tablas Dolibarr con Formato XML de Actividades Vulnerables

Este documento identifica los campos equivalentes entre las tablas principales de Dolibarr (societe, facture, y relacionadas) y los campos requeridos en los XMLs de avisos PLD según la LFPIORPI.

---

## 📋 Estructura General del XML PLD

### Secciones Principales del XML
```xml
<archivo>
  <informe>
    <mes_reportado/>
    <sujeto_obligado>
      <clave_sujeto_obligado/>
      <clave_actividad/>
    </sujeto_obligado>
    <aviso>
      <referencia_aviso/>
      <prioridad/>
      <alerta/>
      <persona_aviso>
        <!-- Datos del cliente/tercero -->
      </persona_aviso>
      <dueno_beneficiario>
        <!-- Beneficiario controlador -->
      </dueno_beneficiario>
      <acto_operacion>
        <!-- Datos de la operación -->
      </acto_operacion>
    </aviso>
  </informe>
</archivo>
```

---

## 🏢 TABLA: llx_societe (Terceros/Clientes)

### ✅ Campos Equivalentes Existentes en Dolibarr

| Campo Dolibarr | Campo XML PLD | Tipo | Comentarios |
|----------------|---------------|------|-------------|
| **nom** | `razon_social` / `nombre` | varchar(128) | Nombre/razón social del tercero |
| **name_alias** | `nombre_comercial` | varchar(128) | Nombre comercial |
| **siren** | `rfc` (en México) | varchar(128) | RFC en contexto mexicano |
| **siret** | - | varchar(128) | No aplica en México |
| **address** | `calle` | varchar(255) | Calle y número |
| **zip** | `codigo_postal` | varchar(25) | Código postal |
| **town** | `ciudad` / `municipio` | varchar(50) | Ciudad |
| **state** | `entidad_federativa` | varchar(50) | Estado (necesita mapeo a catálogo) |
| **country** | `pais` | int | Mapea a código de país |
| **phone** | `numero_telefono` | varchar(20) | Teléfono principal |
| **email** | `correo_electronico` | varchar(255) | Email de contacto |
| **tva_intra** | `rfc` | varchar(20) | RFC en México |
| **forme_juridique_code** | `tipo_sociedad` | int | Forma jurídica |
| **typent_code** | - | varchar(12) | Tipo de entidad |
| **datec** | `fecha_alta` | datetime | Fecha de creación |
| **tms** | `fecha_modificacion` | timestamp | Última modificación |
| **fk_user_creat** | - | int | Usuario creador |
| **fk_user_modif** | - | int | Usuario modificador |

### ❌ Campos Faltantes (No existen en Dolibarr estándar)

| Campo XML PLD Requerido | Tipo Dato | Tabla Sugerida | Implementación |
|-------------------------|-----------|----------------|----------------|
| **curp** | varchar(18) | extrafields | Campo adicional obligatorio |
| **fecha_nacimiento** | date | extrafields | Para personas físicas |
| **pais_nacimiento** | varchar(50) | extrafields | País de nacimiento |
| **entidad_nacimiento** | varchar(50) | extrafields | Estado de nacimiento |
| **nacionalidad** | varchar(50) | extrafields | Nacionalidad principal |
| **actividad_economica** | varchar(10) | extrafields | Clave de actividad (catálogo SAT) |
| **numero_exterior** | varchar(10) | extrafields | Número exterior (separado) |
| **numero_interior** | varchar(10) | extrafields | Número interior |
| **colonia** | varchar(100) | extrafields | Colonia específica |
| **alcaldia_municipio** | varchar(100) | extrafields | Alcaldía o municipio |
| **clave_pais_telefono** | varchar(5) | extrafields | Código de país del teléfono |
| **extension** | varchar(10) | extrafields | Extensión telefónica |
| **tipo_persona** | enum | extrafields | Física/Moral/Fideicomiso |
| **regimen_capital** | varchar(50) | extrafields | Variable/Fijo para morales |
| **giro_mercantil** | text | extrafields | Descripción del giro |
| **fecha_constitucion** | date | extrafields | Para personas morales |
| **numero_escritura** | varchar(20) | extrafields | Escritura constitutiva |
| **folio_mercantil** | varchar(50) | extrafields | Folio RPP |
| **fecha_inscripcion_rpp** | date | extrafields | Fecha registro público |

---

## 🧑‍💼 TABLA: llx_socpeople (Contactos)

### ✅ Campos Equivalentes Existentes

| Campo Dolibarr | Campo XML PLD | Tipo | Uso en PLD |
|----------------|---------------|------|------------|
| **lastname** | `apellido_paterno` | varchar(50) | Apellido paterno |
| **firstname** | `nombre` | varchar(50) | Nombre(s) |
| **phone** | `numero_telefono` | varchar(30) | Teléfono contacto |
| **email** | `correo_electronico` | varchar(255) | Email |
| **address** | `domicilio` | varchar(255) | Dirección |
| **zip** | `codigo_postal` | varchar(25) | CP |
| **town** | `ciudad` | varchar(50) | Ciudad |
| **birthday** | `fecha_nacimiento` | date | Fecha nacimiento |
| **civility** | - | varchar(6) | Título (Sr., Sra.) |

### ❌ Campos Faltantes para Contactos PLD

| Campo XML PLD Requerido | Tipo | Implementación |
|-------------------------|------|----------------|
| **apellido_materno** | varchar(50) | extrafields obligatorio |
| **curp** | varchar(18) | extrafields obligatorio |
| **rfc_persona_fisica** | varchar(13) | extrafields obligatorio |
| **pais_nacimiento** | varchar(50) | extrafields |
| **entidad_nacimiento** | varchar(50) | extrafields |
| **nacionalidad** | varchar(50) | extrafields |
| **ocupacion** | varchar(100) | extrafields |
| **identificacion_tipo** | enum | extrafields (INE, Pasaporte, etc.) |
| **identificacion_numero** | varchar(20) | extrafields |
| **identificacion_autoridad** | varchar(100) | extrafields |

---

## 💰 TABLA: llx_facture (Facturas)

### ✅ Campos Equivalentes Existentes

| Campo Dolibarr | Campo XML PLD | Tipo | Comentarios |
|----------------|---------------|------|-------------|
| **ref** | `referencia_operacion` | varchar(30) | Referencia de factura |
| **fk_soc** | - | int | Link al tercero |
| **datef** | `fecha_operacion` | date | Fecha de factura |
| **date_valid** | `fecha_validacion` | datetime | Fecha validación |
| **total_ht** | `monto_sin_iva` | decimal | Subtotal |
| **total_tva** | `monto_iva` | decimal | IVA |
| **total_ttc** | `monto_total` | decimal | Total con IVA |
| **note_private** | `descripcion_operacion` | text | Descripción interna |
| **note_public** | `descripcion_publica` | text | Descripción pública |
| **fk_mode_reglement** | `forma_pago` | int | Método de pago |
| **model_pdf** | - | varchar(255) | Plantilla PDF |

### ❌ Campos Faltantes para Avisos PLD

| Campo XML PLD Requerido | Tipo | Tabla Sugerida | Implementación |
|-------------------------|------|----------------|----------------|
| **tipo_actividad_vulnerable** | varchar(5) | extrafields | Fracción art. 17 LFPIORPI |
| **umbral_superado** | boolean | extrafields | Si supera umbral |
| **monto_efectivo** | decimal | Nueva tabla | Monto pagado en efectivo |
| **monto_transferencia** | decimal | Nueva tabla | Monto por transferencia |
| **monto_cheque** | decimal | Nueva tabla | Monto por cheque |
| **monto_tarjeta** | decimal | Nueva tabla | Monto por tarjeta |
| **forma_pago_detallada** | enum | Nueva tabla | Efectivo/Transfer/Cheque/etc |
| **institucion_bancaria** | varchar(100) | Nueva tabla | Banco origen |
| **numero_cuenta** | varchar(20) | Nueva tabla | Últimos 4 dígitos |
| **numero_cheque** | varchar(20) | Nueva tabla | Número de cheque |
| **razon_operacion** | text | extrafields | Justificación/razón |
| **operacion_acumulada** | boolean | extrafields | Si es acumulación 6 meses |
| **fecha_inicio_acumulacion** | date | extrafields | Inicio período acumulación |
| **fecha_fin_acumulacion** | date | extrafields | Fin período acumulación |
| **moneda** | varchar(3) | Existe | Código ISO moneda |
| **tipo_cambio** | decimal | extrafields | Si no es MXN |

---

## 💳 TABLA: llx_paiement (Pagos)

### ✅ Campos Equivalentes Existentes

| Campo Dolibarr | Campo XML PLD | Tipo | Uso |
|----------------|---------------|------|-----|
| **datep** | `fecha_pago` | datetime | Fecha de pago |
| **amount** | `monto_pago` | decimal | Importe pagado |
| **fk_paiement** | `tipo_pago` | int | Tipo de pago |
| **num_paiement** | `referencia_pago` | varchar(50) | Referencia/Folio |
| **note** | `observaciones` | text | Notas del pago |

### ❌ Campos Críticos Faltantes

| Campo XML PLD Requerido | Tipo | Implementación |
|-------------------------|------|----------------|
| **forma_pago_especifica** | enum | Nueva tabla / extrafields |
| **monto_efectivo_pago** | decimal | **CRÍTICO** - Nueva tabla |
| **institucion_financiera** | varchar(100) | Nueva tabla |
| **cuenta_origen** | varchar(20) | Nueva tabla |
| **cuenta_destino** | varchar(20) | Nueva tabla |
| **numero_autorizacion** | varchar(20) | Nueva tabla |
| **comprobante_pago** | blob/text | Nueva tabla |

---

## 🚗 ACTIVIDAD VULNERABLE ESPECÍFICA: Vehículos (Fracción VIII)

### Campos Específicos para Venta de Vehículos

| Campo XML PLD Requerido | Existe en Dolibarr | Implementación |
|-------------------------|--------------------|-----------------| 
| **tipo_vehiculo** | ❌ | extrafields product (Terrestre/Aéreo/Marítimo) |
| **marca** | ❌ | extrafields product |
| **modelo** | ❌ | extrafields product |
| **anio** | ❌ | extrafields product |
| **numero_serie** | ✅ Parcial | product.ref / product_batch.batch |
| **numero_motor** | ❌ | extrafields product |
| **placas** | ❌ | extrafields product |
| **numero_factura_original** | ❌ | extrafields (si es usado) |
| **procedencia** | ❌ | extrafields (Nacional/Importado) |
| **estado_vehiculo** | ❌ | extrafields (Nuevo/Usado) |
| **precio_venta** | ✅ | product.price |
| **valor_comercial** | ❌ | extrafields |
| **uso_destino** | ❌ | extrafields (Personal/Comercial) |

---

## 🎯 BENEFICIARIO CONTROLADOR

### Tabla Nueva Requerida: llx_beneficiario_controlador

Esta tabla **NO EXISTE** en Dolibarr y es **OBLIGATORIA** por LFPIORPI.

| Campo Requerido XML | Tipo | Descripción |
|---------------------|------|-------------|
| **fk_societe** | int | Link al tercero |
| **nombre** | varchar(50) | Nombre completo |
| **apellido_paterno** | varchar(50) | Apellido paterno |
| **apellido_materno** | varchar(50) | Apellido materno |
| **fecha_nacimiento** | date | Fecha nacimiento |
| **curp** | varchar(18) | CURP |
| **rfc** | varchar(13) | RFC |
| **nacionalidad** | varchar(50) | Nacionalidad |
| **pais_nacimiento** | varchar(50) | País nacimiento |
| **porcentaje_participacion** | decimal(5,2) | % de control |
| **tipo_participacion** | enum | Directa/Indirecta/Ambas |
| **domicilio** | text | Domicilio completo |
| **pep** | boolean | Persona Expuesta Políticamente |
| **relacion_pep** | varchar(100) | Si tiene relación con PEP |
| **documento_identificacion** | blob | Copia de identificación |

---

## 📊 TABLA NUEVA REQUERIDA: llx_pld_operacion

Para cumplir con PLD, se necesita una tabla específica que registre operaciones vulnerables:

```sql
CREATE TABLE llx_pld_operacion (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  
  -- Referencias Dolibarr
  fk_facture INT,
  fk_societe INT NOT NULL,
  fk_paiement INT,
  
  -- Datos PLD
  fecha_operacion DATE NOT NULL,
  mes_reportado VARCHAR(6) NOT NULL, -- YYYYMM
  tipo_actividad_vulnerable VARCHAR(5) NOT NULL, -- Fracción LFPIORPI
  monto_total DECIMAL(15,2) NOT NULL,
  moneda VARCHAR(3) DEFAULT 'MXN',
  tipo_cambio DECIMAL(10,4),
  
  -- Umbrales
  supera_umbral_identificacion BOOLEAN,
  supera_umbral_aviso BOOLEAN,
  es_operacion_acumulada BOOLEAN,
  fecha_inicio_acumulacion DATE,
  fecha_fin_acumulacion DATE,
  
  -- Formas de pago (desglosadas)
  monto_efectivo DECIMAL(15,2) DEFAULT 0,
  monto_transferencia DECIMAL(15,2) DEFAULT 0,
  monto_cheque DECIMAL(15,2) DEFAULT 0,
  monto_tarjeta DECIMAL(15,2) DEFAULT 0,
  monto_otros DECIMAL(15,2) DEFAULT 0,
  
  -- Datos bancarios
  institucion_financiera VARCHAR(100),
  cuenta_origen VARCHAR(20),
  cuenta_destino VARCHAR(20),
  numero_cheque VARCHAR(20),
  numero_autorizacion VARCHAR(20),
  
  -- Control de avisos
  aviso_presentado BOOLEAN DEFAULT FALSE,
  fecha_presentacion_aviso DATE,
  referencia_aviso VARCHAR(50),
  tipo_aviso ENUM('mensual', '24_horas', 'informe_cero'),
  archivo_xml_generado TEXT,
  acuse_sat TEXT,
  
  -- Alertas y riesgos
  alerta_generada BOOLEAN DEFAULT FALSE,
  tipo_alerta VARCHAR(10), -- 'interna', 'lista', 'inusual'
  razon_alerta TEXT,
  requiere_aviso_24hrs BOOLEAN DEFAULT FALSE,
  
  -- Auditoría
  descripcion_operacion TEXT,
  observaciones TEXT,
  documentacion_respaldo TEXT,
  datec DATETIME,
  tms TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  INDEX idx_societe (fk_societe),
  INDEX idx_fecha (fecha_operacion),
  INDEX idx_mes (mes_reportado),
  INDEX idx_aviso (aviso_presentado)
);
```

---

## 📁 TABLA NUEVA: llx_pld_documento

Para almacenar documentos de identificación y comprobantes:

```sql
CREATE TABLE llx_pld_documento (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  
  fk_societe INT,
  fk_socpeople INT,
  fk_pld_operacion INT,
  
  tipo_documento ENUM(
    'identificacion_oficial',
    'comprobante_domicilio',
    'acta_constitutiva',
    'poder_legal',
    'cedula_fiscal',
    'comprobante_pago',
    'contrato',
    'otro'
  ) NOT NULL,
  
  tipo_identificacion VARCHAR(50), -- INE, Pasaporte, etc.
  numero_documento VARCHAR(50),
  fecha_expedicion DATE,
  fecha_vencimiento DATE,
  autoridad_emite VARCHAR(100),
  
  nombre_archivo VARCHAR(255),
  ruta_archivo VARCHAR(500),
  hash_archivo VARCHAR(64), -- SHA256
  mime_type VARCHAR(100),
  tamano_bytes INT,
  
  fecha_digitalizacion DATE,
  verificado BOOLEAN DEFAULT FALSE,
  fecha_verificacion DATE,
  fk_user_verificador INT,
  
  datec DATETIME,
  tms TIMESTAMP,
  fk_user_creat INT
);
```

---

## 🔍 RESUMEN DE GAPS CRÍTICOS

### ❌ Campos Obligatorios TOTALMENTE Ausentes

1. **Personas Físicas**:
   - CURP (Clave Única de Registro de Población)
   - Apellido materno
   - País y entidad de nacimiento
   - Nacionalidad específica
   - Ocupación/actividad económica

2. **Domicilios**:
   - Número exterior (separado)
   - Número interior
   - Colonia (específica, no genérica)
   - Alcaldía/Municipio (separado de ciudad)

3. **Formas de Pago**:
   - Desglose detallado por tipo de pago
   - Monto específico en efectivo
   - Datos bancarios (institución, cuenta, autorización)
   - Número de cheque

4. **Beneficiario Controlador**:
   - Todo el concepto falta completamente
   - Porcentaje de participación
   - Tipo de control

5. **Control PLD**:
   - Tipo de actividad vulnerable
   - Control de umbrales
   - Registro de avisos generados
   - Trazabilidad de XMLs enviados
   - Acuses del SAT

---

## 💡 RECOMENDACIONES DE IMPLEMENTACIÓN

### Fase 1: Campos Básicos (Extrafields)
```php
// Agregar a llx_societe_extrafields
- curp (varchar 18)
- fecha_nacimiento (date)
- nacionalidad (varchar 50)
- actividad_economica (varchar 10)
- numero_exterior (varchar 10)
- numero_interior (varchar 10)
- colonia (varchar 100)
- tipo_persona (enum: fisica/moral/fideicomiso)
```

### Fase 2: Tablas Nuevas Específicas PLD
- `llx_pld_operacion` → Registro de operaciones vulnerables
- `llx_pld_beneficiario` → Beneficiarios controladores
- `llx_pld_documento` → Documentos de identificación
- `llx_pld_aviso` → Control de avisos SAT
- `llx_pld_forma_pago` → Desglose detallado de pagos

### Fase 3: Integración con Workflow
- Hook en validación de factura → Evaluar si es operación vulnerable
- Hook en registro de pago → Capturar forma de pago detallada
- Trigger en alta de cliente → Solicitar datos PLD obligatorios
- Cron job mensual → Generar avisos XML automáticamente

### Fase 4: Módulo PLD Completo
- Generador de XML según especificaciones SAT
- Validador de umbrales LFPIORPI
- Catálogos SAT integrados
- Dashboard de cumplimiento
- Alertas automáticas

---

## 📌 CONCLUSIÓN

**Cobertura actual de Dolibarr: ~35%**

- ✅ **Datos básicos** de terceros y facturas: Cubiertos parcialmente
- ⚠️ **Datos específicos PLD**: Requieren extrafields extensivos
- ❌ **Beneficiario controlador**: No existe concepto
- ❌ **Formas de pago detalladas**: Información insuficiente
- ❌ **Control de avisos**: No existe infraestructura

**Para cumplimiento completo LFPIORPI se requiere**:
1. ~25 campos adicionales en extrafields
2. 4-5 tablas nuevas específicas PLD
3. Módulo generador de XML
4. Sistema de catálogos SAT
5. Workflow de alertas y umbrales
