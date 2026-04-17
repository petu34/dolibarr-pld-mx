# User Journey PLD — De la Venta al Aviso Mensual

> **Módulo:** `modulecompliancepld` | **Regulación:** LFPIORPI Art. 17 Fracc. VIII  
> **Actividad vulnerable:** Compra-venta de vehículos terrestres, marítimos y aéreos  
> **Actualizado:** 2026-04-17

---

## Actores del sistema

| Actor | Rol | Acceso en Dolibarr |
|-------|-----|--------------------|
| **Carlos (Vendedor)** | Asesor comercial que cierra la venta o intento de venta | Módulo Ventas, pedidos, facturas |
| **Lucía (Oficial PLD)** | Responsable de cumplimiento, evalúa operaciones y gestiona avisos | Módulo PLD completo (lectura/escritura) |
| **Roberto (Director)** | Socio administrador y representante legal ante el SAT | Aprobación de avisos, firma electrónica |
| **Ana (Analista de riesgo)** | Analiza alertas internas de actividades inusuales | Módulo PLD — sección Alertas |
| **Sistema (automatizado)** | Evaluación de umbrales, creación de registros, cómputo de retención | Backend/cron |

---

## Fase 0 — Prospectar y registrar al cliente

### Paso 0.1 · Carlos crea o busca al tercero (societe)

Carlos abre **Terceros → Nuevo tercero** (o busca uno existente).  
Antes de avanzar, el formulario le solicita los **campos PLD obligatorios**:

- `tipo_persona` → Física o Moral
- Si es **persona física**: `curp`, `rfc`, `fecha_nacimiento`, `nacionalidad`, `actividad_economica`
- Si es **persona moral**: `rfc`, `fecha_constitucion`, `forma_juridica`, `numero_escritura`
- Dirección completa: `colonia`, `alcaldia_municipio`, `clave_pais_telefono`

> **Punto de control:** El validador `PLDFormValidator` verifica el formato de CURP (18 caracteres, regex SPR) y RFC antes de guardar. Si falla, el sistema muestra el error en línea y bloquea el guardado.

### Paso 0.2 · Carlos captura el contacto (socpeople)

Para personas físicas que actúan como representantes o como el propio cliente, Carlos registra el contacto con:

- `apellido_paterno`, `apellido_materno`
- `tipo_identificacion` (INE, Pasaporte, FM3, Cédula)
- `numero_identificacion`, `vigencia_identificacion`, `autoridad_emite`
- Si es representante legal: `es_representante_legal = true`, `escritura_poder`, `fecha_poder`, `notario_poder`

### Paso 0.3 · Carlos carga el vehículo como producto

En **Productos/Servicios → Nuevo producto**, Carlos completa:

- `tipo_vehiculo` (Terrestre / Marítimo / Aéreo)
- `marca`, `modelo`, `anio_modelo`
- `vin` (17 caracteres — validado por `VIN.php`)
- `repuve`, `placas`, `numero_serie`
- `estado_vehiculo` (Nuevo / Usado / Seminuevo)
- `origen` (Nacional / Importado) y `pais_origen`
- `valor_factura`, `valor_comercial`
- Si es usado: `propietario_anterior`, `tarjeta_circulacion`, `numero_pedimento`

---

## Fase 1 — La venta o intento de venta

### Escenario A — Venta consumada

#### Paso 1A.1 · Carlos crea el pedido (commande)

Abre **Ventas → Nuevo pedido** y lo vincula al tercero ya registrado.  
El sistema activa automáticamente los campos PLD del pedido (extrafields `migration_006`):

- `es_actividad_vulnerable` → Carlos lo marca `Sí`
- `tipo_actividad` → `VIII` (compra-venta de vehículos)
- `requiere_identificacion` → `Sí`
- `estado_pld` → `pendiente_documentacion`

#### Paso 1A.2 · Carlos registra la forma de pago (paiement)

Al confirmar el pago, Carlos desglosa los montos por forma:

| Campo | Descripción |
|-------|-------------|
| `monto_efectivo` | Parte pagada en efectivo (Art. 6 — alertante si > $7,500 USD) |
| `monto_transferencia` | Transferencia bancaria (`cuenta_origen`, `cuenta_destino`) |
| `monto_cheque` | Cheque (`numero_cheque`, `institucion_financiera`) |
| `monto_tarjeta` | Tarjeta (`numero_autorizacion`) |

> **Regla de negocio:** La suma de todos los parciales debe igualar el monto total de la factura; el sistema valida esto antes de guardar.

#### Paso 1A.3 · Sistema crea la operación PLD (`llx_pld_operacion`)

Al guardar la factura vinculada, el trigger `ModulecompliancepldTriggers` crea automáticamente el registro en `llx_pld_operacion`:

```
tipo_operacion          = "compraventa"
tipo_actividad_vulnerable = "VIII"
fecha_operacion         = hoy
mes_reportado           = YYYYMM
monto_mxn               = monto total (con IVA) — para el XML
monto_sin_impuestos     = monto base — para comparar umbral
tasa_impuesto           = 16%
estado                  = "borrador"
```

#### Paso 1A.4 · Sistema evalúa el umbral (`PLDOperacion::evaluarUmbral()`)

El sistema corre la evaluación y registra el resultado:

```
UMBRAL_VEHICULO_NUEVO  = 377,778.20 MXN (3,220 UMAs, DOF 27/03/2026)
UMBRAL_VEHICULO_USADO  = 117,310.00 MXN (1,000 UMAs)
```

**Evaluación doble (Art. 6 y 7 DOF 2026):**

1. **Individual:** ¿`monto_sin_impuestos` ≥ umbral? → `supera_umbral = true`, `motivo = "individual"`
2. **Acumulada:** ¿Suma de los últimos 6 meses para el mismo tercero ≥ umbral? → `supera_umbral = true`, `motivo = "acumulada"`

Si supera cualquiera: `requiere_aviso = true`

---

### Escenario B — Intento de venta / operación cancelada o inusual

#### Paso 1B.1 · Carlos intenta cerrar pero el cliente se retira

Carlos igualmente registra el pedido con `estado_pld = "pendiente_documentacion"`.  
Si el cliente se retira antes de pagar, Carlos actualiza el estado de la operación PLD a `cancelada`, pero **el registro permanece** en `llx_pld_operacion` para el expediente de 10 años.

#### Paso 1B.2 · Ana recibe alerta automática

Si el cliente pagó en efectivo una cantidad cercana al umbral o fraccionó el pago en varias visitas, el sistema genera un registro en `llx_pld_alerta`:

```
tipo_alerta  = "inusual" o "limite_efectivo"
nivel_riesgo = "alto" o "crítico"
estado       = "abierta"
fk_operacion = referencia a la operación
```

---

## Fase 2 — Gestión documental y beneficiarios

### Paso 2.1 · Lucía revisa la operación en el dashboard PLD

Lucía abre **PLD → Dashboard** y ve los contadores:

- Operaciones capturadas este mes
- Avisos pendientes de generar
- Alertas abiertas
- Documentos próximos a vencer

Navega a **PLD → Operaciones** y filtra por `estado = borrador` o `requiere_aviso = Sí`.

### Paso 2.2 · Lucía solicita documentación al cliente (via Carlos)

Para cada operación que supera umbral, Lucía verifica en **PLD → Documentos** que existan los siguientes expedientes digitalizados (vinculados al módulo ECM de Dolibarr):

| Tipo de persona | Documentos requeridos |
|-----------------|----------------------|
| Persona física | INE/Pasaporte vigente, CURP, comprobante de domicilio, constancia de situación fiscal |
| Persona moral | Acta constitutiva, poder notarial, identificación del representante, RFC |

Por cada documento, Lucía registra en `llx_pld_documento`:

```
tipo_documento_pld      = "INE" | "pasaporte" | "acta_constitutiva" | ...
numero_documento        = número del documento físico
fecha_emision           = fecha de emisión
verificado              = false   ← pendiente
fecha_retencion_hasta   = fecha_operacion + 10 años  (Art. 20 LFPIORPI)
```

> **Retención (ADR-009):** La `fecha_inicio_custodia` se fija al máximo entre la fecha de operación y el 2025-07-17 (Transitorio 7º DOF 2026). El expediente no puede destruirse antes de los 10 años.

### Paso 2.3 · Lucía verifica y valida los documentos

Después de revisar cada documento físico contra el digitalizado, Lucía marca:

```
verificado        = true
fk_user_verificador = rowid de Lucía
fecha_verificacion  = hoy
```

Actualiza el estado de la operación: `estado = "pendiente_documentacion"` → `"completada"`.

### Paso 2.4 · Lucía registra al beneficiario controlador (Art. 18)

Para clientes que son personas morales o fideicomisos, Lucía abre **PLD → Beneficiarios → Nuevo** y captura a cada persona física que controla ≥ 25% del capital:

```
nombre / apellido_paterno / apellido_materno
curp / rfc / fecha_nacimiento / nacionalidad
porcentaje_participacion  = 30%
tipo_participacion        = "directa"
es_pep                    = false   (verificado en listas oficiales)
verificado                = true
fk_user_verificador       = Lucía
```

---

## Fase 3 — Análisis de alertas (si aplica)

### Paso 3.1 · Ana revisa la alerta

Ana abre **PLD → Alertas** y filtra las alertas `estado = "abierta"`.  
Selecciona la alerta generada por la operación sospechosa y analiza:

- Historial de operaciones del mismo tercero
- Forma de pago utilizada
- Comportamiento respecto al umbral

### Paso 3.2 · Ana documenta su análisis

Ana registra en el campo `observaciones_analisis` y selecciona su `decisión`:

| Decisión | Efecto |
|----------|--------|
| `aprobar` | La operación continúa; alerta → `resuelta` |
| `rechazar` | La operación se marca para no generar XML normal; alerta → `archivada` |
| `escalar` | Pasa a Roberto para revisión directiva |
| `aviso_24hrs` | Se genera un aviso tipo `24H` fuera del ciclo mensual |

### Paso 3.3 · Si `aviso_24hrs` — Roberto autoriza y se genera XML urgente

Roberto recibe notificación interna. Entra a **PLD → Avisos → Nuevo** y crea un aviso de tipo `24H` con las operaciones marcadas. El flujo de XML es el mismo que el mensual (ver Fase 4) pero con plazo de 24 horas.

---

## Fase 4 — Generación del aviso mensual

> Este proceso ocurre típicamente entre el **día 1 y 17 del mes siguiente** al periodo reportado.

### Paso 4.1 · Lucía abre el generador XML

Lucía navega a **PLD → Generar XML** (`xml_generator.php`).  
Selecciona el periodo: `mes_reportado = YYYYMM` (ej. `202603` para marzo 2026).

El sistema muestra automáticamente:

- Todas las operaciones con `requiere_aviso = true` y `mes_reportado = 202603`
- Estado de documentación por operación
- Errores de validación pendientes (RFC inválido, CURP faltante, VIN incompleto)

> Si hay operaciones con errores, el sistema **bloquea la generación** y lista los campos faltantes. Lucía regresa con Carlos para completarlos.

### Paso 4.2 · Lucía selecciona operaciones y genera el XML

Lucía marca las operaciones validadas y hace clic en **"Generar XML"**.  
`PLDAvisoService::generarXML()` orquesta:

1. `PLDOperacionRepository::fetchOperacionesPorMes("202603")` — obtiene las operaciones
2. `PLDOperacionRepository::fetchCliente(fk_societe)` — datos del tercero + extrafields PLD
3. `PLDOperacionRepository::fetchVehiculo(fk_product)` — datos del vehículo
4. `PLDOperacionRepository::fetchBeneficiarios(fk_societe)` — dueños beneficiarios
5. `PLDOperacionRepository::fetchFormasPago(fk_operacion)` — desglose de pago

El generador `PLDXMLGenerator` construye el XML con namespace SAT:

```xml
<archivo xmlns="http://www.uif.shcp.gob.mx/recepcion/veh"
         xsi:schemaLocation="... veh.xsd">
  <informe>
    <mes_reportado>202603</mes_reportado>
    <sujeto_obligado>
      <clave_actividad>VIII</clave_actividad>
      <rfc_sujeto_obligado>XAXX010101000</rfc_sujeto_obligado>
    </sujeto_obligado>
    <aviso>
      <referencia_aviso>PLD-2026-0312-001</referencia_aviso>
      <prioridad>2</prioridad>
      <persona_aviso>
        <curp>HEGJ800101HDFRRN09</curp>
        <rfc>HEGJ800101AB1</rfc>
        <!-- identificación, domicilio, contacto -->
      </persona_aviso>
      <dueno_beneficiario> ... </dueno_beneficiario>
      <acto_operacion>
        <vin>1HGBH41JXMN109186</vin>
        <marca>Toyota</marca>
        <modelo>Hilux</modelo>
        <anio_modelo>2025</anio_modelo>
        <monto>580000.00</monto>  <!-- con IVA — ADR-008 -->
      </acto_operacion>
    </aviso>
  </informe>
</archivo>
```

> **Aviso en ceros:** Si no hay operaciones que superen umbral, el sistema genera un XML de tipo `en_ceros = true` con el bloque `<informe>` vacío, obligatorio por regulación.

### Paso 4.3 · Sistema guarda el archivo y crea el registro de aviso

```
archivo_xml_ruta  = documents/modulecompliancepld/xml/aviso_MEN_202603.xml
archivo_xml_hash  = SHA256 del archivo (trazabilidad)
fecha_generacion_xml = hoy
estado            = "borrador"
tipo_aviso        = "MEN"
mes_reportado     = "202603"
numero_operaciones = 3
monto_total_operaciones = 1,740,000.00
```

Se crean también los registros en `llx_pld_aviso_operacion` vinculando cada operación al aviso.

### Paso 4.4 · (Opcional) Roberto firma digitalmente con e.firma

Si la configuración tiene las credenciales cargadas (`admin/setup.php`), Roberto:

1. Abre el aviso en **PLD → Avisos → [detalle]**
2. Hace clic en **"Firmar con e.firma"**
3. `PLDEFirmaIntegration::firmarXML()` carga el `.cert` y `.key` del servidor, genera la cadena original, aplica el sello y lo incrusta en el XML
4. El sistema actualiza el archivo y recalcula el hash SHA256

> **Seguridad:** Las credenciales de e.firma nunca viajan al cliente; la firma ocurre server-side con paths configurados solo en `setup.php`.

---

## Fase 5 — Presentación ante el SAT y seguimiento

### Paso 5.1 · Lucía descarga el XML y lo presenta al SAT

Lucía descarga el XML desde **PLD → Avisos → [detalle] → "Descargar XML"**.  
Accede al portal SPPLD del SAT y carga el archivo.

Una vez presentado, el SAT emite un **acuse con folio**.

### Paso 5.2 · Lucía registra el folio SAT

En **PLD → Avisos → [detalle]**, Lucía actualiza:

```
folio_sat          = "SPPLD-2026-0000012345"
fecha_presentacion = 2026-04-10
estado             = "enviado"   → "aceptado"
estado_acuse       = "aceptado"
```

Si el SAT rechaza el archivo, `estado = "rechazado"`. Lucía corrige los errores (usualmente datos faltantes) y repite desde Paso 4.2.

### Paso 5.3 · Lucía genera el reporte interno mensual

En **PLD → Reportes**, Lucía genera el **Resumen Mensual** para Roberto y los registros internos:

| Sección | Contenido |
|---------|-----------|
| Resumen mensual | Total ops, monto total, ops que superan umbral, avisos por estado |
| Operaciones por cliente | Agrupadas por tercero con montos acumulados |
| Estado de avisos | Distribución: borrador / enviado / aceptado / rechazado |
| Alertas por tipo | Inusual / PEP / Límite efectivo — por nivel de riesgo |

---

## Diagrama de flujo simplificado

```
[Carlos]                  [Sistema]              [Lucía/Ana]           [Roberto]       [SAT]
   │                          │                       │                    │              │
   ├─ Crea tercero ──────────►│ Valida CURP/RFC        │                    │              │
   ├─ Crea vehículo (prod.) ─►│ Valida VIN             │                    │              │
   ├─ Crea pedido/factura ───►│ Marca actividad VIII   │                    │              │
   ├─ Registra pago ─────────►│ Crea llx_pld_operacion │                    │              │
   │                          │ Evalúa umbral          │                    │              │
   │                          │ [supera] ──────────────►│                    │              │
   │                          │                        ├─ Revisa operación  │              │
   │                          │                        ├─ Verifica docs     │              │
   │                          │                        ├─ Registra benefic. │              │
   │                          │ [alerta inusual] ──────►│                    │              │
   │                          │                        ├─ Ana analiza alerta│              │
   │                          │                        ├─ decide → escalar ─►│              │
   │                          │                        │                    │              │
   │                 Ciclo mensual (día 1-17)           │                    │              │
   │                          │                        ├─ Abre generador XML│              │
   │                          │◄─ genera XML ──────────┤                    │              │
   │                          │ Guarda archivo         │                    │              │
   │                          │ SHA256 hash            │                    │              │
   │                          │                        │                    ├─ firma e.firma│
   │                          │                        ├─ descarga XML ─────►              │
   │                          │                        │                    │─ sube al SAT─►│
   │                          │                        │                    │              ├─ acuse + folio
   │                          │                        ├─ registra folio ◄──────────────────┤
   │                          │                        ├─ estado = aceptado │              │
   │                          │                        └─ reporte interno   │              │
```

---

## Reglas de negocio críticas (resumen)

| Regla | Valor / Fuente |
|-------|---------------|
| Umbral vehículo nuevo | 377,778.20 MXN (3,220 UMAs) — sin IVA |
| Umbral vehículo usado | 117,310.00 MXN (1,000 UMAs) — sin IVA |
| Acumulación | 6 meses mismo tercero (Art. 7 DOF 2026) — evita fraccionamiento |
| Plazo aviso mensual | Día 17 del mes siguiente al periodo |
| Plazo aviso 24H | 24 horas desde detección de operación relevante |
| Retención expediente | 10 años desde max(fecha_op, 2025-07-17) — Art. 20 + Trans. 7º |
| Monto en XML | `monto_mxn` con IVA (ADR-008) |
| Monto para umbral | `monto_sin_impuestos` sin IVA (ADR-008) |
| Beneficiarios | Personas físicas con ≥ 25% capital — Art. 18 LFPIORPI |

---

## Casos de borde

### Cliente que no supera umbral

Carlos completa la venta. El sistema evalúa el umbral: `supera_umbral = false`, `requiere_aviso = false`. La operación queda en estado `completada` sin generar aviso. **El expediente se conserva igualmente** 10 años por obligación de resguardo.

### Cliente que divide la operación (fraccionamiento)

Carlos registra dos facturas en meses distintos por el mismo cliente. Al evaluar la segunda, `PLDOperacion::evaluarUmbral()` detecta que la **suma acumulada de 6 meses** supera el umbral. Ambas operaciones se marcan `requiere_aviso = true` retroactivamente y se incluyen en el siguiente aviso mensual.

### Sin operaciones en el mes

Lucía abre el generador XML para el mes sin operaciones vulnerables. El sistema detecta `en_ceros = true` y genera el XML de aviso en ceros. **Este aviso sigue siendo obligatorio** y se presenta igual que cualquier otro.

### Aviso rechazado por el SAT

El SAT devuelve error (ej. CURP inválida). Lucía actualiza el estado a `rechazado`, corrige el dato en el tercero correspondiente, regresa a Paso 4.1 para regenerar el XML y lo reenvía con un nuevo número de folio interno.
