-- Migration 002: Extrafields PLD para llx_socpeople (Contactos)
-- Compliance: LFPIORPI Art. 17 Fracc. VIII
-- Fecha: 2026-02-20
-- Reutilizable: 100% (idéntico en los 3 XSD: veh, inmu, ssprof2)

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_apellido_paterno', 'Apellido Paterno (PLD)', 'varchar', '200', 100, 1, 'socpeople', 0, 1, 1, 1, 'Apellido paterno según identificación', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_apellido_materno', 'Apellido Materno (PLD)', 'varchar', '200', 101, 1, 'socpeople', 0, 1, 1, 1, 'Apellido materno según identificación', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_nombre_completo', 'Nombre(s) Completo(s) (PLD)', 'varchar', '200', 102, 1, 'socpeople', 0, 1, 1, 1, 'Nombre(s) de pila', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_curp', 'CURP (PLD)', 'varchar', '18', 103, 1, 'socpeople', 0, 1, 1, 1, 'CURP 18 caracteres', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_rfc', 'RFC (PLD)', 'varchar', '13', 104, 1, 'socpeople', 0, 1, 1, 1, 'RFC del contacto', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_fecha_nacimiento', 'Fecha de Nacimiento (PLD)', 'date', '', 105, 1, 'socpeople', 0, 1, 1, 0, 'Fecha nacimiento contacto', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_nacionalidad', 'Nacionalidad (PLD)', 'varchar', '2', 106, 1, 'socpeople', 0, 1, 1, 0, 'ISO alpha-2', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_actividad_economica', 'Actividad Económica SCIAN (PLD)', 'varchar', '7', 107, 1, 'socpeople', 0, 1, 1, 0, 'Clave SCIAN 7 dígitos', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_tipo_identificacion', 'Tipo de Identificación (PLD)', 'select', '', 108, 1, 'socpeople', 0, 1, 1, 0, 'INE/Pasaporte/FM3/Cédula', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_numero_identificacion', 'Número de Identificación (PLD)', 'varchar', '20', 109, 1, 'socpeople', 0, 1, 1, 0, 'Folio de la identificación', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_vigencia_identificacion', 'Vigencia de Identificación (PLD)', 'date', '', 110, 1, 'socpeople', 0, 1, 1, 0, 'Fecha vencimiento', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_autoridad_emite', 'Autoridad Emisora (PLD)', 'varchar', '100', 111, 1, 'socpeople', 0, 1, 1, 0, 'Autoridad que emitió la identificación', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_clave_elector', 'Clave de Elector (PLD)', 'varchar', '18', 112, 1, 'socpeople', 0, 0, 1, 0, 'Clave elector INE/IFE', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_es_representante_legal', '¿Es Representante Legal? (PLD)', 'boolean', '', 113, 1, 'socpeople', 0, 1, 1, 0, 'Es representante legal o apoderado', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_tipo_representacion', 'Tipo de Representación (PLD)', 'select', '', 114, 1, 'socpeople', 0, 0, 1, 0, 'General/Especial/Ambos', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_escritura_poder', 'Escritura del Poder (PLD)', 'varchar', '20', 115, 1, 'socpeople', 0, 0, 1, 0, 'Número escritura pública del poder', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_fecha_poder', 'Fecha del Poder (PLD)', 'date', '', 116, 1, 'socpeople', 0, 0, 1, 0, 'Fecha otorgamiento del poder', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_notario_poder', 'Notario del Poder (PLD)', 'varchar', '150', 117, 1, 'socpeople', 0, 0, 1, 0, 'Notario que protocolizó el poder', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_clave_pais_telefono', 'Clave País Teléfono (PLD)', 'varchar', '2', 118, 1, 'socpeople', 0, 0, 1, 0, 'ISO alpha-2 teléfono', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_numero_telefono', 'Número de Teléfono (PLD)', 'varchar', '12', 119, 1, 'socpeople', 0, 0, 1, 0, '10-12 dígitos', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs) VALUES
('pld_correo_electronico', 'Correo Electrónico (PLD)', 'varchar', '60', 120, 1, 'socpeople', 0, 0, 1, 0, 'Email formato SAT (máx 60)', 'modulecompliancepld@modulecompliancepld') ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Total socpeople: 22 extrafields
