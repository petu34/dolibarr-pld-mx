# Diccionario de Datos PLD — Extrafields Dolibarr

> **Compliance:** LFPIORPI Art. 17 Fracc. VIII — Compraventa de vehículos
> **Ultima actualizacion:** 22 de febrero de 2026
> **Total de extrafields:** 88 campos en 4 tablas Dolibarr

**Nota:** Estos extrafields se crean programaticamente por el modulo mediante `addExtraField()` en el metodo `init()` de la clase del modulo. Las migraciones SQL (`migration_003` a `migration_006`) documentan la estructura pero **NO deben ejecutarse directamente**.

---

## Resumen por tabla

| Tabla Dolibarr | elementtype | Extrafields | Rango de posiciones | Migración |
|---|---|---|---|---|
| llx_product_extrafields | product | 24 | 100–123 | migration_003 |
| llx_facture_extrafields | facture | 31 | 200–231 | migration_004 |
| llx_paiement_extrafields | payment | 29 | 300–328 | migration_005 |
| llx_commande_extrafields | commande | 4 | 400–403 | migration_006 |
| **Total** | | **88** | | |

---

## 1. llx_product_extrafields — Vehiculos (24 campos)

Datos de identificacion y caracteristicas del vehiculo objeto de la operacion vulnerable.

### 1.1 Identificacion del vehiculo

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 100 | pld_tipo_vehiculo | Tipo de Vehiculo (PLD) | select | — | No | Si | Terrestre, Maritimo o Aereo |
| 101 | pld_marca | Marca del Vehiculo (PLD) | varchar | 40 | No | Si | Marca del fabricante |
| 102 | pld_modelo | Modelo del Vehiculo (PLD) | varchar | 40 | No | Si | Modelo del vehiculo |
| 103 | pld_anio_modelo | Ano Modelo (PLD) | varchar | 4 | No | Si | Ano modelo en 4 digitos |

### 1.2 Numeros de serie e identificadores

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 104 | pld_vin | VIN (PLD) | varchar | 17 | Si | No | Vehicle Identification Number — exactamente 17 caracteres (terrestre) |
| 105 | pld_repuve | Clave REPUVE (PLD) | varchar | 8 | No | No | Clave REPUVE 8 caracteres |
| 106 | pld_placas | Placas (PLD) | varchar | 12 | No | No | Placas 1-12 caracteres |
| 107 | pld_nivel_blindaje | Nivel de Blindaje (PLD) | varchar | 1 | No | Si | Nivel de blindaje 1 digito (0 = sin blindaje) |
| 108 | pld_numero_serie | Numero de Serie (PLD) | varchar | 20 | No | No | Serie maritimo/aereo 1-20 caracteres |
| 109 | pld_bandera | Pais Bandera (PLD) | varchar | 2 | No | No | Pais bandera ISO alpha-2 (maritimo) |
| 110 | pld_matricula | Matricula (PLD) | varchar | 12 | No | No | Matricula aerea/maritima 1-12 caracteres |

### 1.3 Origen y estado

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 111 | pld_origen | Origen del Vehiculo (PLD) | select | — | No | Si | Nacional o Importado |
| 112 | pld_pais_origen | Pais de Origen (PLD) | varchar | 2 | No | No | Pais fabricacion ISO alpha-2 |
| 113 | pld_estado_vehiculo | Estado del Vehiculo (PLD) | select | — | No | Si | Nuevo, Usado o Seminuevo |
| 114 | pld_kilometraje | Kilometraje (PLD) | int | — | No | No | Kilometraje actual (usado/seminuevo) |
| 115 | pld_uso_destino | Uso o Destino (PLD) | select | — | No | Si | Particular, Comercial o Transporte |

### 1.4 Documentacion legal (vehiculos usados)

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 116 | pld_numero_factura_original | Factura Original (PLD) | varchar | 30 | No | No | Numero factura original (usado) |
| 117 | pld_fecha_factura_original | Fecha Factura Original (PLD) | date | — | No | No | Fecha factura original (usado) |
| 118 | pld_propietario_anterior | Propietario Anterior (PLD) | varchar | 200 | No | No | Nombre propietario anterior (usado) |
| 119 | pld_tarjeta_circulacion | Tarjeta de Circulacion (PLD) | varchar | 20 | No | No | Numero tarjeta circulacion |
| 120 | pld_numero_pedimento | Pedimento Aduanal (PLD) | varchar | 20 | No | No | Numero pedimento aduanal (importado) |

### 1.5 Valores

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 121 | pld_valor_factura | Valor Facturado (PLD) | price | 15,2 | No | Si | Valor facturado del vehiculo |
| 122 | pld_valor_comercial | Valor Comercial (PLD) | price | 15,2 | No | Si | Valor comercial o avaluo |
| 123 | pld_valor_libro_azul | Valor Libro Azul (PLD) | price | 15,2 | No | No | Valor libro azul (referencia) |

---

## 2. llx_facture_extrafields — Operaciones y avisos (31 campos)

Datos de control PLD asociados a cada factura que constituye una actividad vulnerable.

### 2.1 Control PLD de la operacion

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 200 | pld_es_actividad_vulnerable | Es Actividad Vulnerable? (PLD) | boolean | — | No | Si | Marca la factura como actividad vulnerable |
| 201 | pld_clave_actividad | Clave de Actividad (PLD) | varchar | 3 | No | Si | Clave actividad vulnerable SAT (VEH/INM/SPR) |
| 202 | pld_tipo_operacion | Tipo de Operacion (PLD) | varchar | 4 | No | Si | Tipo operacion catalogo SAT 3-4 digitos |
| 203 | pld_supera_umbral_id | Supera Umbral Identificacion? (PLD) | boolean | — | No | Si | Supera umbral de identificacion |
| 204 | pld_supera_umbral_aviso | Supera Umbral de Aviso? (PLD) | boolean | — | No | Si | Supera umbral de aviso SAT |
| 205 | pld_requiere_aviso | Requiere Aviso SAT? (PLD) | boolean | — | No | Si | Requiere presentar aviso al SAT |
| 206 | pld_tipo_aviso | Tipo de Aviso (PLD) | select | — | No | No | Mensual, 24 Horas o Acumulado |

### 2.2 Datos de la operacion

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 207 | pld_fecha_operacion | Fecha de Operacion (PLD) | date | — | No | Si | Fecha real de la operacion YYYYMMDD |
| 208 | pld_codigo_postal_operacion | CP de la Operacion (PLD) | varchar | 5 | No | Si | Codigo postal donde ocurre la operacion (5 digitos) |
| 209 | pld_descripcion_operacion | Descripcion de la Operacion (PLD) | text | — | No | Si | Descripcion detallada de la operacion |
| 210 | pld_razon_operacion | Razon de la Operacion (PLD) | text | — | No | Si | Justificacion o motivo de la operacion |
| 211 | pld_monto_moneda_nacional | Monto en Moneda Nacional (PLD) | price | 15,2 | No | Si | Monto total en pesos mexicanos MXN |
| 212 | pld_tipo_cambio_aplicado | Tipo de Cambio (PLD) | price | 10,4 | No | No | Tipo de cambio si moneda extranjera |

### 2.3 Referencia del aviso

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 213 | pld_referencia_aviso | Referencia del Aviso (PLD) | varchar | 14 | No | No | Referencia interna del aviso (14 caracteres) |
| 214 | pld_prioridad | Prioridad (PLD) | varchar | 1 | No | No | 1 = Normal, 2 = Prioritario |

### 2.4 Alertas

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 215 | pld_tipo_alerta | Tipo de Alerta (PLD) | varchar | 4 | No | No | Codigo alerta 3-4 digitos catalogo SAT |
| 216 | pld_descripcion_alerta | Descripcion de Alerta (PLD) | varchar | 3000 | No | No | Texto descriptivo de la alerta (maximo 3000) |

### 2.5 Acumulacion de operaciones

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 217 | pld_es_operacion_acumulada | Operacion Acumulada? (PLD) | boolean | — | No | Si | Parte de acumulacion 6 meses mismo cliente |
| 218 | pld_fecha_inicio_acumulacion | Inicio Acumulacion (PLD) | date | — | No | No | Inicio periodo de acumulacion |
| 219 | pld_fecha_fin_acumulacion | Fin Acumulacion (PLD) | date | — | No | No | Fin periodo de acumulacion |
| 220 | pld_monto_acumulado_total | Monto Acumulado Total (PLD) | price | 15,2 | No | No | Total acumulado en periodo |

### 2.6 Control de avisos

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 221 | pld_aviso_presentado | Aviso Presentado? (PLD) | boolean | — | No | Si | Ya se presento aviso al SAT |
| 222 | pld_fecha_presentacion | Fecha de Presentacion (PLD) | date | — | No | No | Fecha presentacion ante SAT |
| 223 | pld_folio_aviso | Folio del Aviso SAT (PLD) | varchar | 14 | No | No | Folio del aviso asignado por SAT |
| 224 | pld_mes_reportado | Mes Reportado (PLD) | varchar | 6 | No | No | Mes reportado formato YYYYMM |
| 225 | pld_acuse_sat | Acuse Digital SAT (PLD) | text | — | No | No | Acuse digital del SAT |

### 2.7 Modificatorio

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 226 | pld_es_modificatorio | Es Modificatorio? (PLD) | boolean | — | No | No | Correccion de aviso previo |
| 227 | pld_folio_modificacion | Folio a Modificar (PLD) | varchar | 14 | No | No | Folio del aviso que se modifica |
| 228 | pld_descripcion_modificacion | Descripcion Modificacion (PLD) | varchar | 3000 | No | No | Razon de la modificacion al aviso |

### 2.8 Alertas y aviso 24 horas

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 229 | pld_genera_alerta | Genera Alerta? (PLD) | boolean | — | No | Si | Genera alerta interna en el sistema |
| 230 | pld_requiere_aviso_24hrs | Requiere Aviso 24 Horas? (PLD) | boolean | — | No | Si | Requiere aviso urgente 24 horas |
| 231 | pld_razon_24hrs | Razon Aviso 24 Horas (PLD) | text | — | No | No | Justificacion aviso urgente 24 horas |

---

## 3. llx_paiement_extrafields — Pagos y liquidacion (29 campos)

Datos de liquidacion de la operacion. El `elementtype` es `payment` (convencion Dolibarr), no `paiement`.

### 3.1 Datos de liquidacion (estructura XSD)

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 300 | pld_fecha_pago | Fecha de Pago (PLD) | date | — | No | Si | Fecha del pago formato YYYYMMDD |
| 301 | pld_forma_pago | Forma de Pago SAT (PLD) | varchar | 1 | No | Si | Catalogo SAT formas de pago 1 digito |
| 302 | pld_instrumento_monetario | Instrumento Monetario (PLD) | varchar | 2 | No | No | Catalogo instrumento monetario 1-2 digitos |
| 303 | pld_moneda | Moneda (PLD) | varchar | 3 | No | Si | Catalogo SAT monedas 1-3 digitos |
| 304 | pld_monto_operacion | Monto de Operacion (PLD) | varchar | 17 | No | Si | Monto formato SAT: d{1,14}.d{2} |

### 3.2 Desglose por forma de pago

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 305 | pld_monto_efectivo | Monto en Efectivo (PLD) | price | 15,2 | No | Si | Monto pagado en efectivo |
| 306 | pld_monto_transferencia | Monto por Transferencia (PLD) | price | 15,2 | No | Si | Monto por transferencia bancaria |
| 307 | pld_monto_cheque | Monto por Cheque (PLD) | price | 15,2 | No | Si | Monto pagado con cheque |
| 308 | pld_monto_tarjeta | Monto por Tarjeta (PLD) | price | 15,2 | No | Si | Monto pagado con tarjeta |
| 309 | pld_monto_otros | Monto Otros Medios (PLD) | price | 15,2 | No | Si | Monto por otros medios de pago |

### 3.3 Datos bancarios (transferencia)

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 310 | pld_banco_origen | Banco Origen (PLD) | varchar | 100 | No | No | Institucion financiera origen |
| 311 | pld_cuenta_origen | Cuenta Origen (PLD) | varchar | 4 | No | No | Ultimos 4 digitos cuenta origen |
| 312 | pld_clabe_origen | CLABE Origen (PLD) | varchar | 18 | No | No | CLABE interbancaria origen 18 digitos |
| 313 | pld_banco_destino | Banco Destino (PLD) | varchar | 100 | No | No | Institucion financiera destino |
| 314 | pld_cuenta_destino | Cuenta Destino (PLD) | varchar | 4 | No | No | Ultimos 4 digitos cuenta destino |
| 315 | pld_numero_autorizacion | Numero de Autorizacion (PLD) | varchar | 20 | No | No | Numero autorizacion transferencia |
| 316 | pld_fecha_transferencia | Fecha de Transferencia (PLD) | date | — | No | No | Fecha de la transferencia bancaria |

### 3.4 Datos del cheque

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 317 | pld_banco_cheque | Banco Emisor Cheque (PLD) | varchar | 100 | No | No | Banco emisor del cheque |
| 318 | pld_numero_cheque | Numero de Cheque (PLD) | varchar | 20 | No | No | Numero del cheque |
| 319 | pld_cuenta_cheque | Cuenta Cheque (PLD) | varchar | 4 | No | No | Ultimos 4 digitos cuenta del cheque |
| 320 | pld_fecha_cheque | Fecha del Cheque (PLD) | date | — | No | No | Fecha del cheque |
| 321 | pld_librador_cheque | Librador del Cheque (PLD) | varchar | 200 | No | No | Nombre del librador del cheque |

### 3.5 Datos de tarjeta

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 322 | pld_tipo_tarjeta | Tipo de Tarjeta (PLD) | select | — | No | No | Debito o Credito |
| 323 | pld_emisor_tarjeta | Emisor de Tarjeta (PLD) | varchar | 100 | No | No | Banco emisor de la tarjeta |
| 324 | pld_ultimos_digitos | Ultimos 4 Digitos Tarjeta (PLD) | varchar | 4 | No | No | Ultimos 4 digitos de la tarjeta |
| 325 | pld_numero_autorizacion_tarjeta | Autorizacion de Tarjeta (PLD) | varchar | 20 | No | No | Numero autorizacion de compra con tarjeta |

### 3.6 Control de efectivo

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 326 | pld_supera_limite_efectivo | Supera Limite Efectivo? (PLD) | boolean | — | No | Si | Supera $363,692 MXN (3,100 UMAs) en efectivo |
| 327 | pld_alerta_efectivo | Alerta por Efectivo? (PLD) | boolean | — | No | Si | Alerta por uso elevado de efectivo |
| 328 | pld_justificacion_efectivo | Justificacion Uso Efectivo (PLD) | text | — | No | No | Justificacion uso elevado de efectivo |

---

## 4. llx_commande_extrafields — Pre-validacion (4 campos)

Campos de pre-validacion PLD en la etapa de pedido, antes de facturacion.

| Pos | Nombre tecnico | Label | Tipo | Tamaño | Unico | Obligatorio | Descripcion |
|-----|----------------|-------|------|--------|-------|-------------|-------------|
| 400 | pld_preventa_identificada | Cliente Identificado? (PLD) | boolean | — | No | No | Cliente ya identificado al momento del pedido |
| 401 | pld_anticipo_estimado | Anticipo Estimado (PLD) | price | 15,2 | No | No | Anticipo aproximado del pedido |
| 402 | pld_forma_pago_planeada | Forma de Pago Planeada (PLD) | select | — | No | No | Forma de pago esperada |
| 403 | pld_alerta_previa | Alerta Previa? (PLD) | boolean | — | No | No | Alerta generada en etapa de pedido |

---

## Notas tecnicas

### Tipos de datos Dolibarr

| Tipo extrafield | Tipo SQL resultante | Notas |
|---|---|---|
| varchar | VARCHAR(n) | Texto con longitud maxima |
| text | TEXT | Texto largo sin limite practico |
| int | INTEGER | Numero entero |
| price | DECIMAL(n,d) | Monto monetario con decimales |
| date | DATE | Fecha sin hora |
| boolean | TINYINT(1) | 0 = falso, 1 = verdadero |
| select | VARCHAR(255) | Valor del catalogo seleccionado |

### Convenciones

- **Prefijo:** Todos los extrafields PLD usan el prefijo `pld_`
- **langs:** `modulecompliancepld@modulecompliancepld` para internacionalizacion
- **entity:** `1` (entidad principal de Dolibarr)
- **enabled:** `1` (campo activo)
- **Idempotencia:** Las migraciones usan `ON DUPLICATE KEY UPDATE` para ser re-ejecutables

### Referencia regulatoria

Todos los campos de este diccionario responden a los requisitos de la **LFPIORPI Art. 17 Fraccion VIII** (compraventa de vehiculos) y sus reglamentos. Los umbrales de aviso son:

| Tipo de operacion | Umbral de aviso | Referencia |
|---|---|---|
| Vehiculo nuevo | >= $250,000 MXN | Art. 17 Fracc. VIII |
| Vehiculo usado | >= $100,000 MXN | Art. 17 Fracc. VIII |
| Limite efectivo | >= $363,692 MXN (3,100 UMAs) | Art. 32 LFPIORPI |
| Acumulado 6 meses | >= $500,000 MXN | Reglas UIF |
