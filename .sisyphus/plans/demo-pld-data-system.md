# Sistema Demo de Datos de Prueba PLD

## TL;DR

> **Objetivo:** Crear un sistema completo de datos de prueba para verificar que las operaciones vulnerables se identifican correctamente mediante proceso batch.
> 
> **Enfoque:** Script PHP que genera clientes, vehículos, cotizaciones, facturas y pagos, luego ejecuta proceso batch para crear operaciones PLD evaluando umbrales.
> 
> **Escenarios:** Umbral individual (vehículos > $377,778 y > $117,310)
> 
> **Entrega:** Script CLI demo `scripts/demo_generar_datos_prueba.php` + Proceso batch `scripts/demo_evaluar_operaciones.php`

---

## Contexto

### Sistema PLD Actual

El módulo PLD **NO tiene automatización** al crear facturas:

- **Hooks:** Solo validan campos en formularios (contactos/empresas)
- **Triggers:** Stub vacío, no implementa eventos de facturas
- **Proceso:** Manual/Batch - Se debe ejecutar proceso para evaluar facturas y crear operaciones PLD

### Umbrales Configurados

| Tipo | Umbral | Configuración |
|------|--------|---------------|
| Vehículo nuevo | $377,778.20 MXN | `MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO` |
| Vehículo usado | $117,310.00 MXN | `MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO` |
| Acumulado 6 meses | $500,000.00 MXN | `MODULECOMPLIANCEPLD_UMBRAL_ACUMULADO_6M` |

### Estructura de Datos

**Tabla `llx_pld_operacion`:**
- `fk_facture` - ID factura relacionada
- `fk_societe` - ID cliente
- `fk_product` - ID vehículo
- `monto_mxn` - Monto total con IVA
- `monto_sin_impuestos` - Monto sin IVA (para comparar con umbral)
- `supera_umbral` - Flag 0/1
- `requiere_aviso` - Flag 0/1
- `estado` - borrador/pendiente/completada/cancelada

### Método de Evaluación

```php
// En PLDOperacion::evaluarUmbral()
$umbral = ($tipo_vehiculo === 'nuevo') ? 377778.20 : 117310.00;
$supera_individual = $monto_bruto >= $umbral;
```

---

## Work Objectives

### Core Objective
Crear sistema demo completo que permita probar el flujo de detección de operaciones vulnerables mediante proceso batch.

### Concrete Deliverables
- Script `scripts/demo_generar_datos_prueba.php` - Genera datos de prueba
- Script `scripts/demo_evaluar_operaciones.php` - Proceso batch que evalúa facturas y crea operaciones PLD
- Documentación de uso en `docs/demo-pld-testing.md`

### Definition of Done
- [ ] Script genera 10 clientes PF con extrafields PLD completos
- [ ] Script genera 10 vehículos con datos completos
- [ ] Script genera cotizaciones, facturas y pagos (5 que superan umbral, 5 que no)
- [ ] Proceso batch crea operaciones PLD marcando correctamente `supera_umbral`
- [ ] Verificación muestra operaciones correctamente clasificadas

### Must Have
- Datos ficticios RFC/CURP válidos según algoritmo SAT
- VIN de 17 caracteres válidos
- Extrafields PLD completos en clientes
- Montos específicos para probar umbrales
- Idempotencia (skip si ya existen)

### Must NOT Have
- Datos reales de personas
- Modificaciones al core de Dolibarr
- Cambios a la lógica de umbrales existente

---

## Verification Strategy

### Test Decision
- **Infrastructure exists:** YES - PHPUnit configurado
- **Automated tests:** YES (Tests-after) - Validar script y resultados
- **Agent-Executed QA:** YES

### QA Policy
Cada task incluye escenarios de verificación ejecutables.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Foundation):
├── Task 1: Script generador de clientes con extrafields PLD
├── Task 2: Script generador de vehículos (productos)
└── Task 3: Estructura base y utilidades comunes

Wave 2 (Core Data):
├── Task 4: Script generador de cotizaciones y facturas
├── Task 5: Script generador de pagos
└── Task 6: Datos de prueba predefinidos (escenarios)

Wave 3 (Batch Processing):
├── Task 7: Proceso batch evaluar facturas y crear operaciones PLD
├── Task 8: Lógica de evaluación de umbrales
└── Task 9: Marcado de supera_umbral y requiere_aviso

Wave 4 (Verification):
├── Task 10: Script de verificación de resultados
├── Task 11: Documentación de uso
└── Task 12: Tests PHPUnit

Wave FINAL:
├── Task F1: Code quality review
└── Task F2: Real manual QA
```

### Agent Dispatch Summary

| Wave | Tasks | Agent Category |
|------|-------|----------------|
| 1 | T1-T3 | `quick` |
| 2 | T4-T6 | `unspecified-high` |
| 3 | T7-T9 | `deep` |
| 4 | T10-T12 | `unspecified-high` |
| FINAL | F1-F2 | `oracle` + `unspecified-high` |

---

## TODOs

- [ ] 1. Script generador de clientes con extrafields PLD

  **What to do:**
  Crear `scripts/demo_generar_datos_prueba.php` sección clientes:
  - Generar 10 clientes PF con datos completos
  - RFC: 13 caracteres (4 letras + 6 dígitos + 3 alfanuméricos)
  - CURP: 18 caracteres válidos
  - Extrafields PLD: pld_curp, pld_rfc, pld_nacionalidad, pld_identificacion_tipo, etc.
  - Validar que no existan antes (skip si RFC ya existe)
  
  **Must NOT do:**
  - No usar datos reales
  - No modificar tabla llx_societe directamente, usar API Dolibarr
  
  **Recommended Agent Profile:**
  - **Category:** `quick`
  - **Skills:** PHP básico, conocimiento de API Dolibarr
  
  **Parallelization:**
  - **Can Run In Parallel:** YES - con T2, T3
  - **Blocks:** T4 (facturas necesitan clientes)
  
  **References:**
  - `class/actions_modulecompliancepld.class.php` - Validaciones de campos
  - `sql/llx_pld_extrafields.sql` - Estructura de extrafields
  
  **Acceptance Criteria:**
  - [ ] Script crea 10 clientes PF en llx_societe
  - [ ] Cada cliente tiene extrafields PLD poblados
  - [ ] RFC y CURP tienen formato válido
  - [ ] Idempotente: no duplica si ya existen
  
  **QA Scenarios:**
  ```
  Scenario: Generar clientes de prueba
    Tool: Bash (php CLI)
    Steps:
      1. Ejecutar: php scripts/demo_generar_datos_prueba.php --clientes
      2. Verificar en BD: SELECT COUNT(*) FROM llx_societe WHERE nom LIKE 'DEMO%'
    Expected Result: 10 clientes creados con nombre DEMO_
    Evidence: .sisyphus/evidence/demo-clientes.log
  ```
  
  **Commit:** YES
  - Message: `[DEMO] feat: script generador de clientes PLD`
  - Files: `scripts/demo_generar_datos_prueba.php`

- [ ] 2. Script generador de vehículos (productos)

  **What to do:**
  Crear sección vehículos en script demo:
  - Generar 10 vehículos como productos (tipo service o predefined)
  - VIN: 17 caracteres válidos (no usar I, O, Q)
  - Marca, modelo, año, precio
  - Campo extrafield pld_tipo_vehiculo (nuevo/usado)
  
  **Must NOT do:**
  - No crear vehículos duplicados (validar VIN)
  
  **Recommended Agent Profile:**
  - **Category:** `quick`
  - **Skills:** PHP básico
  
  **Parallelization:**
  - **Can Run In Parallel:** YES - con T1, T3
  - **Blocks:** T4 (facturas necesitan productos)
  
  **Acceptance Criteria:**
  - [ ] 10 productos creados en llx_product
  - [ ] Cada producto tiene VIN válido de 17 chars
  - [ ] Campo pld_tipo_vehiculo poblado (nuevo/usado)
  
  **QA Scenarios:**
  ```
  Scenario: Generar vehículos de prueba
    Steps:
      1. Ejecutar: php scripts/demo_generar_datos_prueba.php --vehiculos
      2. Verificar: SELECT COUNT(*) FROM llx_product WHERE ref LIKE 'DEMO-VEH%'
    Expected Result: 10 vehículos
  ```
  
  **Commit:** YES (grupo con T1)

- [ ] 3. Estructura base y utilidades comunes

  **What to do:**
  Crear archivo de utilidades comunes:
  - Funciones para generar RFC válido
  - Funciones para generar CURP válido
  - Funciones para generar VIN válido
  - Clase base DemoDataGenerator
  - Configuración de umbrales para demo
  
  **Must NOT do:**
  - No hardcodear datos sensibles
  
  **Recommended Agent Profile:**
  - **Category:** `quick`
  
  **Parallelization:**
  - **Can Run In Parallel:** YES
  - **Blocks:** T1, T2
  
  **Acceptance Criteria:**
  - [ ] Clase DemoDataGenerator con métodos helper
  - [ ] Función generarRFC() - formato válido
  - [ ] Función generarCURP() - 18 chars válidos
  - [ ] Función generarVIN() - 17 chars sin I,O,Q
  
  **QA Scenarios:**
  ```
  Scenario: Validar generadores de datos
    Steps:
      1. Ejecutar tests unitarios de generadores
    Expected Result: 100% datos válidos
  ```
  
  **Commit:** YES (grupo con T1-T2)

- [ ] 4. Script generador de cotizaciones y facturas

  **What to do:**
  Crear sección facturas en script demo:
  - Generar 10 facturas de cliente:
    - 5 facturas > $377,778 (deben superar umbral vehículo nuevo)
    - 3 facturas entre $117,310-$377,778 (deben superar umbral usado)
    - 2 facturas < $117,310 (no superan umbral)
  - Relacionar con vehículos generados
  - Estado: validada (facture = 1)
  
  **Must NOT do:**
  - No usar facturación real del SAT
  
  **Recommended Agent Profile:**
  - **Category:** `unspecified-high`
  - **Skills:** API Dolibarr Facturas
  
  **Parallelization:**
  - **Can Run In Parallel:** NO - depende T1, T2
  - **Blocks:** T5 (pagos), T7 (evaluación)
  
  **Acceptance Criteria:**
  - [ ] 10 facturas creadas en llx_facture
  - [ ] Facturas con montos específicos para cada escenario
  - [ ] Relación factura-cliente-vehículo establecida
  - [ ] Estado validada (para poder evaluar)
  
  **QA Scenarios:**
  ```
  Scenario: Generar facturas de prueba
    Steps:
      1. Ejecutar: php scripts/demo_generar_datos_prueba.php --facturas
      2. Verificar distribución de montos
    Expected Result: 5 facturas > 377778, 3 entre 117310-377778, 2 < 117310
  ```
  
  **Commit:** YES
  - Message: `[DEMO] feat: script generador de facturas PLD`

- [ ] 5. Script generador de pagos

  **What to do:**
  Crear pagos para las facturas generadas:
  - Generar pagos completos (totalmente pagadas)
  - Forma de pago: Transferencia (para evitar alertas de efectivo)
  - Estado: pagado
  
  **Recommended Agent Profile:**
  - **Category:** `quick`
  
  **Parallelization:**
  - **Can Run In Parallel:** NO - depende T4
  - **Blocks:** T7
  
  **Acceptance Criteria:**
  - [ ] Pagos creados para las 10 facturas
  - [ ] Pagos en estado pagado
  
  **Commit:** YES (grupo con T4)

- [ ] 6. Datos de prueba predefinidos (escenarios)

  **What to do:**
  Crear array de escenarios predefinidos:
  ```php
  $escenarios = [
    ['cliente' => 'DEMO_001', 'vehiculo' => 'DEMO-VEH-001', 'monto' => 450000, 'tipo' => 'nuevo', 'esperado' => 'supera'],
    ['cliente' => 'DEMO_002', 'vehiculo' => 'DEMO-VEH-002', 'monto' => 200000, 'tipo' => 'usado', 'esperado' => 'supera'],
    // ... etc
  ];
  ```
  
  **Acceptance Criteria:**
  - [ ] 10 escenarios definidos
  - [ ] Escenarios cubren todos los casos de umbral
  
  **Commit:** YES (grupo con T4-T5)

- [ ] 7. Proceso batch evaluar facturas y crear operaciones PLD

  **What to do:**
  Crear `scripts/demo_evaluar_operaciones.php`:
  - Leer facturas del período con productos de tipo vehículo
  - Para cada factura:
    - Verificar si ya existe operación PLD (skip si existe)
    - Crear operación PLD en llx_pld_operacion
    - Poblar fk_facture, fk_societe, fk_product, monto_mxn
    - Ejecutar evaluarUmbral()
  
  **Must NOT do:**
  - No modificar operaciones existentes (solo crear nuevas)
  
  **Recommended Agent Profile:**
  - **Category:** `deep`
  - **Skills:** PLDOperacion class, evaluarUmbral logic
  
  **Parallelization:**
  - **Can Run In Parallel:** NO - depende T4-T6
  - **Blocks:** T8, T10
  
  **References:**
  - `class/pldoperacion.class.php` - Lógica de operaciones
  - `class/PLDOperacionService.php` - Servicio de operaciones
  
  **Acceptance Criteria:**
  - [ ] Script recorre facturas no procesadas
  - [ ] Crea operaciones PLD correctamente
  - [ ] Asocia factura, cliente, vehículo
  
  **QA Scenarios:**
  ```
  Scenario: Proceso batch crea operaciones
    Steps:
      1. Ejecutar: php scripts/demo_evaluar_operaciones.php
      2. Verificar: SELECT COUNT(*) FROM llx_pld_operacion
    Expected Result: 10 operaciones creadas
    Evidence: .sisyphus/evidence/demo-operaciones.log
  ```
  
  **Commit:** YES
  - Message: `[DEMO] feat: proceso batch evaluación operaciones PLD`

- [ ] 8. Lógica de evaluación de umbrales

  **What to do:**
  Implementar en proceso batch:
  - Determinar tipo de vehículo (nuevo/usado) del extrafield
  - Calcular monto_sin_impuestos (monto / 1.16)
  - Comparar con umbral correspondiente
  - Establecer supera_umbral = 1/0
  - Establecer requiere_aviso = 1 si supera_umbral
  
  **Must NOT do:**
  - No hardcodear umbrales, usar constantes de CompliancePLD
  
  **Recommended Agent Profile:**
  - **Category:** `deep`
  
  **Parallelization:**
  - **Can Run In Parallel:** NO - parte de T7
  
  **Acceptance Criteria:**
  - [ ] Evalúa correctamente tipo de vehículo
  - [ ] Calcula monto_sin_impuestos correctamente
  - [ ] Establece supera_umbral según umbral configurado
  
  **Commit:** YES (grupo con T7)

- [ ] 9. Marcado de supera_umbral y requiere_aviso

  **What to do:**
  Actualizar operaciones en BD:
  - UPDATE llx_pld_operacion SET supera_umbral=1, requiere_aviso=1 WHERE ...
  - Establecer estado='pendiente_documentacion' si requiere_aviso
  
  **Acceptance Criteria:**
  - [ ] 5 operaciones con supera_umbral=1, requiere_aviso=1
  - [ ] 5 operaciones con supera_umbral=0, requiere_aviso=0
  
  **Commit:** YES (grupo con T7-T8)

- [ ] 10. Script de verificación de resultados

  **What to do:**
  Crear `scripts/demo_verificar_resultados.php`:
  - Listar operaciones PLD creadas
  - Mostrar distribución: cuántas superan umbral vs no
  - Validar montos vs umbrales configurados
  - Reportar discrepancias
  
  **Acceptance Criteria:**
  - [ ] Muestra resumen de operaciones
  - [ ] Valida umbrales correctamente
  - [ ] Detecta errores si los hay
  
  **QA Scenarios:**
  ```
  Scenario: Verificar resultados
    Steps:
      1. Ejecutar: php scripts/demo_verificar_resultados.php
    Expected Result: Reporte con 5 operaciones que superan umbral, 5 que no
  ```
  
  **Commit:** YES
  - Message: `[DEMO] feat: script verificación de resultados`

- [ ] 11. Documentación de uso

  **What to do:**
  Crear `docs/demo-pld-testing.md`:
  - Instrucciones de ejecución
  - Descripción de escenarios
  - Cómo interpretar resultados
  - Cómo limpiar datos de prueba
  
  **Acceptance Criteria:**
  - [ ] Documentación clara y completa
  - [ ] Ejemplos de comandos
  - [ ] Explicación de umbrales
  
  **Commit:** YES
  - Message: `[DEMO] docs: guía de uso sistema demo PLD`

- [ ] 12. Tests PHPUnit

  **What to do:**
  Crear tests en `tests/Unit/Demo/`:
  - Test generación RFC/CURP/VIN válidos
  - Test evaluación de umbrales
  - Test creación de operaciones PLD
  
  **Acceptance Criteria:**
  - [ ] Tests unitarios pasan
  - [ ] Cobertura > 80% de funciones demo
  
  **Commit:** YES
  - Message: `[DEMO] test: tests unitarios sistema demo`

---

## Final Verification Wave

- [ ] F1. **Code Quality Review**
  Verificar: sin código duplicado, sin datos reales, comentarios apropiados
  
- [ ] F2. **Real Manual QA**
  Ejecutar flujo completo en Docker y verificar en UI de Dolibarr

---

## Success Criteria

### Verification Commands

```bash
# Generar datos de prueba
php scripts/demo_generar_datos_prueba.php --all

# Evaluar operaciones
php scripts/demo_evaluar_operaciones.php

# Verificar resultados
php scripts/demo_verificar_resultados.php
```

### Expected Results

```
Resumen de operaciones PLD creadas:
- Total: 10
- Superan umbral (requieren aviso): 5
- No superan umbral: 5

Distribución por tipo:
- Vehículos nuevos > $377,778: 5
- Vehículos usados > $117,310: 0
- Montos < $117,310: 5
```

### Final Checklist
- [ ] Scripts ejecutan sin errores
- [ ] Datos se generan correctamente
- [ ] Operaciones PLD se crean con flags correctos
- [ ] Verificación reporta resultados esperados
- [ ] Documentación completa

---

## Notas de Implementación

### Datos Ficticios Válidos

**RFC Persona Física:**
- Formato: 4 letras + 6 dígitos + 3 alfanuméricos
- Ejemplo: `TESE010101ABC`

**CURP:**
- Formato: 18 caracteres
- Ejemplo: `TESE010101HDFSTR00`

**VIN:**
- 17 caracteres, sin I, O, Q
- Ejemplo: `3FA6P0H71HR123456`

### Comandos de Ejecución

```bash
# Modo completo
docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/demo_generar_datos_prueba.php --all

# Modo individual
docker exec doli20 php scripts/demo_generar_datos_prueba.php --clientes
docker exec doli20 php scripts/demo_generar_datos_prueba.php --vehiculos
docker exec doli20 php scripts/demo_generar_datos_prueba.php --facturas

# Evaluación
docker exec doli20 php scripts/demo_evaluar_operaciones.php

# Verificación
docker exec doli20 php scripts/demo_verificar_resultados.php

# Limpieza
docker exec doli20 php scripts/demo_generar_datos_prueba.php --clean
```

---

**Plan creado por:** Prometheus (Planner)  
**Fecha:** 2026-05-06  
**Rama:** demo/pld-testing
