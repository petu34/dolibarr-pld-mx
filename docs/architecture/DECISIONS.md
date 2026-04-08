# Registro de Decisiones de Arquitectura (ADR)

> Cada decisión técnica significativa se documenta aquí con contexto, alternativas evaluadas y justificación.
> Formato: ADR (Architecture Decision Record)

---

## ADR-001: Usar extrafields de Dolibarr en lugar de tablas custom para datos de persona

**Fecha:** 2026-02-18
**Estado:** Aprobada
**Contexto:** Los datos PLD de personas (CURP, RFC, domicilio, nacionalidad) podrían implementarse como extrafields en `llx_societe`/`llx_socpeople` o como tablas nuevas `llx_pld_persona`.

**Decisión:** Usar extrafields de Dolibarr para los campos de persona.

**Justificación:**
- Los extrafields se integran automáticamente en los formularios de Dolibarr sin modificar el core
- El sistema de permisos de Dolibarr ya protege los extrafields
- Los hooks existentes de Dolibarr funcionan con extrafields nativamente
- Menor complejidad de mantenimiento entre versiones de Dolibarr

**Consecuencias:**
- (+) Integración nativa con UI de Dolibarr
- (+) Sin necesidad de formularios custom para captura de datos
- (-) Los extrafields no soportan relaciones complejas (se resuelve en Fase 2 con tablas especializadas)
- (-) Limitación de ~200 extrafields por tabla (no es problema con ~33 máximo)

---

## ADR-002: Usar regex más estricto (SPR) para validaciones compartidas

**Fecha:** 2026-02-18
**Estado:** Aprobada
**Contexto:** Los 3 XSD del SAT (veh.xsd, inmu.xsd, ssprof2.xsd) definen regex diferentes para CURP, RFC y fechas. El esquema VEH es el más permisivo; SPR es el más estricto.

**Decisión:** Usar siempre la variante más estricta (SPR) para todos los validadores compartidos.

**Justificación:**
- Un dato que pasa validación SPR también pasa VEH e INM (compatibilidad futura garantizada)
- Evita reescribir validadores al agregar nuevas actividades vulnerables
- Menor riesgo de rechazo por el portal SPPLD del SAT

**Alternativas evaluadas:**
1. *Regex por actividad:* Mantener validadores separados por fracción. Descartado por duplicación y riesgo de inconsistencia.
2. *Regex VEH (más simple):* Usar el más permisivo. Descartado porque no pasaría validación SPR al agregar Fracc. XI.

**Consecuencias:**
- (+) Un solo set de validadores para todo el proyecto
- (+) Compatibilidad garantizada con futuras fracciones
- (-) Regex más complejos y difíciles de depurar
- (-) Algunos datos válidos para VEH podrían ser rechazados (falsos negativos marginales)

---

## ADR-003: Separar datos de operación (Fase 2) de datos de persona (Fase 1)

**Fecha:** 2026-02-18
**Estado:** Aprobada
**Contexto:** Se podrían implementar todos los campos PLD en una sola fase masiva, o dividir en fases lógicas.

**Decisión:** Fase 1 = extrafields en tablas core (persona, vehículo, factura, pago). Fase 2 = tablas especializadas (operaciones, beneficiarios, documentos, alertas, envíos).

**Justificación:**
- Los extrafields no requieren cambios en la arquitectura de Dolibarr
- Las tablas especializadas requieren clases PHP completas con CRUD
- Separar permite validar la captura de datos antes de construir el motor de avisos
- El equipo humano puede verificar la Fase 1 mientras se desarrolla la Fase 2

**Consecuencias:**
- (+) Entregables incrementales y verificables
- (+) Menor riesgo por entrega progresiva
- (-) Algunas funcionalidades de control (alertas automáticas) no estarán disponibles hasta Fase 2

---

## ADR-004: Campos de vehículo (llx_product) NO son reutilizables

**Fecha:** 2026-02-18
**Estado:** Aprobada
**Contexto:** El análisis cross-schema de los 3 XSD reveló que `detalle_operaciones` es 100% diferente entre VEH, INM y SPR. Los campos de vehículo (marca, modelo, VIN, etc.) solo existen en veh.xsd.

**Decisión:** Marcar los ~24 extrafields de `llx_product` como específicos de Fracción VIII (VEH) con reusabilidad 0%.

**Justificación:**
- INM usa `caracteristicas_inmueble` (tipo, valor, dimensiones, folio real)
- SPR usa `tipo_actividad` (10 subtipos de servicios profesionales)
- No hay intersección con los campos de vehículo

**Consecuencias:**
- (+) Claridad sobre qué campos son universales vs específicos
- (+) Las futuras fracciones tendrán sus propios extrafields en `llx_product` sin conflicto
- (-) No hay ahorro de trabajo para la parte de producto al agregar INM o SPR

---

## ADR-005: Umbrales basados en UMAs, no en montos fijos

**Fecha:** 2026-02-18
**Estado:** Pendiente de confirmación
**Contexto:** AGENTS.md define umbrales fijos ($250,000 vehículos nuevos / $100,000 usados). El plan Fase 1 define 3,220 UMAs = $377,778.20 MXN (valor UMA 2026: $117.32).

**Decisión pendiente:** Implementar umbrales como constantes en UMAs con conversión dinámica basada en el valor UMA vigente.

**Opciones:**
1. *Montos fijos en MXN:* Simple pero se desactualizan cada año
2. *UMAs con tabla de conversión:* Flexible, se actualiza con un solo campo en `llx_pld_configuracion`
3. *UMAs hardcoded con valor UMA configurable:* Compromiso entre simplicidad y flexibilidad

**Estado:** Requiere decisión del equipo humano antes de implementar.

---

## ADR-006: Estructura de módulo en htdocs/custom/

**Fecha:** 2026-02-18
**Estado:** Aprobada
**Contexto:** Dolibarr permite módulos en `htdocs/custom/` (externo, no toca core) o directamente en `htdocs/` (interno, requiere merge con core).

**Decisión:** Todo el código PLD va en `htdocs/custom/modulecompliancepld/`.

**Justificación:**
- No modifica el core de Dolibarr (regla del proyecto)
- Facilita actualizaciones de Dolibarr sin conflictos
- Compatible con Dolibarr 20 y 21
- Sigue la convención estándar de módulos externos

---

## ADR-007: Acumulación de 6 meses para evaluación de umbral (Art. 7 DOF 27/03/2026)

**Fecha:** 2026-04-07
**Estado:** Aprobada
**Contexto:** El Art. 7 del Reglamento DOF 27/03/2026 confirma que la evaluación de umbral para aviso al SAT no es solo por operación individual, sino que se considera la **acumulación de operaciones con el mismo cliente en un período de 6 meses**. Si la suma acumulada supera el umbral, todas las operaciones del período requieren aviso.

**Decisión:** `PLDOperacion::evaluarUmbral()` realiza dos evaluaciones:
1. **Individual:** monto de la operación actual vs. umbral del tipo de vehículo
2. **Acumulada:** `SUM(COALESCE(monto_sin_impuestos, monto_mxn))` de los últimos 6 meses para el mismo `fk_societe`, excluyendo operaciones canceladas

Si cualquiera supera el umbral → `requiere_aviso = 1`. Se devuelve `motivo` ('individual' o 'acumulada') para trazabilidad.

**Base legal:** Art. 7 Reglamento LFPIORPI, DOF 27/03/2026. Anterior: interpretación no era explícita en el texto del reglamento pre-2026.

**Justificación:**
- Cumplimiento legal directo con texto normativo vigente
- Evita evasión del umbral mediante fraccionamiento de operaciones (smurfing)
- El campo `motivo` en el resultado permite explicar al cliente/auditor por qué se genera aviso

**Alternativas evaluadas:**
1. *Solo evaluación individual:* Simple pero no cumple Art. 7. Descartado.
2. *Ventana deslizante de 12 meses:* Más restrictivo que la ley; podría generar avisos innecesarios. Descartado.
3. *Ventana de 6 meses calendario:* Más predecible para el operador. Descartado por no ser la interpretación del reglamento (dice "6 meses anteriores a la fecha de la operación").

**Consecuencias:**
- (+) Cumplimiento Art. 7 DOF 2026
- (+) Trazabilidad del motivo del aviso
- (-) Consulta SQL adicional en cada creación/evaluación de operación (aceptable para volumen de VEH)
- (-) Requiere campo `monto_sin_impuestos` para comparación correcta (ver ADR-008)

---

## ADR-008: Separación monto_sin_impuestos / monto_mxn (Art. 6 DOF 27/03/2026)

**Fecha:** 2026-04-07
**Estado:** Aprobada
**Contexto:** El Art. 6 del Reglamento DOF 27/03/2026 establece que:
- La **evaluación del umbral** se hace sobre el **monto sin IVA** (base gravable)
- El **XML SAT** debe reportar el **monto con IVA** (monto total de la operación)

Antes de este cambio, `monto_mxn` era usado para ambos propósitos, lo que podía llevar a no reportar operaciones que superan umbral cuando se calcula sin IVA, o a reportar montos incorrectos en el XML.

**Decisión:** Agregar campo `monto_sin_impuestos DOUBLE(24,8)` y `tasa_impuesto DOUBLE(5,4) DEFAULT 0.16` a `llx_pld_operacion`.
- `getMontoBruto()` → devuelve `monto_sin_impuestos ?? monto_mxn` (para umbral)
- `getMontoXML()` → devuelve `monto_mxn` (siempre con IVA, para XML)
- `calcularMontoTotal()` → recalcula `monto_mxn = monto_sin_impuestos * (1 + tasa_impuesto)`

**Justificación:**
- Separación clara entre dato regulatorio (umbral) y dato contable (XML)
- Retrocompatibilidad: si `monto_sin_impuestos` es NULL, `getMontoBruto()` cae a `monto_mxn`
- La tasa es configurable via `MODULECOMPLIANCEPLD_IVA_DEFAULT` para cambios futuros de IVA

**Consecuencias:**
- (+) XML correcto con monto total (lo que pagó el cliente)
- (+) Umbral correcto sobre base sin IVA
- (+) Retrocompatible con operaciones ya capturadas
- (-) UI requiere dos campos de monto (mitigado con cálculo JS automático)

---

## ADR-009: Retención 10 años con inicio retroactivo 2025-07-17 (Art. 20 + Trans. 7º DOF 2026)

**Fecha:** 2026-04-07
**Estado:** Aprobada
**Contexto:** El Art. 20 del Reglamento DOF 27/03/2026 amplía el período de retención de expedientes PLD de **5 a 10 años**. El Transitorio Séptimo establece que para registros anteriores a la entrada en vigor, el reloj de 10 años comienza el **17 de julio de 2025** (fecha de entrada en vigor de la resolución previa).

**Decisión:** `PLDOperacion::getFechaFinCustodia()` calcula:
```
fecha_inicio = MAX(fecha_operacion, '2025-07-17')
fecha_fin = fecha_inicio + 10 años
```
`PLDDocumento::ANIOS_RETENCION = 10` (cambiado de 5).
`MODULECOMPLIANCEPLD_PERIODO_CONSERVACION` default cambiado a 10.

**Consecuencias:**
- (+) Cumplimiento Art. 20 + Transitorio Séptimo
- (+) Para registros anteriores a 2025-07-17, la fecha de fin queda en 2035-07-17 (no en fechas pasadas)
- (-) Expedientes que bajo la ley anterior ya podían destruirse ahora deben conservarse hasta 2035

---

## Plantilla para nuevas decisiones

```markdown
## ADR-NNN: [Título descriptivo]

**Fecha:** YYYY-MM-DD
**Estado:** Propuesta / Aprobada / Obsoleta
**Contexto:** [Situación que requiere una decisión]

**Decisión:** [Lo que se decidió]

**Justificación:** [Por qué se tomó esta decisión]

**Alternativas evaluadas:**
1. [Alternativa 1] — [razón de descarte]
2. [Alternativa 2] — [razón de descarte]

**Consecuencias:**
- (+) [Beneficio]
- (-) [Costo o riesgo]
```
