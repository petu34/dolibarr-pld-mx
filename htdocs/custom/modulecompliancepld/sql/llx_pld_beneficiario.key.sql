-- Module: CompliancePLD
-- Description: Indexes and foreign keys for llx_pld_beneficiario

ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_societe (fk_societe);
ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_curp (curp);
ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_rfc (rfc);
ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_pep (es_pep);
ALTER TABLE llx_pld_beneficiario ADD INDEX idx_pld_beneficiario_activo (activo);
