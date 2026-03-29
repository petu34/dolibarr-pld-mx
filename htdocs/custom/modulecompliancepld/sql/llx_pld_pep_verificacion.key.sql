-- Module: CompliancePLD
-- Indexes for llx_pld_pep_verificacion

ALTER TABLE llx_pld_pep_verificacion ADD INDEX idx_pld_pep_verificacion_fk_societe (fk_societe);
ALTER TABLE llx_pld_pep_verificacion ADD INDEX idx_pld_pep_verificacion_fecha (fecha_consulta);
ALTER TABLE llx_pld_pep_verificacion ADD INDEX idx_pld_pep_verificacion_entity (entity);
