-- Migration 004: Extrafields PLD para llx_facture (Facturas)
-- Compliance: LFPIORPI Art. 17 Fracc. VIII
-- Fecha: 2026-02-21
-- Módulo: CompliancePLD
-- Reutilizable: 70% (datos de operación y avisos son comunes a VEH, INM y SPR)
-- Nota: Estos INSERTs documentan los extrafields creados por addExtraField() en init().
--       NO ejecutar directamente — el módulo los crea programáticamente.

-- 4.1 Control PLD de la Operación
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_es_actividad_vulnerable', '¿Es Actividad Vulnerable? (PLD)', 'boolean', '', 200, 1, 'facture', 0, 1, 1, 1, 'Marca la factura como actividad vulnerable', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_clave_actividad', 'Clave de Actividad (PLD)', 'varchar', '3', 201, 1, 'facture', 0, 1, 1, 0, 'Clave actividad vulnerable SAT (VEH/INM/SPR)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_tipo_operacion', 'Tipo de Operación (PLD)', 'varchar', '4', 202, 1, 'facture', 0, 1, 1, 0, 'Tipo operación catálogo SAT 3-4 dígitos', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_supera_umbral_id', '¿Supera Umbral Identificación? (PLD)', 'boolean', '', 203, 1, 'facture', 0, 1, 1, 0, 'Supera umbral de identificación', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_supera_umbral_aviso', '¿Supera Umbral de Aviso? (PLD)', 'boolean', '', 204, 1, 'facture', 0, 1, 1, 0, 'Supera umbral de aviso SAT', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_requiere_aviso', '¿Requiere Aviso SAT? (PLD)', 'boolean', '', 205, 1, 'facture', 0, 1, 1, 1, 'Requiere presentar aviso al SAT', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_tipo_aviso', 'Tipo de Aviso (PLD)', 'select', '', 206, 1, 'facture', 0, 0, 1, 1, 'Mensual/24 Horas/Acumulado', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 4.2 Datos de la Operación
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_operacion', 'Fecha de Operación (PLD)', 'date', '', 207, 1, 'facture', 0, 1, 1, 0, 'Fecha real de la operación YYYYMMDD', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_codigo_postal_operacion', 'CP de la Operación (PLD)', 'varchar', '5', 208, 1, 'facture', 0, 1, 1, 0, 'Código postal donde ocurre la operación (5 dígitos)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_descripcion_operacion', 'Descripción de la Operación (PLD)', 'text', '', 209, 1, 'facture', 0, 1, 1, 0, 'Descripción detallada de la operación', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_razon_operacion', 'Razón de la Operación (PLD)', 'text', '', 210, 1, 'facture', 0, 1, 1, 0, 'Justificación/motivo de la operación', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_monto_moneda_nacional', 'Monto en Moneda Nacional (PLD)', 'price', '15,2', 211, 1, 'facture', 0, 1, 1, 1, 'Monto total en pesos mexicanos MXN', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_tipo_cambio_aplicado', 'Tipo de Cambio (PLD)', 'price', '10,4', 212, 1, 'facture', 0, 0, 1, 0, 'Tipo de cambio si moneda extranjera', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 4.3 Referencia del aviso
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_referencia_aviso', 'Referencia del Aviso (PLD)', 'varchar', '14', 213, 1, 'facture', 0, 0, 1, 0, 'Referencia interna del aviso (14 chars)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_prioridad', 'Prioridad (PLD)', 'varchar', '1', 214, 1, 'facture', 0, 0, 1, 0, '1=Normal, 2=Prioritario', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 4.4 Alerta
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_tipo_alerta', 'Tipo de Alerta (PLD)', 'varchar', '4', 215, 1, 'facture', 0, 0, 1, 0, 'Código alerta 3-4 dígitos catálogo SAT', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_descripcion_alerta', 'Descripción de Alerta (PLD)', 'varchar', '3000', 216, 1, 'facture', 0, 0, 1, 0, 'Texto descriptivo de la alerta (máx 3000)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 4.5 Acumulación de Operaciones
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_es_operacion_acumulada', '¿Operación Acumulada? (PLD)', 'boolean', '', 217, 1, 'facture', 0, 1, 1, 0, 'Parte de acumulación 6 meses mismo cliente', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_inicio_acumulacion', 'Inicio Acumulación (PLD)', 'date', '', 218, 1, 'facture', 0, 0, 1, 0, 'Inicio período de acumulación', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_fin_acumulacion', 'Fin Acumulación (PLD)', 'date', '', 219, 1, 'facture', 0, 0, 1, 0, 'Fin período de acumulación', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_monto_acumulado_total', 'Monto Acumulado Total (PLD)', 'price', '15,2', 220, 1, 'facture', 0, 0, 1, 0, 'Total acumulado en período', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 4.6 Control de Avisos
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_aviso_presentado', '¿Aviso Presentado? (PLD)', 'boolean', '', 221, 1, 'facture', 0, 1, 1, 1, 'Ya se presentó aviso al SAT', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_presentacion', 'Fecha de Presentación (PLD)', 'date', '', 222, 1, 'facture', 0, 0, 1, 0, 'Fecha presentación ante SAT', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_folio_aviso', 'Folio del Aviso SAT (PLD)', 'varchar', '14', 223, 1, 'facture', 0, 0, 1, 0, 'Folio del aviso asignado por SAT', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_mes_reportado', 'Mes Reportado (PLD)', 'varchar', '6', 224, 1, 'facture', 0, 0, 1, 0, 'Mes reportado formato YYYYMM', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_acuse_sat', 'Acuse Digital SAT (PLD)', 'text', '', 225, 1, 'facture', 0, 0, 1, 0, 'Acuse digital del SAT', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 4.7 Modificatorio
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_es_modificatorio', '¿Es Modificatorio? (PLD)', 'boolean', '', 226, 1, 'facture', 0, 0, 1, 0, 'Corrección de aviso previo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_folio_modificacion', 'Folio a Modificar (PLD)', 'varchar', '14', 227, 1, 'facture', 0, 0, 1, 0, 'Folio del aviso que se modifica', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_descripcion_modificacion', 'Descripción Modificación (PLD)', 'varchar', '3000', 228, 1, 'facture', 0, 0, 1, 0, 'Razón de la modificación al aviso', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 4.8 Alertas y aviso 24 horas
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_genera_alerta', '¿Genera Alerta? (PLD)', 'boolean', '', 229, 1, 'facture', 0, 1, 1, 0, 'Genera alerta interna en el sistema', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_requiere_aviso_24hrs', '¿Requiere Aviso 24 Horas? (PLD)', 'boolean', '', 230, 1, 'facture', 0, 1, 1, 0, 'Requiere aviso urgente 24 horas', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_razon_24hrs', 'Razón Aviso 24 Horas (PLD)', 'text', '', 231, 1, 'facture', 0, 0, 1, 0, 'Justificación aviso urgente 24 horas', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Total facture: 31 extrafields
