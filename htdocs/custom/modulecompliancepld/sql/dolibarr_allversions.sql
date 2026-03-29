--
-- Script run when an upgrade of Dolibarr is done. Whatever is the Dolibarr version.
--

-- VERSION: 1.1.0 — DOF 27/03/2026 Art. 6
ALTER TABLE llx_pld_operacion ADD COLUMN monto_sin_impuestos DOUBLE(24,8) DEFAULT NULL;
ALTER TABLE llx_pld_operacion ADD COLUMN tasa_impuesto DOUBLE(5,4) DEFAULT 0.1600;

-- VERSION: 1.1.0 — DOF 27/03/2026 Art. 20 + Transitorio 7º
ALTER TABLE llx_pld_operacion ADD COLUMN fecha_inicio_custodia DATE DEFAULT NULL;
-- NULL = el sistema calcula MAX(fecha_operacion, '2025-07-17') en PHP

-- VERSION: 1.1.0 — DOF 27/03/2026 Art. 7 Bis
ALTER TABLE llx_pld_operacion ADD COLUMN estado_operacion VARCHAR(15) DEFAULT 'completada';
-- valores: 'completada' | 'intentada' | 'cancelada'
ALTER TABLE llx_pld_operacion ADD COLUMN motivo_no_completada VARCHAR(200) DEFAULT NULL;
ALTER TABLE llx_pld_operacion ADD COLUMN fecha_deteccion_alerta DATETIME DEFAULT NULL;

-- Nueva tabla PEP (crear via sql/llx_pld_pep_verificacion.sql al instalar)
