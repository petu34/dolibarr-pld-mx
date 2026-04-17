-- Module: CompliancePLD
-- Description: Relación N:M entre avisos y operaciones

CREATE TABLE IF NOT EXISTS llx_pld_aviso_operacion (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  fk_pld_aviso INT NOT NULL,
  fk_pld_operacion INT NOT NULL,
  datec DATETIME,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  import_key VARCHAR(14) DEFAULT NULL

) ENGINE=InnoDB;
