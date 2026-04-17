-- Module: CompliancePLD
-- Description: Control de avisos presentados al SAT SPPLD

CREATE TABLE IF NOT EXISTS llx_pld_aviso (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Clasificación del aviso
  tipo_aviso VARCHAR(50) NOT NULL,                               -- mensual|24_horas|acumulado
  
  -- Período reportado
  mes_reportado VARCHAR(6) NOT NULL,                             -- YYYYMM
  fecha_inicio_periodo DATE,
  fecha_fin_periodo DATE,
  
  -- Referencia única del aviso
  referencia_aviso VARCHAR(50),
  
  -- Operaciones incluidas
  numero_operaciones INT DEFAULT 0,
  monto_total_operaciones DECIMAL(15,2) DEFAULT 0,
  
  -- XML Generado
  archivo_xml_ruta VARCHAR(500),                                 -- Ruta al archivo XML en documents/
  archivo_xml_hash VARCHAR(64),                                  -- SHA256 del XML para integridad
  fecha_generacion_xml DATETIME,
  
  -- Presentación
  presentado tinyint DEFAULT 0,
  fecha_presentacion DATETIME,
  fk_user_presento INT,
  
  -- Acuse SAT
  folio_sat VARCHAR(100),
  fecha_acuse DATETIME,
  estado_acuse VARCHAR(50),                                      -- aceptado|rechazado|pendiente
  
  -- Estado general
  estado VARCHAR(50) DEFAULT 'borrador',                         -- borrador|generado|enviado|aceptado|rechazado
  
  -- Observaciones
  observaciones TEXT,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  import_key VARCHAR(14) DEFAULT NULL

) ENGINE=InnoDB;
