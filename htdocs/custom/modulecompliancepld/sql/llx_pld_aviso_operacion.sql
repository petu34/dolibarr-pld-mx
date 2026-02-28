-- Module: CompliancePLD
-- Description: Relación N:M entre avisos y operaciones

CREATE TABLE IF NOT EXISTS llx_pld_aviso_operacion (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  fk_pld_aviso INT NOT NULL,
  fk_pld_operacion INT NOT NULL
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
