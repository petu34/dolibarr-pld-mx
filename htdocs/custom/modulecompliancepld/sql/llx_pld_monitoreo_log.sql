-- Module: CompliancePLD
-- Description: Log de monitoreo automatizado PLD — registro de evaluaciones de perfil, PEP y alto riesgo

CREATE TABLE IF NOT EXISTS llx_pld_monitoreo_log (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,

  tipo_evaluacion VARCHAR(50) NOT NULL,

  fk_societe INT,
  fk_pld_operacion INT,
  fk_pld_alerta INT,

  resultado VARCHAR(50) NOT NULL,
  nivel_riesgo_detectado VARCHAR(50),
  detalle TEXT,

  z_score DECIMAL(5,2),
  variacion_porcentual DECIMAL(5,1),
  supera_umbral_perfil tinyint DEFAULT 0,

  genero_alerta tinyint DEFAULT 0,

  datec DATETIME NOT NULL,

  INDEX idx_pld_monitoreo_tipo (tipo_evaluacion),
  INDEX idx_pld_monitoreo_societe (fk_societe),
  INDEX idx_pld_monitoreo_fecha (datec),
  INDEX idx_pld_monitoreo_resultado (resultado)
) ENGINE=InnoDB;
