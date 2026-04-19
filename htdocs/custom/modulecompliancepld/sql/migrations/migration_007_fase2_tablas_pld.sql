-- ============================================================================
-- Migración 007: Fase 2 — Tablas Especializadas PLD
-- ============================================================================
-- Módulo: ModulecompliancePLD
-- Fecha: Febrero 2026
-- Descripción: Creación de 5 tablas especializadas + 1 tabla relacional
--              para compliance PLD según LFPIORPI Art. 17 Fracc. VIII
-- Versión Plan: 2.0 (Enfoque híbrido ECM)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- TABLA 1: llx_pld_operacion
-- Propósito: Registro central de operaciones vulnerables
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS llx_pld_operacion (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,

  -- Referencias Dolibarr
  fk_facture INT DEFAULT NULL,
  fk_societe INT NOT NULL,
  fk_product INT DEFAULT NULL,

  -- Tipo de operación
  tipo_operacion VARCHAR(50) NOT NULL DEFAULT 'venta_vehiculo',
  tipo_actividad_vulnerable VARCHAR(10) NOT NULL,

  -- Datos de la operación
  fecha_operacion DATE NOT NULL,
  mes_reportado VARCHAR(6) NOT NULL,
  folio_interno VARCHAR(50),

  -- Montos
  moneda VARCHAR(3) DEFAULT 'MXN',
  monto_mxn DECIMAL(15,2) NOT NULL,

  -- Umbrales (calculados en PHP, no triggers SQL)
  supera_umbral tinyint DEFAULT 0,

  -- Estado de cumplimiento
  cliente_identificado tinyint DEFAULT 0,
  documentacion_completa tinyint DEFAULT 0,

  -- Avisos
  requiere_aviso tinyint DEFAULT 0,
  aviso_presentado tinyint DEFAULT 0,
  fk_pld_aviso INT DEFAULT NULL,

  -- Alertas
  genera_alerta tinyint DEFAULT 0,
  fk_pld_alerta INT DEFAULT NULL,

  -- Estado: borrador|pendiente_documentacion|completada|cancelada
  estado VARCHAR(50) DEFAULT 'borrador',

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
  INDEX idx_estado (estado)

) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- TABLA 2: llx_pld_beneficiario
-- Propósito: Beneficiarios controladores de personas morales (Art. 18 LFPIORPI)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS llx_pld_beneficiario (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,

  -- Relación
  fk_societe INT NOT NULL,
  fk_socpeople INT DEFAULT NULL,

  -- Tipo: accionista|fideicomitente|fideicomisario|administrador
  tipo_beneficiario VARCHAR(50) NOT NULL,

  -- Datos personales (si fk_socpeople = NULL, se capturan aquí)
  nombre VARCHAR(100),
  apellido_paterno VARCHAR(100),
  apellido_materno VARCHAR(100),

  -- Identificación
  curp VARCHAR(18),
  rfc VARCHAR(13),
  fecha_nacimiento DATE,
  nacionalidad VARCHAR(50),

  -- Participación (máximo 100.00)
  porcentaje_participacion DECIMAL(5,2) NOT NULL,

  -- PEP (Persona Expuesta Políticamente)
  es_pep tinyint DEFAULT 0,
  cargo_pep VARCHAR(200),

  -- Validación
  verificado tinyint DEFAULT 0,
  fecha_verificacion DATE,
  fk_user_verificador INT,

  -- Vigencia
  activo tinyint DEFAULT 1,

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
  INDEX idx_activo (activo)

) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- TABLA 3: llx_pld_documento
-- Propósito: Metadatos PLD que complementan llx_ecm_files (enfoque híbrido)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS llx_pld_documento (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,

  -- Vínculo al archivo en ECM (obligatorio)
  fk_ecm_files INT NOT NULL,

  -- Relación opcional con entidades Dolibarr
  fk_societe INT DEFAULT NULL,
  fk_socpeople INT DEFAULT NULL,

  -- Tipo: INE|pasaporte|acta_constitutiva|comprobante_domicilio
  tipo_documento_pld VARCHAR(100) NOT NULL,

  -- Datos del documento
  numero_documento VARCHAR(50),
  fecha_emision DATE,
  fecha_vencimiento DATE,
  autoridad_emite VARCHAR(100),

  -- Validación PLD
  verificado tinyint DEFAULT 0,
  fecha_verificacion DATE,
  fk_user_verificador INT,

  -- Retención regulatoria (10 años Art. 20 LFPIORPI + Trans. 7 DOF 2026)
  fecha_retencion_hasta DATE,

  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,

  -- Índices
  INDEX idx_ecm_files (fk_ecm_files),
  INDEX idx_societe (fk_societe),
  INDEX idx_socpeople (fk_socpeople),
  INDEX idx_tipo (tipo_documento_pld),
  INDEX idx_verificado (verificado),
  INDEX idx_vencimiento (fecha_vencimiento)

) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- TABLA 4: llx_pld_aviso
-- Propósito: Control de avisos presentados al SAT SPPLD
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS llx_pld_aviso (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,

  -- Tipo: mensual|24_horas|acumulado
  tipo_aviso VARCHAR(50) NOT NULL,

  -- Período reportado (YYYYMM)
  mes_reportado VARCHAR(6) NOT NULL,
  fecha_inicio_periodo DATE,
  fecha_fin_periodo DATE,

  -- Referencia única del aviso
  referencia_aviso VARCHAR(50) UNIQUE,

  -- Operaciones incluidas
  numero_operaciones INT DEFAULT 0,
  monto_total_operaciones DECIMAL(15,2) DEFAULT 0,

  -- XML Generado
  archivo_xml_ruta VARCHAR(500),
  archivo_xml_hash VARCHAR(64),
  fecha_generacion_xml DATETIME,

  -- Presentación
  presentado tinyint DEFAULT 0,
  fecha_presentacion DATETIME,
  fk_user_presento INT,

  -- Acuse SAT
  folio_sat VARCHAR(100),
  fecha_acuse DATETIME,
  -- estado_acuse: aceptado|rechazado|pendiente
  estado_acuse VARCHAR(50),

  -- Estado general: borrador|generado|enviado|aceptado|rechazado
  estado VARCHAR(50) DEFAULT 'borrador',

  -- Observaciones
  observaciones TEXT,

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
  UNIQUE INDEX idx_referencia (referencia_aviso)

) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- TABLA 5: llx_pld_alerta
-- Propósito: Sistema de alertas internas para operaciones sospechosas
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS llx_pld_alerta (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,

  -- Relaciones
  fk_pld_operacion INT NOT NULL,
  fk_societe INT NOT NULL,

  -- Clasificación: tipo=inusual|inconsistencia|pep|limite_efectivo
  tipo_alerta VARCHAR(100) NOT NULL,
  -- nivel=bajo|medio|alto|critico
  nivel_riesgo VARCHAR(50) NOT NULL,

  -- Descripción
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT NOT NULL,

  -- PEP
  involucra_pep tinyint DEFAULT 0,

  -- Análisis
  requiere_analisis tinyint DEFAULT 1,
  fecha_analisis DATE,
  fk_user_analista INT,
  -- decision: aprobar|rechazar|escalar|aviso_24hrs
  decision VARCHAR(50),

  -- Estado: abierta|en_revision|resuelta|archivada
  estado VARCHAR(50) DEFAULT 'abierta',
  fecha_resolucion DATE,

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
  INDEX idx_estado (estado)

) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- TABLA RELACIONAL: llx_pld_aviso_operacion
-- Propósito: Relación N:M entre avisos y operaciones
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS llx_pld_aviso_operacion (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  fk_pld_aviso INT NOT NULL,
  fk_pld_operacion INT NOT NULL,
  datec DATETIME,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  import_key VARCHAR(14) DEFAULT NULL,

  INDEX idx_aviso (fk_pld_aviso),
  INDEX idx_operacion (fk_pld_operacion),
  UNIQUE INDEX idx_aviso_operacion (fk_pld_aviso, fk_pld_operacion)

) ENGINE=InnoDB;

-- ============================================================================
-- FIN DE MIGRACIÓN 007
-- ============================================================================
