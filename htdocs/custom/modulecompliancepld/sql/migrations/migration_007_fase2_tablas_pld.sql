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
  fk_product INT DEFAULT NULL COMMENT 'ID del vehículo',
  
  -- Tipo de operación
  tipo_operacion VARCHAR(50) NOT NULL DEFAULT 'venta_vehiculo',
  tipo_actividad_vulnerable VARCHAR(10) NOT NULL COMMENT 'Fracción Art. 17 LFPIORPI',
  
  -- Datos de la operación
  fecha_operacion DATE NOT NULL,
  mes_reportado VARCHAR(6) NOT NULL COMMENT 'YYYYMM para agrupación',
  folio_interno VARCHAR(50),
  
  -- Montos
  moneda VARCHAR(3) DEFAULT 'MXN',
  monto_mxn DECIMAL(15,2) NOT NULL,
  
  -- Umbrales (calculados en PHP, no triggers SQL)
  supera_umbral TINYINT(1) DEFAULT 0,
  
  -- Estado de cumplimiento
  cliente_identificado TINYINT(1) DEFAULT 0,
  documentacion_completa TINYINT(1) DEFAULT 0,
  
  -- Avisos
  requiere_aviso TINYINT(1) DEFAULT 0,
  aviso_presentado TINYINT(1) DEFAULT 0,
  fk_pld_aviso INT DEFAULT NULL,
  
  -- Alertas
  genera_alerta TINYINT(1) DEFAULT 0,
  fk_pld_alerta INT DEFAULT NULL,
  
  -- Estado
  estado VARCHAR(50) DEFAULT 'borrador' COMMENT 'borrador|pendiente_documentacion|completada|cancelada',
  
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
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLA 2: llx_pld_beneficiario
-- Propósito: Beneficiarios controladores de personas morales (Art. 18 LFPIORPI)
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS llx_pld_beneficiario (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Relación
  fk_societe INT NOT NULL COMMENT 'Empresa a la que pertenece el beneficiario',
  fk_socpeople INT DEFAULT NULL COMMENT 'Contacto Dolibarr si existe',
  
  -- Tipo de beneficiario
  tipo_beneficiario VARCHAR(50) NOT NULL COMMENT 'accionista|fideicomitente|fideicomisario|administrador',
  
  -- Datos personales (si fk_socpeople = NULL, se capturan aquí)
  nombre VARCHAR(100),
  apellido_paterno VARCHAR(100),
  apellido_materno VARCHAR(100),
  
  -- Identificación
  curp VARCHAR(18),
  rfc VARCHAR(13),
  fecha_nacimiento DATE,
  nacionalidad VARCHAR(50),
  
  -- Participación
  porcentaje_participacion DECIMAL(5,2) NOT NULL COMMENT 'Máximo 100.00',
  
  -- PEP (Persona Expuesta Políticamente)
  es_pep TINYINT(1) DEFAULT 0,
  cargo_pep VARCHAR(200),
  
  -- Validación
  verificado TINYINT(1) DEFAULT 0,
  fecha_verificacion DATE,
  fk_user_verificador INT,
  
  -- Vigencia
  activo TINYINT(1) DEFAULT 1,
  
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
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  
  -- Tipo de documento PLD
  tipo_documento_pld VARCHAR(100) NOT NULL COMMENT 'INE|pasaporte|acta_constitutiva|comprobante_domicilio',
  
  -- Datos del documento
  numero_documento VARCHAR(50),
  fecha_emision DATE,
  fecha_vencimiento DATE COMMENT 'Para IDs con vigencia',
  autoridad_emite VARCHAR(100),
  
  -- Validación PLD
  verificado TINYINT(1) DEFAULT 0,
  fecha_verificacion DATE,
  fk_user_verificador INT,
  
  -- Retención regulatoria (5 años Art. 18 LFPIORPI)
  fecha_retencion_hasta DATE COMMENT 'Auto-calculado: fecha_emision + 5 años',
  
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
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLA 4: llx_pld_aviso
-- Propósito: Control de avisos presentados al SAT SPPLD
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS llx_pld_aviso (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Clasificación del aviso
  tipo_aviso VARCHAR(50) NOT NULL COMMENT 'mensual|24_horas|acumulado',
  
  -- Período reportado
  mes_reportado VARCHAR(6) NOT NULL COMMENT 'YYYYMM',
  fecha_inicio_periodo DATE,
  fecha_fin_periodo DATE,
  
  -- Referencia única del aviso
  referencia_aviso VARCHAR(50) UNIQUE,
  
  -- Operaciones incluidas
  numero_operaciones INT DEFAULT 0,
  monto_total_operaciones DECIMAL(15,2) DEFAULT 0,
  
  -- XML Generado
  archivo_xml_ruta VARCHAR(500) COMMENT 'Ruta al archivo XML en documents/',
  archivo_xml_hash VARCHAR(64) COMMENT 'SHA256 del XML para integridad',
  fecha_generacion_xml DATETIME,
  
  -- Presentación
  presentado TINYINT(1) DEFAULT 0,
  fecha_presentacion DATETIME,
  fk_user_presento INT,
  
  -- Acuse SAT
  folio_sat VARCHAR(100),
  fecha_acuse DATETIME,
  estado_acuse VARCHAR(50) COMMENT 'aceptado|rechazado|pendiente',
  
  -- Estado general
  estado VARCHAR(50) DEFAULT 'borrador' COMMENT 'borrador|generado|enviado|aceptado|rechazado',
  
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
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  
  -- Clasificación de la alerta
  tipo_alerta VARCHAR(100) NOT NULL COMMENT 'inusual|inconsistencia|pep|limite_efectivo',
  nivel_riesgo VARCHAR(50) NOT NULL COMMENT 'bajo|medio|alto|critico',
  
  -- Descripción
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT NOT NULL,
  
  -- PEP
  involucra_pep TINYINT(1) DEFAULT 0,
  
  -- Análisis
  requiere_analisis TINYINT(1) DEFAULT 1,
  fecha_analisis DATE,
  fk_user_analista INT,
  decision VARCHAR(50) COMMENT 'aprobar|rechazar|escalar|aviso_24hrs',
  
  -- Estado
  estado VARCHAR(50) DEFAULT 'nueva' COMMENT 'nueva|en_revision|resuelta|archivada',
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
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLA RELACIONAL: llx_pld_aviso_operacion
-- Propósito: Relación N:M entre avisos y operaciones
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS llx_pld_aviso_operacion (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  fk_pld_aviso INT NOT NULL,
  fk_pld_operacion INT NOT NULL,
  
  INDEX idx_aviso (fk_pld_aviso),
  INDEX idx_operacion (fk_pld_operacion),
  UNIQUE INDEX idx_aviso_operacion (fk_pld_aviso, fk_pld_operacion)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- FOREIGN KEYS (se agregan después de crear las tablas)
-- ============================================================================

-- llx_pld_operacion
ALTER TABLE llx_pld_operacion
  ADD CONSTRAINT IF NOT EXISTS fk_pld_operacion_societe 
    FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE RESTRICT,
  ADD CONSTRAINT IF NOT EXISTS fk_pld_operacion_facture 
    FOREIGN KEY (fk_facture) REFERENCES llx_facture(rowid) ON DELETE SET NULL,
  ADD CONSTRAINT IF NOT EXISTS fk_pld_operacion_product 
    FOREIGN KEY (fk_product) REFERENCES llx_product(rowid) ON DELETE SET NULL;

-- llx_pld_beneficiario
ALTER TABLE llx_pld_beneficiario
  ADD CONSTRAINT IF NOT EXISTS fk_pld_beneficiario_societe 
    FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE CASCADE,
  ADD CONSTRAINT IF NOT EXISTS fk_pld_beneficiario_socpeople 
    FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople(rowid) ON DELETE SET NULL;

-- llx_pld_documento
ALTER TABLE llx_pld_documento
  ADD CONSTRAINT IF NOT EXISTS fk_pld_documento_ecm_files 
    FOREIGN KEY (fk_ecm_files) REFERENCES llx_ecm_files(rowid) ON DELETE CASCADE,
  ADD CONSTRAINT IF NOT EXISTS fk_pld_documento_societe 
    FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE CASCADE,
  ADD CONSTRAINT IF NOT EXISTS fk_pld_documento_socpeople 
    FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople(rowid) ON DELETE CASCADE;

-- llx_pld_alerta
ALTER TABLE llx_pld_alerta
  ADD CONSTRAINT IF NOT EXISTS fk_pld_alerta_operacion 
    FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion(rowid) ON DELETE CASCADE,
  ADD CONSTRAINT IF NOT EXISTS fk_pld_alerta_societe 
    FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE RESTRICT;

-- llx_pld_aviso_operacion
ALTER TABLE llx_pld_aviso_operacion
  ADD CONSTRAINT IF NOT EXISTS fk_pld_aviso_operacion_aviso 
    FOREIGN KEY (fk_pld_aviso) REFERENCES llx_pld_aviso(rowid) ON DELETE CASCADE,
  ADD CONSTRAINT IF NOT EXISTS fk_pld_aviso_operacion_operacion 
    FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion(rowid) ON DELETE CASCADE;

-- ============================================================================
-- FIN DE MIGRACIÓN 007
-- ============================================================================
