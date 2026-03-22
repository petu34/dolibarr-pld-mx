# Plan: aviso.php — Ficha de detalle y cambio de estado del aviso SAT

## Contexto

`avisos_list.php` ya existe y enlaza a `aviso.php?id=X` para ver el detalle de cada aviso, y a `aviso.php?action=create` para crear uno manual. Ese archivo **no existe** — por eso aparece en blanco. `PLDAviso` tiene CRUD completo (`fetch`, `create`, `update`, `delete`) y soporta todos los campos necesarios: `estado`, `folio_sat`, `fecha_presentacion`, `fk_user_presento`, `observaciones`, etc.

El objetivo es que desde `aviso.php?id=1` el usuario pueda ver los datos del aviso generado, descargarlo, y avanzar su estado hasta "presentado" ingresando el folio SAT recibido del portal SPPLD.

---

## Máquina de estados

```
borrador ──► pendiente ──► presentado  (estado final)
    │             │
    └──────────► cancelado
```

| Transición | Botón | Datos requeridos |
|---|---|---|
| borrador → pendiente | "Marcar como Enviado al SAT" | ninguno |
| pendiente → presentado | "Registrar Acuse SAT" | folio_sat, fecha_presentacion |
| cualquiera → cancelado | "Cancelar Aviso" | observaciones (opcional) |

---

## Archivo a crear

**`htdocs/custom/modulecompliancepld/aviso/card.php`** (~200 líneas)

### Estructura (patrón card estándar Dolibarr)

**1. Bootstrap + permisos**
- Incluir `main.inc.php`, `require_once PLDAviso`, `require_once html.form.class.php`
- `$user->hasRight('modulecompliancepld', 'read')` / `'write'`

**2. Acciones (`$action` = GETPOST)**

| action | Qué hace |
|---|---|
| `setborrador` | `$aviso->estado = 'borrador'` + `update()` |
| `setpendiente` | `$aviso->estado = 'pendiente'` + `update()` |
| `setpresentado` | captura `folio_sat`, `fecha_presentacion` → `$aviso->estado = 'presentado'` + `$aviso->presentado = 1` + `update()` |
| `setcancelado` | `$aviso->estado = 'cancelado'` + `update()` |
| `update` | guarda `observaciones`, `folio_sat`, `fecha_presentacion` libres |

**3. Vista — ficha principal**

- `llxHeader()` + `load_fiche_titre()` con ref del aviso
- Tabla de campos:
  - Tipo aviso, Mes reportado, Periodo (inicio–fin)
  - Referencia aviso
  - N° operaciones / Monto total
  - Estado (badge de color)
  - Fecha generación XML + enlace descarga (→ `xml_generator.php?action=descargar&filepath=...`)
  - Folio SAT (editable si estado ≠ presentado)
  - Fecha presentación
  - Observaciones (textarea)

**4. Botones de acción (`tabsAction` / `dolGetButtonAction`)**

Mostrar solo los botones válidos para el estado actual:
```
borrador  → [Marcar Enviado al SAT]  [Cancelar]
pendiente → [Registrar Acuse SAT]    [Cancelar]  [Volver a Borrador]
presentado → (sin botones de cambio)
cancelado  → (sin botones de cambio)
```

"Registrar Acuse SAT" abre un pequeño form inline (o modal) para ingresar `folio_sat` + `fecha_presentacion`.

---

## Claves de idioma a añadir en `langs/es_MX/modulecompliancepld.lang`

```
PLDAvisoDetalle = Detalle del Aviso
BtnMarcarEnviado = Marcar como Enviado al SAT
BtnRegistrarAcuse = Registrar Acuse SAT
BtnVolverBorrador = Volver a Borrador
BtnCancelarAviso = Cancelar Aviso
LblFolioSAT = Folio SAT
LblFechaAcuse = Fecha acuse
LblFechaPresentacion = Fecha presentación
LblPeriodo = Período reportado
LblXMLGenerado = XML generado
```

---

## Archivos a crear / modificar

| Archivo | Cambio |
|---|---|
| `htdocs/custom/modulecompliancepld/aviso/card.php` | CREAR (~200 líneas) |
| `htdocs/custom/modulecompliancepld/avisos_list.php` | Actualizar enlaces de `aviso.php?id=X` → `aviso/card.php?id=X` |
| `htdocs/custom/modulecompliancepld/langs/es_MX/modulecompliancepld.lang` | +10 claves |

No se modifica `PLDAviso` — `fetch()` y `update()` ya cubren todo lo necesario.

---

## Verificación

1. `docker cp` al container `doli20`
2. Abrir `http://localhost:8088/custom/modulecompliancepld/avisos_list.php?filtro_mes=202411`
3. Clic en referencia del aviso → debe abrir ficha con datos del aviso MEN
4. Botón "Marcar como Enviado al SAT" → estado cambia a `pendiente`
5. Botón "Registrar Acuse SAT" → ingresar folio y fecha → estado cambia a `presentado`
6. Volver a `avisos_list.php` → badge muestra "Presentado" en verde
