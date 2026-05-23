-- Module: CompliancePLD
-- Migration: 012 — Tablas de monitoreo automatizado (Fracción X)
-- Description: Perfil transaccional por cliente + log de monitoreo permanente
-- Compliance: LFPIORPI Arts. 17, 18, 32, 45 Bis-45 Quinquies — PLD México

-- ============================================================================
-- Tabla: Perfil transaccional del cliente
-- ============================================================================
-- Almacena métricas estadísticas del comportamiento transaccional de cada
-- cliente, recalculadas cada vez que se registra una operación.
-- Sirve para detectar operaciones fuera del perfil habitual.
CREATE TABLE IF NOT EXISTS llx_pld_perfil_cliente (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,

  fk_societe INT NOT NULL,

  -- Periodo de evaluación
  periodo_inicio DATE NOT NULL,                                -- Fecha inicio del periodo analizado
  periodo_fin DATE NOT NULL,                                   -- Fecha fin del periodo analizado
  num_operaciones_periodo INT DEFAULT 0,                       -- Cantidad de operaciones en el periodo

  -- Métricas de monto
  promedio_monto DECIMAL(14,2) DEFAULT 0,                      -- Monto promedio de operaciones en el periodo
  desviacion_monto DECIMAL(14,2) DEFAULT 0,                     -- Desviación estándar del monto
  max_monto_historico DECIMAL(14,2) DEFAULT 0,                  -- Monto máximo de cualquier operación histórica
  min_monto_historico DECIMAL(14,2) DEFAULT 0,                  -- Monto mínimo de cualquier operación histórica
  monto_acumulado_periodo DECIMAL(14,2) DEFAULT 0,              -- Suma total de montos en el periodo

  -- Métricas de frecuencia
  frecuencia_mensual DECIMAL(5,1) DEFAULT 0,                    -- Operaciones por mes (promedio)

  -- Clasificación de riesgo del perfil
  nivel_riesgo_perfil VARCHAR(50) DEFAULT 'bajo',               -- bajo|medio|alto|critico
  factores_riesgo TEXT,                                         -- JSON con factores detectados

  -- PEP y diligencia
  es_pep tinyint DEFAULT 0,
  nivel_diligencia VARCHAR(50) DEFAULT 'normal',                -- simplificada|normal|reforzada

  -- Fecha de última evaluación
  fecha_ultima_evaluacion DATETIME NOT NULL,

  -- Auditoría
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_pld_perfil_societe (fk_societe),
  INDEX idx_pld_perfil_riesgo (nivel_riesgo_perfil),
  INDEX idx_pld_perfil_evaluacion (fecha_ultima_evaluacion),
  UNIQUE KEY uk_pld_perfil_societe (fk_societe, entity)
) ENGINE=InnoDB;

-- ============================================================================
-- Tabla: Log de monitoreo automatizado
-- ============================================================================
-- Registra cada ejecución del motor de monitoreo, indicando si se detectaron
-- anomalías, alertas generadas, y el resultado de la evaluación.
CREATE TABLE IF NOT EXISTS llx_pld_monitoreo_log (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,

  -- Tipo de evaluación ejecutada
  tipo_evaluacion VARCHAR(50) NOT NULL,                         -- perfil_transaccional|batch_periodico|pep|alto_riesgo|acumulacion

  -- Relaciones
  fk_societe INT,
  fk_pld_operacion INT,
  fk_pld_alerta INT,

  -- Resultado
  resultado VARCHAR(50) NOT NULL,                               -- ok|anomalia|alerta|escalado
  nivel_riesgo_detectado VARCHAR(50),                           -- bajo|medio|alto|critico
  detalle TEXT,                                                 -- Descripción del hallazgo o resultado

  -- Indicadores
  z_score DECIMAL(5,2),                                         -- Desviación estándar detectada (si aplica)
  variacion_porcentual DECIMAL(5,1),                            -- % de variación respecto al perfil (si aplica)
  supera_umbral_perfil tinyint DEFAULT 0,                       -- 1 si la operación está fuera de perfil

  -- ¿Se generó alerta?
  genero_alerta tinyint DEFAULT 0,

  -- Auditoría
  datec DATETIME NOT NULL,

  INDEX idx_pld_monitoreo_tipo (tipo_evaluacion),
  INDEX idx_pld_monitoreo_societe (fk_societe),
  INDEX idx_pld_monitoreo_fecha (datec),
  INDEX idx_pld_monitoreo_resultado (resultado),
  INDEX idx_pld_monitoreo_alerta (genero_alerta)
) ENGINE=InnoDB;

-- ============================================================================
-- Mantenimiento: Limpiar logs de monitoreo > 5 años (Art. 18 LFPIORPI)
-- ============================================================================
-- Se ejecuta manualmente o vía cron. Los logs de monitoreo son registros
-- de control interno; la obligación de conservación de 5 años aplica
-- a la documentación del cliente, no a los logs operativos.
-- No obstante, se proporciona el script para cumplimiento conservador.
-- DELETE FROM llx_pld_monitoreo_log WHERE datec < DATE_SUB(NOW(), INTERVAL 5 YEAR);
