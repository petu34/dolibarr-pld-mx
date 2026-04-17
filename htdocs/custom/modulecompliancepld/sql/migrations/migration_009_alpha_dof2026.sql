-- Migration 009: Ajustes para nivel alfa + cumplimiento DOF 27/03/2026
-- Módulo: modulecompliancepld
-- Fecha: 2026-04-07
--
-- Cambios incluidos:
--   1. llx_pld_operacion: monto_sin_impuestos, tasa_impuesto, fecha_inicio_custodia, import_key
--   2. llx_pld_pep_verificacion: nueva tabla historial consultas PEP (Art. 45 Bis-Quinquies)
--   3. llx_pld_aviso: import_key
--   4. llx_pld_alerta: import_key
--   5. llx_pld_beneficiario: import_key
--   6. llx_pld_documento: import_key; fecha_retencion_hasta recalculada a 10 años
--
-- Reglas DDL:
--   - Sintaxis MySQL-first (DoliDB traduce a PostgreSQL en runtime)
--   - ENGINE=InnoDB en tablas nuevas
--   - Sin FK físicas, sin BOOLEAN, sin SERIAL
--   - DOUBLE(24,8) para montos
--   - Nombres de índices: idx_<tabla>_<campo>

-- ============================================================
-- 1. llx_pld_operacion — campos nuevos Art. 6 DOF 2026
-- ============================================================

ALTER TABLE llx_pld_operacion
  ADD COLUMN IF NOT EXISTS monto_sin_impuestos double(24,8) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS tasa_impuesto double(5,4) DEFAULT 0.16,
  ADD COLUMN IF NOT EXISTS fecha_inicio_custodia DATE DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS import_key VARCHAR(14) DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_pld_operacion_import_key ON llx_pld_operacion (import_key);

-- ============================================================
-- 2. llx_pld_pep_verificacion — nueva tabla Art. 45 Bis-Quinquies
-- ============================================================

CREATE TABLE IF NOT EXISTS llx_pld_pep_verificacion (
  rowid          INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity         INTEGER DEFAULT 1 NOT NULL,
  fk_societe     INTEGER NOT NULL,
  fecha_consulta DATETIME NOT NULL,
  resultado      VARCHAR(20) NOT NULL,
  referencia_uif VARCHAR(100) DEFAULT NULL,
  nivel_diligencia VARCHAR(12) DEFAULT NULL,
  observaciones  TEXT,
  date_creation  DATETIME NOT NULL,
  tms            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat  INTEGER DEFAULT NULL,
  fk_user_modif  INTEGER DEFAULT NULL,
  import_key     VARCHAR(14) DEFAULT NULL
) ENGINE=InnoDB;

CREATE INDEX IF NOT EXISTS idx_pld_pep_verif_societe ON llx_pld_pep_verificacion (fk_societe);
CREATE INDEX IF NOT EXISTS idx_pld_pep_verif_fecha   ON llx_pld_pep_verificacion (fecha_consulta);
CREATE INDEX IF NOT EXISTS idx_pld_pep_verif_entity  ON llx_pld_pep_verificacion (entity);

-- ============================================================
-- 3-6. import_key en tablas existentes (campo estándar Dolibarr)
-- ============================================================

ALTER TABLE llx_pld_aviso
  ADD COLUMN IF NOT EXISTS import_key VARCHAR(14) DEFAULT NULL;

ALTER TABLE llx_pld_alerta
  ADD COLUMN IF NOT EXISTS import_key VARCHAR(14) DEFAULT NULL;

ALTER TABLE llx_pld_beneficiario
  ADD COLUMN IF NOT EXISTS import_key VARCHAR(14) DEFAULT NULL;

ALTER TABLE llx_pld_documento
  ADD COLUMN IF NOT EXISTS import_key VARCHAR(14) DEFAULT NULL;
