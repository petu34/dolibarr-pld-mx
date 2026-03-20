# Plan Fase 3.1: Habilitar Generador XML desde la UI de Dolibarr

> **Creado:** 2026-03-19
> **Actualizado:** 2026-03-20
> **Estado:** ✅ IMPLEMENTADO — pendiente de commit/push

---

## Contexto

El script `xml_generator.php` ya existe y es funcional, y los datos de prueba (Grupo A, mes 202411, 10 ops con factura + pagos con extrafields PLD) están en la BD. Sin embargo, hay **dos bloqueantes** que impiden usar el generador desde la UI:

1. `MODULECOMPLIANCEPLD_RFC_SUJETO` y los 4 campos de e.firma están en el array `$params` de `admin/setup.php` (se guardan correctamente) pero **no se renderizan en el formulario** — el HTML nunca fue añadido.
2. `xml_generator.php` **no está en el menú** del módulo — solo accesible por URL directa.

---

## Estado actual

| Componente | Estado |
|---|---|
| `xml_generator.php` | ✅ Funcional — genera XML con DOMDocument |
| `PLDXMLGenerator` class | ✅ Completa — fetchCliente/Vehiculo/FormasPago |
| Datos de prueba (202411) | ✅ 10 ops + facturas + pagos con extrafields PLD |
| `llx_paiement_extrafields` | ✅ Creada y poblada (VIR/LIQ/CHQ) |
| RFC Sujeto Obligado | ✅ Leído de `$mysoc->profid1` (company.php → campo R.F.C.) — no se duplica en constantes |
| Sección e.firma en setup.php | ✅ 3 campos: ruta .cer, ruta .key, contraseña |
| Menú → xml_generator.php | ✅ Entrada "Generar XML SAT" añadida al módulo |
| Claves de idioma | ✅ `PLDGenerarXML` y `SeccionSujetoObligado` en es_MX |
| e.firma (firma digital) | ⚠️ Opcional — sin certs el XML se genera sin firma |
| Commit / Push | ❌ Pendiente |

---

## Cambios a realizar

### 1. `admin/setup.php` — Nueva sección "Sujeto Obligado y e.firma"

Añadir **antes de `<div class="tabsAction">`** (línea ~190) una nueva sección con 4 campos:

| Constante | Tipo de input | Etiqueta |
|---|---|---|
# Usar campo 'profid1' with label 'R.F.C.' que se ingresa en company.php|| `MODULECOMPLIANCEPLD_RFC_SUJETO` | `text` | RFC Sujeto Obligado |
| `MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH` | `text` | Ruta certificado .cer (fuera de docroot) |
| `MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH` | `text` | Ruta llave privada .key (fuera de docroot) |
| `MODULECOMPLIANCEPLD_EFIRMA_PASSWORD` | `password` | Contraseña e.firma |

### 2. `core/modules/modModulecompliancepld.class.php` — Entrada de menú

Añadir **después de `PLDReportes`** y antes de `PLDConfiguracion` (líneas 364–392):

```php
$this->menu[$r++] = array(
    'fk_menu'  => 'fk_mainmenu=modulecompliancepld',
    'type'     => 'left',
    'titre'    => 'PLDGenerarXML',
    'mainmenu' => 'modulecompliancepld',
    'leftmenu' => 'pld_xml_generator',
    'url'      => '/modulecompliancepld/xml_generator.php',
    'langs'    => $menuLang,
    'position' => 1000 + $r,
    'enabled'  => $enabledCond,
    'perms'    => '$user->hasRight("modulecompliancepld", "write")',
    'target'   => '',
    'user'     => 2,
);
```

### 3. `langs/es_MX/modulecompliancepld.lang` — 2 claves

```
PLDGenerarXML = Generar XML SAT
SeccionSujetoObligado = Sujeto Obligado y e.firma
```

---

## Archivos a modificar

| Archivo | Cambio |
|---|---|
| `htdocs/custom/modulecompliancepld/admin/setup.php` | +25 líneas — sección y 4 campos de formulario |
| `htdocs/custom/modulecompliancepld/core/modules/modModulecompliancepld.class.php` | +14 líneas — entrada de menú |
| `htdocs/custom/modulecompliancepld/langs/es_MX/modulecompliancepld.lang` | +2 claves |

---

## Pasos para el usuario (tras los cambios)

### 1 — Configurar RFC
1. Menú PLD → **Configuración** (admin/setup.php)
2. Sección nueva **"Sujeto Obligado y e.firma"** → introducir RFC de prueba (ej. `ABC010101XXX`)
3. **Guardar**

### 2 — Generar XML
1. Menú lateral PLD → **"Generar XML SAT"**
2. Campo **"Mes reportado"**: `202411`
3. Checkbox de firma: dejar desmarcado (sin e.firma configurada está OK para pruebas)
4. Clic en **"Generar XML"**

### 3 — Descargar
- Vista previa del XML aparece (primeras 80 líneas)
- Clic en **"Descargar XML"** → archivo `PLD_VEH_202411_*.xml`
- Historial de archivos generados visible en la misma página

### Resultado esperado
XML con 10 avisos (Toyota, BMW, Mercedes, Audi, Mazda, Honda, Chevrolet, Ford, Nissan, Jeep) y formas de pago variadas (04=transferencia, 01=efectivo, 06=cheque).

---

## Verificación

```bash
docker cp htdocs/custom/modulecompliancepld/. doli20:/var/www/html/custom/modulecompliancepld/
```

Luego en UI:
- `Admin > PLD > Configurar` → sección "Sujeto Obligado y e.firma" con RFC en solo lectura (tomado de `profid1`)
- Menú lateral → "Generar XML SAT" → formulario accesible
- Mes 202411 → Generar → descargar XML → validar estructura

---

## Próxima tarea — Commit y Push

```bash
git add htdocs/custom/modulecompliancepld/admin/setup.php \
        htdocs/custom/modulecompliancepld/admin/about.php \
        htdocs/custom/modulecompliancepld/class/pldxmlgenerator.class.php \
        htdocs/custom/modulecompliancepld/xml_generator.php \
        htdocs/custom/modulecompliancepld/core/modules/modModulecompliancepld.class.php \
        htdocs/custom/modulecompliancepld/langs/es_MX/modulecompliancepld.lang \
        docs/plans/plan-fase3.1-AvisoFix.md

git commit -m "feat(fase3.1): habilitar generador XML SAT desde UI

- setup.php: sección e.firma (cert, key, password); RFC leído de mysoc->profid1
- about.php + xml_generator.php + pldxmlgenerator.class.php: RFC desde profid1
- modModulecompliancepld: entrada de menú 'Generar XML SAT'
- langs: PLDGenerarXML y SeccionSujetoObligado"

git push origin fase1/extrafields
```
