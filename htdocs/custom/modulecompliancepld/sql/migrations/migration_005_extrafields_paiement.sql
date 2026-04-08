-- Migration 005: Extrafields PLD para llx_paiement (Pagos/Liquidación)
-- Compliance: LFPIORPI Art. 17 Fracc. VIII
-- Fecha: 2026-02-21
-- Módulo: CompliancePLD
-- Reutilizable: 90% (datos_liquidacion casi idéntico en los 3 XSD: veh, inmu, ssprof2)
-- Nota: Estos INSERTs documentan los extrafields creados por addExtraField() en init().
--       NO ejecutar directamente — el módulo los crea programáticamente.
-- IMPORTANTE: elementtype = 'payment' (convención Dolibarr, NO 'paiement')

-- 5.1 Datos de Liquidación (estructura XSD)
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_pago', 'Fecha de Pago (PLD)', 'date', '', 300, 1, 'payment', 0, 1, 1, 0, 'Fecha del pago formato YYYYMMDD', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_forma_pago', 'Forma de Pago SAT (PLD)', 'varchar', '1', 301, 1, 'payment', 0, 1, 1, 1, 'Catálogo SAT formas de pago 1 dígito', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_instrumento_monetario', 'Instrumento Monetario (PLD)', 'varchar', '2', 302, 1, 'payment', 0, 0, 1, 0, 'Catálogo instrumento monetario 1-2 dígitos', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_moneda', 'Moneda (PLD)', 'varchar', '3', 303, 1, 'payment', 0, 1, 1, 0, 'Catálogo SAT monedas 1-3 dígitos', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_monto_operacion', 'Monto de Operación (PLD)', 'varchar', '17', 304, 1, 'payment', 0, 1, 1, 1, 'Monto formato SAT: d{1,14}.d{2}', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 5.2 Desglose por Forma de Pago
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_monto_efectivo', 'Monto en Efectivo (PLD)', 'price', '15,2', 305, 1, 'payment', 0, 1, 1, 0, 'Monto pagado en efectivo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_monto_transferencia', 'Monto por Transferencia (PLD)', 'price', '15,2', 306, 1, 'payment', 0, 1, 1, 0, 'Monto por transferencia bancaria', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_monto_cheque', 'Monto por Cheque (PLD)', 'price', '15,2', 307, 1, 'payment', 0, 1, 1, 0, 'Monto pagado con cheque', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_monto_tarjeta', 'Monto por Tarjeta (PLD)', 'price', '15,2', 308, 1, 'payment', 0, 1, 1, 0, 'Monto pagado con tarjeta', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_monto_otros', 'Monto Otros Medios (PLD)', 'price', '15,2', 309, 1, 'payment', 0, 1, 1, 0, 'Monto por otros medios de pago', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 5.3 Datos Bancarios (Transferencia)
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_banco_origen', 'Banco Origen (PLD)', 'varchar', '100', 310, 1, 'payment', 0, 0, 1, 0, 'Institución financiera origen', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_cuenta_origen', 'Cuenta Origen (PLD)', 'varchar', '4', 311, 1, 'payment', 0, 0, 1, 0, 'Últimos 4 dígitos cuenta origen', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_clabe_origen', 'CLABE Origen (PLD)', 'varchar', '18', 312, 1, 'payment', 0, 0, 1, 0, 'CLABE interbancaria origen 18 dígitos', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_banco_destino', 'Banco Destino (PLD)', 'varchar', '100', 313, 1, 'payment', 0, 0, 1, 0, 'Institución financiera destino', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_cuenta_destino', 'Cuenta Destino (PLD)', 'varchar', '4', 314, 1, 'payment', 0, 0, 1, 0, 'Últimos 4 dígitos cuenta destino', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_numero_autorizacion', 'Número de Autorización (PLD)', 'varchar', '20', 315, 1, 'payment', 0, 0, 1, 0, 'Número autorización transferencia', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_transferencia', 'Fecha de Transferencia (PLD)', 'date', '', 316, 1, 'payment', 0, 0, 1, 0, 'Fecha de la transferencia bancaria', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 5.4 Datos del Cheque
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_banco_cheque', 'Banco Emisor Cheque (PLD)', 'varchar', '100', 317, 1, 'payment', 0, 0, 1, 0, 'Banco emisor del cheque', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_numero_cheque', 'Número de Cheque (PLD)', 'varchar', '20', 318, 1, 'payment', 0, 0, 1, 0, 'Número del cheque', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_cuenta_cheque', 'Cuenta Cheque (PLD)', 'varchar', '4', 319, 1, 'payment', 0, 0, 1, 0, 'Últimos 4 dígitos cuenta del cheque', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_cheque', 'Fecha del Cheque (PLD)', 'date', '', 320, 1, 'payment', 0, 0, 1, 0, 'Fecha del cheque', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_librador_cheque', 'Librador del Cheque (PLD)', 'varchar', '200', 321, 1, 'payment', 0, 0, 1, 0, 'Nombre del librador del cheque', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 5.5 Datos de Tarjeta
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_tipo_tarjeta', 'Tipo de Tarjeta (PLD)', 'select', '', 322, 1, 'payment', 0, 0, 1, 0, 'Débito/Crédito', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_emisor_tarjeta', 'Emisor de Tarjeta (PLD)', 'varchar', '100', 323, 1, 'payment', 0, 0, 1, 0, 'Banco emisor de la tarjeta', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_ultimos_digitos', 'Últimos 4 Dígitos Tarjeta (PLD)', 'varchar', '4', 324, 1, 'payment', 0, 0, 1, 0, 'Últimos 4 dígitos de la tarjeta', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_numero_autorizacion_tarjeta', 'Autorización de Tarjeta (PLD)', 'varchar', '20', 325, 1, 'payment', 0, 0, 1, 0, 'Número autorización de compra con tarjeta', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 5.6 Control de Efectivo
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_supera_limite_efectivo', '¿Supera Límite Efectivo? (PLD)', 'boolean', '', 326, 1, 'payment', 0, 1, 1, 1, 'Supera $363,692 MXN (3,100 UMAs) en efectivo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_alerta_efectivo', '¿Alerta por Efectivo? (PLD)', 'boolean', '', 327, 1, 'payment', 0, 1, 1, 0, 'Alerta por uso elevado de efectivo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_justificacion_efectivo', 'Justificación Uso Efectivo (PLD)', 'text', '', 328, 1, 'payment', 0, 0, 1, 0, 'Justificación uso elevado de efectivo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Total paiement/payment: 29 extrafields
