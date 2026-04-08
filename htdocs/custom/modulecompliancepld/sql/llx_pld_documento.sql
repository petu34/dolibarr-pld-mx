-- Module: CompliancePLD
-- Description: Metadatos PLD que complementan llx_ecm_files (enfoque híbrido)

CREATE TABLE IF NOT EXISTS llx_pld_documento (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Vínculo al archivo en ECM (obligatorio)
  fk_ecm_files INT NOT NULL,
  
  -- Relación opcional con entidades Dolibarr
  fk_societe INT DEFAULT NULL,
  fk_socpeople INT DEFAULT NULL,
  
  -- Tipo de documento PLD
  tipo_documento_pld VARCHAR(100) NOT NULL,                       -- INE|pasaporte|acta_constitutiva|comprobante_domicilio
  
  -- Datos del documento
  numero_documento VARCHAR(50),
  fecha_emision DATE,
  fecha_vencimiento DATE,                                         -- Para IDs con vigencia
  autoridad_emite VARCHAR(100),
  
  -- Validación PLD
  verificado tinyint DEFAULT 0,
  fecha_verificacion DATE,
  fk_user_verificador INT,
  
  -- Retención regulatoria (5 años Art. 18 LFPIORPI)
  fecha_retencion_hasta DATE,                                     -- Auto-calculado: fecha_emision + 5 años
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
