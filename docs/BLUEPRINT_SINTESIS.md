# Blueprint PLD v3.0 — Síntesis Técnica para OMO

> **Documento de referencia rápida para agentes OMO**
> Proyecto: Módulo PLD Compliance Dolibarr — México
> Fecha: Febrero 2026

---

## 1. Contexto del Proyecto

**Objetivo:** Implementar módulo de Prevención de Lavado de Dinero (PLD) en Dolibarr ERP para cumplimiento LFPIORPI Art. 17 Fracción VIII (compraventa de vehículos en México).

**Entregables técnicos:**
- 152 extrafields en 6 tablas core Dolibarr
- 7 tablas especializadas PLD
- Generador XML para portal SPPLD del SAT
- Integración e.firma para firma digital
- Dashboard de alertas y compliance

**Timeline:** 8-10 semanas divididas en 3 fases

**Entorno:** Dolibarr 20-21, PHP 8.1+, MySQL/MariaDB, macOS M2 + Docker

---

## 2. Stack Tecnológico

```
OhMyOpenCode (orquestador)
    └── OpenCode (runtime)
          ├── Claude Sonnet 4.5    → Generador PHP/SQL/XML
          ├── Gemini 2.5 Pro       → Revisor compliance + Documentador
          ├── PHPUnit              → QA principal (tests PHP)
          └── pytest + lxml        → QA XML (validación XSD)

GitHub     → Control versiones obligatorio
Composer   → Gestor dependencias PHP
```

**Modelos asignados por agente OMO:**
- **Sisyphus** (Planner/Generador): `anthropic/claude-sonnet-4-5`
- **Librarian** (Explorador/Documentador): `google/gemini-2.5-pro`
- **Oracle** (Revisor compliance): `google/gemini-2.5-pro`
- **Explore** (Navegador codebase): `opencode/default`

---

## 3. Arquitectura de 3 Fases

| Fase | Alcance | Rama Git | Semanas | Herramienta principal |
|------|---------|----------|---------|----------------------|
| **Fase 1** | 152 extrafields en 6 tablas | `fase1/extrafields` | 1-3 | Sisyphus + Claude Code |
| **Fase 2** | 7 tablas especializadas PLD | `fase2/tablas-pld` | 4-5 | Sisyphus + Claude Code |
| **Fase 3** | XML SAT + e.firma + envío | `fase3/xml-sat` | 6-9 | Sisyphus + Oracle + Gemini |

**Ramas de soporte:**
- `qa/tests` → Scripts PHPUnit y pytest
- `docs/compliance` → Documentación regulatoria

---

## 4. Roles de Agentes

### 🛠️ Generador (Sisyphus + Claude Code)

**Responsabilidades:**
- Generar código PHP, SQL, XML
- Seguir convenciones Dolibarr (prefijo `llx_`, namespace, permisos)
- No detenerse hasta completar tarea (enforced by OMO)

**Archivos que puede modificar:**
- `/htdocs/custom/modulecompliancepld/**`
- `/sql/**`
- `/scripts/**`

**Archivos prohibidos:**
- `/htdocs/core/**` (core Dolibarr)
- `.env`, certificados `.cert/.key/.p12`

### 🔍 Revisor (Oracle + Gemini)

**Responsabilidades:**
- Validar compliance LFPIORPI Art. 17 Fracc. VIII
- Verificar umbrales: $250k vehículos nuevos, $100k usados
- Revisar regex CURP/RFC
- Validar estructura XML contra XSD SAT

**Checklist mínimo:**
- [ ] Campos obligatorios LFPIORPI presentes
- [ ] Umbrales de alerta correctos
- [ ] Período retención 5 años (Art. 18)
- [ ] XML cumple esquema XSD SAT

### 🧪 QA (PHPUnit + pytest vía Hook PostToolUse)

**División del trabajo:**
- **PHPUnit (80%)**: Tests unitarios e integración PHP
  - Validaciones CURP/RFC
  - Umbrales PLD
  - Extrafields en BD
- **pytest (20%)**: Validación XML contra XSD

**Hook automático:** Cuando Claude Code genera archivo `.php` → PHPUnit corre inmediatamente

**Criterio de aceptación:** 0 tests fallidos, cobertura mínima 80%

### 📄 Documentador (Librarian + Gemini)

**Documentos a mantener:**
- `/docs/manual-usuario-pld.md`
- `/docs/diccionario-datos.md`
- `/docs/trazabilidad-lfpiorpi.md` ← Campo → Artículo ley
- `/CHANGELOG.md`

---

## 5. Convenciones de Código

### Nomenclatura

```php
// Clases: CamelCase con prefijo módulo
class CompliancePLDOperacion { }

// Métodos: camelCase
public function generarAvisoXML() { }

// Variables: snake_case
$monto_operacion = 250000;

// Constantes: UPPER_SNAKE_CASE
const PLD_UMBRAL_VEHICULO_NUEVO = 250000;

// Tablas SQL: llx_ + nombre
llx_pld_operaciones
llx_pld_beneficiarios

// Extrafields: pld_ + nombre
pld_curp, pld_rfc, pld_nacionalidad
```

### Estructura del módulo

```
/htdocs/custom/modulecompliancepld/
├── modulecompliancepld.class.php    # Clase principal
├── class/
│   ├── compliancepld.class.php
│   ├── avisosat.class.php
│   └── efirma.class.php
├── core/modules/
├── core/triggers/
├── sql/
│   ├── llx_pld_*.sql
│   └── migrations/
├── langs/es_MX/
└── scripts/xml/
```

### Regex obligatorios

```php
// CURP (18 caracteres)
$curp_regex = '/^[A-Z]{1}[AEIOU]{1}[A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1])[HM]{1}(AS|BC|BS|CC|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TL|TS|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]{1}[0-9]{1}$/';

// RFC persona física (13 caracteres)
$rfc_fisica_regex = '/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/';

// RFC persona moral (12 caracteres)
$rfc_moral_regex = '/^[A-Z]{3}[0-9]{6}[A-Z0-9]{3}$/';
```

---

## 6. Estructura de Base de Datos

### 6 Tablas Core con Extrafields (Fase 1)

```
llx_socpeople_extrafields   ← Personas físicas (contactos)
llx_societe_extrafields     ← Personas morales (empresas)
llx_propal_extrafields      ← Propuestas comerciales
llx_commande_extrafields    ← Órdenes de venta
llx_facture_extrafields     ← Facturas
llx_product_extrafields     ← Vehículos (productos)
```

### 7 Tablas Especializadas PLD (Fase 2)

```sql
llx_pld_operaciones         -- Operaciones vulnerables
llx_pld_beneficiarios       -- Beneficiarios finales
llx_pld_documentos          -- Documentos digitalizados identificación
llx_pld_alertas             -- Alertas automáticas del sistema
llx_pld_envios_sat          -- Log de envíos al SAT
llx_pld_configuracion       -- Configuración del módulo
llx_pld_periodos_reporte    -- Períodos de reporte
```

---

## 7. Umbrales Regulatorios Críticos

| Tipo de operación | Umbral aviso | Referencia legal |
|---|---|---|
| Vehículo nuevo | ≥ $250,000 MXN | Art. 17 Fracc. VIII LFPIORPI |
| Vehículo usado | ≥ $100,000 MXN | Art. 17 Fracc. VIII LFPIORPI |
| Arrendamiento | ≥ $1,605 UMAs/mes | Reglamento LFPIORPI |
| Acumulado 6 meses | ≥ $500,000 MXN | Reglas UIF |

---

## 8. Convenciones Git

### Formato de commit

```
[PREFIJO] tipo: descripción breve

[cuerpo opcional]

Refs: #issue
Compliance: Art.XX LFPIORPI
```

**Prefijos por agente:**
- `[GEN]` → Generador (Sisyphus/Claude)
- `[REV]` → Revisor (Oracle/Gemini)
- `[QA]` → Tests (PHPUnit/pytest)
- `[DOC]` → Documentador (Librarian/Gemini)
- `[OMO]` → OhMyOpenCode (plan/config)
- `[HUMAN]` → Equipo humano

**Tipos:**
- `feat` → Nueva funcionalidad
- `fix` → Corrección bug
- `sql` → Cambio BD o migración
- `test` → Añadir/modificar tests
- `docs` → Documentación
- `xml` → Cambios generación XML

### Ejemplo completo

```bash
[GEN] feat: extrafields pld_curp y pld_rfc en llx_socpeople — 2/152

Se agregan los primeros campos de identificación PLD según 
especificaciones LFPIORPI Art. 17 Fracc. VIII.

Compliance: Art. 17 Fracc. VIII LFPIORPI
```

---

## 9. Testing

### Estructura de tests

```
/tests/
  ├── Unit/                    # PHPUnit
  │   ├── CURPValidationTest.php
  │   ├── RFCValidationTest.php
  │   └── UmbralesTest.php
  ├── Integration/             # PHPUnit
  │   ├── ExtrafieldsTest.php
  │   └── TablasPLDTest.php
  ├── XML/                     # pytest
  │   ├── test_xml_schema.py
  │   └── test_xml_encoding.py
  └── phpunit.xml
```

### Comandos de ejecución

```bash
# PHPUnit (automático via Hook PostToolUse)
composer test

# Solo unitarios
./vendor/bin/phpunit tests/Unit

# pytest para XML
pytest tests/XML/ -v

# Cobertura
./vendor/bin/phpunit --coverage-html coverage/
```

---

## 10. Workflow Operativo Diario

```
1. Pete define tarea → prompt a OMO
2. Sisyphus planifica y subdivide
3. Claude Code genera PHP/SQL/XML
4. Hook PostToolUse → PHPUnit corre automáticamente
5. Dev PHP revisa output + test results
6. Oracle/Gemini revisa compliance LFPIORPI
7. Librarian actualiza documentación
8. Commit a rama de fase con prefijo correcto
9. PR a develop (requiere 1 aprobación humana)
```

**Modo UltraWork (tareas largas):**
```bash
opencode
> ultrawork: genera los 30 extrafields PLD para llx_socpeople según AGENTS.md
```

Sisyphus no se detendrá hasta completar todos los 30 campos.

---

## 11. Campos XML SAT Obligatorios

```xml
<!-- Estructura mínima XML SPPLD -->
<Aviso>
  <SujetoObligado>
    <RFC><!-- RFC empresa --></RFC>
    <RazonSocial><!-- Nombre --></RazonSocial>
  </SujetoObligado>
  
  <Cliente>
    <Nombre><!-- Nombre completo --></Nombre>
    <CURP><!-- Solo personas físicas MX --></CURP>
    <RFC><!-- Obligatorio --></RFC>
    <Identificacion>
      <Tipo><!-- INE, Pasaporte, etc --></Tipo>
      <Numero><!-- Número documento --></Numero>
    </Identificacion>
    <Nacionalidad><!-- ISO 3166-1 alpha-3 --></Nacionalidad>
    <Domicilio>
      <Pais><!-- ISO 3166-1 alpha-3 --></Pais>
      <Estado><!-- Entidad federativa --></Estado>
    </Domicilio>
  </Cliente>
  
  <Operacion>
    <TipoActividad>808</TipoActividad> <!-- Vehículos -->
    <Monto><!-- En MXN --></Monto>
    <Moneda>MXN</Moneda>
    <Fecha><!-- ISO 8601 --></Fecha>
    <FormaPago><!-- EF/TC/TR --></FormaPago>
  </Operacion>
</Aviso>
```

**Validación obligatoria:** XML debe validarse contra XSD del SAT antes de cualquier intento de envío.

---

## 12. Documentos de Referencia

| Documento | Ubicación | Propósito |
|---|---|---|
| AGENTS.md | `/AGENTS.md` | Reglas completas del proyecto |
| Spec legal LFPIORPI | `/docs/regulatorio/*.pdf` | Especificaciones oficiales campos |
| Blueprint completo | `/docs/blueprint_pld_v3.docx` | Documento extenso de arquitectura |
| Esta síntesis | `/docs/BLUEPRINT_SINTESIS.md` | Referencia rápida para agentes |

---

## 13. Comandos Rápidos OMO

```bash
# Iniciar sesión
cd ~/dolibarr-pld-mx
opencode

# Dentro de OpenCode:
/models                    # Ver modelos disponibles
/model [nombre]           # Cambiar modelo
Tab                       # Cambiar a modo Prometheus (planner)

# Tareas comunes:
"Lee AGENTS.md y resume las reglas principales"
"ultrawork: [tarea larga que no debe interrumpirse]"
"Explora el codebase Dolibarr y mapea las tablas relacionadas con clientes"
```

---

## 14. Seguridad y Datos Sensibles

**NUNCA incluir en el código:**
- RFC/CURP reales
- Números de cuenta bancaria
- Credenciales e.firma (`.cert`, `.key`, `.p12`)
- API keys en archivos versionados

**Datos de prueba ficticios:**
```
RFC:  TEST010101ABC
CURP: TESE010101MDFSTR00
```

**Archivos protegidos en .gitignore:**
```
.env
*.cert
*.key
*.p12
/vendor/
xml-samples/*.xml  (solo .sample permitidos)
```

---

## 15. Checklist Pre-Commit

Antes de cada commit, verificar:

- [ ] Código sigue convenciones de nomenclatura
- [ ] Sin datos sensibles en el código
- [ ] Tests PHPUnit pasan (0 fallos)
- [ ] Sin archivos `vendor/` o `.env` en el commit
- [ ] Mensaje de commit con prefijo correcto
- [ ] Archivos SQL con `IF NOT EXISTS`
- [ ] XML validado contra XSD si aplica

---

## 16. Troubleshooting Común

| Problema | Solución |
|---|---|
| "Gemini busy" | Usar `gemini-2.5-flash` o esperar 5-15 min |
| Tests PHPUnit fallan | Verificar `composer.json` y `phpunit.xml` |
| OMO no lee AGENTS.md | Verificar que estás en directorio del proyecto |
| Commit rechazado | Verificar que no incluyes `vendor/` |
| XML no valida | Verificar encoding UTF-8 y namespace correcto |

---

## 17. Primera Tarea Recomendada

**Objetivo:** Generar primeros 30 extrafields de `llx_socpeople`

**Comando:**
```bash
cd ~/dolibarr-pld-mx
opencode
> ultrawork: genera los primeros 30 extrafields PLD para llx_socpeople conforme LFPIORPI Art.17 según AGENTS.md y el PDF en docs/regulatorio/
```

**Resultado esperado:**
- 30 definiciones de extrafields en SQL
- Script de migración numerado
- Tests PHPUnit para validar su existencia
- Documentación en diccionario-datos.md

---

*Blueprint PLD v3.0 — Síntesis Técnica | Generado con Claude Sonnet 4.5 | Febrero 2026*
