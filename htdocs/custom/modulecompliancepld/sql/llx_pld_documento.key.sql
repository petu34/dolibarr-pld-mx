-- Module: CompliancePLD
-- Description: Indexes and foreign keys for llx_pld_documento

ALTER TABLE llx_pld_documento ADD INDEX idx_pld_documento_ecm_files (fk_ecm_files);
ALTER TABLE llx_pld_documento ADD INDEX idx_pld_documento_societe (fk_societe);
ALTER TABLE llx_pld_documento ADD INDEX idx_pld_documento_socpeople (fk_socpeople);
ALTER TABLE llx_pld_documento ADD INDEX idx_pld_documento_tipo (tipo_documento_pld);
ALTER TABLE llx_pld_documento ADD INDEX idx_pld_documento_verificado (verificado);
ALTER TABLE llx_pld_documento ADD INDEX idx_pld_documento_vencimiento (fecha_vencimiento);

ALTER TABLE llx_pld_documento ADD CONSTRAINT fk_pld_documento_ecm_files FOREIGN KEY (fk_ecm_files) REFERENCES llx_ecm_files (rowid);
ALTER TABLE llx_pld_documento ADD CONSTRAINT fk_pld_documento_societe FOREIGN KEY (fk_societe) REFERENCES llx_societe (rowid);
ALTER TABLE llx_pld_documento ADD CONSTRAINT fk_pld_documento_socpeople FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople (rowid);
