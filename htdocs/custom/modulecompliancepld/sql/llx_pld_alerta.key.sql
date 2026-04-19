-- Module: CompliancePLD
-- Description: Indexes and foreign keys for llx_pld_alerta

ALTER TABLE llx_pld_alerta ADD INDEX idx_pld_alerta_operacion (fk_pld_operacion);
ALTER TABLE llx_pld_alerta ADD INDEX idx_pld_alerta_societe (fk_societe);
ALTER TABLE llx_pld_alerta ADD INDEX idx_pld_alerta_tipo_alerta (tipo_alerta);
ALTER TABLE llx_pld_alerta ADD INDEX idx_pld_alerta_nivel_riesgo (nivel_riesgo);
ALTER TABLE llx_pld_alerta ADD INDEX idx_pld_alerta_estado (estado);
