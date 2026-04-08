-- ============================================================================
-- Migración 008: Ocultar extrafields PLD del formulario nativo de facturas
-- ============================================================================
-- Módulo: ModulecompliancePLD
-- Fecha: 2026-03-16
-- Descripción: Los 32 extrafields PLD del objeto 'facture' se muestran
--              exclusivamente en el tab PLD (pld_invoice.php). Se establece
--              list=0 para que no aparezcan en el formulario nativo de facturas.
-- Referencia:  Tab registrado en modModulecompliancepld: invoice:+plddata
-- Aplicación:  Ejecutado automáticamente en init() del módulo.
--              NOTA: NO ejecutar directamente sobre la BD.
-- ============================================================================

UPDATE llx_extrafields
SET list = '0'
WHERE elementtype = 'facture'
  AND name LIKE 'pld_%';
