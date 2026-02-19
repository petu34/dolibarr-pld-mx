# AGENTS.md — Reglas del Proyecto PLD Dolibarr México
> **Versión:** 1.2 | **Última actualización:** Febrero 2026
> Este archivo es leído automáticamente por OhMyOpenCode (OMO) y Claude Code al iniciar sesión.
> **TODOS los agentes deben leer este archivo completo antes de ejecutar cualquier tarea.**

---

## 📋 Índice

1. [Contexto del Proyecto](#1-contexto-del-proyecto)
2. [Stack Tecnológico](#2-stack-tecnológico)
3. [Reglas Globales](#3-reglas-globales-todos-los-agentes)
4. [Agente Generador — Claude Code / Sisyphus](#4-agente-generador--claude-code--sisyphus)
5. [Agente Revisor — Gemini / Oracle](#5-agente-revisor--gemini--oracle)
6. [Agente QA — Antigravity / Hook PostToolUse](#6-agente-qa--antigravity--hook-posttooluse)
7. [Agente Documentador — Gemini / Librarian](#7-agente-documentador--gemini--librarian)
8. [Convenciones de Código Dolibarr](#8-convenciones-de-código-dolibarr)
9. [Estructura de Base de Datos PLD](#9-estructura-de-base-de-datos-pld)
10. [Convenciones de Git y GitHub](#10-convenciones-de-git-y-github)
11. [Referencia Regulatoria LFPIORPI](#11-referencia-regulatoria-lfpiorpi)
12. [Glosario del Proyecto](#12-glosario-del-proyecto)

---

## 1. Contexto del Proyecto

### ¿Qué es este proyecto?

Implementación del módulo de **Prevención de Lavado de Dinero (PLD)** para Dolibarr ERP en México, cumpliendo con la **Ley Federal para la Prevención e Identificación de Operaciones con Recursos de Procedencia Ilícita (LFPIORPI)**, empezando por el **Artículo 17, Fracción VIII** (actividades vulnerables — compraventa de vehículos), pero con la posibilidad de configurar en el futuro otras actividades vulnerables ( fracciones V - Servicios de construcción o desarrollo de bienes; XI, Servicios profesionales para realizar acciones a nombre de un cliente; XII - Servicos de fe pública; XIII-Recepción de donativos; XV-Derechos personales de uso o goce de bienes inmuebles ) .

### Objetivo técnico

Extender Dolibarr ERP con:
- **152 extrafields** distribuidos en 6 tablas del core de Dolibarr
- **7 tablas especializadas** de compliance PLD
- **Generación automática de XML** para envío al portal SPPLD del SAT
- **Firma electrónica (e.firma)** integrada en el flujo de envío
- **Dashboard de alertas** y seguimiento de operaciones vulnerables

### Fases del proyecto

| Fase | Alcance | Ramas Git | Semanas |
|------|---------|-----------|---------|
| Fase 1 | 152 extrafields en 6 tablas Dolibarr | `fase1/extrafields` | 1–3 |
| Fase 2 | 7 tablas especializadas PLD | `fase2/tablas-pld` | 4–5 |
| Fase 3 | XML SAT + e.firma + envío SPPLD | `fase3/xml-sat` | 6–9 |
| Continuo | Documentación técnica y regulatoria | `docs/compliance` | Todo |

### Entorno técnico

- **Dolibarr:** versiones 20–21 (PHP 8.x)
- **Base de datos:** MySQL/MariaDB
- **Servidor de desarrollo:** macOS M2 + Docker (local) o VPS Linux
- **PHP:** 8.1+
- **Estándar XML:** Esquema XSD publicado por el SAT México para SPPLD

---

## 2. Stack Tecnológico

```
OhMyOpenCode (OMO)           ← Orquestador central
    └── OpenCode (runtime)
          ├── Claude Sonnet 4.5   ← Generador (PHP/SQL/XML)
          ├── Gemini 2.5 Pro      ← Revisor + Documentador
          ├── PHPUnit             ← QA principal (PHP tests)
          └── pytest + lxml       ← QA específico (XML validation)

GitHub                       ← Control de versiones obligatorio
Composer                     ← Gestor de dependencias PHP
Dolibarr ERP v20-21         ← Sistema objetivo de implementación
SAT SPPLD Portal            ← Destino final del XML generado
```

---

## 3. Reglas Globales (todos los agentes)

> ⚠️ Estas reglas son **obligatorias** para todos los agentes sin excepción.

### 3.1 Idioma y terminología

- Todo el código, comentarios en código y mensajes de commit van en **español**
- Los nombres de variables, funciones y tablas siguen convenciones de Dolibarr (inglés técnico + prefijo `llx_`)
- Los documentos de compliance y reportes van en **español formal mexicano**
- Usar terminología regulatoria exacta: "Actividad Vulnerable", "Aviso", "Entidad Financiera", "UIF", "SAT"

### 3.2 Seguridad y datos sensibles

- **NUNCA** incluir en el código valores reales de RFC, CURP, números de cuenta o datos personales
- Usar datos ficticios para pruebas: `RFC: TEST010101ABC`, `CURP: TESE010101MDFSTR00`
- Las credenciales de e.firma **JAMÁS** se almacenan en el repositorio
- El archivo `.env` está en `.gitignore` — no crear alternativas que expongan credenciales
- Los archivos XML con datos reales de prueba van en `/xml-samples/` con sufijo `.sample`

### 3.3 Filosofía de código

- Código generado debe ser **indistinguible de código humano** — sin comentarios excesivos de IA
- Preferir código explícito sobre código "inteligente" difícil de auditar
- Cada función debe hacer **una sola cosa**
- Máximo 200 líneas por archivo — si crece más, dividir en módulos
- Siempre validar entradas del usuario antes de persistir en base de datos

### 3.4 Compatibilidad Dolibarr

- Siempre verificar compatibilidad con **Dolibarr 20 y 21**
- No romper funcionalidad nativa de Dolibarr bajo ninguna circunstancia
- Seguir la arquitectura de módulos de Dolibarr: `/htdocs/custom/modulecompliancepld/`
- Respetar el sistema de permisos de Dolibarr (`$user->hasRight()`)

---

## 4. Agente Generador — Claude Code / Sisyphus

**Modelo asignado:** `anthropic/claude-sonnet-4-5`
**Agente OMO:** Sisyphus (Planner)
**Rama principal:** `fase1/extrafields`, `fase2/tablas-pld`, `fase3/xml-sat`

### 4.1 Misión

Generar todo el código PHP, SQL y XML del módulo PLD. Sisyphus planifica la tarea y Claude Code la ejecuta. **No se detiene hasta completar el objetivo definido.**

### 4.2 Reglas específicas

#### PHP
- Usar **PHP 8.1+** con tipado estricto: `declare(strict_types=1);`
- Siempre heredar de las clases base de Dolibarr cuando corresponda:
  ```php
  // Correcto: extender clase base Dolibarr
  class ModulePLD extends DolibarrModules { ... }
  
  // Correcto: usar globals de Dolibarr
  global $db, $user, $langs, $conf;
  ```
- Sanitizar TODAS las entradas con `GETPOST()` de Dolibarr, nunca `$_POST` directo
- Escapar salidas HTML con `dol_escape_htmltag()`
- Para consultas SQL, usar el objeto `$db` de Dolibarr con parámetros preparados

#### SQL / Extrafields
- Prefijo obligatorio: `llx_` para todas las tablas
- Los extrafields de Dolibarr siguen la convención: `llx_[tabla]_extrafields`
- Script de migración con nombre secuencial: `migration_001_extrafields_socpeople.sql`
- Siempre incluir `IF NOT EXISTS` en `CREATE TABLE`
- Incluir `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` en migraciones
- Cada extrafield debe tener: `label` en español, `type` correcto, `size` definido, `enabled=1`

```sql
-- Ejemplo correcto de extrafield PLD
INSERT INTO llx_extrafields (
  attrname, label, type, size, elementtype, 
  fieldunique, fieldrequired, enabled, position
) VALUES (
  'pld_curp', 'CURP (PLD)', 'varchar', 18, 'socpeople',
  1, 1, 1, 100
);
```

#### XML SAT
- Seguir **exactamente** el esquema XSD del SAT para SPPLD
- Namespace obligatorio: el que especifique el XSD vigente del SAT
- Codificación: `UTF-8`
- Fechas en formato ISO 8601: `YYYY-MM-DDTHH:MM:SS`
- Montos en pesos mexicanos (MXN), sin decimales si son enteros
- Validar el XML contra el XSD antes de cualquier intento de envío

### 4.3 Flujo de trabajo del Generador

```
1. Leer especificación de la tarea
2. Consultar Librarian para contexto del codebase existente
3. Generar código en rama de fase correspondiente
4. NO hacer commit — esperar validación de QA y Revisor
5. Si QA falla → corregir antes de continuar
```

### 4.4 Archivos que el Generador puede modificar

```
✅ PUEDE modificar:
/htdocs/custom/modulecompliancepld/**
/scripts/migrations/*.sql
/xml-samples/*.xml

❌ NUNCA modificar:
/htdocs/core/**          (core de Dolibarr)
/htdocs/includes/**      (librerías base)
.env
*.cert, *.key, *.p12     (certificados e.firma)
```

---

## 5. Agente Revisor — Gemini / Oracle

**Modelo asignado:** `google/gemini-2.5-pro`
**Agente OMO:** Oracle
**Tarea:** Revisión de compliance regulatorio y validación técnica

### 5.1 Misión

Verificar que todo el código generado cumple con la **LFPIORPI** y sus reglamentos, y que la estructura técnica es correcta. Es el guardián regulatorio del proyecto.

### 5.2 Checklist de revisión (ejecutar en cada PR)

#### Compliance regulatorio
- [ ] ¿Todos los campos requeridos por Art. 17 Fracc. VIII están presentes?
- [ ] ¿Los umbrales de alerta son correctos? (Operaciones ≥ $250,000 MXN en vehículos)
- [ ] ¿El período de conservación de datos es de 5 años (Art. 18 LFPIORPI)?
- [ ] ¿Se captura correctamente la identificación del cliente (INE/IFE, pasaporte)?
- [ ] ¿Se registra la geolocalización de la operación cuando aplica?
- [ ] ¿El XML incluye todos los campos obligatorios del formato SPPLD?

#### Validación técnica
- [ ] ¿El código es compatible con Dolibarr 20 y 21?
- [ ] ¿Las consultas SQL son seguras (sin SQL injection)?
- [ ] ¿Las validaciones de CURP y RFC usan regex correcto?
- [ ] ¿Los tipos de datos SQL son apropiados para cada campo PLD?
- [ ] ¿Los índices de base de datos son adecuados para las consultas de reporte?

### 5.3 Regex de validación (usar exactamente estos)

```php
// CURP — 18 caracteres
$curp_regex = '/^[A-Z]{1}[AEIOU]{1}[A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1])[HM]{1}(AS|BC|BS|CC|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TL|TS|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]{1}[0-9]{1}$/';

// RFC persona física — 13 caracteres
$rfc_fisica_regex = '/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/';

// RFC persona moral — 12 caracteres
$rfc_moral_regex = '/^[A-Z]{3}[0-9]{6}[A-Z0-9]{3}$/';

// Monto en pesos (sin decimales o con 2 decimales)
$monto_regex = '/^\d{1,12}(\.\d{1,2})?$/';
```

### 5.4 Umbrales regulatorios críticos

| Tipo de operación | Umbral de aviso | Referencia legal |
|---|---|---|
| Compraventa vehículos nuevos | ≥ $250,000 MXN | Art. 17 Fracc. VIII LFPIORPI |
| Compraventa vehículos usados | ≥ $100,000 MXN | Art. 17 Fracc. VIII LFPIORPI |
| Arrendamiento vehículos | ≥ $1,605 UMAs/mes | Reglamento LFPIORPI |
| Acumulado 6 meses mismo cliente | ≥ $500,000 MXN | Reglas de carácter general UIF |

### 5.5 Formato del reporte de revisión

```markdown
## Revisión Compliance — [Nombre del archivo/PR]
**Fecha:** YYYY-MM-DD
**Revisor:** Gemini (Agente Oracle)

### ✅ Aprobado
- [Lista de elementos que cumplen]

### ⚠️ Observaciones (no bloquean merge)
- [Observaciones menores]

### ❌ Bloqueante (requiere corrección antes de merge)
- [Problemas críticos que impiden el merge]

### Artículos LFPIORPI verificados
- Art. 17 Fracc. VIII: [CUMPLE / NO CUMPLE]
- Art. 18 (conservación): [CUMPLE / NO CUMPLE]
```

---

## 6. Agente QA — PHPUnit + pytest / Hook PostToolUse

**Herramientas:** 
- **PHPUnit** (principal) — Tests PHP unitarios e integración
- **pytest + lxml** (específico) — Validación XML contra XSD del SAT

**Disparado por:** Hook `PostToolUse` de OMO — automático tras cada generación
**Rama:** `qa/tests`

### 6.1 Misión

Ejecutar pruebas automáticas cada vez que el Generador produce un archivo. El QA **no espera** — corre inmediatamente y reporta.

**División del trabajo:**
- Archivos `.php` → PHPUnit se ejecuta automáticamente
- Archivos `.xml` → pytest se ejecuta automáticamente

### 6.2 Suite de pruebas requerida

#### Tests PHPUnit — Validaciones PHP (80% del QA)

```php
<?php
// tests/Unit/CURPValidationTest.php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../htdocs/custom/modulecompliancepld/class/pldvalidator.class.php';

class CURPValidationTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new PLDValidator();
    }

    public function testCURPValida()
    {
        $valid_curps = ['TESE010101MDFSTR00', 'GOGA850315HDFNZR07'];
        foreach ($valid_curps as $curp) {
            $this->assertTrue(
                $this->validator->validarCURP($curp),
                "CURP válida rechazada: {$curp}"
            );
        }
    }

    public function testCURPInvalida()
    {
        $invalid_curps = ['1234567890ABCDEF', 'CORTO', '', 'TESE01010'];
        foreach ($invalid_curps as $curp) {
            $this->assertFalse(
                $this->validator->validarCURP($curp),
                "CURP inválida aceptada: {$curp}"
            );
        }
    }
}
```

```php
<?php
// tests/Unit/UmbralesTest.php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../htdocs/custom/modulecompliancepld/class/compliancepld.class.php';

class UmbralesTest extends TestCase
{
    public function testUmbralVehiculoNuevo()
    {
        $pld = new CompliancePLD();
        
        // >= $250,000 debe generar alerta
        $this->assertTrue($pld->debeGenerarAviso(250000, 'vehiculo_nuevo'));
        
        // < $250,000 NO debe generar alerta
        $this->assertFalse($pld->debeGenerarAviso(249999, 'vehiculo_nuevo'));
    }

    public function testUmbralVehiculoUsado()
    {
        $pld = new CompliancePLD();
        
        // >= $100,000 debe generar alerta
        $this->assertTrue($pld->debeGenerarAviso(100000, 'vehiculo_usado'));
        
        // < $100,000 NO debe generar alerta
        $this->assertFalse($pld->debeGenerarAviso(99999, 'vehiculo_usado'));
    }
}
```

```php
<?php
// tests/Integration/ExtrafieldsTest.php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../htdocs/master.inc.php';

class ExtrafieldsTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        global $db;
        $this->db = $db;
    }

    public function testExtrafieldsExisten()
    {
        $required_fields = [
            'pld_curp', 'pld_rfc', 'pld_identificacion_tipo',
            'pld_identificacion_numero', 'pld_nacionalidad',
            'pld_pais_residencia', 'pld_actividad_economica'
        ];

        $sql = "SELECT attrname FROM llx_extrafields WHERE elementtype = 'socpeople'";
        $result = $this->db->query($sql);
        
        $existing_fields = [];
        while ($obj = $this->db->fetch_object($result)) {
            $existing_fields[] = $obj->attrname;
        }

        foreach ($required_fields as $field) {
            $this->assertContains(
                $field,
                $existing_fields,
                "Extrafield faltante: {$field}"
            );
        }
    }
}
```

#### Tests pytest — Validación XML (20% del QA)

```python
# tests/XML/test_xml_schema.py
import pytest
from lxml import etree
import os

def test_xml_valido_contra_xsd():
    """Validar XML generado contra esquema XSD del SAT"""
    xml_path = 'xml-samples/aviso_veh_test.xml.sample'
    xsd_path = 'schemas/veh.xsd'
    
    assert os.path.exists(xml_path), f"XML no encontrado: {xml_path}"
    assert os.path.exists(xsd_path), f"XSD no encontrado: {xsd_path}"
    
    xml_doc = etree.parse(xml_path)
    xsd_doc = etree.XMLSchema(file=xsd_path)
    
    assert xsd_doc.validate(xml_doc), \
        f"XML no cumple esquema XSD del SAT: {xsd_doc.error_log}"

def test_xml_encoding_utf8():
    """Verificar que el XML está en UTF-8"""
    xml_path = 'xml-samples/aviso_test.xml.sample'
    
    with open(xml_path, 'rb') as f:
        content = f.read()
        assert content.startswith(b'<?xml version="1.0" encoding="UTF-8"'), \
            "XML no tiene encoding UTF-8 declarado"

def test_xml_namespace_correcto():
    """Verificar que el XML tiene el namespace del SAT"""
    xml_path = 'xml-samples/aviso_test.xml.sample'
    
    xml_doc = etree.parse(xml_path)
    root = xml_doc.getroot()
    
    # El namespace exacto dependerá del XSD del SAT
    # Este es un ejemplo genérico
    assert root.nsmap is not None, "XML sin namespace definido"
```

### 6.3 Configuración de phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php"
         colors="true"
         verbose="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">htdocs/custom/modulecompliancepld</directory>
        </include>
    </coverage>
</phpunit>
```

### 6.4 Reporte de QA

```
QA Report — [timestamp]
========================
PHPUnit Tests:
  Tests ejecutados: XX
  ✅ Pasaron: XX
  ❌ Fallaron: XX
  ⚠️  Warnings: XX
  Cobertura: XX%

pytest XML Tests:
  Tests ejecutados: X
  ✅ Pasaron: X
  ❌ Fallaron: X

Archivos analizados:
- [archivo.php]: PASS / FAIL
- [archivo.xml]: PASS / FAIL (validación XSD)

Estado general: PASS / FAIL
```

### 6.5 Criterio de aceptación

- **0 tests fallidos** en PHPUnit para proceder a revisión de compliance
- **0 tests fallidos** en pytest para XML generado
- **Cobertura mínima:** 80% en módulos de validación de datos PHP
- Los warnings no bloquean, pero se documentan

### 6.6 Comandos para ejecutar tests

```bash
# Ejecutar todos los tests PHPUnit
composer test

# Ejecutar solo tests unitarios
./vendor/bin/phpunit tests/Unit

# Ejecutar tests de integración
./vendor/bin/phpunit tests/Integration

# Ejecutar tests XML con pytest
pytest tests/XML/ -v

# Generar reporte de cobertura
./vendor/bin/phpunit --coverage-html coverage/
```

---

## 7. Agente Documentador — Gemini / Librarian

**Modelo asignado:** `google/gemini-2.5-pro`
**Agente OMO:** Librarian
**Rama:** `docs/compliance`

### 7.1 Misión

Mantener la documentación técnica y regulatoria actualizada **en paralelo** al desarrollo, no al final. Cada entregable de código tiene su documentación correspondiente.

### 7.2 Documentos que debe generar y mantener

| Documento | Ubicación | Actualizar cuando |
|---|---|---|
| Manual de usuario PLD | `/docs/manual-usuario-pld.md` | Cada nueva feature |
| Diccionario de datos | `/docs/diccionario-datos.md` | Cada nuevo extrafield o tabla |
| Trazabilidad regulatoria | `/docs/trazabilidad-lfpiorpi.md` | Cada campo relacionado con ley |
| Guía de instalación | `/docs/instalacion.md` | Cambios en dependencias |
| Changelog | `/CHANGELOG.md` | Cada merge a develop |
| Guía de envío SAT | `/docs/guia-envio-sppld.md` | Cambios en proceso XML |

### 7.3 Formato de trazabilidad regulatoria

```markdown
## Campo: pld_curp

| Atributo | Valor |
|---|---|
| Tabla Dolibarr | llx_socpeople_extrafields |
| Nombre técnico | pld_curp |
| Tipo SQL | VARCHAR(18) |
| Obligatorio | Sí |
| Validación | Regex CURP 18 caracteres |
| Artículo LFPIORPI | Art. 17 Fracc. VIII, inciso a) |
| Campo XML SAT | `/Aviso/Actividad/Identificacion/CURP` |
| Nota regulatoria | Requerido para personas físicas mexicanas |
```

### 7.4 Reglas de estilo para documentación

- Usar **español formal** sin anglicismos innecesarios
- Incluir ejemplos concretos en cada sección técnica
- Los documentos regulatorios deben citar **artículo y fracción exactos**
- Nunca incluir datos personales reales en ejemplos
- Formato de fechas en docs: `DD de [mes] de YYYY` (ej: 15 de febrero de 2026)

---

## 8. Convenciones de Código Dolibarr

### 8.1 Estructura del módulo

```
/htdocs/custom/modulecompliancepld/
├── modulecompliancepld.class.php    # Clase principal del módulo
├── core/
│   ├── modules/                     # Clases de módulo
│   └── triggers/                    # Triggers automáticos
├── class/
│   ├── compliancepld.class.php      # Objeto principal PLD
│   ├── avisosat.class.php           # Generación de avisos SAT
│   └── efirma.class.php             # Integración e.firma
├── htdocs/
│   ├── compliancepld/
│   │   ├── index.php                # Dashboard PLD
│   │   ├── operacion.php            # Captura de operaciones
│   │   └── generar_xml.php          # Generación y envío XML
│   └── css/
│       └── compliancepld.css
├── sql/
│   ├── llx_pld_operaciones.sql
│   ├── llx_pld_beneficiarios.sql
│   ├── llx_pld_documentos.sql
│   ├── llx_pld_alertas.sql
│   └── migrations/
│       └── migration_001_extrafields.sql
├── langs/
│   └── es_MX/
│       └── modulecompliancepld.lang
└── scripts/
    └── xml/
        └── generar_aviso.php
```

### 8.2 Nomenclatura

```php
// Clases: CamelCase con prefijo del módulo
class CompliancePLDOperacion { }

// Métodos: camelCase
public function generarAvisoXML() { }
public function validarCURP(string $curp): bool { }

// Variables: snake_case
$monto_operacion = 0;
$fecha_operacion = '';

// Constantes: UPPER_SNAKE_CASE
const PLD_UMBRAL_VEHICULO_NUEVO = 250000;
const PLD_UMBRAL_VEHICULO_USADO = 100000;

// Tablas SQL: llx_ + nombre_descriptivo
// llx_pld_operaciones
// llx_pld_beneficiarios
// llx_pld_documentos_digitalizados

// Extrafields: pld_ + nombre_campo
// pld_curp, pld_rfc, pld_nacionalidad
```

### 8.3 Encabezado obligatorio en archivos PHP

```php
<?php
/**
 * @file        [nombre_archivo].php
 * @module      CompliancePLD
 * @description [Descripción breve en español]
 * @author      [nombre del desarrollador o Agente IA]
 * @version     [versión]
 * @date        [fecha YYYY-MM-DD]
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 *
 * @license     GNU/GPL
 */

// Seguridad: evitar acceso directo
if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}
```

---

## 9. Estructura de Base de Datos PLD

### 9.1 Tablas especializadas (Fase 2)

```sql
-- Tabla principal de operaciones vulnerables
llx_pld_operaciones (
  rowid, ref, fk_soc, fk_facture,
  tipo_operacion, monto, moneda,
  fecha_operacion, estado_aviso,
  numero_aviso_sat, fecha_envio_sat,
  tms_creation, tms_modification, fk_user_creat
)

-- Beneficiarios finales de las operaciones
llx_pld_beneficiarios (
  rowid, fk_pld_operacion, nombre_completo,
  curp, rfc, fecha_nacimiento, nacionalidad,
  tipo_identificacion, numero_identificacion,
  pais_residencia, entidad_federativa
)

-- Documentos digitalizados de identificación
llx_pld_documentos (
  rowid, fk_soc, tipo_documento,
  numero_documento, fecha_emision, fecha_vencimiento,
  archivo_nombre, archivo_hash, archivo_ruta,
  verificado, fk_user_verifico, fecha_verificacion
)

-- Alertas automáticas del sistema
llx_pld_alertas (
  rowid, tipo_alerta, nivel_riesgo,
  fk_soc, fk_pld_operacion,
  descripcion, estado,
  fecha_alerta, fk_user_asignado, fecha_resolucion
)

-- Log de envíos al SAT
llx_pld_envios_sat (
  rowid, numero_folio_sat, tipo_aviso,
  fk_pld_operacion, xml_generado,
  estado_envio, respuesta_sat,
  fecha_generacion, fecha_envio, fk_user_envio
)

-- Configuración del módulo PLD
llx_pld_configuracion (
  rowid, param_nombre, param_valor,
  descripcion, fk_user_modifico, tms_modification
)

-- Períodos de reporte
llx_pld_periodos_reporte (
  rowid, periodo_inicio, periodo_fin,
  estado, total_operaciones, total_avisos,
  xml_consolidado, fecha_cierre
)
```

### 9.2 Extrafields en tablas Dolibarr (Fase 1 — parcial)

```
llx_socpeople_extrafields      ← Datos PLD de contactos / personas físicas
llx_societe_extrafields        ← Datos PLD de empresas / personas morales
llx_propal_extrafields         ← Datos PLD en propuestas comerciales
llx_commande_extrafields       ← Datos PLD en órdenes de venta
llx_facture_extrafields        ← Datos PLD en facturas
llx_product_extrafields        ← Datos PLD en vehículos (productos)
```

---

## 10. Convenciones de Git y GitHub

### 10.1 Formato de commit (obligatorio)

```
[PREFIJO] tipo: descripción breve en español

[cuerpo opcional con más detalles]

Refs: #[número de issue si aplica]
Compliance: [artículo LFPIORPI si aplica]
```

**Prefijos por agente:**

| Prefijo | Quién lo usa |
|---|---|
| `[GEN]` | Agente Generador (Claude Code / Sisyphus) |
| `[REV]` | Agente Revisor (Gemini / Oracle) |
| `[QA]` | Agente QA (Antigravity) |
| `[DOC]` | Agente Documentador (Gemini / Librarian) |
| `[OMO]` | OhMyOpenCode (plan o configuración) |
| `[HUMAN]` | Equipo humano (revisiones manuales) |

**Tipos de commit:**

```
feat:     Nueva funcionalidad
fix:      Corrección de bug
sql:      Cambio de base de datos o migración
test:     Añadir o modificar pruebas
docs:     Documentación únicamente
refactor: Reestructura sin cambio de funcionalidad
config:   Configuración del módulo o entorno
xml:      Cambios en generación o esquema XML SAT
```

**Ejemplos:**

```bash
[GEN] feat: extrafields pld_curp y pld_rfc en llx_socpeople — 2/152 campos

[REV] fix: corregir umbral aviso vehículo nuevo a $250,000 MXN
Compliance: Art. 17 Fracc. VIII LFPIORPI

[QA] test: validación CURP regex — 100% pass en 45 casos de prueba

[DOC] docs: agregar trazabilidad campo pld_curp → Art. 17 Fracc. VIII

[GEN] sql: migración 003 — crear tabla llx_pld_beneficiarios
```

### 10.2 Reglas de ramas

```
main        ← NUNCA commit directo. Solo merge de PRs aprobados
develop     ← Rama de integración. PR requiere 1 aprobación humana
fase1/*     ← Feature branches de Fase 1
fase2/*     ← Feature branches de Fase 2
fase3/*     ← Feature branches de Fase 3
qa/*        ← Scripts de prueba y validación
docs/*      ← Documentación técnica
hotfix/*    ← Correcciones urgentes en producción
```

### 10.3 Proceso de Pull Request

```
1. Crear PR de rama de fase → develop
2. Título del PR: "[FASE X] Descripción clara del cambio"
3. Checklist del PR:
   [ ] Tests QA pasaron (0 fallos)
   [ ] Revisión de compliance completada por Agente Revisor
   [ ] Documentación actualizada
   [ ] Sin datos reales en el código
   [ ] Compatible con Dolibarr 20 y 21
4. Al menos 1 aprobación humana
5. Merge con squash o merge commit (NO rebase en develop)
```

---

## 11. Referencia Regulatoria LFPIORPI

### 11.1 Artículos clave

| Artículo | Tema | Impacto en el sistema |
|---|---|---|
| Art. 17 Fracc. VIII | Compraventa de vehículos como actividad vulnerable | Umbral de reporte, campos obligatorios |
| Art. 18 | Conservación de información (5 años) | Política de retención de datos en BD |
| Art. 24 | Presentación de avisos a la UIF | Proceso de generación y envío XML |
| Art. 32 | Medidas de identificación del cliente | Campos de identificación obligatorios |
| Art. 38 | Sanciones por incumplimiento | Justificación de la criticidad del módulo |

### 11.2 Campos mínimos obligatorios por aviso SAT

```
Datos del sujeto obligado (empresa):
  - RFC de la empresa
  - Nombre o razón social
  - Domicilio fiscal

Datos del cliente (persona física o moral):
  - Nombre completo
  - CURP (personas físicas mexicanas)
  - RFC
  - Tipo y número de identificación oficial
  - Nacionalidad
  - País y estado de residencia
  - Actividad económica (SCIAN)

Datos de la operación:
  - Tipo de actividad vulnerable
  - Monto total en pesos mexicanos
  - Fecha de la operación
  - Descripción del vehículo (marca, modelo, año, VIN)
  - Forma de pago

Datos del beneficiario final (si aplica):
  - Mismos campos que cliente
```
#### Documentos regulatorios de referencia

- `/docs/regulatorio/RESOLUCION-Avisos.pdf` — Especificación oficial de campos requeridos por LFPIORPI

### 11.3 Catálogos SAT utilizados en el XML

```
Catálogo de Actividades Vulnerables: Clave "808" para vehículos
Catálogo de Tipos de Aviso: A (Acto), O (Operación Inusual), P (Preocupante)
Catálogo de Formas de Pago: EF (Efectivo), TC (Tarjeta crédito), TR (Transferencia)
Catálogo de Países: ISO 3166-1 alpha-3
Catálogo de Monedas: ISO 4217
```

---

## 12. Glosario del Proyecto

| Término | Definición |
|---|---|
| **LFPIORPI** | Ley Federal para la Prevención e Identificación de Operaciones con Recursos de Procedencia Ilícita |
| **PLD** | Prevención de Lavado de Dinero |
| **UIF** | Unidad de Inteligencia Financiera (dependencia del SAT) |
| **SPPLD** | Sistema de Portal para la Prevención de Lavado de Dinero (portal del SAT) |
| **Aviso** | Reporte de operación vulnerable enviado al SAT en formato XML |
| **Actividad Vulnerable** | Operación que por su naturaleza o monto requiere ser reportada al SAT |
| **Sujeto Obligado** | Empresa o persona que debe presentar avisos (en este caso, el concesionario de vehículos) |
| **Beneficiario Final** | Persona física que en última instancia controla o se beneficia de la operación |
| **e.firma** | Firma Electrónica Avanzada del SAT, necesaria para firmar y enviar los XML |
| **Extrafield** | Campo personalizado de Dolibarr que extiende las tablas existentes sin modificarlas |
| **LLX** | Prefijo estándar de Dolibarr para todas sus tablas y objetos |
| **XSD** | XML Schema Definition — esquema que define la estructura válida de los XML del SAT |
| **CURP** | Clave Única de Registro de Población — 18 caracteres |
| **RFC** | Registro Federal de Contribuyentes — 12 o 13 caracteres |
| **UMA** | Unidad de Medida y Actualización (referencia económica del INEGI) |

---

> **Nota para agentes IA:** Si tienes dudas sobre alguna regla de este archivo,
> consulta al **Agente Oracle** antes de proceder.
> Si la duda es regulatoria, cita el artículo específico de la LFPIORPI en tu respuesta.

---
*AGENTS.md — Proyecto PLD Dolibarr México | Generado con Claude Sonnet 4.5 (Anthropic)*
