-- Migration 001: Extrafields PLD para llx_societe (Terceros/Clientes)
-- Compliance: LFPIORPI Art. 17 Fracc. VIII
-- Fecha: 2026-02-20
-- Módulo: CompliancePLD
-- Reutilizable: 95% (aplica para VEH Fracc. VIII, INM Fracc. XV, SPR Fracc. XI)
-- Nota: Estos INSERTs documentan los extrafields creados por addExtraField() en init().
--       NO ejecutar directamente — el módulo los crea programáticamente.

-- 1.1 Identificación Básica del Cliente
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_tipo_persona', 'Tipo de Persona (PLD)', 'select', '', 100, 1, 'thirdparty', 0, 1, 1, 1, 'Clasificación según LFPIORPI: Persona Física, Persona Moral o Fideicomiso', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_curp', 'CURP (PLD)', 'varchar', '18', 101, 1, 'thirdparty', 0, 0, 1, 1, 'CURP 18 caracteres. Obligatorio para personas físicas mexicanas', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_rfc_validado', 'RFC Validado (PLD)', 'varchar', '13', 102, 1, 'thirdparty', 0, 1, 1, 1, 'RFC validado. 13 chars PF, 12 chars PM', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_nacimiento', 'Fecha de Nacimiento (PLD)', 'date', '', 103, 1, 'thirdparty', 0, 0, 1, 1, 'Fecha nacimiento persona física', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_constitucion', 'Fecha de Constitución (PLD)', 'date', '', 104, 1, 'thirdparty', 0, 0, 1, 1, 'Fecha constitución persona moral', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_nacionalidad', 'Nacionalidad (PLD)', 'varchar', '2', 105, 1, 'thirdparty', 0, 1, 1, 1, 'ISO alpha-2', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_pais_nacimiento', 'País de Nacimiento (PLD)', 'varchar', '2', 106, 1, 'thirdparty', 0, 0, 1, 1, 'País nacimiento ISO alpha-2', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_estado_nacimiento', 'Estado de Nacimiento (PLD)', 'varchar', '50', 107, 1, 'thirdparty', 0, 0, 1, 1, 'Entidad federativa nacimiento', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 1.2 Domicilio Fiscal Detallado
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_calle', 'Calle (PLD)', 'varchar', '100', 108, 1, 'thirdparty', 0, 1, 1, 0, 'Calle del domicilio fiscal', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_numero_exterior', 'Número Exterior (PLD)', 'varchar', '56', 109, 1, 'thirdparty', 0, 1, 1, 0, 'Número exterior del domicilio', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_numero_interior', 'Número Interior (PLD)', 'varchar', '40', 110, 1, 'thirdparty', 0, 0, 1, 0, 'Número interior (opcional)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_colonia', 'Colonia (PLD)', 'varchar', '50', 111, 1, 'thirdparty', 0, 1, 1, 0, 'Colonia del domicilio fiscal', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_codigo_postal', 'Código Postal (PLD)', 'varchar', '5', 112, 1, 'thirdparty', 0, 1, 1, 0, 'CP 5 dígitos', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_municipio', 'Municipio / Alcaldía (PLD)', 'varchar', '100', 113, 1, 'thirdparty', 0, 1, 1, 0, 'Municipio o alcaldía', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_estado', 'Entidad Federativa (PLD)', 'select', '', 114, 1, 'thirdparty', 0, 1, 1, 0, 'Estado de la república', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_pais', 'País (PLD)', 'varchar', '2', 115, 1, 'thirdparty', 0, 1, 1, 0, 'País ISO alpha-2. Default: MX', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_es_domicilio_extranjero', '¿Domicilio Extranjero? (PLD)', 'boolean', '', 116, 1, 'thirdparty', 0, 1, 1, 0, 'Indica si el domicilio es extranjero', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_estado_provincia_ext', 'Estado/Provincia Extranjero (PLD)', 'varchar', '100', 117, 1, 'thirdparty', 0, 0, 1, 0, 'Estado/provincia domicilio extranjero', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_ciudad_poblacion_ext', 'Ciudad/Población Extranjero (PLD)', 'varchar', '100', 118, 1, 'thirdparty', 0, 0, 1, 0, 'Ciudad domicilio extranjero', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 1.3 Actividad Económica
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_actividad_economica', 'Actividad Económica SCIAN (PLD)', 'varchar', '7', 119, 1, 'thirdparty', 0, 1, 1, 0, 'Clave SCIAN 7 dígitos', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_giro_mercantil', 'Giro Mercantil (PLD)', 'varchar', '7', 120, 1, 'thirdparty', 0, 0, 1, 0, 'Giro mercantil PM SCIAN 7 dígitos', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_ocupacion', 'Ocupación / Profesión (PLD)', 'varchar', '100', 121, 1, 'thirdparty', 0, 0, 1, 0, 'Profesión u ocupación PF', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 1.4 Datos Constitutivos (Personas Morales)
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_denominacion_razon', 'Denominación o Razón Social (PLD)', 'varchar', '254', 122, 1, 'thirdparty', 0, 0, 1, 0, 'Razón social formal para XML SAT', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_numero_escritura', 'Número de Escritura (PLD)', 'varchar', '20', 123, 1, 'thirdparty', 0, 0, 1, 0, 'Escritura constitutiva', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_escritura', 'Fecha de Escritura (PLD)', 'date', '', 124, 1, 'thirdparty', 0, 0, 1, 0, 'Fecha escritura constitutiva', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_notario_numero', 'Número de Notario (PLD)', 'varchar', '8', 125, 1, 'thirdparty', 0, 0, 1, 0, 'Número del notario', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_notario_nombre', 'Nombre del Notario (PLD)', 'varchar', '150', 126, 1, 'thirdparty', 0, 0, 1, 0, 'Nombre completo del notario', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_notario_estado', 'Estado del Notario (PLD)', 'varchar', '50', 127, 1, 'thirdparty', 0, 0, 1, 0, 'Entidad federativa del notario', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_folio_mercantil', 'Folio Mercantil (PLD)', 'varchar', '200', 128, 1, 'thirdparty', 0, 0, 1, 0, 'Folio RPP', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 1.5 Datos de Fideicomiso
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_identificador_fideicomiso', 'Identificador del Fideicomiso (PLD)', 'varchar', '40', 129, 1, 'thirdparty', 0, 0, 1, 0, 'Identificador único del fideicomiso', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 1.6 Control PLD
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_cliente_identificado', '¿Cliente Identificado? (PLD)', 'boolean', '', 130, 1, 'thirdparty', 0, 1, 1, 1, 'Proceso de identificación completado', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_identificacion', 'Fecha de Identificación (PLD)', 'date', '', 131, 1, 'thirdparty', 0, 0, 1, 0, 'Fecha identificación', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_expediente_completo', '¿Expediente Completo? (PLD)', 'boolean', '', 132, 1, 'thirdparty', 0, 1, 1, 1, 'Expediente PLD completo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_es_pep', '¿Persona Expuesta Políticamente? (PLD)', 'boolean', '', 133, 1, 'thirdparty', 0, 1, 1, 1, 'PEP o familiar de PEP', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_relacion_pep', 'Relación con PEP (PLD)', 'varchar', '200', 134, 1, 'thirdparty', 0, 0, 1, 0, 'Parentesco con PEP', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_tiene_beneficiario', '¿Tiene Beneficiario Controlador? (PLD)', 'boolean', '', 135, 1, 'thirdparty', 0, 1, 1, 1, 'Dueño beneficiario o controlador', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_observaciones', 'Observaciones PLD', 'text', '', 136, 1, 'thirdparty', 0, 0, 1, 0, 'Observaciones generales compliance', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Total societe: 33 extrafields
