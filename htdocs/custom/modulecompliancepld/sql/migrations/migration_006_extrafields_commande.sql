-- Migration 006: Extrafields PLD para llx_commande (Pedidos — Pre-validación)
-- Compliance: LFPIORPI Art. 17 Fracc. VIII
-- Fecha: 2026-02-21
-- Módulo: CompliancePLD
-- Reutilizable: 70% (pre-validación genérica reutilizable para VEH, INM, SPR)
-- Nota: Estos INSERTs documentan los extrafields creados por addExtraField() en init().
--       NO ejecutar directamente — el módulo los crea programáticamente.

-- 6.1 Pre-validación PLD
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_preventa_identificada', '¿Cliente Identificado? (PLD)', 'boolean', '', 400, 1, 'commande', 0, 0, 1, 0, 'Cliente ya identificado al momento del pedido', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_anticipo_estimado', 'Anticipo Estimado (PLD)', 'price', '15,2', 401, 1, 'commande', 0, 0, 1, 0, 'Anticipo aproximado del pedido', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_forma_pago_planeada', 'Forma de Pago Planeada (PLD)', 'select', '', 402, 1, 'commande', 0, 0, 1, 0, 'Forma de pago esperada', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_alerta_previa', '¿Alerta Previa? (PLD)', 'boolean', '', 403, 1, 'commande', 0, 0, 1, 0, 'Alerta generada en etapa de pedido', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Total commande: 4 extrafields
