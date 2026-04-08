-- Module: CompliancePLD
-- Description: Indexes and foreign keys for llx_pld_beneficiario

ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_societe (fk_societe);
ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_curp (curp);
ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_rfc (rfc);
ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_pep (es_pep);
ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_activo (activo);

ALTER TABLE llx_pld_beneficiario ADD CONSTRAINT fk_pld_beneficiario_societe FOREIGN KEY (fk_societe) REFERENCES llx_societe (rowid);
ALTER TABLE llx_pld_beneficiario ADD CONSTRAINT fk_pld_beneficiario_socpeople FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople (rowid);
