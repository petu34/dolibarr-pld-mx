-- Module: CompliancePLD
-- Description: Perfil transaccional del cliente — métricas estadísticas para detección de operaciones fuera de perfil

CREATE TABLE IF NOT EXISTS llx_pld_perfil_cliente (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,

  fk_societe INT NOT NULL,

  periodo_inicio DATE NOT NULL,
  periodo_fin DATE NOT NULL,
  num_operaciones_periodo INT DEFAULT 0,

  promedio_monto DECIMAL(14,2) DEFAULT 0,
  desviacion_monto DECIMAL(14,2) DEFAULT 0,
  max_monto_historico DECIMAL(14,2) DEFAULT 0,
  min_monto_historico DECIMAL(14,2) DEFAULT 0,
  monto_acumulado_periodo DECIMAL(14,2) DEFAULT 0,

  frecuencia_mensual DECIMAL(5,1) DEFAULT 0,

  nivel_riesgo_perfil VARCHAR(50) DEFAULT 'bajo',
  factores_riesgo TEXT,

  es_pep tinyint DEFAULT 0,
  nivel_diligencia VARCHAR(50) DEFAULT 'normal',

  fecha_ultima_evaluacion DATETIME NOT NULL,

  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_pld_perfil_societe (fk_societe),
  INDEX idx_pld_perfil_riesgo (nivel_riesgo_perfil),
  UNIQUE KEY uk_pld_perfil_societe (fk_societe, entity)
) ENGINE=InnoDB;
