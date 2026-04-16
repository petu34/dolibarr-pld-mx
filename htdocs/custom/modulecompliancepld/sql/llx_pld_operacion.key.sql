-- Module: CompliancePLD
-- Description: Indexes and foreign keys for llx_pld_operacion

ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_societe (fk_societe);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_facture (fk_facture);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_fecha (fecha_operacion);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_mes_reportado (mes_reportado);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_requiere_aviso (requiere_aviso);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_estado (estado);
