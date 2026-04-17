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
  
  -- Retención regulatoria (10 años Art. 20 LFPIORPI + Transitorio 7 DOF 2026)
  fecha_retencion_hasta DATE,

  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  import_key VARCHAR(14) DEFAULT NULL

) ENGINE=InnoDB;
