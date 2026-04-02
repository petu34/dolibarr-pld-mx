# Plan Ajuste-Reglamento — DOF 27/03/2026

> **Creado:** 2026-03-29
> **Estado:** 🔲 PENDIENTE
> **Base legal:** Reglamento de la LFPIORPI — Decreto DOF 27 de marzo de 2026
> **Vigencia decreto:** 28 de marzo de 2026 (Transitorio Primero)
> **Rama sugerida:** `reglamento-dof-2026`
> **Prerequisito:** Rama `FePub` mergeada a `develop`

---

## Contexto

El 27 de marzo de 2026 se publicó en el DOF un Decreto que reforma y adiciona
el Reglamento de la LFPIORPI. Las modificaciones entran en vigor el 28 de marzo
salvo excepciones expresas (Transitorios).

Este plan enumera los ajustes requeridos al módulo `modulecompliancepld`,
ordenados por impacto, con el detalle técnico suficiente para implementación
directa.

---

## Resumen de impacto por artículo

| Artículo | Tema | Impacto | Paso |
|---|---|:---:|:---:|
| Arts. 45 Bis – 45 Quinquies | Personas Políticamente Expuestas (PEPs) | 🔴 Alto | 1 |
| Art. 7 Bis | Aviso de operación intentada (24 h) | 🔴 Alto | 2 |
| Art. 6 | Doble monto: umbral sin IVA / XML con IVA | 🔴 Alto | 3 |
| Art. 15 | Medidas simplificadas — nivel de riesgo | 🟡 Medio | 4 |
| Art. 7 | Acumulación 6 meses — confirmar diseño | 🟡 Medio | 5 |
| Art. 20 + Trans. 7º | Conservación 10 años — fecha inicio custodia | 🟡 Medio | 6 |
| Art. 55 Bis | Procedimiento de auto-reporte de infracciones | 🟢 Bajo | 7 |
| Trans. 3º | Onboarding — formato Anexo A desde 17-jul-2025 | 🟢 Bajo | 8 |

---

## Paso 1 — PEPs: Personas Políticamente Expuestas

**Base legal:** Arts. 45 Bis, 45 Ter, 45 Quáter, 45 Quinquies (nuevos)
**Transitorio:** Sin excepción — vigente desde 28/03/2026

### Qué exige el Reglamento

- Los sujetos obligados deben **verificar** si un cliente/beneficiario es PEP
  consultando a la UIF por medio electrónico (Art. 45 Ter).
- El resultado de la consulta debe registrarse con fecha.
- Si el resultado es positivo (es PEP), aplican controles reforzados de debida
  diligencia (Art. 45 Quáter).
- La negativa del cliente a suministrar información para la verificación PEP
  obliga al sujeto obligado a presentar aviso de operación inusual (Art. 45 Quinquies).

### Cambios en BD — nuevos extrafields en `llx_societe`

```sql
-- Agregar a: htdocs/custom/modulecompliancepld/sql/llx_pld_societe_extrafields.sql
ALTER TABLE llx_societe_extrafields
  ADD COLUMN pld_is_pep                TINYINT DEFAULT 0,
  ADD COLUMN pld_fecha_verificacion_pep DATE DEFAULT NULL,
  ADD COLUMN pld_resultado_pep         VARCHAR(20) DEFAULT NULL,
  -- valores: 'no_consultado' | 'negativo' | 'positivo' | 'sin_respuesta'
  ADD COLUMN pld_nivel_diligencia      VARCHAR(10) DEFAULT NULL;
  -- valores: 'simplificada' | 'normal' | 'reforzada'
```

> **Nota:** Los extrafields de Dolibarr se agregan mediante el módulo como
> `CommonObject::addExtraFields()` en `modulecompliancepld.php` — no usar
> `ALTER TABLE` directo. Ver implementación existente en `fase1-extrafields-plan.md`.

### Cambios en PHP

**Nuevo:** `class/pldpepverificacion.class.php`
- `verificarPEP(int $fk_societe): array` — registra la consulta y guarda resultado
- `esClientePEP(int $fk_societe): bool` — lectura rápida del extrafield
- `getHistorialVerificaciones(int $fk_societe): array`

**Modificar:** `operacion.php` y `fep_instrumento.php`
- Mostrar badge PEP junto al nombre del cliente/participante si `pld_is_pep = 1`
- Bloquear cierre de operación como `completada` si `pld_resultado_pep = 'no_consultado'`

**Nueva tabla de historial:**

```sql
-- htdocs/custom/modulecompliancepld/sql/llx_pld_pep_verificacion.sql
CREATE TABLE IF NOT EXISTS llx_pld_pep_verificacion (
  rowid         INTEGER AUTO_INCREMENT PRIMARY KEY,
  entity        INTEGER DEFAULT 1,
  fk_societe    INTEGER NOT NULL,
  fecha_consulta DATETIME NOT NULL,
  resultado     VARCHAR(20) NOT NULL,
  -- 'negativo' | 'positivo' | 'sin_respuesta' | 'error_uif'
  referencia_uif VARCHAR(100) DEFAULT NULL,
  observaciones TEXT,
  date_creation DATETIME NOT NULL,
  tms           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INTEGER DEFAULT NULL,
  fk_user_modif INTEGER DEFAULT NULL,
  import_key    VARCHAR(14) DEFAULT NULL
) ENGINE=InnoDB;
```

### Archivos afectados

| Archivo | Acción |
|---|---|
| `modulecompliancepld.php` | Registrar nuevos extrafields de PEP en `llx_societe` |
| `sql/llx_pld_pep_verificacion.sql` | Nueva tabla historial consultas |
| `sql/llx_pld_pep_verificacion.key.sql` | Índices: `fk_societe`, `fecha_consulta` |
| `class/pldpepverificacion.class.php` | Nueva clase CRUD + lógica consulta |
| `operacion.php` | Badge PEP + bloqueo si no verificado |
| `fep_instrumento.php` | Badge PEP en participantes tipo `es_persona_aviso` |
| `admin/setup.php` | Nueva constante `MODULECOMPLIANCEPLD_PEP_ENDPOINT` (URL UIF) |

---

## Paso 2 — Aviso de operación intentada (Art. 7 Bis)

**Base legal:** Art. 7 Bis (nuevo)
**Transitorio Quinto:** Los Anexos XML aún no están actualizados — el módulo debe
manejar la espera y activar esta funcionalidad cuando el SAT publique el XSD revisado.

### Qué exige el Reglamento

Deben presentarse avisos aunque la operación **no se haya completado** si el
cliente intentó realizarla y genera señal de alerta. El plazo es de **24 horas**
a partir del momento en que se detecta la operación intentada.

### Cambios en BD

**Modificar:** `llx_pld_operacion`

```sql
-- Migración a agregar en dolibarr_allversions.sql (versión módulo)
ALTER TABLE llx_pld_operacion
  ADD COLUMN estado_operacion VARCHAR(15) DEFAULT 'completada',
  -- valores: 'completada' | 'intentada' | 'cancelada'
  ADD COLUMN motivo_no_completada VARCHAR(200) DEFAULT NULL,
  ADD COLUMN fecha_deteccion_alerta DATETIME DEFAULT NULL;
```

**Modificar:** `llx_pld_fep_instrumento`

```sql
ALTER TABLE llx_pld_fep_instrumento
  ADD COLUMN estado_operacion VARCHAR(15) DEFAULT 'completada',
  ADD COLUMN motivo_no_completada VARCHAR(200) DEFAULT NULL,
  ADD COLUMN fecha_deteccion_alerta DATETIME DEFAULT NULL;
```

### Cambios en PHP

**Modificar:** `class/pldoperacion.class.php`
- Constante `ESTADOS_OPERACION = ['completada', 'intentada', 'cancelada']`
- Validar `estado_operacion` en `create()` y `update()`
- Si `estado_operacion = 'intentada'` y `requiere_aviso = 1`: calcular y almacenar
  `fecha_limite_aviso = fecha_deteccion_alerta + 24 horas`

**Modificar:** `class/pldfepinstrumento.class.php`
- Misma lógica; campo `estado_operacion` adicional al `estado` del workflow del módulo

**Modificar:** `xml_generator.php` y `fep_xml_generator.php`
- Mostrar advertencia si hay operaciones `intentada` pendientes de aviso
- Bloquear la generación para periodos con operaciones intentadas con `fecha_limite_aviso` vencida

**Nueva constante de configuración:**
- `MODULECOMPLIANCEPLD_AVISOS_INTENTADAS_ACTIVO` (int, `0`) — activar cuando el SAT
  publique el XSD revisado (Transitorio Quinto)

### Archivos afectados

| Archivo | Acción |
|---|---|
| `sql/dolibarr_allversions.sql` | Migración ALTER TABLE para ambas tablas |
| `class/pldoperacion.class.php` | `ESTADOS_OPERACION`, validación, fecha_limite |
| `class/pldfepinstrumento.class.php` | Mismo patrón |
| `operacion.php` | Campo `estado_operacion` en formulario |
| `fep_instrumento.php` | Campo `estado_operacion` en formulario |
| `xml_generator.php` | Aviso visual + bloqueo por intentadas vencidas |
| `fep_xml_generator.php` | Ídem |
| `admin/setup.php` | Constante `MODULECOMPLIANCEPLD_AVISOS_INTENTADAS_ACTIVO` |

---

## Paso 3 — Doble monto: umbral sin IVA / XML con IVA (Art. 6)

**Base legal:** Art. 6 (modificado)

### Qué exige el Reglamento

| Propósito | Monto a usar |
|---|---|
| Comparar contra umbral en UMAs para decidir si reportar | `monto_sin_impuestos` |
| Reportar en el campo `monto` del XML del SAT | `monto_total_con_impuestos` |

### Cambios en BD

**Modificar:** `llx_pld_operacion`

```sql
ALTER TABLE llx_pld_operacion
  ADD COLUMN monto_sin_impuestos DOUBLE(24,8) DEFAULT NULL,
  -- Si NULL, el sistema asume que monto = monto_sin_impuestos (sin IVA)
  ADD COLUMN tasa_impuesto       DOUBLE(5,4) DEFAULT 0.16;
  -- 0.16 = IVA 16% México; configurable para otros impuestos/tasas
```

El campo `monto` existente conserva su semántica como **monto total** (con IVA)
para el XML. El campo nuevo `monto_sin_impuestos` se usa para comparación contra umbral.

### Cambios en PHP

**Modificar:** `class/pldoperacion.class.php`
- Agregar campos `monto_sin_impuestos` y `tasa_impuesto` al `fetch()`, `create()`, `update()`
- Nuevo método `getMontoBruto(): float` — devuelve `monto_sin_impuestos ?? monto`
- Nuevo método `getMontoXML(): float` — devuelve `monto` (con IVA, para el XML)
- `evaluarRequiereAviso()` debe usar `getMontoBruto()` para la comparación con umbral

**Modificar:** `class/pldxmlgeneratorveh.class.php`
- `crearActoOperacion()` debe llamar `$operacion->getMontoXML()` (ya hace esto
  con `$operacion->monto`; verificar que sea el campo correcto)

**Modificar:** `operacion.php`
- Separar el formulario en dos campos: importe sin IVA + tasa de impuesto
- Calcular y mostrar el total automáticamente vía JS

### Archivos afectados

| Archivo | Acción |
|---|---|
| `sql/dolibarr_allversions.sql` | ALTER TABLE `llx_pld_operacion` |
| `class/pldoperacion.class.php` | Nuevos campos + métodos `getMontoBruto`/`getMontoXML` |
| `class/pldxmlgeneratorveh.class.php` | Verificar uso de `getMontoXML()` en XML |
| `operacion.php` | Separar campos de monto en el formulario |
| `admin/setup.php` | Constante `MODULECOMPLIANCEPLD_IVA_DEFAULT` (float, `0.16`) |

---

## Paso 4 — Nivel de riesgo del cliente (Art. 15)

**Base legal:** Art. 15 (modificado) — medidas simplificadas para bajo riesgo

### Qué exige el Reglamento

Los sujetos obligados pueden aplicar **identificación simplificada** a clientes
clasificados como de bajo riesgo, y deben aplicar **medidas reforzadas** a clientes
de alto riesgo y PEPs.

### Cambios en BD

```sql
-- Extrafield adicional en llx_societe (incluir en Paso 1 si se ejecuta antes)
-- pld_nivel_riesgo: 'bajo' | 'medio' | 'alto'
-- Si Paso 1 ya agrega pld_nivel_diligencia, este paso es redundante — fusionar.
```

> **Nota:** Si el Paso 1 (PEPs) se ejecuta primero, el campo `pld_nivel_diligencia`
> (`simplificada | normal | reforzada`) ya cubre este requerimiento. En ese caso
> el Paso 4 se reduce a la lógica de negocio que auto-sugiere el nivel al capturar
> un cliente.

### Cambios en PHP

**Modificar:** `operacion.php` y `fep_instrumento.php`
- Mostrar el nivel de riesgo del cliente en la ficha
- Si nivel = `bajo`: marcar visualmente que aplica identificación simplificada

**Nuevo:** Lógica de clasificación de riesgo (puede ser parte de `pldpepverificacion.class.php`)
- Reglas básicas: cliente PEP → alto; cliente extranjero → medio; resto → bajo
- Las reglas deben ser configurables o documentadas como criterio del sujeto obligado

### Archivos afectados

| Archivo | Acción |
|---|---|
| `modulecompliancepld.php` | Extrafield `pld_nivel_riesgo` en `llx_societe` (si no viene del Paso 1) |
| `class/pldpepverificacion.class.php` | Método `clasificarRiesgo(int $fk_societe): string` |
| `operacion.php` | Badge de nivel de riesgo junto al cliente |
| `fep_instrumento.php` | Ídem para notario y participantes |

---

## Paso 5 — Acumulación 6 meses: confirmar diseño (Art. 7)

**Base legal:** Art. 7 (confirma la acumulación por período de 6 meses)

### Qué confirma el Reglamento

La acumulación de operaciones con el mismo cliente en un período de **6 meses** para
efectos del umbral de reporte es ahora obligación expresa. El aviso se presenta al
**alcanzar** el umbral, no al cierre del mes.

### Verificación del diseño actual

Revisar en `class/pldoperacion.class.php`:

1. ¿El método `evaluarRequiereAviso()` consulta la acumulación de los últimos 6 meses?
2. ¿La lógica dispara la bandera `requiere_aviso` en tiempo real (al guardar) o solo
   al generar el XML mensual?
3. ¿El campo `mes_reportado` refleja el mes en que se alcanzó el umbral?

### Acción esperada

- Si el diseño ya implementa acumulación de 6 meses: documentarlo en `DECISIONS.md`
  con referencia al Art. 7 del Reglamento.
- Si no lo implementa: agregar una consulta `SUM(monto_sin_impuestos)` sobre los
  6 meses anteriores en `evaluarRequiereAviso()` antes de comparar contra el umbral.

### Archivos afectados

| Archivo | Acción |
|---|---|
| `class/pldoperacion.class.php` | Verificar/corregir lógica de acumulación 6 meses |
| `docs/architecture/DECISIONS.md` | Documentar decisión de diseño con base en Art. 7 |

---

## Paso 6 — Conservación 10 años: campo `fecha_inicio_custodia` (Art. 20 + Trans. 7º)

**Base legal:** Art. 20 / Transitorio Séptimo
**Transitorio Séptimo:** El plazo de 10 años empezó a correr el 17 de julio de 2025.

### Qué exige el Reglamento

Los sujetos obligados deben conservar los registros por **10 años** contados a partir
de la fecha de la operación. Para registros anteriores al 17-jul-2025, el reloj inició
en esa fecha.

### Cambios en BD

```sql
-- Agregar a llx_pld_operacion y llx_pld_fep_instrumento
ALTER TABLE llx_pld_operacion
  ADD COLUMN fecha_inicio_custodia DATE DEFAULT NULL;
  -- Si NULL: el sistema calcula como MAX(fecha_operacion, '2025-07-17')

ALTER TABLE llx_pld_fep_instrumento
  ADD COLUMN fecha_inicio_custodia DATE DEFAULT NULL;
```

### Cambios en PHP

**Nuevo método en ambas clases CRUD:**

```php
public function getFechaFinCustodia(): ?string
{
    $inicio = $this->fecha_inicio_custodia
        ?? max($this->fecha_operacion, '2025-07-17');
    return date('Y-m-d', strtotime($inicio . ' +10 years'));
}
```

**Modificar:** vistas de lista (`operaciones.php`, `fep_instrumentos_list.php`)
- Columna opcional "Custodia hasta" que muestre `getFechaFinCustodia()`
- Permitir filtrar registros próximos a vencer su período de custodia

### Archivos afectados

| Archivo | Acción |
|---|---|
| `sql/dolibarr_allversions.sql` | ALTER TABLE para ambas tablas |
| `class/pldoperacion.class.php` | Método `getFechaFinCustodia()` |
| `class/pldfepinstrumento.class.php` | Método `getFechaFinCustodia()` |
| `operaciones.php` | Columna "Custodia hasta" (opcional/toggle) |
| `fep_instrumentos_list.php` | Ídem |

---

## Paso 7 — Auto-reporte de infracciones (Art. 55 Bis)

**Base legal:** Art. 55 Bis (nuevo) — procedimiento de reconocimiento de infracciones

### Qué establece el Reglamento

El sujeto obligado puede reconocer voluntariamente infracciones para obtener reducción
de sanción. Es un procedimiento operativo ante el SAT — no genera estructura de datos
en el módulo.

### Acción requerida

- Agregar nota en `docs/architecture/DECISIONS.md` describiendo el procedimiento
  externo al módulo (referencia legal y URL del portal SAT cuando esté disponible).
- Opcionalmente: campo `notas_cumplimiento` en `llx_pld_aviso` para que el usuario
  registre observaciones sobre infracciones auto-reportadas.

### Archivos afectados

| Archivo | Acción |
|---|---|
| `docs/architecture/DECISIONS.md` | Nota sobre Art. 55 Bis y procedimiento SAT |

---

## Paso 8 — Onboarding: Anexo A desde 17-jul-2025 (Transitorio 3º)

**Base legal:** Transitorio Tercero

### Qué establece

Los sujetos obligados que se den de alta como actividad vulnerable desde el
17 de julio de 2025 deben usar el **formato Anexo A** de la Resolución de
30 de agosto de 2013.

### Acción requerida

- Verificar que `admin/setup.php` incluya instrucciones o enlace al Anexo A
  en el bloque de configuración inicial del módulo.
- No requiere cambios de código — es un recordatorio documental.

### Archivos afectados

| Archivo | Acción |
|---|---|
| `admin/setup.php` | Nota informativa en la sección de configuración inicial |
| `doc/Documentation.asciidoc` | Mencionar Transitorio 3º en sección de configuración |

---

## Orden de ejecución recomendado

```
Paso 3 (doble monto IVA)          ← impacta XML inmediatamente; sin dependencias
    │
    ▼
Paso 1 (PEPs)                     ← nueva tabla + extrafields + lógica consulta UIF
    │
    ▼
Paso 4 (nivel riesgo)             ← aprovecha extrafields del Paso 1
    │
    ▼
Paso 5 (acumulación 6 meses)      ← verificación/corrección interna; sin BD nueva
    │
    ▼
Paso 6 (custodia 10 años)         ← campo nuevo; sin dependencias de los anteriores
    │
    ▼
Paso 2 (operaciones intentadas)   ← esperar confirmación XSD SAT (Trans. 5º)
    │
    ▼
Pasos 7 y 8 (documentación)       ← en cualquier momento; no bloquean
```

---

## Notas técnicas transversales

### Migraciones de BD

Todos los `ALTER TABLE` deben agregarse en `sql/dolibarr_allversions.sql` usando el
mecanismo estándar de versiones de Dolibarr:

```sql
-- VERSION: 1.1.0
-- Migraciones DOF 27/03/2026
ALTER TABLE llx_pld_operacion ADD COLUMN ...
```

Y el número de versión del módulo en `modulecompliancepld.php` debe incrementarse
de `1.0` a `1.1.0` para que Dolibarr ejecute la migración automáticamente.

### Convención DDL

Todos los campos nuevos siguen las reglas del proyecto (`CLAUDE.md`):
- `DOUBLE(24,8)` para montos
- `ENGINE=InnoDB`
- Sin FK físicas
- `import_key VARCHAR(14) DEFAULT NULL` en toda tabla nueva
- Sin sintaxis PostgreSQL nativa

### Rama de trabajo

Se recomienda una rama por paso o un único branch `reglamento-dof-2026` con
commits convencionales:

```
feat(pep): nuevo capítulo PEPs — tabla historial y extrafields (Paso 1)
feat(monto): separar monto_sin_impuestos/monto_xml — Art. 6 (Paso 3)
feat(custodia): campo fecha_inicio_custodia 10 años — Art. 20 (Paso 6)
```
