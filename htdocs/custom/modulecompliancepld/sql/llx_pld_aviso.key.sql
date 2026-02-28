-- Module: CompliancePLD
-- Description: Indexes for llx_pld_aviso

ALTER TABLE llx_pld_aviso ADD INDEX idx_pld_aviso_mes_reportado (mes_reportado);
ALTER TABLE llx_pld_aviso ADD INDEX idx_pld_aviso_tipo_aviso (tipo_aviso);
ALTER TABLE llx_pld_aviso ADD INDEX idx_pld_aviso_estado (estado);
ALTER TABLE llx_pld_aviso ADD INDEX idx_pld_aviso_presentado (presentado);
ALTER TABLE llx_pld_aviso ADD INDEX idx_pld_aviso_folio_sat (folio_sat);
ALTER TABLE llx_pld_aviso ADD UNIQUE INDEX uk_pld_aviso_referencia (referencia_aviso);
