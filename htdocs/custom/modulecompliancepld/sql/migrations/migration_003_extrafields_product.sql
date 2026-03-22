-- Migration 003: Extrafields PLD para llx_product (Vehículos)
-- Compliance: LFPIORPI Art. 17 Fracc. VIII
-- Fecha: 2026-02-21
-- Módulo: CompliancePLD
-- Reutilizable: 0% (específico para actividad vulnerable VEH — compraventa de vehículos)
-- Nota: Estos INSERTs documentan los extrafields creados por addExtraField() en init().
--       NO ejecutar directamente — el módulo los crea programáticamente.

-- 3.1 Identificación del Vehículo
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_tipo_vehiculo', 'Tipo de Vehículo (PLD)', 'select', '', 100, 1, 'product', 0, 1, 1, 1, 'Terrestre/Marítimo/Aéreo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_marca', 'Marca del Vehículo (PLD)', 'varchar', '40', 101, 1, 'product', 0, 1, 1, 1, 'Marca del fabricante (máx 40 chars)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_modelo', 'Modelo del Vehículo (PLD)', 'varchar', '40', 102, 1, 'product', 0, 1, 1, 1, 'Modelo del vehículo (máx 40 chars)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_anio_modelo', 'Año Modelo (PLD)', 'varchar', '4', 103, 1, 'product', 0, 1, 1, 1, 'Año modelo 4 dígitos', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 3.2 Números de Serie e Identificadores
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_vin', 'VIN (PLD)', 'varchar', '17', 104, 1, 'product', 1, 0, 1, 0, 'Vehicle Identification Number — exactamente 17 caracteres (terrestre)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_repuve', 'Clave REPUVE (PLD)', 'varchar', '8', 105, 1, 'product', 0, 0, 1, 0, 'Clave REPUVE 8 caracteres', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_placas', 'Placas (PLD)', 'varchar', '12', 106, 1, 'product', 0, 0, 1, 0, 'Placas 1-12 caracteres', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_nivel_blindaje', 'Nivel de Blindaje (PLD)', 'varchar', '1', 107, 1, 'product', 0, 1, 1, 0, 'Nivel de blindaje 1 dígito (0=sin blindaje)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_numero_serie', 'Número de Serie (PLD)', 'varchar', '20', 108, 1, 'product', 0, 0, 1, 0, 'Serie marítimo/aéreo 1-20 chars', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_bandera', 'País Bandera (PLD)', 'varchar', '2', 109, 1, 'product', 0, 0, 1, 0, 'País bandera ISO alpha-2 (marítimo)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_matricula', 'Matrícula (PLD)', 'varchar', '12', 110, 1, 'product', 0, 0, 1, 0, 'Matrícula aérea/marítima 1-12 chars', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 3.3 Origen y Estado
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_origen', 'Origen del Vehículo (PLD)', 'select', '', 111, 1, 'product', 0, 1, 1, 0, 'Nacional/Importado', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_pais_origen', 'País de Origen (PLD)', 'varchar', '2', 112, 1, 'product', 0, 0, 1, 0, 'País fabricación ISO alpha-2', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_estado_vehiculo', 'Estado del Vehículo (PLD)', 'select', '', 113, 1, 'product', 0, 1, 1, 0, 'Nuevo/Usado/Seminuevo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_kilometraje', 'Kilometraje (PLD)', 'int', '', 114, 1, 'product', 0, 0, 1, 0, 'Kilometraje actual (usado/seminuevo)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_uso_destino', 'Uso o Destino (PLD)', 'select', '', 115, 1, 'product', 0, 1, 1, 0, 'Particular/Comercial/Transporte', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 3.4 Documentación Legal (vehículos usados)
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_numero_factura_original', 'Factura Original (PLD)', 'varchar', '30', 116, 1, 'product', 0, 0, 1, 0, 'Número factura original (usado)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_fecha_factura_original', 'Fecha Factura Original (PLD)', 'date', '', 117, 1, 'product', 0, 0, 1, 0, 'Fecha factura original (usado)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_propietario_anterior', 'Propietario Anterior (PLD)', 'varchar', '200', 118, 1, 'product', 0, 0, 1, 0, 'Nombre propietario anterior (usado)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_tarjeta_circulacion', 'Tarjeta de Circulación (PLD)', 'varchar', '20', 119, 1, 'product', 0, 0, 1, 0, 'Número tarjeta circulación', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_numero_pedimento', 'Pedimento Aduanal (PLD)', 'varchar', '20', 120, 1, 'product', 0, 0, 1, 0, 'Número pedimento aduanal (importado)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- 3.5 Valores
INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_valor_factura', 'Valor Facturado (PLD)', 'price', '15,2', 121, 1, 'product', 0, 1, 1, 0, 'Valor facturado del vehículo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_valor_comercial', 'Valor Comercial (PLD)', 'price', '15,2', 122, 1, 'product', 0, 1, 1, 0, 'Valor comercial / avalúo', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO llx_extrafields (name, label, type, size, pos, entity, elementtype, fieldunique, fieldrequired, enabled, list, help, langs)
VALUES ('pld_valor_libro_azul', 'Valor Libro Azul (PLD)', 'price', '15,2', 123, 1, 'product', 0, 0, 1, 0, 'Valor libro azul (referencia)', 'modulecompliancepld@modulecompliancepld')
ON DUPLICATE KEY UPDATE label = VALUES(label);

-- Total product: 24 extrafields
