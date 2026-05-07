# Sistema Demo de Datos de Prueba PLD

## Objetivo

Probar la detección de **operaciones vulnerables** (LFPIORPI Art. 17 Fracc. VIII) 
en el módulo PLD de Dolibarr, verificando que las facturas que superan umbrales 
se marcan correctamente como `supera_umbral=1` y `requiere_aviso=1`.

## Estructura

```
scripts/
├── DemoDataGenerator.php          # Clase de utilidades (RFC, CURP, VIN)
├── demo_generar_datos_prueba.php  # Generador de datos de prueba  
├── demo_evaluar_operaciones.php   # Proceso batch de evaluación de umbrales
└── demo_verificar_resultados.php  # Verificación de resultados
```

## Uso

### 1. Generar datos de prueba

```bash
# Docker (recomendado)
docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/demo_generar_datos_prueba.php --all

# Por partes
docker exec doli20 php .../scripts/demo_generar_datos_prueba.php --clientes
docker exec doli20 php .../scripts/demo_generar_datos_prueba.php --vehiculos
docker exec doli20 php .../scripts/demo_generar_datos_prueba.php --facturas
```

### 2. Evaluar umbrales

```bash
docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/demo_evaluar_operaciones.php
```

### 3. Verificar resultados

```bash
docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/demo_verificar_resultados.php
```

### 4. Limpiar datos

```bash
docker exec doli20 php .../scripts/demo_generar_datos_prueba.php --clean
```

## Escenarios de Prueba

| # | Tipo | Monto | Umbral | Resultado esperado |
|---|------|-------|--------|-------------------|
| 1 | Nuevo | $450,000 | $377,778.20 | supera_umbral=1 ✅ |
| 2 | Nuevo | $520,000 | $377,778.20 | supera_umbral=1 ✅ |
| 3 | Nuevo | $380,000 | $377,778.20 | supera_umbral=1 ✅ |
| 4 | Nuevo | $610,000 | $377,778.20 | supera_umbral=1 ✅ |
| 5 | Nuevo | $400,000 | $377,778.20 | supera_umbral=1 ✅ |
| 6 | Usado | $95,000  | $117,310.00 | supera_umbral=0 ❌ |
| 7 | Usado | $80,000  | $117,310.00 | supera_umbral=0 ❌ |
| 8 | Usado | $50,000  | $117,310.00 | supera_umbral=0 ❌ |
| 9 | Usado | $110,000 | $117,310.00 | supera_umbral=0 ❌ |
| 10| Usado | $70,000  | $117,310.00 | supera_umbral=0 ❌ |

## Datos Generados

| Entidad | Cantidad | Prefijo |
|---------|----------|---------|
| Clientes PF | 10 | `DEMO ` |
| Vehículos | 10 | `DEMO-VEH-` |
| Facturas | 10 | `DEMO...` |
| Pagos | ~10 | `DEMO-PAGO-` |
| Ops. PLD | 10 | (auto) |

## Umbrales

Configurables en `admin/setup.php`:

| Constante | Default | Descripción |
|-----------|---------|-------------|
| `MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO` | $377,778.20 | Vehículo nuevo |
| `MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO` | $117,310.00 | Vehículo usado |
| `MODULECOMPLIANCEPLD_UMBRAL_ACUMULADO_6M` | $500,000.00 | Acumulado 6 meses |

## Notas

- Los RFC, CURP y nombres son **totalmente ficticios**
- La semilla aleatoria es fija (`42`) para resultados reproducibles
- El script es **idempotente**: no duplica datos existentes
- El proceso batch usa la clase `PLDOperacion` nativa y `evaluarUmbral()`
