---
marp: true
theme: default
paginate: true
backgroundColor: #ffffff
style: |
  section {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 1.1rem;
  }
  section.lead {
    background: #1a3a5c;
    color: #ffffff;
  }
  section.lead h1 {
    color: #ffffff;
    font-size: 2.2rem;
  }
  section.lead h2 {
    color: #a8c8e8;
    font-weight: normal;
  }
  section.lead p {
    color: #c8dff0;
  }
  h1 { color: #1a3a5c; border-bottom: 3px solid #c0392b; padding-bottom: 8px; }
  h2 { color: #2c5f8a; }
  h3 { color: #c0392b; }
  code { background: #f4f4f4; border-radius: 4px; padding: 2px 6px; font-size: 0.9rem; }
  pre code { background: transparent; padding: 0; }
  pre { background: #1e1e1e; color: #d4d4d4; border-radius: 8px; padding: 16px; font-size: 0.78rem; }
  table { width: 100%; font-size: 0.85rem; }
  th { background: #1a3a5c; color: white; }
  tr:nth-child(even) { background: #f0f5fa; }
  .columns { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
  blockquote { border-left: 4px solid #c0392b; background: #fef9f9; padding: 8px 16px; color: #555; }
---

<!-- _class: lead -->

# Mejoras Arquitectónicas
## Módulo `modulecompliancepld`

**Dolibarr ERP — PLD México**
Prevención de Lavado de Dinero · LFPIORPI Art. 17

---
Abril 2026

---

## Agenda

1. Arquitectura actual — qué patrones usa hoy
2. Diagnóstico — categorías de problemas identificados
3. **8 mejoras** propuestas con ejemplos concretos
4. Hoja de ruta — prioridades y esfuerzo estimado
5. Arquitectura objetivo

---

## Arquitectura Actual

El módulo combina dos patrones:

<div class="columns">

**Capas (dominante)**
```
Presentación
  operacion.php, index.php
  pld_*.php (tabs)

Lógica de Negocio
  CompliancePLD, PLDValidator
  PLDCatalogos, PLDXMLGenerator

Acceso a Datos (DAO)
  PLDOperacion, PLDAviso
  PLDAlerta → CommonObject

Persistencia
  7 tablas llx_pld_*
  143 extrafields
```

**Orientado a Eventos (complementario)**
```
Triggers del sistema
  CONTACT_CREATE → validar CURP/RFC
  COMPANY_CREATE → validar datos PLD

Hook system (6 tabs)
  thirdpartycard → pld_thirdparty.php
  contactcard    → pld_contact.php
  productcard    → pld_product.php
  invoicecard    → pld_invoice.php
  ordercard      → pld_order.php
  paymentcard    → pld_payment.php
```

</div>

---

## Diagnóstico General

| Categoría | Archivos afectados | Severidad |
|---|---|:---:|
| Mezcla de responsabilidades (SQL + HTML + negocio) | `operacion.php`, `index.php`, `pldoperacion.class.php` | 🔴 Alta |
| SQL directo en vistas y generadores | `index.php:40–66`, `pldxmlgenerator.class.php:100` | 🔴 Alta |
| Configuración regulatoria hardcoded | `compliancepld.class.php:33–48` | 🔴 Alta |
| Acoplamiento fuerte / sin inyección | `pldxmlgenerator.class.php:119,159` | 🟡 Media |
| Lógica de negocio en capa de datos | `pldoperacion.class.php:366–417` | 🟡 Media |
| Validador monolítico no extensible | `pldvalidator.class.php` (522 líneas) | 🟡 Media |
| Sin separación template / controlador | Todos los `*.php` de vista | 🟢 Baja |
| Tipos primitivos para datos regulatorios | CURP, RFC, VIN como `string` | 🟢 Baja |

---

## Mejora 1 — Service Layer

**Problema:** La lógica de negocio está dispersa en tres lugares distintos.

```php
// operacion.php:63–77 — lógica de IVA y umbral en la vista
$monto = price2num(GETPOST('monto_mxn'));
$iva   = $monto * 0.16;          // cálculo en controlador
if ($monto > $umbral) { ... }    // decisión de negocio en vista

// pldoperacion.class.php:366–417 — evaluarUmbral() en la clase de datos
public function evaluarUmbral(): bool {
    $sql = "SELECT SUM(monto) FROM ..."; // query + decisión mezclados
}
```

**Solución:** Crear `class/services/` como capa de orquestación:

```
class/services/
├── PLDOperacionService.php   ← crear operación, evaluar umbral, generar folio
├── PLDAvisoService.php       ← crear aviso, vincular operaciones
├── PLDAlertaService.php      ← detectar y disparar alertas
└── PLDReporteService.php     ← agregaciones para dashboard
```

> Las vistas llaman al servicio. Los servicios llaman al DAO. Nunca al revés.

---

## Mejora 1 — Service Layer (ejemplo)

```php
// class/services/PLDOperacionService.php
class PLDOperacionService
{
    public function __construct(
        private PLDOperacionRepository $repo,
        private CompliancePLD $compliance
    ) {}

    public function crearOperacion(array $datos, User $user): PLDOperacion
    {
        // 1. Validar
        $monto = $this->compliance->calcularMontoTotal($datos['monto_mxn']);

        // 2. Evaluar umbral (lógica de negocio)
        $superaUmbral   = $this->compliance->debeGenerarAviso($monto, $datos['tipo']);
        $acumulado6m    = $this->repo->getAcumuladoSeisMeses($datos['fk_societe']);

        // 3. Persistir
        $operacion = new PLDOperacion($this->repo->getDb());
        $operacion->monto_mxn    = $monto;
        $operacion->supera_umbral = $superaUmbral ? 1 : 0;
        $operacion->create($user);

        return $operacion;
    }
}

// operacion.php — sólo orquesta la petición HTTP
$service = new PLDOperacionService($repo, $compliance);
$result  = $service->crearOperacion(GETPOST_array(), $user);
```

---

## Mejora 2 — Repository Pattern

**Problema:** `pldoperacion.class.php` (717 líneas) mezcla persistencia básica con queries complejas de negocio.

```php
// pldoperacion.class.php:378–398 — query de acumulación DENTRO del modelo
public function evaluarUmbral(): bool {
    $sql = "SELECT SUM(o.monto_mxn) FROM ".MAIN_DB_PREFIX."pld_operacion o
            WHERE o.fk_societe = ".$this->fk_societe."
            AND o.date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
    // + lógica de decisión en el mismo método
}
```

**Solución:** Separar las queries de negocio en repositorios dedicados:

```
class/repository/
├── PLDOperacionRepository.php   ← getAcumuladoSeisMeses(), fetchByMes()
├── PLDAvisoRepository.php       ← fetchPendientes(), fetchByFolio()
└── PLDDocumentoRepository.php   ← fetchVencidos(), fetchByEntidad()
```

El modelo (`PLDOperacion extends CommonObject`) conserva sólo `create / fetch / update / delete`.

---

## Mejora 2 — Repository (ejemplo)

```php
// class/repository/PLDOperacionRepository.php
class PLDOperacionRepository
{
    public function __construct(private $db) {}

    public function getAcumuladoSeisMeses(int $fkSociete): float
    {
        $sql = "SELECT SUM(o.monto_mxn) as total
                FROM ".MAIN_DB_PREFIX."pld_operacion o
                WHERE o.fk_societe = ".(int)$fkSociete."
                AND o.date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                AND o.entity = ".getEntity('pld_operacion');

        $resql = $this->db->query($sql);
        $obj   = $this->db->fetch_object($resql);
        return (float)($obj->total ?? 0);
    }

    public function fetchByMes(string $mes, int $limit = 100): array
    {
        // ...
    }
}
```

> Beneficio: las queries complejas están en un único lugar, son fáciles de optimizar y de testear de forma aislada.

---

## Mejora 3 — Configuración Regulatoria Externalizada

**Problema:** Constantes con valores que cambian cada año están hardcoded en el código fuente.

```php
// compliancepld.class.php:33–48
class CompliancePLD
{
    const UMA_2026             = 117.32;   // ← actualizar manualmente cada enero
    const UMBRAL_UMAS_VEHICULO = 3220;     // ← definido por LFPIORPI
    const UMBRAL_ACUMULADO_6M  = 500000.0; // ← resolución SAT
}
```

**Solución:** Mover a constantes Dolibarr (`llx_const`) desde `admin/setup.php` (ya existe):

```php
// compliancepld.class.php — después
class CompliancePLD
{
    public function getUMA(): float
    {
        return (float) getDolGlobalString('PLD_UMA_VALOR', 117.32);
    }

    public function getUmbralUMAs(): int
    {
        return (int) getDolGlobalString('PLD_UMBRAL_UMAS_VEHICULO', 3220);
    }
}
```

> El oficial de cumplimiento actualiza el UMA desde el panel de administración cada enero — sin tocar código.

---

## Mejora 4 — Eliminar SQL Directo en Vistas

**Problema:** `index.php` contiene 5 queries SQL hardcoded para contar registros del dashboard.

```php
// index.php:40–66
$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."pld_operacion
        WHERE entity = ".$conf->entity." AND supera_umbral = 1";
$resql = $db->query($sql);  // query en la vista

$sql2 = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."pld_aviso
         WHERE entity = ".$conf->entity." AND statut = 0";
// ... 3 queries más
```

**Solución:** Delegar en el `PLDReporteService`:

```php
// index.php — después
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/services/PLDReporteService.php';

$stats = PLDReporteService::getDashboardStats($db, $conf->entity);

// Vista usa sólo variables
echo $stats['operaciones_alerta'];
echo $stats['avisos_pendientes'];
echo $stats['documentos_vencidos'];
```

> Regla: **cero queries SQL en archivos de vista**.

---

## Mejora 5 — Value Objects para Datos Regulatorios

**Problema:** CURP, RFC y VIN son `string` en todo el sistema. La validación depende de llamar manualmente a `PLDValidator`.

```php
// Hoy: string sin garantía de validez
$socpeople->array_options['options_pld_curp'] = GETPOST('curp', 'alpha');
// ¿Está validado? ¿Cuándo? ¿En qué archivo?
```

**Solución:** Value Objects inmutables que validan al construirse:

```php
// class/vo/CURP.php
final class CURP
{
    private string $value;

    public function __construct(string $raw)
    {
        $clean = strtoupper(trim($raw));
        if (!preg_match(PLDValidator::REGEX_CURP, $clean)) {
            throw new \InvalidArgumentException("CURP inválido: $raw");
        }
        $this->value = $clean;
    }

    public function __toString(): string { return $this->value; }
}

// Uso
$curp = new CURP(GETPOST('curp', 'alpha')); // lanza excepción si es inválido
```

```
class/vo/
├── CURP.php   ├── RFC.php   └── VIN.php
```

---

## Mejora 6 — Strategy/Composite en PLDValidator

**Problema:** `pldvalidator.class.php` (522 líneas) es monolítico. Agregar nuevas reglas exige modificar la clase.

```php
// Hoy: llamadas dispersas y manuales
$v = new PLDValidator();
if (!$v->validarCURP($datos['curp'])) { ... }
if (!$v->validarRFC($datos['rfc']))  { ... }
if (!$v->validarTelefono($datos['tel'])) { ... }
// 8 llamadas separadas sin coordinación
```

**Solución:** Interfaz + composición de validadores (Open/Closed Principle):

```php
// class/validator/ValidadorPLD.php
interface ValidadorPLD {
    public function validar(array $datos): ValidationResult;
}

class ValidadorPersonaFisicaPLD implements ValidadorPLD { ... }
class ValidadorPersonaMoralPLD  implements ValidadorPLD { ... }

class ValidadorCompuesto implements ValidadorPLD {
    public function __construct(private array $validadores) {}

    public function validar(array $datos): ValidationResult {
        foreach ($this->validadores as $v) {
            $result = $v->validar($datos);
            if (!$result->esValido()) return $result;
        }
        return ValidationResult::ok();
    }
}
```

> Agregar una nueva regla = crear una clase nueva, sin tocar las existentes.

---

## Mejora 7 — Inyección de Dependencias en PLDXMLGenerator

**Problema:** El generador de XML instancia sus dependencias internamente y lee configuración global en el constructor.

```php
// pldxmlgenerator.class.php:29–34, 119, 159
public function __construct($db) {
    $this->rfcSujeto = getDolGlobalString('PLD_RFC_SUJETO'); // global hardcoded
}

public function generarXMLMensual(...) {
    $op = new PLDOperacion($this->db);  // instanciación interna — no testeable
    $op->fetch($id);
}
```

**Solución:** Constructor Injection:

```php
// pldxmlgenerator.class.php — después
class PLDXMLGenerator
{
    public function __construct(
        private PLDOperacionRepository $operacionRepo,
        private PLDAvisoRepository     $avisoRepo,
        private array                  $config        // ['rfc' => ..., 'clave_actividad' => ...]
    ) {}

    public function generarXMLMensual(string $mes, array $ids): string
    {
        $operaciones = $this->operacionRepo->fetchByIds($ids); // sin new interno
        // ...
    }
}
```

> Testeable sin base de datos: se puede inyectar un mock del repositorio.

---

## Mejora 8 — Separación Templates / Controladores

**Problema:** Los archivos de vista mezclan lógica PHP con renderizado HTML en el mismo bloque.

```php
// operacion.php — controlador y vista en el mismo archivo
if ($action == 'add') {
    $object->monto_mxn = price2num(GETPOST('monto_mxn'));
    $object->create($user);                    // lógica
    header('Location: operacion.php?id=...');
}

llxHeader('', $langs->trans("Operacion"));
print '<form method="POST" ...>';              // HTML
print '<input name="monto_mxn" ...>';
```

**Solución:** Separar en dos bloques o archivos `.tpl.php`:

```
tpl/
├── operacion_form.tpl.php      ← sólo variables PHP en HTML
├── operacion_view.tpl.php      ← ficha de detalle
└── operaciones_list.tpl.php    ← tabla + paginación
```

```php
// operacion.php — sólo acción + asignación de variables
if ($action == 'add') {
    $result = $operacionService->crearOperacion(GETPOST_array(), $user);
}
$tplData = ['object' => $object, 'action' => $action];

// Al final, sólo include del template
include DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/tpl/operacion_view.tpl.php';
```

---

## Hoja de Ruta

| # | Mejora | Impacto | Esfuerzo | Fase sugerida |
|:---:|---|:---:|:---:|:---:|
| 3 | Configuración regulatoria en BD | 🔴 Alto | 🟢 Bajo | Fase 2 |
| 4 | Eliminar SQL directo en vistas | 🔴 Alto | 🟢 Bajo | Fase 2 |
| 1 | Service Layer | 🔴 Alto | 🟡 Medio | Fase 3 |
| 2 | Repository Pattern | 🔴 Alto | 🟡 Medio | Fase 3 |
| 7 | DI en PLDXMLGenerator | 🟡 Medio | 🟢 Bajo | Fase 3 |
| 5 | Value Objects (CURP, RFC, VIN) | 🟡 Medio | 🟡 Medio | Fase 4 |
| 6 | Strategy en PLDValidator | 🟡 Medio | 🔴 Alto | Fase 4 |
| 8 | Separar templates | 🟢 Bajo | 🟡 Medio | Fase 4 |

> **Fase 2:** Sin refactor estructural. Cambios aislados de alto ROI.
> **Fase 3:** Introduce nuevas capas sin romper funcionalidad existente.
> **Fase 4:** Refactor profundo, mayor cobertura de pruebas.

---

## Arquitectura Objetivo

```
┌─────────────────────────────────────────────────────┐
│              CAPA DE PRESENTACIÓN                    │
│   operacion.php · index.php · pld_*.php             │
│   tpl/*.tpl.php  ←  sólo variables + HTML           │
└──────────────────┬──────────────────────────────────┘
                   │ llama a
┌──────────────────▼──────────────────────────────────┐
│              CAPA DE SERVICIOS  (nueva)              │
│   PLDOperacionService · PLDAvisoService             │
│   PLDAlertaService   · PLDReporteService            │
└──────────────────┬──────────────────────────────────┘
                   │ usa
┌──────────────────▼──────────────────────────────────┐
│         CAPA DE LÓGICA DE NEGOCIO                    │
│   CompliancePLD (config desde BD)                   │
│   ValidadorCompuesto (Strategy)                     │
│   CURP / RFC / VIN  (Value Objects)                 │
└──────────────────┬──────────────────────────────────┘
                   │ persiste vía
┌──────────────────▼──────────────────────────────────┐
│         CAPA DE ACCESO A DATOS                       │
│   PLDOperacionRepository · PLDAvisoRepository       │
│   PLDOperacion extends CommonObject  (CRUD básico)  │
└──────────────────┬──────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────┐
│              PERSISTENCIA                            │
│   MySQL / PostgreSQL — 7 tablas llx_pld_*           │
│   143 extrafields en tablas nativas Dolibarr        │
└─────────────────────────────────────────────────────┘
```

---

<!-- _class: lead -->

# Resumen

| Hoy | Objetivo |
|---|---|
| Lógica en vistas | Service Layer |
| SQL en controladores | Repository Pattern |
| Constantes hardcoded | Config en BD (admin/setup.php) |
| Validador monolítico | Strategy/Composite |
| `new Clase()` interno | Constructor Injection |
| `string` para CURP/RFC | Value Objects tipados |

**Prioridad inmediata:** Mejoras 3 y 4 — bajo riesgo, alto impacto, sin romper funcionalidad existente.
