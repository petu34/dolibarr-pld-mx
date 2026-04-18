-- ============================================================================
-- Migración 011: Fix DECIMAL → double(24,8) en campos de importe monetario
-- ============================================================================
-- Módulo: ModulecompliancePLD
-- Fecha: Abril 2026
-- Descripción: Cambia DECIMAL(15,2) a double(24,8) en campos de importe
--              para cumplir con CLAUDE.md (Dolibarr DDL Rules).
-- ============================================================================

ALTER TABLE llx_pld_operacion MODIFY COLUMN monto_mxn double(24,8) NOT NULL;

ALTER TABLE llx_pld_aviso MODIFY COLUMN monto_total_operaciones double(24,8) DEFAULT 0;

-- ============================================================================
-- FIN DE MIGRACIÓN 011
-- ============================================================================
