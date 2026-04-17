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

-- ----------------------------------------------------------------------------
-- DT-01: FK físicas eliminadas del DDL base y .key.sql
-- No se ejecuta DROP FOREIGN KEY en migración porque:
--   1. DROP FOREIGN KEY IF EXISTS requiere MySQL 8.0.31+ y no existe en PostgreSQL
--   2. DoliDB no traduce DROP FOREIGN KEY → DROP CONSTRAINT
--   3. Las FK nunca fueron creadas exitosamente en PostgreSQL vía DoliDB
-- Los .key.sql ya no contienen FOREIGN KEY; instalaciones nuevas quedan limpias.
-- ----------------------------------------------------------------------------

-- ============================================================================
-- FIN DE MIGRACIÓN 010
-- ============================================================================
