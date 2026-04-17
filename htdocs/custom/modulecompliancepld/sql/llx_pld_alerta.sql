-- Module: CompliancePLD
-- Description: Sistema de alertas internas para operaciones sospechosas

CREATE TABLE IF NOT EXISTS llx_pld_alerta (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Relaciones
  fk_pld_operacion INT NOT NULL,
  fk_societe INT NOT NULL,
  
  -- Clasificación de la alerta
  tipo_alerta VARCHAR(100) NOT NULL,                             -- inusual|inconsistencia|pep|limite_efectivo
  nivel_riesgo VARCHAR(50) NOT NULL,                             -- bajo|medio|alto|critico
  
  -- Descripción
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT NOT NULL,
  
  -- PEP
  involucra_pep tinyint DEFAULT 0,
  
  -- Análisis
  requiere_analisis tinyint DEFAULT 1,
  fecha_analisis DATE,
  fk_user_analista INT,
  decision VARCHAR(50),                                          -- aprobar|rechazar|escalar|aviso_24hrs
  
  -- Estado
  estado VARCHAR(50) DEFAULT 'abierta',                          -- abierta|en_revision|resuelta|archivada
  fecha_resolucion DATE,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  import_key VARCHAR(14) DEFAULT NULL

) ENGINE=InnoDB;
