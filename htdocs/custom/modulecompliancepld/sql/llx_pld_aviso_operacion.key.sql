-- Module: CompliancePLD
-- Description: Indexes and foreign keys for llx_pld_aviso_operacion

ALTER TABLE llx_pld_aviso_operacion ADD INDEX idx_pld_aviso_operacion_aviso (fk_pld_aviso);
ALTER TABLE llx_pld_aviso_operacion ADD INDEX idx_pld_aviso_operacion_operacion (fk_pld_operacion);
ALTER TABLE llx_pld_aviso_operacion ADD UNIQUE INDEX uk_pld_aviso_operacion (fk_pld_aviso, fk_pld_operacion);

ALTER TABLE llx_pld_aviso_operacion ADD CONSTRAINT fk_pld_aviso_operacion_aviso FOREIGN KEY (fk_pld_aviso) REFERENCES llx_pld_aviso (rowid);
ALTER TABLE llx_pld_aviso_operacion ADD CONSTRAINT fk_pld_aviso_operacion_operacion FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion (rowid);
