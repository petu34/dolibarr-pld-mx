# Plan Fase 2: PLD Vehículos - Beneficiarios Controladores y Tablas Específicas

## 🎯 Objetivo de la Fase 2

Crear **tablas especializadas** para gestionar información PLD que no puede manejarse eficientemente con extrafields, con énfasis en **Beneficiarios Controladores** (obligatorio LFPIORPI Art. 18).

---

## 📊 Alcance de la Fase 2

### Prerequisito
✅ Fase 1 completada (extrafields implementados)

### Nuevas Tablas a Crear
1. **llx_pld_beneficiario** - Beneficiarios controladores
2. **llx_pld_operacion** - Registro detallado de operaciones vulnerables
3. **llx_pld_documento** - Gestión de documentos digitalizados
4. **llx_pld_aviso** - Control de avisos presentados al SAT
5. **llx_pld_forma_pago_detalle** - Desglose granular de pagos
6. **llx_pld_alerta** - Sistema de alertas y seguimiento
7. **llx_pld_catalogo** - Catálogos SAT y referencias

---

## 🏛️ TABLA 1: llx_pld_beneficiario

### Propósito
Registrar beneficiarios controladores de personas morales (obligatorio Art. 18 LFPIORPI).

### Estructura SQL

```sql
CREATE TABLE llx_pld_beneficiario (
  -- Identificador
  rowid BIGINT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Relaciones
  fk_societe INT NOT NULL,
  fk_socpeople INT DEFAULT NULL,
  
  -- Tipo de beneficiario
  tipo_beneficiario ENUM(
    'accionista',
    'fideicomitente',
    'fideicomisario',
    'fiduciario',
    'administrador',
    'apoderado',
    'otro'
  ) NOT NULL,
  
  -- Datos personales
  nombre VARCHAR(50) NOT NULL,
  apellido_paterno VARCHAR(50) NOT NULL,
  apellido_materno VARCHAR(50) NOT NULL,
  nombre_completo VARCHAR(150) GENERATED ALWAYS AS 
    (CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno)) STORED,
  
  -- Identificación
  curp VARCHAR(18) NOT NULL,
  rfc VARCHAR(13) NOT NULL,
  fecha_nacimiento DATE NOT NULL,
  nacionalidad VARCHAR(50) NOT NULL,
  pais_nacimiento VARCHAR(50),
  estado_nacimiento VARCHAR(50),
  
  -- Domicilio
  calle VARCHAR(100),
  numero_exterior VARCHAR(10),
  numero_interior VARCHAR(10),
  colonia VARCHAR(100),
  codigo_postal VARCHAR(5),
  municipio VARCHAR(100),
  estado VARCHAR(50),
  pais VARCHAR(50) DEFAULT 'México',
  
  -- Contacto
  telefono VARCHAR(20),
  email VARCHAR(255),
  
  -- Participación
  porcentaje_participacion DECIMAL(5,2) NOT NULL,
  tipo_participacion ENUM('directa', 'indirecta', 'ambas') NOT NULL,
  descripcion_participacion TEXT,
  
  -- Estructura corporativa
  cadena_propiedad TEXT COMMENT 'JSON con árbol de propiedad',
  nivel_jerarquia INT DEFAULT 1,
  fk_beneficiario_superior INT DEFAULT NULL,
  
  -- PEP (Persona Expuesta Políticamente)
  es_pep BOOLEAN DEFAULT FALSE,
  tipo_pep ENUM('nacional', 'extranjero', 'organismo_internacional') DEFAULT NULL,
  cargo_pep VARCHAR(200),
  fecha_inicio_cargo DATE,
  fecha_fin_cargo DATE,
  relacion_con_pep VARCHAR(200) COMMENT 'Si no es PEP pero tiene relación',
  
  -- Documentación
  tipo_identificacion VARCHAR(50),
  numero_identificacion VARCHAR(20),
  vigencia_identificacion DATE,
  autoridad_emite VARCHAR(100),
  
  -- Control y validación
  verificado BOOLEAN DEFAULT FALSE,
  fecha_verificacion DATE,
  fk_user_verificador INT,
  metodo_verificacion VARCHAR(100),
  observaciones TEXT,
  
  -- Vigencia
  fecha_alta DATE NOT NULL,
  fecha_baja DATE DEFAULT NULL,
  activo BOOLEAN DEFAULT TRUE,
  motivo_baja VARCHAR(200),
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  -- Índices
  INDEX idx_societe (fk_societe),
  INDEX idx_curp (curp),
  INDEX idx_rfc (rfc),
  INDEX idx_pep (es_pep),
  INDEX idx_activo (activo),
  INDEX idx_porcentaje (porcentaje_participacion),
  
  -- Constraints
  CONSTRAINT fk_pld_beneficiario_societe 
    FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE CASCADE,
  CONSTRAINT fk_pld_beneficiario_socpeople 
    FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople(rowid) ON DELETE SET NULL,
  CONSTRAINT fk_pld_beneficiario_superior 
    FOREIGN KEY (fk_beneficiario_superior) REFERENCES llx_pld_beneficiario(rowid),
  CONSTRAINT chk_porcentaje 
    CHECK (porcentaje_participacion > 0 AND porcentaje_participacion <= 100),
  CONSTRAINT chk_fechas_cargo 
    CHECK (fecha_fin_cargo IS NULL OR fecha_fin_cargo >= fecha_inicio_cargo)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Reglas de Negocio

**Umbrales para identificar beneficiario:**
- Persona moral: **≥ 25% de acciones o participación**
- Control efectivo: Poder de veto, designación de administradores
- Fideicomiso: Fideicomitente, fideicomisario, fiduciario

**Validaciones:**
```sql
-- Trigger: Validar suma de porcentajes no exceda 100%
DELIMITER $$
CREATE TRIGGER trg_pld_beneficiario_porcentaje
BEFORE INSERT ON llx_pld_beneficiario
FOR EACH ROW
BEGIN
  DECLARE total_porcentaje DECIMAL(5,2);
  
  SELECT COALESCE(SUM(porcentaje_participacion), 0) INTO total_porcentaje
  FROM llx_pld_beneficiario
  WHERE fk_societe = NEW.fk_societe 
    AND activo = TRUE
    AND rowid != NEW.rowid;
  
  IF (total_porcentaje + NEW.porcentaje_participacion > 100) THEN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'La suma de porcentajes excede 100%';
  END IF;
END$$
DELIMITER ;
```

---

## 💼 TABLA 2: llx_pld_operacion

### Propósito
Registro central de todas las operaciones vulnerables realizadas.

### Estructura SQL

```sql
CREATE TABLE llx_pld_operacion (
  -- Identificador
  rowid BIGINT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Referencias Dolibarr
  fk_facture INT DEFAULT NULL,
  fk_propal INT DEFAULT NULL,
  fk_commande INT DEFAULT NULL,
  fk_societe INT NOT NULL,
  fk_product INT DEFAULT NULL,
  
  -- Tipo de operación
  tipo_operacion ENUM(
    'venta_vehiculo',
    'compra_vehiculo',
    'consignacion',
    'permuta',
    'donacion',
    'otro'
  ) NOT NULL DEFAULT 'venta_vehiculo',
  
  -- Clasificación PLD
  tipo_actividad_vulnerable VARCHAR(10) NOT NULL COMMENT 'Fracción Art. 17',
  es_actividad_vulnerable BOOLEAN DEFAULT TRUE,
  
  -- Datos de la operación
  fecha_operacion DATE NOT NULL,
  mes_reportado VARCHAR(6) NOT NULL COMMENT 'YYYYMM',
  folio_interno VARCHAR(50),
  referencia_externa VARCHAR(50),
  
  -- Montos
  moneda VARCHAR(3) DEFAULT 'MXN',
  tipo_cambio DECIMAL(10,4) DEFAULT 1.0000,
  monto_original DECIMAL(15,2) NOT NULL,
  monto_mxn DECIMAL(15,2) NOT NULL,
  
  -- IVA y otros impuestos
  subtotal DECIMAL(15,2),
  iva DECIMAL(15,2),
  otros_impuestos DECIMAL(15,2),
  total_impuestos DECIMAL(15,2),
  
  -- Descripción
  descripcion_operacion TEXT NOT NULL,
  descripcion_detallada TEXT,
  observaciones TEXT,
  
  -- Umbrales
  umbral_identificacion DECIMAL(15,2) COMMENT 'Umbral vigente',
  umbral_aviso DECIMAL(15,2),
  supera_umbral_identificacion BOOLEAN DEFAULT FALSE,
  supera_umbral_aviso BOOLEAN DEFAULT FALSE,
  
  -- Acumulación
  es_operacion_acumulada BOOLEAN DEFAULT FALSE,
  periodo_acumulacion_inicio DATE,
  periodo_acumulacion_fin DATE,
  numero_operaciones_acumuladas INT DEFAULT 1,
  monto_acumulado_periodo DECIMAL(15,2),
  
  -- Forma de pago (resumen)
  usa_efectivo BOOLEAN DEFAULT FALSE,
  monto_efectivo DECIMAL(15,2) DEFAULT 0,
  supera_limite_efectivo BOOLEAN DEFAULT FALSE,
  limite_efectivo_vigente DECIMAL(15,2),
  
  -- Estado de cumplimiento
  cliente_identificado BOOLEAN DEFAULT FALSE,
  fecha_identificacion_cliente DATE,
  expediente_completo BOOLEAN DEFAULT FALSE,
  documentacion_completa BOOLEAN DEFAULT FALSE,
  
  -- Avisos
  requiere_aviso BOOLEAN DEFAULT FALSE,
  tipo_aviso_requerido ENUM('mensual', '24_horas', 'acumulado', 'ninguno'),
  aviso_presentado BOOLEAN DEFAULT FALSE,
  fk_pld_aviso INT DEFAULT NULL,
  
  -- Alertas
  genera_alerta BOOLEAN DEFAULT FALSE,
  nivel_alerta ENUM('baja', 'media', 'alta', 'critica'),
  requiere_aviso_24hrs BOOLEAN DEFAULT FALSE,
  fk_pld_alerta INT DEFAULT NULL,
  
  -- Estado
  estado ENUM(
    'borrador',
    'pendiente_identificacion',
    'pendiente_documentacion',
    'pendiente_aviso',
    'completada',
    'cancelada'
  ) DEFAULT 'borrador',
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  -- Índices
  INDEX idx_societe (fk_societe),
  INDEX idx_facture (fk_facture),
  INDEX idx_fecha (fecha_operacion),
  INDEX idx_mes_reportado (mes_reportado),
  INDEX idx_requiere_aviso (requiere_aviso),
  INDEX idx_aviso_presentado (aviso_presentado),
  INDEX idx_estado (estado),
  INDEX idx_alerta (genera_alerta),
  INDEX idx_tipo_actividad (tipo_actividad_vulnerable),
  
  -- Constraints
  CONSTRAINT fk_pld_operacion_societe 
    FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid),
  CONSTRAINT fk_pld_operacion_facture 
    FOREIGN KEY (fk_facture) REFERENCES llx_facture(rowid) ON DELETE SET NULL,
  CONSTRAINT fk_pld_operacion_product 
    FOREIGN KEY (fk_product) REFERENCES llx_product(rowid) ON DELETE SET NULL,
  CONSTRAINT chk_montos_positivos 
    CHECK (monto_original > 0 AND monto_mxn > 0),
  CONSTRAINT chk_periodo_acumulacion 
    CHECK (periodo_acumulacion_fin IS NULL OR 
           periodo_acumulacion_fin >= periodo_acumulacion_inicio)
           
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Triggers Automatizados

```sql
-- Auto-calcular monto en MXN
DELIMITER $$
CREATE TRIGGER trg_pld_operacion_calc_mxn
BEFORE INSERT ON llx_pld_operacion
FOR EACH ROW
BEGIN
  IF NEW.moneda != 'MXN' THEN
    SET NEW.monto_mxn = NEW.monto_original * NEW.tipo_cambio;
  ELSE
    SET NEW.monto_mxn = NEW.monto_original;
  END IF;
  
  -- Evaluar umbrales (UMA 2026 = $117.31)
  SET NEW.umbral_identificacion = 377778.20; -- 3,220 UMAs
  SET NEW.umbral_aviso = 377778.20;
  SET NEW.limite_efectivo_vigente = 363661.00; -- 3,100 UMAs
  
  SET NEW.supera_umbral_identificacion = (NEW.monto_mxn >= NEW.umbral_identificacion);
  SET NEW.supera_umbral_aviso = (NEW.monto_mxn >= NEW.umbral_aviso);
  SET NEW.supera_limite_efectivo = (NEW.monto_efectivo > NEW.limite_efectivo_vigente);
  
  -- Determinar si requiere aviso
  IF NEW.supera_umbral_aviso THEN
    SET NEW.requiere_aviso = TRUE;
    SET NEW.tipo_aviso_requerido = 'mensual';
  END IF;
  
  -- Generar mes reportado
  SET NEW.mes_reportado = DATE_FORMAT(NEW.fecha_operacion, '%Y%m');
END$$
DELIMITER ;
```

---

## 📄 TABLA 3: llx_pld_documento

### Propósito
Gestión centralizada de documentos digitalizados con control de versiones.

### Estructura SQL

```sql
CREATE TABLE llx_pld_documento (
  -- Identificador
  rowid BIGINT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Relaciones (al menos una debe estar presente)
  fk_societe INT DEFAULT NULL,
  fk_socpeople INT DEFAULT NULL,
  fk_pld_operacion INT DEFAULT NULL,
  fk_pld_beneficiario INT DEFAULT NULL,
  fk_product INT DEFAULT NULL,
  
  -- Tipo de documento
  categoria ENUM(
    'identificacion',
    'domicilio',
    'constitutivo',
    'legal',
    'fiscal',
    'financiero',
    'vehiculo',
    'operacion',
    'otro'
  ) NOT NULL,
  
  tipo_documento VARCHAR(100) NOT NULL,
  subtipo_documento VARCHAR(100),
  
  -- Específico para identificación
  tipo_identificacion ENUM(
    'ine', 'ife', 'pasaporte', 
    'cedula_profesional', 'fm2', 'fm3',
    'licencia_conducir', 'cartilla_militar',
    'otro'
  ) DEFAULT NULL,
  
  numero_documento VARCHAR(50),
  fecha_expedicion DATE,
  fecha_vencimiento DATE,
  autoridad_emite VARCHAR(100),
  pais_emite VARCHAR(50),
  
  -- Archivo
  nombre_archivo VARCHAR(255) NOT NULL,
  nombre_original VARCHAR(255),
  ruta_archivo VARCHAR(500) NOT NULL,
  extension VARCHAR(10),
  mime_type VARCHAR(100),
  tamano_bytes INT,
  
  -- Seguridad
  hash_sha256 VARCHAR(64) COMMENT 'Hash del archivo para integridad',
  encriptado BOOLEAN DEFAULT FALSE,
  algoritmo_encriptacion VARCHAR(50),
  
  -- Versiones
  version INT DEFAULT 1,
  fk_documento_anterior INT DEFAULT NULL,
  es_version_actual BOOLEAN DEFAULT TRUE,
  
  -- Digitalización
  fecha_digitalizacion DATE NOT NULL,
  metodo_digitalizacion ENUM('escaner', 'foto', 'pdf_digital', 'otro'),
  resolucion_dpi INT,
  fk_user_digitalizo INT,
  
  -- Validación
  verificado BOOLEAN DEFAULT FALSE,
  fecha_verificacion DATE,
  fk_user_verificador INT,
  metodo_verificacion VARCHAR(100),
  resultado_verificacion TEXT,
  
  -- OCR / Extracción de datos
  ocr_procesado BOOLEAN DEFAULT FALSE,
  fecha_ocr DATE,
  texto_extraido TEXT,
  datos_extraidos JSON COMMENT 'Datos estructurados extraídos',
  
  -- Clasificación y búsqueda
  etiquetas VARCHAR(500) COMMENT 'Tags separados por comas',
  descripcion TEXT,
  notas TEXT,
  confidencial BOOLEAN DEFAULT TRUE,
  
  -- Cumplimiento
  obligatorio BOOLEAN DEFAULT TRUE,
  requerido_para ENUM('identificacion', 'aviso', 'ambos', 'archivo'),
  fecha_limite DATE COMMENT 'Fecha límite para obtenerlo',
  
  -- Estado
  estado ENUM('pendiente', 'recibido', 'verificado', 'rechazado', 'expirado') DEFAULT 'recibido',
  motivo_rechazo TEXT,
  
  -- Retención
  fecha_retencion_hasta DATE COMMENT '10 años desde operación',
  puede_eliminar BOOLEAN DEFAULT FALSE,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  -- Índices
  INDEX idx_societe (fk_societe),
  INDEX idx_socpeople (fk_socpeople),
  INDEX idx_operacion (fk_pld_operacion),
  INDEX idx_beneficiario (fk_pld_beneficiario),
  INDEX idx_categoria (categoria),
  INDEX idx_tipo (tipo_documento),
  INDEX idx_hash (hash_sha256),
  INDEX idx_verificado (verificado),
  INDEX idx_estado (estado),
  INDEX idx_vencimiento (fecha_vencimiento),
  FULLTEXT INDEX idx_texto_extraido (texto_extraido),
  
  -- Constraints
  CONSTRAINT fk_pld_documento_societe 
    FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE CASCADE,
  CONSTRAINT fk_pld_documento_socpeople 
    FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople(rowid) ON DELETE CASCADE,
  CONSTRAINT fk_pld_documento_operacion 
    FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion(rowid) ON DELETE CASCADE,
  CONSTRAINT fk_pld_documento_beneficiario 
    FOREIGN KEY (fk_pld_beneficiario) REFERENCES llx_pld_beneficiario(rowid) ON DELETE CASCADE,
  CONSTRAINT fk_pld_documento_anterior 
    FOREIGN KEY (fk_documento_anterior) REFERENCES llx_pld_documento(rowid),
  CONSTRAINT chk_al_menos_una_relacion 
    CHECK (fk_societe IS NOT NULL OR fk_socpeople IS NOT NULL OR 
           fk_pld_operacion IS NOT NULL OR fk_pld_beneficiario IS NOT NULL OR
           fk_product IS NOT NULL)
           
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 📮 TABLA 4: llx_pld_aviso

### Propósito
Control de avisos presentados al SAT con trazabilidad completa.

### Estructura SQL

```sql
CREATE TABLE llx_pld_aviso (
  -- Identificador
  rowid BIGINT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Clasificación del aviso
  tipo_aviso ENUM(
    'mensual',
    '24_horas',
    'informe_cero',
    'acumulado',
    'rectificacion'
  ) NOT NULL,
  
  subtipo_aviso VARCHAR(50) COMMENT 'Fracción específica si aplica',
  
  -- Período reportado
  mes_reportado VARCHAR(6) NOT NULL COMMENT 'YYYYMM',
  fecha_inicio_periodo DATE,
  fecha_fin_periodo DATE,
  
  -- Datos del sujeto obligado
  rfc_sujeto_obligado VARCHAR(13) NOT NULL,
  nombre_sujeto_obligado VARCHAR(200) NOT NULL,
  clave_actividad VARCHAR(10) NOT NULL,
  
  -- Referencia y prioridad
  referencia_aviso VARCHAR(50) UNIQUE,
  prioridad ENUM('normal', 'alta', 'urgente') DEFAULT 'normal',
  
  -- Operaciones incluidas
  numero_operaciones INT DEFAULT 0,
  monto_total_operaciones DECIMAL(15,2) DEFAULT 0,
  
  -- XML Generado
  archivo_xml_nombre VARCHAR(255),
  archivo_xml_ruta VARCHAR(500),
  archivo_xml_contenido LONGTEXT,
  archivo_xml_hash VARCHAR(64),
  fecha_generacion_xml DATETIME,
  fk_user_genero_xml INT,
  
  -- Validación XML
  xml_validado BOOLEAN DEFAULT FALSE,
  fecha_validacion_xml DATETIME,
  errores_validacion TEXT,
  schema_version VARCHAR(20),
  
  -- Presentación
  presentado BOOLEAN DEFAULT FALSE,
  fecha_presentacion DATETIME,
  metodo_presentacion ENUM('portal_web', 'carga_masiva', 'api') DEFAULT 'portal_web',
  fk_user_presento INT,
  
  -- Acuse SAT
  folio_sat VARCHAR(100),
  sello_digital TEXT,
  cadena_original TEXT,
  fecha_acuse DATETIME,
  estado_acuse ENUM('aceptado', 'rechazado', 'pendiente', 'error'),
  mensaje_acuse TEXT,
  archivo_acuse_ruta VARCHAR(500),
  
  -- Seguimiento
  estado ENUM(
    'borrador',
    'generado',
    'validado',
    'enviado',
    'aceptado',
    'rechazado',
    'rectificado'
  ) DEFAULT 'borrador',
  
  -- Rectificaciones
  es_rectificacion BOOLEAN DEFAULT FALSE,
  fk_aviso_original INT DEFAULT NULL,
  motivo_rectificacion TEXT,
  
  -- Requerimientos posteriores
  tiene_requerimiento BOOLEAN DEFAULT FALSE,
  fecha_requerimiento DATE,
  folio_requerimiento VARCHAR(50),
  fecha_respuesta_requerimiento DATE,
  estado_requerimiento ENUM('pendiente', 'respondido', 'cerrado'),
  
  -- Observaciones
  observaciones TEXT,
  notas_internas TEXT,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  -- Índices
  INDEX idx_mes_reportado (mes_reportado),
  INDEX idx_tipo_aviso (tipo_aviso),
  INDEX idx_estado (estado),
  INDEX idx_presentado (presentado),
  INDEX idx_folio_sat (folio_sat),
  INDEX idx_fecha_presentacion (fecha_presentacion),
  UNIQUE INDEX idx_referencia (referencia_aviso),
  
  -- Constraints
  CONSTRAINT fk_pld_aviso_original 
    FOREIGN KEY (fk_aviso_original) REFERENCES llx_pld_aviso(rowid),
  CONSTRAINT chk_periodo_valido 
    CHECK (fecha_fin_periodo IS NULL OR fecha_fin_periodo >= fecha_inicio_periodo)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 💳 TABLA 5: llx_pld_forma_pago_detalle

### Propósito
Desglose granular de formas de pago por operación.

### Estructura SQL

```sql
CREATE TABLE llx_pld_forma_pago_detalle (
  -- Identificador
  rowid BIGINT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Relaciones
  fk_pld_operacion INT NOT NULL,
  fk_paiement INT DEFAULT NULL,
  
  -- Tipo de pago
  forma_pago ENUM(
    'efectivo',
    'transferencia',
    'cheque',
    'tarjeta_debito',
    'tarjeta_credito',
    'vale',
    'permuta',
    'dacion_pago',
    'otro'
  ) NOT NULL,
  
  -- Monto
  monto DECIMAL(15,2) NOT NULL,
  moneda VARCHAR(3) DEFAULT 'MXN',
  tipo_cambio DECIMAL(10,4) DEFAULT 1.0000,
  monto_mxn DECIMAL(15,2),
  
  -- Datos específicos: Efectivo
  denominaciones JSON COMMENT 'Desglose por billetes/monedas',
  origen_efectivo VARCHAR(200),
  
  -- Datos específicos: Transferencia
  banco_origen VARCHAR(100),
  cuenta_origen VARCHAR(4) COMMENT 'Últimos 4 dígitos',
  clabe_origen VARCHAR(18),
  banco_destino VARCHAR(100),
  cuenta_destino VARCHAR(4),
  clabe_destino VARCHAR(18),
  referencia_transferencia VARCHAR(50),
  numero_autorizacion VARCHAR(20),
  fecha_transferencia DATETIME,
  tipo_transferencia ENUM('spei', 'transferencia_local', 'internacional'),
  
  -- Datos específicos: Cheque
  banco_cheque VARCHAR(100),
  numero_cheque VARCHAR(20),
  cuenta_cheque VARCHAR(4),
  librador_cheque VARCHAR(200),
  fecha_cheque DATE,
  tipo_cheque ENUM('nominativo', 'al_portador', 'cruzado', 'certificado'),
  
  -- Datos específicos: Tarjeta
  tipo_tarjeta ENUM('debito', 'credito', 'prepago'),
  marca_tarjeta VARCHAR(50) COMMENT 'Visa, Mastercard, AMEX',
  banco_emisor VARCHAR(100),
  ultimos_digitos VARCHAR(4),
  numero_autorizacion_tarjeta VARCHAR(20),
  terminal_id VARCHAR(20),
  
  -- Comprobante
  tiene_comprobante BOOLEAN DEFAULT FALSE,
  fk_pld_documento INT DEFAULT NULL,
  
  -- Validación
  validado BOOLEAN DEFAULT FALSE,
  fecha_validacion DATE,
  fk_user_validador INT,
  
  -- Observaciones
  observaciones TEXT,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  
  -- Índices
  INDEX idx_operacion (fk_pld_operacion),
  INDEX idx_paiement (fk_paiement),
  INDEX idx_forma_pago (forma_pago),
  INDEX idx_validado (validado),
  
  -- Constraints
  CONSTRAINT fk_pld_forma_pago_operacion 
    FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion(rowid) ON DELETE CASCADE,
  CONSTRAINT fk_pld_forma_pago_paiement 
    FOREIGN KEY (fk_paiement) REFERENCES llx_paiement(rowid) ON DELETE SET NULL,
  CONSTRAINT fk_pld_forma_pago_documento 
    FOREIGN KEY (fk_pld_documento) REFERENCES llx_pld_documento(rowid),
  CONSTRAINT chk_monto_positivo 
    CHECK (monto > 0)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🚨 TABLA 6: llx_pld_alerta

### Propósito
Sistema de alertas internas y seguimiento de operaciones sospechosas.

### Estructura SQL

```sql
CREATE TABLE llx_pld_alerta (
  -- Identificador
  rowid BIGINT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Relaciones
  fk_pld_operacion INT NOT NULL,
  fk_societe INT NOT NULL,
  
  -- Clasificación de la alerta
  tipo_alerta ENUM(
    'inusual',
    'inconsistencia',
    'lista_negra',
    'pep',
    'patron_sospechoso',
    'limite_efectivo',
    'fragmentacion',
    'otro'
  ) NOT NULL,
  
  nivel_riesgo ENUM('bajo', 'medio', 'alto', 'critico') NOT NULL,
  
  -- Descripción
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT NOT NULL,
  razon_detallada TEXT,
  
  -- Indicadores que generaron la alerta
  indicadores JSON COMMENT 'Array de indicadores',
  puntaje_riesgo DECIMAL(5,2) COMMENT 'Score 0-100',
  
  -- Listas de verificación
  en_lista_pld BOOLEAN DEFAULT FALSE,
  nombre_lista VARCHAR(200),
  fecha_consulta_lista DATE,
  resultado_lista TEXT,
  
  -- PEP
  involucra_pep BOOLEAN DEFAULT FALSE,
  nombre_pep VARCHAR(200),
  cargo_pep VARCHAR(200),
  relacion_pep VARCHAR(200),
  
  -- Análisis
  requiere_analisis BOOLEAN DEFAULT TRUE,
  fecha_analisis DATE,
  fk_user_analista INT,
  resultado_analisis TEXT,
  decision ENUM('aprobar', 'rechazar', 'escalar', 'aviso_24hrs', 'monitorear') DEFAULT NULL,
  justificacion_decision TEXT,
  
  -- Acciones tomadas
  accion_tomada TEXT,
  requiere_aviso_24hrs BOOLEAN DEFAULT FALSE,
  aviso_generado BOOLEAN DEFAULT FALSE,
  fk_pld_aviso INT DEFAULT NULL,
  
  -- Seguimiento
  estado ENUM('nueva', 'en_revision', 'escalada', 'resuelta', 'archivada') DEFAULT 'nueva',
  fecha_resolucion DATE,
  
  -- Notificaciones
  usuarios_notificados TEXT COMMENT 'IDs de usuarios separados por comas',
  fecha_notificacion DATETIME,
  
  -- Observaciones
  observaciones TEXT,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  -- Índices
  INDEX idx_operacion (fk_pld_operacion),
  INDEX idx_societe (fk_societe),
  INDEX idx_tipo_alerta (tipo_alerta),
  INDEX idx_nivel_riesgo (nivel_riesgo),
  INDEX idx_estado (estado),
  INDEX idx_pep (involucra_pep),
  INDEX idx_aviso_24hrs (requiere_aviso_24hrs),
  
  -- Constraints
  CONSTRAINT fk_pld_alerta_operacion 
    FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion(rowid) ON DELETE CASCADE,
  CONSTRAINT fk_pld_alerta_societe 
    FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid),
  CONSTRAINT fk_pld_alerta_aviso 
    FOREIGN KEY (fk_pld_aviso) REFERENCES llx_pld_aviso(rowid)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 📚 TABLA 7: llx_pld_catalogo

### Propósito
Almacenar catálogos oficiales SAT y referencias normativas.

### Estructura SQL

```sql
CREATE TABLE llx_pld_catalogo (
  -- Identificador
  rowid BIGINT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Tipo de catálogo
  tipo_catalogo ENUM(
    'actividad_economica',
    'forma_juridica',
    'tipo_identificacion',
    'banco',
    'marca_vehiculo',
    'pais',
    'estado',
    'municipio',
    'colonia',
    'tipo_alerta',
    'lista_pld',
    'otro'
  ) NOT NULL,
  
  -- Clave y descripción
  clave VARCHAR(50) NOT NULL,
  descripcion VARCHAR(500) NOT NULL,
  descripcion_corta VARCHAR(100),
  
  -- Jerarquía (para catálogos anidados)
  fk_catalogo_padre INT DEFAULT NULL,
  nivel INT DEFAULT 1,
  
  -- Datos adicionales
  datos_json JSON COMMENT 'Datos adicionales específicos',
  
  -- Vigencia
  activo BOOLEAN DEFAULT TRUE,
  fecha_inicio DATE,
  fecha_fin DATE,
  
  -- Origen
  fuente VARCHAR(200) COMMENT 'SAT, SEPOMEX, Dolibarr, etc.',
  version_catalogo VARCHAR(20),
  fecha_actualizacion DATE,
  
  -- Orden
  orden INT DEFAULT 999,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Índices
  INDEX idx_tipo (tipo_catalogo),
  INDEX idx_clave (clave),
  INDEX idx_activo (activo),
  INDEX idx_padre (fk_catalogo_padre),
  UNIQUE INDEX idx_tipo_clave (tipo_catalogo, clave),
  
  -- Constraints
  CONSTRAINT fk_pld_catalogo_padre 
    FOREIGN KEY (fk_catalogo_padre) REFERENCES llx_pld_catalogo(rowid)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔗 TABLAS RELACIONALES ADICIONALES

### Tabla: llx_pld_operacion_documento

```sql
CREATE TABLE llx_pld_operacion_documento (
  rowid BIGINT PRIMARY KEY AUTO_INCREMENT,
  fk_pld_operacion INT NOT NULL,
  fk_pld_documento INT NOT NULL,
  tipo_relacion ENUM('soporte', 'comprobante', 'identificacion', 'otro') NOT NULL,
  obligatorio BOOLEAN DEFAULT FALSE,
  datec DATETIME NOT NULL,
  
  INDEX idx_operacion (fk_pld_operacion),
  INDEX idx_documento (fk_pld_documento),
  
  CONSTRAINT fk_operdoc_operacion 
    FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion(rowid) ON DELETE CASCADE,
  CONSTRAINT fk_operdoc_documento 
    FOREIGN KEY (fk_pld_documento) REFERENCES llx_pld_documento(rowid) ON DELETE CASCADE
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tabla: llx_pld_aviso_operacion

```sql
CREATE TABLE llx_pld_aviso_operacion (
  rowid BIGINT PRIMARY KEY AUTO_INCREMENT,
  fk_pld_aviso INT NOT NULL,
  fk_pld_operacion INT NOT NULL,
  secuencia INT COMMENT 'Orden en el XML',
  datec DATETIME NOT NULL,
  
  INDEX idx_aviso (fk_pld_aviso),
  INDEX idx_operacion (fk_pld_operacion),
  UNIQUE INDEX idx_aviso_operacion (fk_pld_aviso, fk_pld_operacion),
  
  CONSTRAINT fk_avisooper_aviso 
    FOREIGN KEY (fk_pld_aviso) REFERENCES llx_pld_aviso(rowid) ON DELETE CASCADE,
  CONSTRAINT fk_avisooper_operacion 
    FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion(rowid) ON DELETE CASCADE
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🎯 PARTE 8: Reglas de Negocio Automáticas

### Trigger: Crear Operación desde Factura

```sql
DELIMITER $
CREATE TRIGGER trg_facture_create_pld_operacion
AFTER INSERT ON llx_facture
FOR EACH ROW
BEGIN
  DECLARE v_monto_total DECIMAL(15,2);
  DECLARE v_umbral DECIMAL(15,2);
  
  SET v_monto_total = NEW.total_ttc;
  SET v_umbral = 377778.20; -- 3,220 UMAs 2026
  
  -- Solo crear operación PLD si supera umbral
  IF v_monto_total >= v_umbral THEN
    INSERT INTO llx_pld_operacion (
      entity, fk_facture, fk_societe,
      tipo_operacion, tipo_actividad_vulnerable,
      fecha_operacion, mes_reportado,
      monto_original, monto_mxn,
      descripcion_operacion,
      requiere_aviso,
      datec, fk_user_creat
    ) VALUES (
      NEW.entity, NEW.rowid, NEW.fk_soc,
      'venta_vehiculo', 'VIII',
      NEW.datef, DATE_FORMAT(NEW.datef, '%Y%m'),
      v_monto_total, v_monto_total,
      CONCAT('Factura ', NEW.ref),
      TRUE,
      NOW(), NEW.fk_user_author
    );
  END IF;
END$
DELIMITER ;
```

### Stored Procedure: Calcular Acumulación 6 Meses

```sql
DELIMITER $
CREATE PROCEDURE sp_calcular_acumulacion_cliente(
  IN p_fk_societe INT,
  IN p_fecha_operacion DATE,
  OUT p_monto_acumulado DECIMAL(15,2),
  OUT p_supera_umbral BOOLEAN
)
BEGIN
  DECLARE v_fecha_inicio DATE;
  DECLARE v_umbral DECIMAL(15,2);
  
  SET v_fecha_inicio = DATE_SUB(p_fecha_operacion, INTERVAL 6 MONTH);
  SET v_umbral = 377778.20;
  
  SELECT COALESCE(SUM(monto_mxn), 0) INTO p_monto_acumulado
  FROM llx_pld_operacion
  WHERE fk_societe = p_fk_societe
    AND fecha_operacion BETWEEN v_fecha_inicio AND p_fecha_operacion
    AND estado != 'cancelada';
  
  SET p_supera_umbral = (p_monto_acumulado >= v_umbral);
END$
DELIMITER ;
```

### Function: Validar Beneficiario Controlador

```sql
DELIMITER $
CREATE FUNCTION fn_requiere_beneficiario(p_fk_societe INT)
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
  DECLARE v_tipo_persona VARCHAR(20);
  DECLARE v_requiere BOOLEAN;
  
  SELECT pld_tipo_persona INTO v_tipo_persona
  FROM llx_societe_extrafields
  WHERE fk_object = p_fk_societe;
  
  -- Solo personas morales requieren beneficiario
  SET v_requiere = (v_tipo_persona = 'moral');
  
  RETURN v_requiere;
END$
DELIMITER ;
```

---

## 📊 PARTE 9: Vistas Útiles

### Vista: Operaciones Pendientes de Aviso

```sql
CREATE OR REPLACE VIEW v_pld_operaciones_pendientes AS
SELECT 
  o.rowid,
  o.folio_interno,
  o.fecha_operacion,
  o.mes_reportado,
  s.nom AS cliente,
  s.code_client,
  o.monto_mxn,
  o.tipo_aviso_requerido,
  DATEDIFF(CURDATE(), o.fecha_operacion) AS dias_transcurridos,
  CASE 
    WHEN DATEDIFF(CURDATE(), o.fecha_operacion) > 17 THEN 'VENCIDO'
    WHEN DATEDIFF(CURDATE(), o.fecha_operacion) > 10 THEN 'URGENTE'
    ELSE 'PENDIENTE'
  END AS prioridad
FROM llx_pld_operacion o
INNER JOIN llx_societe s ON s.rowid = o.fk_societe
WHERE o.requiere_aviso = TRUE
  AND o.aviso_presentado = FALSE
  AND o.estado != 'cancelada'
ORDER BY o.fecha_operacion ASC;
```

### Vista: Dashboard Cumplimiento

```sql
CREATE OR REPLACE VIEW v_pld_dashboard AS
SELECT 
  DATE_FORMAT(CURDATE(), '%Y%m') AS mes_actual,
  COUNT(*) AS total_operaciones,
  SUM(CASE WHEN requiere_aviso THEN 1 ELSE 0 END) AS operaciones_reportables,
  SUM(CASE WHEN aviso_presentado THEN 1 ELSE 0 END) AS avisos_presentados,
  SUM(CASE WHEN requiere_aviso AND NOT aviso_presentado THEN 1 ELSE 0 END) AS avisos_pendientes,
  SUM(monto_mxn) AS monto_total_mes,
  COUNT(DISTINCT fk_societe) AS clientes_unicos,
  SUM(CASE WHEN genera_alerta THEN 1 ELSE 0 END) AS alertas_generadas,
  SUM(CASE WHEN requiere_aviso_24hrs THEN 1 ELSE 0 END) AS avisos_urgentes
FROM llx_pld_operacion
WHERE mes_reportado = DATE_FORMAT(CURDATE(), '%Y%m')
  AND estado != 'cancelada';
```

---

## 🛠️ PARTE 10: Plan de Implementación Fase 2

### Cronograma (8 semanas)

#### Semana 1-2: Diseño y Preparación
- [ ] Revisar y aprobar diseño de tablas
- [ ] Crear scripts SQL completos
- [ ] Preparar entorno de testing
- [ ] Documentar modelo de datos

#### Semana 3-4: Implementación Core
- [ ] Crear tablas principales (beneficiario, operacion, documento)
- [ ] Implementar triggers y stored procedures
- [ ] Crear vistas y funciones
- [ ] Pruebas unitarias de BD

#### Semana 5: Implementación Complementaria
- [ ] Crear tablas de aviso y forma_pago_detalle
- [ ] Implementar tabla de alertas
- [ ] Cargar catálogos iniciales
- [ ] Pruebas de integridad referencial

#### Semana 6: Integración con Fase 1
- [ ] Conectar extrafields con nuevas tablas
- [ ] Migrar datos existentes (si hay)
- [ ] Validar flujos completos
- [ ] Ajustes y correcciones

#### Semana 7: Interfaz de Usuario
- [ ] Formularios para beneficiarios
- [ ] Pantalla de gestión de operaciones
- [ ] Módulo de documentos
- [ ] Dashboard de alertas

#### Semana 8: Testing Final
- [ ] Pruebas funcionales completas
- [ ] Pruebas de carga
- [ ] Corrección de bugs
- [ ] Capacitación usuarios

---

## ✅ Checklist de Completitud Fase 2

### Beneficiarios Controladores
- [ ] Registrar beneficiarios de personas morales
- [ ] Validar suma de porcentajes ≤ 100%
- [ ] Árbol de estructura corporativa
- [ ] Identificación de PEPs
- [ ] Documentación de beneficiarios

### Operaciones
- [ ] Auto-registro desde facturas
- [ ] Cálculo automático de umbrales
- [ ] Evaluación de acumulaciones
- [ ] Generación de alertas
- [ ] Control de estados

### Documentos
- [ ] Upload de archivos
- [ ] Generación de hash SHA256
- [ ] OCR y extracción de datos
- [ ] Control de versiones
- [ ] Retención por 10 años

### Avisos
- [ ] Generación de referencia única
- [ ] Creación de XML
- [ ] Validación contra XSD
- [ ] Registro de acuses SAT
- [ ] Trazabilidad completa

---

## 📈 Métricas de Éxito Fase 2

### KPIs Técnicos
- ✅ 100% integridad referencial
- ✅ Tiempo respuesta < 2 seg en consultas
- ✅ 0 errores en triggers automáticos
- ✅ Backup diario automático

### KPIs Funcionales
- ✅ 100% operaciones con beneficiario (si aplica)
- ✅ 100% documentos con hash verificado
- ✅ Alertas generadas en < 1 minuto
- ✅ Dashboard actualizado en tiempo real

---

## 🚀 Siguientes Pasos (Post Fase 2)

### Fase 3: Generador de XML
- Motor de generación XML según XSD SAT
- Validador automático
- Integración con e.firma
- Envío automático SPPLD

### Fase 4: Inteligencia y Automatización
- Machine Learning para detección de patrones
- Sistema experto de evaluación de riesgos
- Generación automática de avisos
- Dashboard ejecutivo avanzado

---

## 💡 Consideraciones Finales

### Seguridad
- Encriptación de datos sensibles
- Logs de auditoría completos
- Control de acceso granular
- Backup automático diario

### Performance
- Índices optimizados
- Particionamiento de tablas grandes
- Archivado de datos históricos
- Caché de catálogos

### Escalabilidad
- Diseño preparado para múltiples entidades
- Soporte para millones de registros
- API para integraciones futuras
- Modular y extensible

**Inversión estimada Fase 2:**
- Desarrollo: 200-240 horas
- Testing: 60 horas
- Capacitación: 40 horas
- **Total: 300-340 horas** (8-9 semanas)