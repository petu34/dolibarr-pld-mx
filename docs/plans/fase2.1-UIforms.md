# Plan Fase 2.1: Formularios UI y Páginas de Navegación PLD

> **Versión:** 1.0  
> **Fecha:** 27 de febrero de 2026  
> **Prerequisito:** Fase 2 completada — 6 tablas PLD desplegadas + 12 FKs + 38 índices  
> **Rama:** `fase2/ui-forms`  

---

## 🎯 Objetivo

Completar la capa de interfaz de usuario del módulo PLD para que todas las entradas del menú funcionen, los formularios CRUD operen correctamente, y el dashboard muestre información útil al oficial de cumplimiento.

---

## 📋 Diagnóstico Actual

### Estado de archivos existentes

| Archivo | Existe | Funciona | Problema |
|---------|--------|----------|----------|
| `index.php` | ✅ | ⚠️ | Muestra template vacío del ModuleBuilder, sin contenido PLD |
| `operacion.php` | ✅ | ⚠️ | Solo muestra formulario con `action=create` o detalle con `?id=X`; **sin vista de lista** por defecto |
| `beneficiario.php` | ✅ | ⚠️ | Mismo problema: sin vista de lista por defecto |
| `documento.php` | ✅ | ⚠️ | Mismo problema: sin vista de lista por defecto |
| `operaciones_list.php` | ❌ | — | Referenciado en menú, no existe |
| `avisos_list.php` | ❌ | — | Referenciado en menú, no existe |
| `alertas_list.php` | ❌ | — | Referenciado en menú, no existe |
| `reportes.php` | ❌ | — | Referenciado en menú, no existe |
| `admin/setup.php` | ❌ | — | Referenciado en menú, no existe |

### Clases PHP disponibles (todas con CRUD completo)

| Clase | Archivo | Métodos |
|-------|---------|---------|
| `PLDOperacion` | `pldoperacion.class.php` | create, fetch, update, delete, evaluarUmbral, generarFolioInterno |
| `PLDBeneficiario` | `pldbeneficiario.class.php` | create, fetch, update, delete |
| `PLDDocumento` | `plddocumento.class.php` | create, fetch, update, delete |
| `PLDAviso` | `pldaviso.class.php` | create, fetch, update, delete |
| `PLDAlerta` | `pldalerta.class.php` | create, fetch, update, delete |
| `PLDDocumentoUploader` | `plddocumentouploader.class.php` | upload (integración ECM) |
| `CompliancePLD` | `compliancepld.class.php` | debeGenerarAviso, validaciones |

### Claves de traducción existentes

130+ claves PLD ya definidas en `modulecompliancepld.lang`, incluyendo:
- Menús, tipos de operación, estados, alertas, errores, catálogos de entidades federativas
- Faltan: etiquetas de columnas de lista, botones de acción, títulos de páginas de lista

---

## 🏗️ Arquitectura de Páginas

### Patrón Dolibarr para módulos custom

```
[entidad]_list.php    ← Lista paginada con filtros y ordenamiento
[entidad]_card.php    ← Vista de detalle + edición + creación (card pattern)
```

**Decisión:** Los archivos existentes (`operacion.php`, `beneficiario.php`, `documento.php`) ya implementan el patrón "card" (crear/ver/editar). Solo necesitan:
1. Una vista de lista por defecto cuando no hay `action` ni `id`
2. Separar la lista en `*_list.php` para seguir convenciones Dolibarr

**Enfoque elegido:** Crear archivos `*_list.php` separados (convención Dolibarr) y hacer que las card pages redirijan a la lista cuando no hay acción.

---

## 📦 Entregables por Parte

### PARTE 1: Páginas de lista (PRIORIDAD ALTA)

**Archivos a crear:** 5

| # | Archivo | Tabla origen | Columnas en lista | Filtros |
|---|---------|-------------|-------------------|---------|
| 1 | `operaciones_list.php` | `llx_pld_operacion` | folio_interno, empresa, tipo_operacion, fecha, monto, supera_umbral, estado | empresa, tipo_operacion, estado, fecha, supera_umbral |
| 2 | `avisos_list.php` | `llx_pld_aviso` | referencia_aviso, tipo_aviso, mes_reportado, estado, fecha_presentacion, folio_sat | tipo_aviso, estado, mes_reportado |
| 3 | `alertas_list.php` | `llx_pld_alerta` | tipo_alerta, nivel_riesgo, empresa, descripcion, estado, fecha_alerta | nivel_riesgo, estado, tipo_alerta |
| 4 | `beneficiarios_list.php` | `llx_pld_beneficiario` | empresa, nombre_completo, curp, rfc, tipo_beneficiario, porcentaje, es_pep | empresa, es_pep |
| 5 | `documentos_list.php` | `llx_pld_documento` | empresa, contacto, tipo_documento, numero, fecha_emision, verificado | empresa, tipo_documento, verificado |

**Patrón de cada lista:**
```php
// Estructura estándar Dolibarr list page
1. Include main.inc.php
2. Require class + Form
3. $langs->loadLangs()
4. GETPOST para filtros, sort, pagination
5. Security check: $user->hasRight('modulecompliancepld', 'read')
6. SQL query con filtros dinámicos + ORDER BY + LIMIT
7. llxHeader()
8. Barra de filtros
9. Tabla con encabezados ordenables (print_liste_field_titre)
10. Filas con datos + links a card page
11. Paginación (print_barre_liste)
12. Botón "Nueva operación" si tiene permiso write
13. llxFooter()
```

**Funcionalidad de cada lista:**
- Ordenamiento por columna (clickeable en encabezado)
- Paginación (configurable, default 25 registros)
- Filtros por campos clave (dropdowns + fechas)
- Link en cada fila a la card page (`operacion.php?id=X`)
- Botón "Crear nuevo" con permiso `write`
- Indicadores visuales: color rojo si `supera_umbral=1`, badge de estado

### PARTE 2: Dashboard PLD (`index.php`)

**Archivo a modificar:** `index.php` (reemplazar contenido ModuleBuilder genérico)

**Contenido del dashboard:**

```
┌─────────────────────────────────────────────────────┐
│  Panel de Control PLD                                │
├──────────────┬──────────────┬────────────┬──────────┤
│ Operaciones  │ Avisos       │ Alertas    │ Docs     │
│ del mes: XX  │ pendientes:X │ abiertas:X │ vencidos │
├──────────────┴──────────────┴────────────┴──────────┤
│                                                      │
│  Últimas operaciones vulnerables (top 10)            │
│  ─────────────────────────────────────               │
│  [tabla con folio, empresa, monto, estado]           │
│                                                      │
│  Alertas sin resolver                                │
│  ─────────────────────────────────────               │
│  [tabla con tipo, empresa, fecha, prioridad]         │
│                                                      │
│  Avisos SAT pendientes de envío                      │
│  ─────────────────────────────────────               │
│  [tabla con referencia, mes, operaciones, estado]    │
│                                                      │
└──────────────────────────────────────────────────────┘
```

**Widgets (boxes Dolibarr):**
1. **Contadores resumen** — 4 tarjetas con totales del mes actual
2. **Últimas operaciones** — Top 10 más recientes, con link a detalle
3. **Alertas abiertas** — Alertas sin resolver, ordenadas por prioridad
4. **Avisos pendientes** — Avisos en estado borrador/pendiente

### PARTE 3: Mejoras a Card Pages existentes

**Archivos a modificar:** `operacion.php`, `beneficiario.php`, `documento.php`

**Cambios:**
1. Agregar redirección a lista cuando no hay `action` ni `id`:
   ```php
   if (empty($action) && empty($id)) {
       header("Location: operaciones_list.php");
       exit;
   }
   ```
2. Agregar breadcrumb / barra de tabs para navegación contextual
3. Agregar botones de acción en vista de detalle (Editar, Eliminar, Volver a lista)
4. Mejorar formulario de creación con validaciones JavaScript PLD (CURP, RFC, VIN)
5. Agregar `accessforbidden()` si no tiene permisos

### PARTE 4: Página de configuración (`admin/setup.php`)

**Archivo a crear:** `admin/setup.php`

**Parámetros configurables:**
| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `MODULECOMPLIANCEPLD_UMA_VALOR` | float | 117.31 | Valor UMA vigente |
| `MODULECOMPLIANCEPLD_UMA_ANIO` | int | 2026 | Año del valor UMA |
| `MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO` | float | 377778.20 | Umbral vehículo nuevo (auto-calculado) |
| `MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO` | float | 117310.00 | Umbral vehículo usado (auto-calculado) |
| `MODULECOMPLIANCEPLD_DIAS_ALERTA_ID` | int | 30 | Días antes de vencimiento de ID para alertar |
| `MODULECOMPLIANCEPLD_OFICIAL_CUMPLIMIENTO` | int | 0 | fk_user del oficial de cumplimiento |
| `MODULECOMPLIANCEPLD_PERIODO_CONSERVACION` | int | 5 | Años de conservación (Art. 18 LFPIORPI) |
| `MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE` | string | VIII | Fracción del Art. 17 aplicable |

**Patrón:** Usar `dolibarr_set_const()` / `dolibarr_get_const()` vía `$conf->global`

### PARTE 5: Página de reportes (`reportes.php`)

**Archivo a crear:** `reportes.php`

**Reportes disponibles (Fase 2.1 — solo estructura):**
1. **Resumen mensual** — Total operaciones, monto acumulado, avisos presentados
2. **Operaciones por cliente** — Acumulado por tercero (detectar acumulación >$500K)
3. **Estado de avisos** — Avisos presentados vs pendientes por mes
4. **Alertas por tipo** — Distribución de alertas y tiempo de resolución

**Nota:** En Fase 2.1 se crea la estructura y queries. La generación de PDF/Excel se implementa en Fase 3.

### PARTE 6: Claves de traducción adicionales

**Archivo a modificar:** `langs/es_MX/modulecompliancepld.lang`

**Claves nuevas estimadas:** ~60-80

Categorías:
- Títulos de páginas de lista (`ListaOperaciones`, `ListaAvisos`, etc.)
- Encabezados de columnas (`ColFolio`, `ColEmpresa`, `ColMonto`, etc.)
- Botones de acción (`BtnNuevaOperacion`, `BtnEditar`, `BtnEliminar`, etc.)
- Mensajes de confirmación (`ConfirmDelete`, `ConfirmEnviar`, etc.)
- Etiquetas del dashboard (`DashOperacionesMes`, `DashAlertasAbiertas`, etc.)
- Etiquetas de configuración admin
- Etiquetas de reportes

### PARTE 7: Actualizar menú del módulo

**Archivo a modificar:** `core/modules/modModulecompliancepld.class.php`

**Cambios en menús:**
- Agregar submenús para "Nueva Operación", "Nueva Alerta", etc.
- Agregar enlaces a `beneficiarios_list.php` y `documentos_list.php` (actualmente no están en el menú)
- Considerar reorganización del menú lateral:

```
PLDMenu (top)
├── Panel de Control          → index.php
├── Operaciones Vulnerables   → operaciones_list.php
│   └── Nueva Operación       → operacion.php?action=create
├── Beneficiarios             → beneficiarios_list.php
│   └── Nuevo Beneficiario    → beneficiario.php?action=create
├── Documentos                → documentos_list.php
│   └── Nuevo Documento       → documento.php?action=create
├── Avisos SAT                → avisos_list.php
├── Alertas                   → alertas_list.php
├── Reportes                  → reportes.php
└── Configuración             → admin/setup.php (solo admin)
```

---

## 📅 Secuencia de Implementación

| Orden | Parte | Archivos | Estimado | Dependencias |
|-------|-------|----------|----------|--------------|
| 1 | PARTE 7 | `modModulecompliancepld.class.php` | 30 min | Ninguna |
| 2 | PARTE 6 | `modulecompliancepld.lang` | 30 min | Ninguna |
| 3 | PARTE 1a | `operaciones_list.php` | 1.5 hrs | PARTE 6 |
| 4 | PARTE 3a | `operacion.php` (mejoras) | 45 min | PARTE 1a |
| 5 | PARTE 1b | `avisos_list.php` | 1 hr | PARTE 6 |
| 6 | PARTE 1c | `alertas_list.php` | 1 hr | PARTE 6 |
| 7 | PARTE 1d | `beneficiarios_list.php` | 1 hr | PARTE 6 |
| 8 | PARTE 1e | `documentos_list.php` | 1 hr | PARTE 6 |
| 9 | PARTE 3b | `beneficiario.php`, `documento.php` (mejoras) | 45 min | PARTE 1d, 1e |
| 10 | PARTE 2 | `index.php` (dashboard) | 1.5 hrs | PARTE 1a-1e |
| 11 | PARTE 4 | `admin/setup.php` | 1 hr | PARTE 6 |
| 12 | PARTE 5 | `reportes.php` | 1 hr | PARTE 1a-1e |

**Total estimado:** ~10-11 horas de desarrollo

---

## ✅ Criterios de Aceptación

### Funcional
- [ ] Todas las entradas del menú PLD navegan sin 404
- [ ] Las 5 páginas de lista muestran datos de la BD con paginación y filtros
- [ ] Los formularios de creación insertan registros correctamente
- [ ] Los formularios de edición actualizan registros correctamente
- [ ] La eliminación funciona con confirmación
- [ ] El dashboard muestra contadores y tablas resumen
- [ ] La página admin guarda y recupera configuración

### Seguridad
- [ ] Todas las páginas verifican `$user->hasRight('modulecompliancepld', 'read')`
- [ ] Acciones de escritura verifican `$user->hasRight('modulecompliancepld', 'write')`
- [ ] Admin setup verifica `$user->admin`
- [ ] Todas las entradas sanitizadas con `GETPOST()`
- [ ] Token CSRF en todos los formularios (`newToken()`)

### Compatibilidad
- [ ] Funciona con PostgreSQL (driver pgsql de Dolibarr)
- [ ] Compatible con Dolibarr 20 y 21
- [ ] Todas las queries usan `MAIN_DB_PREFIX` (no `llx_` hardcodeado)
- [ ] Idioma en español mexicano completo

### Calidad
- [ ] 0 errores en `lsp_diagnostics` en archivos modificados
- [ ] Sin `as any`, `@ts-ignore` (no aplica PHP, pero equivalente: sin `@` para suprimir errores)
- [ ] Cada archivo con encabezado PHP estándar del proyecto

---

## 🔧 Notas Técnicas

### PostgreSQL Compatibility Checklist
- Usar `$db->plimit()` para paginación (no `LIMIT/OFFSET` directo)
- Usar `$db->order()` para ordenamiento
- Usar `$db->escape()` para strings en WHERE
- Usar `MAIN_DB_PREFIX` constant para nombres de tabla
- No usar `GROUP_CONCAT` (PostgreSQL usa `string_agg`)
- No usar `DATE_FORMAT` (usar `dol_print_date()` en PHP)

### Referencia de funciones Dolibarr para listas
```php
// Paginación
print_barre_liste($title, $page, $file, $param, $sortfield, $sortorder, $morehtmlcenter, $num, $nbtotalofrecords, $picto, $pictoisfullpath, $morehtmlright, $morehtmlleft, $limit, $hideselectlimit, $hidenavigation, $pagenavaliasaliases, $morehtmlrightbeforearrow)

// Encabezado ordenable
print_liste_field_titre($name, $file, $field, $begin, $moreparam, $moreattrib, $sortfield, $sortorder, $prefix, $tooltip, $forcetitleifnotkey)

// Título de página
load_fiche_titre($title, $morehtmlright, $picto, $pictoisfullpath, $id, $morehtmlcenter, $morehtmlleft)
```

### Convención de URLs en menú Dolibarr (módulos custom)
Las URLs en el descriptor de módulo usan rutas relativas desde `DOL_URL_ROOT`:
- `/modulecompliancepld/operaciones_list.php` → Dolibarr resuelve a `/custom/modulecompliancepld/operaciones_list.php`

---

## 📁 Archivos Finales Esperados

```
htdocs/custom/modulecompliancepld/
├── index.php                         [MODIFICAR] Dashboard PLD
├── operacion.php                     [MODIFICAR] Mejoras card page
├── beneficiario.php                  [MODIFICAR] Mejoras card page
├── documento.php                     [MODIFICAR] Mejoras card page
├── operaciones_list.php              [CREAR] Lista de operaciones
├── avisos_list.php                   [CREAR] Lista de avisos SAT
├── alertas_list.php                  [CREAR] Lista de alertas
├── beneficiarios_list.php            [CREAR] Lista de beneficiarios
├── documentos_list.php               [CREAR] Lista de documentos
├── reportes.php                      [CREAR] Página de reportes
├── admin/
│   └── setup.php                     [CREAR] Configuración del módulo
├── core/modules/
│   └── modModulecompliancepld.class.php  [MODIFICAR] Menús actualizados
└── langs/es_MX/
    └── modulecompliancepld.lang      [MODIFICAR] +60-80 claves
```

**Total:** 6 archivos nuevos + 6 archivos modificados = 12 archivos

---

*Plan Fase 2.1 — Proyecto PLD Dolibarr México*
