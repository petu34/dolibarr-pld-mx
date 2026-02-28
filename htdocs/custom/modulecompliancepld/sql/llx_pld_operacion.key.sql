-- Module: CompliancePLD
-- Description: Indexes and foreign keys for llx_pld_operacion

ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_societe (fk_societe);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_facture (fk_facture);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_fecha (fecha_operacion);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_mes_reportado (mes_reportado);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_requiere_aviso (requiere_aviso);
ALTER TABLE llx_pld_operacion ADD INDEX idx_pld_operacion_estado (estado);

ALTER TABLE llx_pld_operacion ADD CONSTRAINT fk_pld_operacion_societe FOREIGN KEY (fk_societe) REFERENCES llx_societe (rowid);
ALTER TABLE llx_pld_operacion ADD CONSTRAINT fk_pld_operacion_facture FOREIGN KEY (fk_facture) REFERENCES llx_facture (rowid);
ALTER TABLE llx_pld_operacion ADD CONSTRAINT fk_pld_operacion_product FOREIGN KEY (fk_product) REFERENCES llx_product (rowid);
