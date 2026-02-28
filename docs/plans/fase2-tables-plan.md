# Plan Fase 2: Tablas Especializadas PLD — Enfoque Híbrido ECM

> **Versión:** 2.0 — Reescritura completa  
> **Fecha:** Febrero 2026  
> **Enfoque:** Híbrido — reutiliza ECM nativo de Dolibarr para documentos

---

## 🎯 Objetivo de Fase 2

Crear **5 tablas especializadas** para compliance PLD que **no pueden manejarse con extrafields**, reutilizando el módulo ECM de Dolibarr para almacenamiento de documentos.

**Cambios respecto a v1.0:**
- ✅ Reutiliza `llx_ecm_files` para almacenamiento de archivos
- ✅ Elimina anti-patterns (ENUM, JSON, GENERATED ALWAYS AS, CHECK constraints, triggers SQL)
- ✅ Reduce `llx_pld_documento` de 50 a 12 columnas (solo metadatos PLD)
- ✅ Elimina 2 tablas relacionales innecesarias
- ✅ Cambia nombres a **singular** según convención del proyecto
- ✅ Timeline realista: **2-3 semanas** (no 8)

---

## 📊 Alcance de Fase 2

### Prerequisito
✅ Fase 1.2 completada — extrafields + validaciones implementados

### Tablas a Crear (5 principales)

| # | Tabla | Propósito | Columnas | Complejidad |
|---|-------|-----------|----------|-------------|
| 1 | `llx_pld_operacion` | Operaciones vulnerables | ~25 | Alta |
| 2 | `llx_pld_beneficiario` | Beneficiarios controladores | ~18 | Media |
| 3 | `llx_pld_documento` | Metadatos PLD (híbrido con ECM) | ~12 | Baja |
| 4 | `llx_pld_aviso` | Avisos SAT | ~20 | Media |
| 5 | `llx_pld_alerta` | Alertas internas | ~15 | Baja |

**Total:** 5 tablas, ~90 columnas (vs 9 tablas / 400+ columnas en v1.0)

---

## 🗂️ TABLA 1: llx_pld_operacion

### Propósito
Registro central de operaciones vulnerables. Es la tabla más importante de la Fase 2.

### Estructura SQL

```sql
CREATE TABLE IF NOT EXISTS llx_pld_operacion (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Referencias Dolibarr
  fk_facture INT DEFAULT NULL,
  fk_societe INT NOT NULL,
  fk_product INT DEFAULT NULL COMMENT 'ID del vehículo',
  
  -- Tipo de operación
  tipo_operacion VARCHAR(50) NOT NULL DEFAULT 'venta_vehiculo',
  tipo_actividad_vulnerable VARCHAR(10) NOT NULL COMMENT 'Fracción Art. 17 LFPIORPI',
  
  -- Datos de la operación
  fecha_operacion DATE NOT NULL,
  mes_reportado VARCHAR(6) NOT NULL COMMENT 'YYYYMM para agrupación',
  folio_interno VARCHAR(50),
  
  -- Montos
  moneda VARCHAR(3) DEFAULT 'MXN',
  monto_mxn DECIMAL(15,2) NOT NULL,
  
  -- Umbrales (calculados en PHP, no triggers SQL)
  supera_umbral TINYINT(1) DEFAULT 0,
  
  -- Estado de cumplimiento
  cliente_identificado TINYINT(1) DEFAULT 0,
  documentacion_completa TINYINT(1) DEFAULT 0,
  
  -- Avisos
  requiere_aviso TINYINT(1) DEFAULT 0,
  aviso_presentado TINYINT(1) DEFAULT 0,
  fk_pld_aviso INT DEFAULT NULL,
  
  -- Alertas
  genera_alerta TINYINT(1) DEFAULT 0,
  fk_pld_alerta INT DEFAULT NULL,
  
  -- Estado
  estado VARCHAR(50) DEFAULT 'borrador' COMMENT 'borrador|pendiente_documentacion|completada|cancelada',
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  -- Índices
  INDEX idx_societe (fk_societe),
  INDEX idx_facture (fk_facture),
  INDEX idx_fecha (fecha_operacion),
  INDEX idx_mes_reportado (mes_reportado),
  INDEX idx_requiere_aviso (requiere_aviso),
  INDEX idx_estado (estado),
  
  -- Foreign keys
  FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE RESTRICT,
  FOREIGN KEY (fk_facture) REFERENCES llx_facture(rowid) ON DELETE SET NULL,
  FOREIGN KEY (fk_product) REFERENCES llx_product(rowid) ON DELETE SET NULL
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Reglas de Negocio (implementadas en CompliancePLD class)

**Umbrales para actividad vulnerable:**
- Venta vehículo nuevo: **≥ $377,778.20 MXN** (3,220 UMAs × $117.31 2026)
- Venta vehículo usado: **≥ $117,310.00 MXN** (1,000 UMAs × $117.31 2026)

**Lógica en PHP, NO en SQL:**
```php
// En class/compliancepld.class.php
public function evaluarUmbral(float $monto, string $tipo_vehiculo): array
{
    $umbral = ($tipo_vehiculo === 'nuevo') ? 377778.20 : 117310.00;
    
    return [
        'supera_umbral' => $monto >= $umbral,
        'umbral_aplicado' => $umbral,
        'requiere_aviso' => $monto >= $umbral
    ];
}
```

---

## 👥 TABLA 2: llx_pld_beneficiario

### Propósito
Beneficiarios controladores de personas morales (Art. 18 LFPIORPI).

### Estructura SQL

```sql
CREATE TABLE IF NOT EXISTS llx_pld_beneficiario (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Relación
  fk_societe INT NOT NULL COMMENT 'Empresa a la que pertenece el beneficiario',
  fk_socpeople INT DEFAULT NULL COMMENT 'Contacto Dolibarr si existe',
  
  -- Tipo de beneficiario
  tipo_beneficiario VARCHAR(50) NOT NULL COMMENT 'accionista|fideicomitente|fideicomisario|administrador',
  
  -- Datos personales (si fk_socpeople = NULL, se capturan aquí)
  nombre VARCHAR(100),
  apellido_paterno VARCHAR(100),
  apellido_materno VARCHAR(100),
  
  -- Identificación
  curp VARCHAR(18),
  rfc VARCHAR(13),
  fecha_nacimiento DATE,
  nacionalidad VARCHAR(50),
  
  -- Participación
  porcentaje_participacion DECIMAL(5,2) NOT NULL COMMENT 'Máximo 100.00',
  
  -- PEP (Persona Expuesta Políticamente)
  es_pep TINYINT(1) DEFAULT 0,
  cargo_pep VARCHAR(200),
  
  -- Validación
  verificado TINYINT(1) DEFAULT 0,
  fecha_verificacion DATE,
  fk_user_verificador INT,
  
  -- Vigencia
  activo TINYINT(1) DEFAULT 1,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  -- Índices
  INDEX idx_societe (fk_societe),
  INDEX idx_curp (curp),
  INDEX idx_rfc (rfc),
  INDEX idx_pep (es_pep),
  INDEX idx_activo (activo),
  
  -- Foreign keys
  FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE CASCADE,
  FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople(rowid) ON DELETE SET NULL
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Reglas de Negocio

**Validación en PHP:**
```php
// Validar que suma de porcentajes ≤ 100%
public function validarPorcentajes(int $fk_societe, float $nuevo_porcentaje, int $exclude_rowid = 0): bool
{
    $sql = "SELECT SUM(porcentaje_participacion) as total
            FROM llx_pld_beneficiario
            WHERE fk_societe = ".(int)$fk_societe."
              AND activo = 1
              AND rowid != ".(int)$exclude_rowid;
    
    $result = $this->db->query($sql);
    $total = $result ? $this->db->fetch_object($result)->total : 0;
    
    return ($total + $nuevo_porcentaje) <= 100;
}
```

---

## 📄 TABLA 3: llx_pld_documento (Híbrido con ECM)

### Propósito
**Metadatos PLD** que complementan `llx_ecm_files`. NO almacena archivos — solo vincula documentos ECM con datos regulatorios.

### Arquitectura Híbrida

```
┌─────────────────────────────────┐
│ llx_pld_documento (12 columnas) │
│ Solo metadatos PLD              │
├─────────────────────────────────┤
│ fk_ecm_files → llx_ecm_files    │ ← Vínculo al archivo real
│ tipo_documento_pld              │
│ numero_documento                │
│ fecha_vencimiento               │
│ verificado                      │
└─────────────────────────────────┘
         ↓ FK
┌─────────────────────────────────┐
│ llx_ecm_files (Dolibarr nativo) │
│ Almacenamiento real del archivo │
├─────────────────────────────────┤
│ filepath, filename, label       │
│ src_object_type, src_object_id  │
│ date_c, fk_user_c               │
└─────────────────────────────────┘
```

### Estructura SQL

```sql
CREATE TABLE IF NOT EXISTS llx_pld_documento (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Vínculo al archivo en ECM (obligatorio)
  fk_ecm_files INT NOT NULL,
  
  -- Relación opcional con entidades Dolibarr
  fk_societe INT DEFAULT NULL,
  fk_socpeople INT DEFAULT NULL,
  
  -- Tipo de documento PLD
  tipo_documento_pld VARCHAR(100) NOT NULL COMMENT 'INE|pasaporte|acta_constitutiva|comprobante_domicilio',
  
  -- Datos del documento
  numero_documento VARCHAR(50),
  fecha_emision DATE,
  fecha_vencimiento DATE COMMENT 'Para IDs con vigencia',
  autoridad_emite VARCHAR(100),
  
  -- Validación PLD
  verificado TINYINT(1) DEFAULT 0,
  fecha_verificacion DATE,
  fk_user_verificador INT,
  
  -- Retención regulatoria (5 años Art. 18 LFPIORPI)
  fecha_retencion_hasta DATE COMMENT 'Auto-calculado: fecha_emision + 5 años',
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  
  -- Índices
  INDEX idx_ecm_files (fk_ecm_files),
  INDEX idx_societe (fk_societe),
  INDEX idx_socpeople (fk_socpeople),
  INDEX idx_tipo (tipo_documento_pld),
  INDEX idx_verificado (verificado),
  INDEX idx_vencimiento (fecha_vencimiento),
  
  -- Foreign keys
  FOREIGN KEY (fk_ecm_files) REFERENCES llx_ecm_files(rowid) ON DELETE CASCADE,
  FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE CASCADE,
  FOREIGN KEY (fk_socpeople) REFERENCES llx_socpeople(rowid) ON DELETE CASCADE
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Flujo de Trabajo

**Upload de documento:**
```php
// 1. Crear archivo en ECM (usa clase EcmFiles de Dolibarr)
$ecmfile = new EcmFiles($db);
$ecmfile->filepath = 'pld_documento/'.((int)$societe_id);
$ecmfile->filename = 'ine_'.time().'.pdf';
$ecmfile->src_object_type = 'societe';
$ecmfile->src_object_id = $societe_id;
$ecmfile->label = dol_hash($file_content, 'md5'); // Hash del contenido
$ecmfile_id = $ecmfile->create($user);

// 2. Crear metadatos PLD
$pld_doc = new PLDDocumento($db);
$pld_doc->fk_ecm_files = $ecmfile_id;
$pld_doc->fk_societe = $societe_id;
$pld_doc->tipo_documento_pld = 'INE';
$pld_doc->numero_documento = 'TESE010101MDFSTR00';
$pld_doc->fecha_emision = '2020-01-01';
$pld_doc->fecha_vencimiento = '2030-01-01';
$pld_doc->fecha_retencion_hasta = date('Y-m-d', strtotime('+5 years'));
$pld_doc->create($user);
```

---

## 📮 TABLA 4: llx_pld_aviso

### Propósito
Control de avisos presentados al SAT SPPLD con trazabilidad completa.

### Estructura SQL

```sql
CREATE TABLE IF NOT EXISTS llx_pld_aviso (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Clasificación del aviso
  tipo_aviso VARCHAR(50) NOT NULL COMMENT 'mensual|24_horas|acumulado',
  
  -- Período reportado
  mes_reportado VARCHAR(6) NOT NULL COMMENT 'YYYYMM',
  fecha_inicio_periodo DATE,
  fecha_fin_periodo DATE,
  
  -- Referencia única del aviso
  referencia_aviso VARCHAR(50) UNIQUE,
  
  -- Operaciones incluidas
  numero_operaciones INT DEFAULT 0,
  monto_total_operaciones DECIMAL(15,2) DEFAULT 0,
  
  -- XML Generado
  archivo_xml_ruta VARCHAR(500) COMMENT 'Ruta al archivo XML en documents/',
  archivo_xml_hash VARCHAR(64) COMMENT 'SHA256 del XML para integridad',
  fecha_generacion_xml DATETIME,
  
  -- Presentación
  presentado TINYINT(1) DEFAULT 0,
  fecha_presentacion DATETIME,
  fk_user_presento INT,
  
  -- Acuse SAT
  folio_sat VARCHAR(100),
  fecha_acuse DATETIME,
  estado_acuse VARCHAR(50) COMMENT 'aceptado|rechazado|pendiente',
  
  -- Estado general
  estado VARCHAR(50) DEFAULT 'borrador' COMMENT 'borrador|generado|enviado|aceptado|rechazado',
  
  -- Observaciones
  observaciones TEXT,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  -- Índices
  INDEX idx_mes_reportado (mes_reportado),
  INDEX idx_tipo_aviso (tipo_aviso),
  INDEX idx_estado (estado),
  INDEX idx_presentado (presentado),
  INDEX idx_folio_sat (folio_sat),
  UNIQUE INDEX idx_referencia (referencia_aviso)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Relación con Operaciones

**Tabla relacional (creada automáticamente en migración):**
```sql
CREATE TABLE IF NOT EXISTS llx_pld_aviso_operacion (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  fk_pld_aviso INT NOT NULL,
  fk_pld_operacion INT NOT NULL,
  
  INDEX idx_aviso (fk_pld_aviso),
  INDEX idx_operacion (fk_pld_operacion),
  UNIQUE INDEX idx_aviso_operacion (fk_pld_aviso, fk_pld_operacion),
  
  FOREIGN KEY (fk_pld_aviso) REFERENCES llx_pld_aviso(rowid) ON DELETE CASCADE,
  FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion(rowid) ON DELETE CASCADE
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🚨 TABLA 5: llx_pld_alerta

### Propósito
Sistema de alertas internas para operaciones sospechosas o inconsistencias.

### Estructura SQL

```sql
CREATE TABLE IF NOT EXISTS llx_pld_alerta (
  rowid INT PRIMARY KEY AUTO_INCREMENT,
  entity INT DEFAULT 1 NOT NULL,
  
  -- Relaciones
  fk_pld_operacion INT NOT NULL,
  fk_societe INT NOT NULL,
  
  -- Clasificación de la alerta
  tipo_alerta VARCHAR(100) NOT NULL COMMENT 'inusual|inconsistencia|pep|limite_efectivo',
  nivel_riesgo VARCHAR(50) NOT NULL COMMENT 'bajo|medio|alto|critico',
  
  -- Descripción
  titulo VARCHAR(200) NOT NULL,
  descripcion TEXT NOT NULL,
  
  -- PEP
  involucra_pep TINYINT(1) DEFAULT 0,
  
  -- Análisis
  requiere_analisis TINYINT(1) DEFAULT 1,
  fecha_analisis DATE,
  fk_user_analista INT,
  decision VARCHAR(50) COMMENT 'aprobar|rechazar|escalar|aviso_24hrs',
  
  -- Estado
  estado VARCHAR(50) DEFAULT 'nueva' COMMENT 'nueva|en_revision|resuelta|archivada',
  fecha_resolucion DATE,
  
  -- Auditoría
  datec DATETIME NOT NULL,
  tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_user_creat INT,
  fk_user_modif INT,
  
  -- Índices
  INDEX idx_operacion (fk_pld_operacion),
  INDEX idx_societe (fk_societe),
  INDEX idx_tipo_alerta (tipo_alerta),
  INDEX idx_nivel_riesgo (nivel_riesgo),
  INDEX idx_estado (estado),
  
  -- Foreign keys
  FOREIGN KEY (fk_pld_operacion) REFERENCES llx_pld_operacion(rowid) ON DELETE CASCADE,
  FOREIGN KEY (fk_societe) REFERENCES llx_societe(rowid) ON DELETE RESTRICT
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🛠️ Plan de Implementación Fase 2

### Timeline: **2-3 semanas** (12-15 días hábiles)

#### Semana 1: Core Database (Días 1-5)
- [ ] **Día 1-2:** Crear script de migración SQL con las 5 tablas + tabla relacional
- [ ] **Día 2-3:** Crear clases PHP para cada tabla (`PLDOperacion`, `PLDBeneficiario`, etc.)
- [ ] **Día 3-4:** Implementar métodos CRUD básicos en cada clase
- [ ] **Día 4-5:** Tests unitarios PHPUnit para clases (mínimo 80% cobertura)

#### Semana 2: Business Logic + ECM Integration (Días 6-10)
- [ ] **Día 6-7:** Integración con `llx_ecm_files` para documentos
- [ ] **Día 7-8:** Lógica de umbrales en `CompliancePLD` class
- [ ] **Día 8-9:** Validación de beneficiarios (porcentajes, PEP)
- [ ] **Día 9-10:** Tests de integración ECM

#### Semana 3: UI + Verificación (Días 11-15)
- [ ] **Día 11-12:** Formulario de operaciones (`operacion.php`)
- [ ] **Día 12-13:** Formulario de beneficiarios (`beneficiario.php`)
- [ ] **Día 13-14:** Upload de documentos con ECM
- [ ] **Día 14-15:** Tests funcionales + corrección de bugs

---

## ✅ Checklist de Completitud

### Base de Datos
- [ ] 5 tablas creadas con `IF NOT EXISTS`
- [ ] Tabla relacional `llx_pld_aviso_operacion` creada
- [ ] Todas las FK definidas correctamente
- [ ] Índices en columnas de búsqueda frecuente
- [ ] Script de migración ejecutable múltiples veces (idempotente)

### Clases PHP
- [ ] 5 clases con `declare(strict_types=1)`
- [ ] Heredan de `CommonObject` de Dolibarr
- [ ] Métodos CRUD: `create()`, `fetch()`, `update()`, `delete()`
- [ ] Validaciones en PHP (no triggers SQL)

### Integración ECM
- [ ] Documentos se suben vía `EcmFiles` class
- [ ] `llx_pld_documento` solo almacena metadatos
- [ ] Hash SHA256 se calcula en PHP
- [ ] Retención 5 años se calcula automáticamente

### Tests
- [ ] PHPUnit: 100% tests pasan
- [ ] Cobertura mínima: 80% en clases de negocio
- [ ] Tests de integridad referencial

---

## 📊 Métricas de Éxito

| Métrica | Objetivo |
|---------|----------|
| Tablas creadas | 5/5 + 1 relacional |
| Clases PHP implementadas | 5/5 |
| Tests PHPUnit passing | 100% |
| Cobertura de código | ≥ 80% |
| Tiempo de implementación | ≤ 15 días |
| SQL anti-patterns | 0 |
| Documentos gestionados con ECM | 100% |

---

## 🚀 Post Fase 2 → Fase 3

**Inputs necesarios para Fase 3:**
- ✅ `llx_pld_operacion` con datos completos
- ✅ `llx_pld_beneficiario` verificados
- ✅ `llx_pld_documento` con archivos en ECM
- ✅ `llx_pld_aviso` con estructura lista para XML

**Outputs de Fase 3:**
- Generador de XML SAT según XSD
- Integración con e.firma
- Envío automático SPPLD

---

## 💡 Decisiones de Diseño

### ✅ Por qué NO usamos:
- **ENUM:** Dificulta cambios, incompatible con algunos ORMs → usar `VARCHAR(50)`
- **JSON columns:** No todas las versiones MySQL lo soportan bien → usar tablas relacionales
- **GENERATED ALWAYS AS:** Complicado de debugear → calcular en PHP
- **CHECK constraints:** No portable entre MySQL/MariaDB → validar en PHP
- **SQL Triggers:** Lógica oculta, difícil de testear → lógica en clases PHP
- **Stored Procedures:** Misma razón que triggers → métodos PHP

### ✅ Por qué SÍ usamos:
- **Foreign keys:** Integridad referencial esencial
- **Índices:** Performance en queries de reporte
- **TINYINT(1):** Para booleanos (compatible con Dolibarr)
- **VARCHAR en lugar de ENUM:** Flexibilidad sin cambios de schema
- **Clases PHP:** Lógica de negocio testeable y auditable
- **Híbrido ECM:** Reutiliza infraestructura probada de Dolibarr

---

**Plan aprobado y listo para ejecución.**
