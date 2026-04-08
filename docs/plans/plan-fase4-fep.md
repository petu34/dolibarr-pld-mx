# Plan Fase 4: Actividad Vulnerable Fe Pública (FEP)

> **Creado:** 2026-03-25
> **Estado:** 🔲 PENDIENTE
> **Base legal:** LFPIORPI Art. 17 Fracción XI/XII (notarios, corredores, fedatarios públicos)
> **XSD destino:** `schemas/fep.xsd` — namespace `http://www.uif.shcp.gob.mx/recepcion/fep`
> **Prerequisito:** `fase4-multi-actividad-plan.md` Paso 1 — refactorizar `PLDXMLGenerator` → `PLDXMLGeneratorBase` (abstracta)

---

## Contexto

El módulo actualmente cubre la actividad vulnerable **VIII** (compraventa de vehículos)
con `veh.xsd`. Este plan implementa la actividad vulnerable **FEP** (Fe Pública —
notarios y corredores públicos), que es estructuralmente distinta: el sujeto obligado
es el notario/corredor, el objeto del aviso es el **instrumento público** (escritura) y
la operación se tipifica por el **acto jurídico protocolizado**.

### Relación con `fase4-multi-actividad-plan.md`

El plan previo Fase 4 define la arquitectura de extensibilidad multi-actividad:

```
PLDXMLGeneratorBase  (abstract)
  └── PLDXMLGeneratorVeh   ← ya existe / migrar desde PLDXMLGenerator
  └── PLDXMLGeneratorFEP   ← este plan
  └── PLDXMLGeneratorInmu  ← futuro
  └── ... otras fracciones
```

Y una `PLDXMLGeneratorFactory` que instancia el generador correcto por fracción.

**Este plan asume que el Paso 1 del plan multi-actividad se ejecuta primero**
(refactorizar `PLDXMLGenerator` → `PLDXMLGeneratorBase` + `PLDXMLGeneratorVeh`).
Si no se ejecuta antes, `PLDXMLGeneratorFEP` puede implementarse como clase independiente
temporalmente, y refactorizarse para heredar de `PLDXMLGeneratorBase` cuando ese paso se complete.

### Métodos heredados de `PLDXMLGeneratorBase` (no reimplementar)

Según `fase4-multi-actividad-plan.md`, la base proveerá:
- `generarXMLMensual()` — orquestador genérico (llamará a los abstractos)
- `crearSujetoObligado()` — estructura igual en todos los XSD
- `crearPersonaFisica()` / `crearPersonaMoral()` — tipos de persona comunes
- `crearDomicilio()` — estructura de domicilio (aunque FEP no la usa en `persona_aviso`)
- `guardarXML()` — guardado en disco
- `cleanXML()`, `formatMonto()`, `formatFecha()` — utilidades

### Métodos abstractos que `PLDXMLGeneratorFEP` debe implementar

```php
public function getNamespace(): string        // 'http://www.uif.shcp.gob.mx/recepcion/fep'
public function getXSDLocation(): string      // URL fep.xsd en SPPLD
public function getTipoActividad(): string    // 'FEP'
public function getOperacionesMes(string $mes): array  // filtra llx_pld_fep_instrumento
public function crearActoOperacion(DOMDocument $dom, $instrumento): DOMElement
// crearActoOperacion aquí produce <detalle_operaciones> → <datos_operacion> → <tipo_actividad>
```

---

## Análisis del fep.xsd

### Estructura raíz del XML
```
<archivo>  (namespace: http://www.uif.shcp.gob.mx/recepcion/fep)
  <informe>
    <mes_reportado>           YYYYMM
    <sujeto_obligado>
      <clave_entidad_colegiada>   RFC del colegio (opcional, 12 chars)
      <clave_sujeto_obligado>     RFC del notario/corredor (12-13 chars)
      <clave_actividad>           "FEP" (fijo)
      <exento>                    "1" (opcional)
    <aviso>  (0..n por informe)
      <referencia_aviso>          [A-ZÑ0-9]{1,14}
      <modificatorio>             (opcional)
      <prioridad>                 "1" o "2"
      <alerta>
        <tipo_alerta>             \d{3,4}
        <descripcion_alerta>      (opcional)
      <persona_aviso>  (1..n)    solo persona_fisica (nombre/ap_pat/ap_mat/rfc/curp)
      <detalle_operaciones>
        <datos_operacion>  (1..n)
          <instrumento_publico>   folio notarial [A-Z\d\-_]{1,20}
          <fecha_operacion>       YYYYMMDD
          <tipo_actividad>        choice de 10 tipos (ver abajo)
```

### Diferencias críticas respecto a veh.xsd

| Aspecto | veh.xsd | fep.xsd |
|---------|---------|---------|
| `clave_actividad` | "VIII" | "FEP" |
| `persona_aviso` | PF o PM con domicilio y teléfono | Solo PF (nombre+RFC+CURP), sin domicilio |
| Detalle operación | `acto_operacion` → vehículo | `instrumento_publico` + `tipo_actividad` |
| Múltiples ops por aviso | No | Sí — `datos_operacion` es 1..n |
| Múltiples personas por aviso | No | Sí — `persona_aviso` es 1..n |
| `tipo_alerta` | "01" / "02" | `\d{3,4}` (catálogo SPPLD) |
| `clave_entidad_colegiada` | No existe | Opcional (colegio de notarios) |

### Los 10 tipos de actividad (choice en `tipo_actividad`)

| # | Elemento XML | Descripción | Participantes clave |
|---|-------------|-------------|---------------------|
| 1 | `otorgamiento_poder` | Poder notarial | poderdante(s) + apoderado(s) |
| 2 | `constitucion_personas_morales` | Constitución de empresa | accionistas + capital |
| 3 | `modificacion_patrimonial` | Cambio de capital social | empresa + accionistas |
| 4 | `fusion` | Fusión de empresas | fusionadas + fusionante |
| 5 | `escision` | Escisión de empresa | escindente + escindidas |
| 6 | `compra_venta_acciones` | C/V de acciones | vendedor(es) + comprador(es) |
| 7 | `constitucion_modificacion_fideicomiso` | Fideicomiso | fideicomitente(s) + fideicomisario(s) |
| 8 | `cesion_derechos_fideicomitente_fideicomisario` | Cesión de derechos | cedente + cesionario |
| 9 | `contrato_mutuo_credito` | Crédito/mutuo | acreedor(es) + deudor(es) |
| 10 | `avaluo` | Avalúo | propietario |

---

## Decisiones de diseño

### ADR-FEP-001: Tipos de actividad en MVP

**Decisión:** Implementar los 3 tipos más frecuentes en el MVP:
- `otorgamiento_poder`
- `constitucion_personas_morales`
- `compra_venta_acciones`

Los otros 7 tipos se implementan en fases subsecuentes.
**Razón:** Los 3 seleccionados cubren ~80% de las operaciones de una notaría típica.
Los tipos de fideicomiso y fusión/escisión tienen estructuras considerablemente más complejas.

### ADR-FEP-002: Modelo de datos para tipos de actividad

**Decisión:** Dos tablas para el detalle:
- `llx_pld_fep_instrumento` — cabecera del instrumento (campos comunes a todos los tipos)
- `llx_pld_fep_participante` — personas con rol por instrumento (1..n)
- `llx_pld_fep_detalle` — campos específicos del tipo de actividad (campos NULLables por tipo)

**Razón:** Evita 10+ tablas tipo-específicas. Los campos NULLables son aceptables dado que
la discriminación por `tipo_actividad` es explícita. Sigue el patrón `CommonObject` de Dolibarr.

### ADR-FEP-003: Reutilización del generador base

**Decisión:** Clase separada `PLDXMLGeneratorFEP` (no extiende `PLDXMLGenerator`).

**Razón:** El namespace, estructura de nodos y lógica de negocio son suficientemente distintos
para justificar una clase independiente. La herencia crearía acoplamiento donde no hay
reutilización real. Ambas clases pueden compartir un trait `PLDXMLGeneratorTrait` para
métodos utilitarios comunes (`cleanXML`, `formatMonto`, etc.).

### ADR-FEP-004: UI — formulario de captura

**Decisión:** Página nueva `fep_instrumento.php` (ficha CRUD) + `fep_instrumentos_list.php`
(lista), siguiendo exactamente el patrón de `operacion.php` / `operaciones_list.php`.

### ADR-FEP-005: Configuración en setup.php

**Decisión:** Agregar una pestaña "Fe Pública" en el setup existente en lugar de una
página de admin separada.

---

## Archivos a crear/modificar

### Archivos NUEVOS

| Archivo | Descripción |
|---------|-------------|
| `sql/llx_pld_fep_instrumento.sql` | Tabla cabecera del instrumento público |
| `sql/llx_pld_fep_instrumento.key.sql` | Índices de la tabla instrumento |
| `sql/llx_pld_fep_participante.sql` | Tabla de participantes por instrumento |
| `sql/llx_pld_fep_participante.key.sql` | Índices de participantes |
| `sql/llx_pld_fep_detalle.sql` | Tabla de detalles específicos por tipo |
| `sql/llx_pld_fep_detalle.key.sql` | Índices de detalle |
| `class/pldfepinstrumento.class.php` | Clase CRUD instrumento (hereda CommonObject) |
| `class/pldfepparticipante.class.php` | Clase CRUD participante |
| `class/pldxmlgeneratorfep.class.php` | Generador XML para fep.xsd |
| `fep_instrumento.php` | Ficha CRUD de instrumento |
| `fep_instrumentos_list.php` | Lista de instrumentos |
| `fep_xml_generator.php` | UI generación XML FEP |

### Archivos MODIFICADOS

| Archivo | Cambio |
|---------|--------|
| `admin/setup.php` | Nueva pestaña "Fe Pública" con constantes FEP |
| `lib/modulecompliancepld.lib.php` | Agregar preparación de head para FEP (si aplica) |
| `core/modules/modModulecompliancepld.class.php` | Registrar tablas FEP en `$this->tables` |
| `index.php` | Agregar widgets/contadores de instrumentos FEP |

---

## Paso 1: DDL — Tablas SQL

### `llx_pld_fep_instrumento`

```sql
CREATE TABLE IF NOT EXISTS llx_pld_fep_instrumento (
  rowid          INT PRIMARY KEY AUTO_INCREMENT,
  entity         INT DEFAULT 1 NOT NULL,

  -- Identificación del instrumento
  numero_instrumento    VARCHAR(20) NOT NULL,   -- folio notarial (max 20 chars según fep.xsd)
  fecha_operacion       DATE NOT NULL,
  mes_reportado         VARCHAR(6) NOT NULL,    -- YYYYMM

  -- Tipo de acto jurídico (discriminador)
  tipo_actividad VARCHAR(50) NOT NULL,
  -- valores: otorgamiento_poder | constitucion_personas_morales |
  --          modificacion_patrimonial | fusion | escision |
  --          compra_venta_acciones | constitucion_modificacion_fideicomiso |
  --          cesion_derechos_fideicomitente_fideicomisario |
  --          contrato_mutuo_credito | avaluo

  -- Referencia al notario/sujeto obligado (llx_societe)
  fk_societe_notario    INT NOT NULL,

  -- Cliente principal del instrumento (persona del aviso)
  fk_societe_cliente    INT DEFAULT NULL,

  -- Control de avisos
  requiere_aviso        TINYINT DEFAULT 0,
  aviso_presentado      TINYINT DEFAULT 0,
  fk_pld_aviso          INT DEFAULT NULL,

  -- Estado
  estado VARCHAR(50) DEFAULT 'borrador',
  -- valores: borrador | completado | presentado | cancelado

  -- Campos estándar
  date_creation DATETIME NOT NULL,
  tms           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT DEFAULT NULL,
  fk_user_modif INT DEFAULT NULL,
  import_key    VARCHAR(14) DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `llx_pld_fep_participante`

```sql
CREATE TABLE IF NOT EXISTS llx_pld_fep_participante (
  rowid          INT PRIMARY KEY AUTO_INCREMENT,
  entity         INT DEFAULT 1 NOT NULL,

  fk_fep_instrumento INT NOT NULL,

  -- Rol en el acto jurídico
  rol VARCHAR(50) NOT NULL,
  -- valores: poderdante | apoderado | accionista | vendedor | comprador |
  --          fideicomitente | fideicomisario | acreedor | deudor |
  --          propietario | cedente | cesionario | escindente | escindida |
  --          fusionada | fusionante

  -- Tipo de persona
  tipo_persona VARCHAR(10) NOT NULL,
  -- valores: fisica | moral | fideicomiso

  -- Persona física
  nombre           VARCHAR(200) DEFAULT NULL,
  apellido_paterno VARCHAR(200) DEFAULT NULL,
  apellido_materno VARCHAR(200) DEFAULT NULL,
  fecha_nacimiento DATE DEFAULT NULL,
  rfc_fisica       VARCHAR(13) DEFAULT NULL,
  curp             VARCHAR(18) DEFAULT NULL,
  pais_nacionalidad VARCHAR(2) DEFAULT 'MX',
  actividad_economica VARCHAR(7) DEFAULT NULL,   -- catálogo SCIAN 7 dígitos

  -- Persona moral
  denominacion_razon  VARCHAR(254) DEFAULT NULL,
  fecha_constitucion  DATE DEFAULT NULL,
  rfc_moral           VARCHAR(12) DEFAULT NULL,
  giro_mercantil      VARCHAR(7) DEFAULT NULL,   -- catálogo SCIAN 7 dígitos

  -- Fideicomiso
  identificador_fideicomiso VARCHAR(40) DEFAULT NULL,

  -- Datos complementarios por rol
  tipo_poder            VARCHAR(1) DEFAULT NULL,  -- solo para apoderado: 1=admin, 2=pleitos, 3=especial
  numero_acciones       DECIMAL(15,2) DEFAULT NULL,
  cargo_accionista      VARCHAR(1) DEFAULT NULL,  -- 1=accionista, 2=administrador, 3=ambos
  tipo_movimiento       VARCHAR(1) DEFAULT NULL,  -- para fideicomiso: 1=constitución, 2=modificación

  -- Orden/posición (para mantener secuencia XML)
  posicion              INT DEFAULT 0,

  -- Campos estándar
  date_creation DATETIME NOT NULL,
  tms           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `llx_pld_fep_detalle`

```sql
CREATE TABLE IF NOT EXISTS llx_pld_fep_detalle (
  rowid          INT PRIMARY KEY AUTO_INCREMENT,
  entity         INT DEFAULT 1 NOT NULL,

  fk_fep_instrumento INT NOT NULL,

  -- tipo_actividad = otorgamiento_poder
  -- (sin campos adicionales a nivel cabecera; los datos están en participantes)

  -- tipo_actividad = constitucion_personas_morales
  tipo_persona_moral     VARCHAR(2) DEFAULT NULL,  -- 1=SA, 2=SRL, 3=SC, 4=otra
  tipo_persona_moral_otra VARCHAR(200) DEFAULT NULL,
  giro_mercantil_pm      VARCHAR(7) DEFAULT NULL,
  folio_mercantil        VARCHAR(200) DEFAULT NULL,
  numero_total_acciones  DECIMAL(15,2) DEFAULT NULL,
  entidad_federativa     VARCHAR(2) DEFAULT NULL,  -- catálogo estados 2 dígitos
  consejo_vigilancia     VARCHAR(2) DEFAULT NULL,  -- SI|NO
  motivo_constitucion    VARCHAR(1) DEFAULT NULL,
  capital_fijo           DECIMAL(15,2) DEFAULT NULL,
  capital_variable       DECIMAL(15,2) DEFAULT NULL,

  -- tipo_actividad = modificacion_patrimonial
  tipo_modificacion_capital_fijo     VARCHAR(1) DEFAULT NULL,
  inicial_capital_fijo               DECIMAL(15,2) DEFAULT NULL,
  final_capital_fijo                 DECIMAL(15,2) DEFAULT NULL,
  tipo_modificacion_capital_variable VARCHAR(1) DEFAULT NULL,
  inicial_capital_variable           DECIMAL(15,2) DEFAULT NULL,
  final_capital_variable             DECIMAL(15,2) DEFAULT NULL,
  motivo_modificacion                VARCHAR(1) DEFAULT NULL,
  numero_total_acciones_mod          DECIMAL(15,2) DEFAULT NULL,

  -- tipo_actividad = compra_venta_acciones
  tipo_operacion_acciones  VARCHAR(1) DEFAULT NULL,  -- 1=compra, 2=venta
  valor_nominal            DECIMAL(15,2) DEFAULT NULL,
  numero_acciones_cv       DECIMAL(15,2) DEFAULT NULL,
  -- datos_liquidacion (forma de pago de las acciones)
  fecha_pago               DATE DEFAULT NULL,
  instrumento_monetario    VARCHAR(2) DEFAULT NULL,
  moneda_acciones          VARCHAR(3) DEFAULT 'MXN',
  monto_operacion          DECIMAL(15,2) DEFAULT NULL,

  -- tipo_actividad = fusion
  tipo_fusion              VARCHAR(1) DEFAULT NULL,
  fusionante_determinada   VARCHAR(2) DEFAULT NULL,  -- SI|NO

  -- tipo_actividad = contrato_mutuo_credito
  tipo_otorgamiento        VARCHAR(1) DEFAULT NULL,
  moneda_mutuo             VARCHAR(3) DEFAULT 'MXN',
  monto_mutuo              DECIMAL(15,2) DEFAULT NULL,

  -- tipo_actividad = avaluo
  tipo_bien                VARCHAR(2) DEFAULT NULL,
  descripcion_avaluo       TEXT DEFAULT NULL,
  valor_avaluo             DECIMAL(15,2) DEFAULT NULL,
  propietario_solicita     VARCHAR(2) DEFAULT NULL,  -- SI|NO

  -- Campos estándar
  date_creation DATETIME NOT NULL,
  tms           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> **Nota sobre `descripcion_avaluo`:** campo TEXT — no debe llevar DEFAULT según CLAUDE.md.

### Archivos `.key.sql` — Índices y referencias lógicas

```sql
-- llx_pld_fep_instrumento.key.sql
ALTER TABLE llx_pld_fep_instrumento ADD INDEX idx_fep_instrumento_mes (mes_reportado);
ALTER TABLE llx_pld_fep_instrumento ADD INDEX idx_fep_instrumento_tipo (tipo_actividad);
ALTER TABLE llx_pld_fep_instrumento ADD INDEX idx_fep_instrumento_estado (estado);
ALTER TABLE llx_pld_fep_instrumento ADD INDEX idx_fep_instrumento_notario (fk_societe_notario);
ALTER TABLE llx_pld_fep_instrumento ADD UNIQUE uk_fep_instrumento_folio (numero_instrumento, mes_reportado, entity);

-- llx_pld_fep_participante.key.sql
ALTER TABLE llx_pld_fep_participante ADD INDEX idx_fep_participante_instrumento (fk_fep_instrumento);
ALTER TABLE llx_pld_fep_participante ADD INDEX idx_fep_participante_rol (rol);

-- llx_pld_fep_detalle.key.sql
ALTER TABLE llx_pld_fep_detalle ADD UNIQUE uk_fep_detalle_instrumento (fk_fep_instrumento);
```

---

## Paso 2: Clase `PLDFEPInstrumento` (CommonObject)

**Archivo:** `class/pldfepinstrumento.class.php`

### Propiedades

Todas las columnas de `llx_pld_fep_instrumento` como propiedades públicas.
Más colecciones relacionadas:
- `$this->participantes` — array de `PLDFEPParticipante`
- `$this->detalle` — instancia de stdClass/array con campos de `llx_pld_fep_detalle`

### Métodos de negocio requeridos

```php
// CRUD estándar CommonObject
public function fetch($id): int
public function create($user, $notrigger = 0): int
public function update($user, $notrigger = 0): int
public function delete($user, $notrigger = 0): int

// Carga de relaciones
public function fetchParticipantes(): int    // popula $this->participantes
public function fetchDetalle(): int          // popula $this->detalle

// Generación de folio
public function generarFolioInterno(): string
// Patrón: FEP-YYYY-MM-NNNN (mismo patrón MAX()+1 que PLDOperacion)

// Evaluación de umbral (si aplica — para los tipos que involucran montos)
// Por ahora, todos los instrumentos con tipo_actividad activo requieren aviso
// (FEP no tiene umbral monetario, sino umbral por tipo de acto)
public function evaluarRequiereAviso(): bool

// Transición de estado
public function setEstado(string $estado, $user): int
```

### `$fields` para CommonObject

Definir el array `$fields` con todos los campos de la tabla para que los
helpers de `CommonObject` (validación, formularios, API REST) funcionen
automáticamente.

---

## Paso 3: Clase `PLDFEPParticipante` (CommonObject)

**Archivo:** `class/pldfepparticipante.class.php`

### Propiedades

Todas las columnas de `llx_pld_fep_participante`.

### Métodos

```php
public function fetch($id): int
public function fetchAll($fk_instrumento): array  // retorna todos los participantes de un instrumento
public function create($user, $notrigger = 0): int
public function update($user, $notrigger = 0): int
public function delete($user, $notrigger = 0): int

// Helpers para construcción de persona XML
public function toPersonaFisicaXML(): array   // array con campos para crearPersonaFisica()
public function toPersonaMoralXML(): array    // array con campos para crearPersonaMoral()
```

---

## Paso 4: Clase `PLDXMLGeneratorFEP`

**Archivo:** `class/pldxmlgeneratorfep.class.php`

### Estructura

```php
// Hereda de PLDXMLGeneratorBase (fase4-multi-actividad-plan.md Paso 1)
class PLDXMLGeneratorFEP extends PLDXMLGeneratorBase
{
    private string $clave_entidad_colegiada = ''; // RFC del colegio (opcional, desde constante)

    // Métodos abstractos requeridos por PLDXMLGeneratorBase
    public function getNamespace(): string
        { return 'http://www.uif.shcp.gob.mx/recepcion/fep'; }

    public function getXSDLocation(): string
        { return 'https://sppld.sat.gob.mx/pld/documentos/links/xsd/fep.xsd'; }

    public function getTipoActividad(): string
        { return 'FEP'; }

    public function getOperacionesMes(string $mes): array
        // filtra llx_pld_fep_instrumento WHERE mes_reportado = $mes
        //   AND requiere_aviso = 1 AND aviso_presentado = 0 AND estado = 'completado'

    public function crearActoOperacion(DOMDocument $dom, $instrumento): DOMElement
        // produce <detalle_operaciones> completo

    // Métodos FEP-específicos (privados)
    private function crearPersonaAvisoFEP(DOMDocument $dom, PLDFEPParticipante $p): DOMElement
        // persona_aviso en FEP: solo PF (nombre/ap_pat/ap_mat/rfc/curp), sin domicilio
    private function crearDatosOperacion(DOMDocument $dom, PLDFEPInstrumento $i): DOMElement
    private function crearTipoActividad(DOMDocument $dom, PLDFEPInstrumento $i): DOMElement

    // Tipos de actividad MVP (privados)
    private function crearOtorgamientoPoder(DOMDocument $dom, PLDFEPInstrumento $i): DOMElement
    private function crearConstitucionPersonasMorales(DOMDocument $dom, PLDFEPInstrumento $i): DOMElement
    private function crearCompraVentaAcciones(DOMDocument $dom, PLDFEPInstrumento $i): DOMElement

    // Personas FEP — tipo completo (con actividad_economica / giro_mercantil)
    private function crearPFCompleta(DOMDocument $dom, PLDFEPParticipante $p): DOMElement
    private function crearPMCompleta(DOMDocument $dom, PLDFEPParticipante $p): DOMElement
    // Personas FEP — tipo simple (sin actividad_economica)
    private function crearPFSimple(DOMDocument $dom, PLDFEPParticipante $p): DOMElement
    private function crearPMSimple(DOMDocument $dom, PLDFEPParticipante $p): DOMElement

    // Formateo (si no están en la base)
    private function buildReferenciaAviso(PLDFEPInstrumento $i): string // FEPYYYYMMNNNN (max 14)
}
```

### Registro en la Factory (`PLDXMLGeneratorFactory`)

Agregar FEP al mapa de la factory definida en el plan multi-actividad:

```php
'FEP' => 'PLDXMLGeneratorFEP',
// También registrar con la fracción LFPIORPI si se usa así:
'XI'  => 'PLDXMLGeneratorFEP',
```
```

### Lógica de `generarXMLMensual()`

```
1. Obtener instrumentos del mes con requiere_aviso=1 AND aviso_presentado=0
2. Para cada instrumento:
   a. fetchParticipantes() + fetchDetalle()
   b. Validar campos obligatorios según tipo_actividad → Exception si falta algo
   c. Crear nodo <aviso> con:
      - referencia_aviso (FEP-YYYYMM-NNNN sin guiones, máx 14 chars → FEPYYYYMMNNNN)
      - prioridad (1)
      - alerta (tipo_alerta del instrumento o '001' por default)
      - persona_aviso[] — participante(s) marcados como "persona_aviso"
      - detalle_operaciones → datos_operacion → tipo_actividad
   d. Capturar Exception → $this->errors[] con ID del instrumento
3. Si hay errores → return false
4. return $dom->saveXML()
```

### Validaciones por tipo de actividad (campos obligatorios fep.xsd)

| Tipo | Campos obligatorios en detalle | Participantes requeridos |
|------|-------------------------------|--------------------------|
| `otorgamiento_poder` | (ninguno en detalle) | ≥1 poderdante + ≥1 apoderado |
| `constitucion_personas_morales` | tipo_persona_moral, denominacion_razon (en participante PM), giro_mercantil_pm, numero_total_acciones, entidad_federativa, consejo_vigilancia, motivo_constitucion, capital_fijo | ≥1 accionista |
| `compra_venta_acciones` | tipo_operacion_acciones, valor_nominal, numero_acciones_cv, monto_operacion, moneda_acciones | ≥1 vendedor + ≥1 comprador + ≥1 PM |

---

## Paso 5: UI — Páginas de captura

### `fep_instrumento.php` — Ficha CRUD

Seguir exactamente el patrón de `operacion.php`:

```
Sección 1: Cabecera del instrumento
  - numero_instrumento (texto, obligatorio)
  - fecha_operacion (date picker)
  - tipo_actividad (select con los 10 tipos; MVP solo habilita 3)
  - fk_societe_notario (selector societe)
  - estado (badge readonly)

Sección 2: Participantes (tabla AJAX/inline)
  - Agregar participante con rol según tipo_actividad seleccionado
  - Tipo de persona (física/moral/fideicomiso)
  - Campos de persona según tipo

Sección 3: Detalle de actividad (aparece según tipo_actividad)
  - Campos específicos del tipo seleccionado (ver tabla arriba)

Sección 4: Acciones
  - Guardar borrador / Marcar como completado / Cancelar
```

### `fep_instrumentos_list.php` — Lista

Lista estándar con filtros por:
- `mes_reportado`
- `tipo_actividad`
- `estado`
- `requiere_aviso`

### `fep_xml_generator.php` — Generación XML

Mismo patrón que `xml_generator.php`:
1. Formulario: mes_reportado + checkbox firmar
2. Acción `generar` → `PLDXMLGeneratorFEP::generarXMLMensual()`
3. Acción `descargar` → servir archivo con `checkToken()`
4. Vista previa XML (primeras 80 líneas)

---

## Paso 6: Configuración en `admin/setup.php`

### Nueva pestaña "Fe Pública"

Agregar al head de tabs:

```php
$head[] = array(
    DOL_URL_ROOT.'/custom/modulecompliancepld/admin/setup.php?mode=fep',
    'Fe Pública',
    'fep'
);
```

### Constantes nuevas

| Constante | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `MODULECOMPLIANCEPLD_FEP_ACTIVO` | int | 0 | Habilitar actividad FEP |
| `MODULECOMPLIANCEPLD_FEP_CLAVE_ENTIDAD_COLEGIADA` | varchar | '' | RFC del colegio de notarios |
| `MODULECOMPLIANCEPLD_FEP_TIPOS_ACTIVOS` | varchar | 'otorgamiento_poder,constitucion_personas_morales,compra_venta_acciones' | Tipos habilitados (CSV) |
| `MODULECOMPLIANCEPLD_FEP_DIAS_REPORTE` | int | 17 | Plazo días hábiles para presentar aviso |

---

## Paso 7: Registro en `modModulecompliancepld.class.php`

### Agregar tablas al constructor

```php
$this->tables = array(
    // ... tablas existentes ...
    'pld_fep_instrumento',
    'pld_fep_participante',
    'pld_fep_detalle',
);
```

Esto permite que el install/uninstall del módulo gestione automáticamente las tablas.

---

## Paso 8: Actualizar `index.php` — Widgets FEP

Agregar 3 boxstat widgets en la sección de Fe Pública (solo si `MODULECOMPLIANCEPLD_FEP_ACTIVO == 1`):

```
[Instrumentos del mes]   [Requieren aviso]   [Presentados]
```

---

## Orden de implementación recomendado

```
[PRE] fase4-multi-actividad-plan.md — Paso 1: refactorizar PLDXMLGenerator → PLDXMLGeneratorBase
      + PLDXMLGeneratorVeh (sin regresión en veh.xsd)
      + PLDXMLGeneratorFactory con clave 'VIII'

[1]  SQL: crear los 6 archivos .sql y .key.sql
[2]  Registrar tablas en modModulecompliancepld.class.php (array $this->tables)
[3]  PLDFEPInstrumento — CRUD + fetchParticipantes + fetchDetalle + generarFolioInterno
[4]  PLDFEPParticipante — CRUD + fetchAll
[5]  Constantes en admin/setup.php (pestaña "Fe Pública")
[6]  fep_instrumentos_list.php — lista simple con filtros
[7]  fep_instrumento.php — formulario CRUD (cabecera + participantes + detalle dinámico por tipo)
[8]  PLDXMLGeneratorFEP extends PLDXMLGeneratorBase:
     [8a] otorgamiento_poder
     [8b] constitucion_personas_morales
     [8c] compra_venta_acciones
[9]  Registrar 'FEP' / 'XI' en PLDXMLGeneratorFactory
[10] fep_xml_generator.php — UI de generación (misma estructura que xml_generator.php)
[11] index.php — widgets FEP condicionales (cuando MODULECOMPLIANCEPLD_FEP_ACTIVO=1)
[12] Pruebas: xmllint --schema schemas/fep.xsd output.xml
```

---

## Catálogos requeridos (a documentar en código)

### `tipo_actividad` — mapeo PHP → XML

```php
const TIPOS_ACTIVIDAD_FEP = [
    'otorgamiento_poder'                             => 'otorgamiento_poder',
    'constitucion_personas_morales'                  => 'constitucion_personas_morales',
    'modificacion_patrimonial'                       => 'modificacion_patrimonial',
    'fusion'                                         => 'fusion',
    'escision'                                       => 'escision',
    'compra_venta_acciones'                          => 'compra_venta_acciones',
    'constitucion_modificacion_fideicomiso'          => 'constitucion_modificacion_fideicomiso',
    'cesion_derechos_fideicomitente_fideicomisario'  => 'cesion_derechos_fideicomitente_fideicomisario',
    'contrato_mutuo_credito'                         => 'contrato_mutuo_credito',
    'avaluo'                                         => 'avaluo',
];
```

### `actividad_economica` / `giro_mercantil`

Código SCIAN de 7 dígitos. Debe estar configurable o buscar en catálogo SAT.
Para el MVP, capturar como texto libre validado con `\d{7}`.

### `tipo_alerta` FEP

El catálogo SPPLD para FEP usa códigos `\d{3,4}`. Los más comunes:
- `001` — Operación inusual
- `002` — Información insuficiente
- `003` — Conductas vulnerables relacionadas

### `pais_nacionalidad`

Código ISO-3166 alpha-2, 2 letras. Default: `MX`.

### `tipo_poder` (apoderado)

- `1` = Administración
- `2` = Pleitos y cobranzas
- `3` = Especial

### `cargo_accionista`

- `1` = Accionista
- `2` = Administrador
- `3` = Accionista y administrador

### `tipo_persona_moral` (constitución)

- `1` = Sociedad Anónima (SA)
- `2` = Sociedad de Responsabilidad Limitada (SRL)
- `3` = Sociedad Civil (SC)
- `4` = Otra

---

## Criterio de aceptación (Definition of Done)

- [ ] Las 3 tablas se crean correctamente al instalar el módulo
- [ ] Se puede capturar un instrumento de tipo `otorgamiento_poder` con ≥2 participantes
- [ ] Se puede capturar un instrumento de tipo `constitucion_personas_morales`
- [ ] Se puede capturar un instrumento de tipo `compra_venta_acciones`
- [ ] `generarXMLMensual()` produce XML válido según `fep.xsd` (validado con `xmllint --schema schemas/fep.xsd`)
- [ ] El XML generado se descarga con token CSRF válido
- [ ] Los errores de campos obligatorios faltantes se reportan por instrumento (no falla silenciosamente)
- [ ] La pestaña FEP en setup.php guarda y lee las 4 constantes correctamente
- [ ] La UI solo muestra opciones FEP cuando `MODULECOMPLIANCEPLD_FEP_ACTIVO == 1`

---

## Puntos de atención / riesgos

| Riesgo | Mitigación |
|--------|-----------|
| `referencia_aviso` máx 14 chars | FEP+YYYYMM+NNNN = 3+6+4 = 13 chars ✓ |
| `tipo_alerta` en FEP es `\d{3,4}` (no "01"/"02") | Usar '001' por default, campo editable |
| `actividad_economica` (SCIAN 7 dígitos) es obligatorio en persona_fisica y persona_moral del tipo completo | Capturar en participante; validar en generador |
| Múltiples `persona_aviso` por aviso | Iterar sobre participantes con rol que aplique |
| Tipos de actividad 4-10 no implementados en MVP | Guardar error claro: "Tipo X no soportado en esta versión" |
| FEP puede no tener umbral monetario explícito | `requiere_aviso` se activa manualmente o por regla: todos los instrumentos de tipos activos lo requieren |
