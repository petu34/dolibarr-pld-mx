# Análisis de cobertura: Monitoreo automatizado permanente

## Texto normativo de referencia
[Párrafo completo de la obligación]

## Desglose de la obligación (3 partes)
1. Mecanismos automatizados + monitoreo permanente
2. Identificar operaciones fuera del perfil transaccional
3. Seguimiento intensificado a PEPs y clientes de alto riesgo

## Lo que el módulo SÍ cubre
- Monitoreo evento-driven (triggers en factura/pago)
- Evaluación de umbral individual (Art. 6, UMAs vigentes)
- Acumulación 6 meses por cliente (Art. 17)
- Sistema PEP: verificación UIF, historial, nivel_diligencia → reforzada
- Alertas por nivel de riesgo (bajo/medio/alto/crítico)
- Seguimiento de beneficiarios con flags PEP

## Lo que el módulo NO cubre (brecha)
- Motor de perfil transaccional: no evalúa si una operación es inusual *para ese cliente específico*
- Sin proceso batch periódico (monitoreo "permanente" fuera del flujo de facturación)

## Tabla de cobertura
| Requisito | Cobertura |
|---|---|
| Mecanismos automatizados | Parcial |
| Umbral individual (Art. 6) | ✅ Completo |
| Acumulación 6 meses (Art. 17) | ✅ Completo |
| Perfil transaccional por cliente | ❌ No implementado |
| Seguimiento intensificado PEPs | ✅ Completo |
| Seguimiento clientes alto riesgo | ✅ Completo |

## Referencias de código
- Triggers: `core/triggers/interface_99_modModulecompliancepld_...`
- Umbral + acumulación: `class/services/PLDOperacionService.php`, `class/repository/PLDOperacionRepository.php`
- PEP: `class/pldpepverificacion.class.php`, `sql/llx_pld_pep_verificacion.sql`
- Alertas: `class/pldalerta.class.php`, `sql/llx_pld_alerta.sql`
