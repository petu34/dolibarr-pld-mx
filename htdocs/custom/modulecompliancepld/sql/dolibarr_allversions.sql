--
-- Script run when an upgrade of Dolibarr is done. Whatever is the Dolibarr version.
--

-- v1.1.0 — Agregar fk_propal a llx_pld_operacion
ALTER TABLE llx_pld_operacion ADD COLUMN IF NOT EXISTS fk_propal INT DEFAULT NULL;
