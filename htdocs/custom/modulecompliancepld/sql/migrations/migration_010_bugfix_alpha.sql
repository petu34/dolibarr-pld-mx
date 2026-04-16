-- ============================================================================
-- Migración 010: Bugfix Alpha — Correcciones diagnóstico etapa alpha
-- ============================================================================
-- Módulo: ModulecompliancePLD
-- Fecha: Abril 2026
-- Descripción: Corrige BUG-01 (columnas faltantes aviso_operacion),
--              BUG-02 (estado alerta nueva→abierta),
--              DT-01 (eliminar FK físicas)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- BUG-01 + DT-03: Añadir columnas estándar a llx_pld_aviso_operacion
-- El INSERT en pldaviso.class.php usa entity, datec, fk_user_creat
-- que no existían en la tabla original.
-- ----------------------------------------------------------------------------

ALTER TABLE llx_pld_aviso_operacion ADD COLUMN IF NOT EXISTS entity INT DEFAULT 1 NOT NULL;
ALTER TABLE llx_pld_aviso_operacion ADD COLUMN IF NOT EXISTS datec DATETIME;
ALTER TABLE llx_pld_aviso_operacion ADD COLUMN IF NOT EXISTS tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE llx_pld_aviso_operacion ADD COLUMN IF NOT EXISTS fk_user_creat INT;
ALTER TABLE llx_pld_aviso_operacion ADD COLUMN IF NOT EXISTS import_key VARCHAR(14) DEFAULT NULL;

-- ----------------------------------------------------------------------------
-- BUG-02: Unificar estado de alerta 'nueva' → 'abierta'
-- DDL y constructor usaban 'nueva', pero todo el código filtra por 'abierta'.
-- ----------------------------------------------------------------------------

UPDATE llx_pld_alerta SET estado = 'abierta' WHERE estado = 'nueva';
ALTER TABLE llx_pld_alerta ALTER COLUMN estado SET DEFAULT 'abierta';

-- ----------------------------------------------------------------------------
-- DT-01: Eliminar FK físicas (Dolibarr usa solo FK lógicas en PHP)
-- ----------------------------------------------------------------------------

ALTER TABLE llx_pld_operacion DROP FOREIGN KEY IF EXISTS fk_pld_operacion_societe;
ALTER TABLE llx_pld_operacion DROP FOREIGN KEY IF EXISTS fk_pld_operacion_facture;
ALTER TABLE llx_pld_operacion DROP FOREIGN KEY IF EXISTS fk_pld_operacion_product;

ALTER TABLE llx_pld_aviso_operacion DROP FOREIGN KEY IF EXISTS fk_pld_aviso_operacion_aviso;
ALTER TABLE llx_pld_aviso_operacion DROP FOREIGN KEY IF EXISTS fk_pld_aviso_operacion_operacion;

ALTER TABLE llx_pld_alerta DROP FOREIGN KEY IF EXISTS fk_pld_alerta_operacion;
ALTER TABLE llx_pld_alerta DROP FOREIGN KEY IF EXISTS fk_pld_alerta_societe;

ALTER TABLE llx_pld_beneficiario DROP FOREIGN KEY IF EXISTS fk_pld_beneficiario_societe;
ALTER TABLE llx_pld_beneficiario DROP FOREIGN KEY IF EXISTS fk_pld_beneficiario_socpeople;

ALTER TABLE llx_pld_documento DROP FOREIGN KEY IF EXISTS fk_pld_documento_ecm_files;
ALTER TABLE llx_pld_documento DROP FOREIGN KEY IF EXISTS fk_pld_documento_societe;
ALTER TABLE llx_pld_documento DROP FOREIGN KEY IF EXISTS fk_pld_documento_socpeople;

-- ============================================================================
-- FIN DE MIGRACIÓN 010
-- ============================================================================
