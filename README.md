# Dolibarr PLD México

**Módulo de Prevención de Lavado de Dinero (PLD)** para Dolibarr ERP que implementa las regulaciones mexicanas LFPIORPI Art. 17, específicamente para transacciones de compraventa de vehículos. El módulo genera avisos XML firmados para el portal SPPLD del SAT e integra monitoreo de cumplimiento, alertas automáticas y capacidades de firma digital utilizando la e.firma (firma electrónica) de México.

**Cumplimiento LFPIORPI Art. 17 Fracciones V, VIII, XI, XII, XIII y XV** — Actividades vulnerables de compra-venta de vehículos en México.

## Estado del proyecto

**En desarrollo - Fase develop inicial**  
Parcialmente funcional para Art. 17 Fracción VIII "Distribución y comercialización de todo tipo de vehículos"

---

## Qué es esto

Un **módulo de cumplimiento Prevención de Lavado de Dinero (PLD)** para Dolibarr ERP que implementa las regulaciones mexicanas LFPIORPI Art. 17, específicamente para transacciones de compraventa de vehículos. El módulo genera avisos XML firmados para el portal SPPLD del SAT e integra monitoreo de cumplimiento, alertas automáticas y capacidades de firma digital utilizando la e.firma (firma electrónica) de México.

### Stack

- **Lenguaje(s):** PHP 100%
- **Framework / runtime:** Dolibarr 20.0.4 ERP, PHP 8.1+, PostgreSQL/MySQL
- **Librerías destacadas:** PHPUnit (testing), lxml/XSD validation (validación XML), DoliDB (capa de abstracción de BD para traducción MySQL-a-PostgreSQL)

## Cómo está organizado

```
htdocs/custom/modulecompliancepld/
  ├── index.php                    Punto de entrada & panel de control
  ├── class/                       Modelos de dominio (Operacion, Beneficiario, AvisoSAT)
  ├── core/                        Hooks y triggers de Dolibarr
  ├── sql/                         Definiciones de tablas & migraciones
  ├── avisos_list.php              Lista/gestión de avisos de cumplimiento
  ├── operaciones_list.php         Seguimiento de transacciones marcadas
  ├── beneficiarios_list.php       Registro de beneficiarios finales
  ├── xml_generator.php            Ensamblaje XML SAT & firma con e.firma
  ├── monitoreo.php                Panel de monitoreo en tiempo real
  ├── pld_*.php                    Hooks de integración (contacto, orden, pago, producto, terceros)
  ├── langs/es_MX/                Cadenas de idioma español
  └── tpl/                         Plantillas de UI

docs/
  ├── plans/                       Hojas de ruta de implementación por fases (4 fases)
  ├── architecture/                Estándares de código Dolibarr, decisiones técnicas
  ├── database/                    Mapeo de campos, referencia de esquema
  ├── regulatorio/                 Requerimientos legales (resoluciones SAT/SHCP)
  └── BLUEPRINT_SINTESIS.md        Referencia rápida para agentes IA

sql/
  ├── llx_pld_operaciones.sql      Transacciones vulnerables
  ├── llx_pld_beneficiarios.sql    Seguimiento de beneficiarios finales
  ├── llx_pld_documentos.sql       Almacenamiento de IDs digitales
  └── migrations/                  152 extrafields en 6 tablas core de Dolibarr

tests/
  ├── Unit/                        Validación CURP/RFC, tests de umbrales (PHPUnit)
  ├── Integration/                 Creación de extrafields, esquema BD (PHPUnit)
  └── XML/                         Cumplimiento de esquema XSD (pytest)

schemas/
  └── veh.xsd                      XSD oficial del SAT para transacciones de vehículos
```

### Cómo encaja todo

El módulo intercepta los flujos de venta de Dolibarr (órdenes → facturas → pagos) y extrae datos relevantes para PLD (CURP, RFC, detalles del vehículo, montos de transacción). Cuando se cumplen los umbrales (≥$250k vehículos nuevos, ≥$100k usados), marca la transacción, la organiza en `llx_pld_operaciones` y dispara la generación de XML. El `xml_generator.php` ensambla avisos compatibles con SAT, aplica firmas digitales con e.firma y registra envíos en `llx_pld_envios_sat`. Un panel de cumplimiento (`monitoreo.php`) muestra alertas y respalda retención de auditoría de 5 años.

## Cómo ejecutarlo

### Requisitos previos

- Dolibarr 20.0.4 corriendo (local: `http://localhost:8088`, Docker interno: `http://172.18.0.2:80`)
- PostgreSQL en `host.docker.internal:5432` con BD `doli20_db` / usuario `doli20_user`
- Certificados e.firma (archivos `.cer` + `.key`) almacenados en `/var/datos_pld/efirma/` (fuera del webroot)

### Instalar el módulo

```bash
# 1. Clonar y sincronizar al contenedor Docker
docker cp htdocs/custom/modulecompliancepld/. doli20:/var/www/html/custom/modulecompliancepld/

# 2. Acceder a UI de Dolibarr como administrador
# 3. Ir a Configuración > Módulos, buscar "Compliance PLD", hacer clic en Activar

# 4. Configurar e.firma
# Ir a Configuración > Módulos > Compliance PLD > Ajustes
# Ingresar: RFC, ruta certificado, ruta llave, contraseña de llave
```

### Ejecutar tests

```bash
# Tests unitarios + integración (PHPUnit)
composer test

# Validación de esquema XML (pytest)
pytest tests/XML/ -v

# Reporte de cobertura
./vendor/bin/phpunit --coverage-html coverage/
```

### Sesión de desarrollo interactiva (ventana de navegador en vivo)

```bash
# Crear y ejecutar script Playwright con headless=false
cat > /tmp/pld_tour.mjs << 'EOF'
import { chromium } from 'playwright';
const browser = await chromium.launch({ headless: false, slowMo: 600 });
const page = await browser.newPage();
await page.goto('http://localhost:8088');
await page.fill('input[name="username"]', 'doli20_user');
await page.fill('input[name="password"]', 'tirejkandani');
await page.click('input[type="submit"]');
await page.waitForURL(/index\.php/);
await page.goto('http://localhost:8088/custom/modulecompliancepld/index.php');
console.log('Navegador en vivo listo. La ventana permanece abierta.');
EOF

cd /tmp && node pld_tour.mjs &
```

## Preguntas que puedes hacer

- ¿Cuáles son los 152 extrafields que necesito crear en Fase 1, y en cuáles tablas core de Dolibarr se adjuntan?
- ¿Cómo funcionan los patrones regex de CURP y RFC, y dónde se aplican en el código?
- ¿Cuál es la estructura del XML del SAT que produce `xml_generator.php`, y cómo valida contra el esquema `veh.xsd`?

---

## Instalación de e.firma (FIEL SAT)

La generación de XMLs firmados requiere que el **sujeto obligado** cuente con su e.firma vigente (antes FIEL) emitida por el SAT. Esta consiste en dos archivos:

| Archivo | Descripción |
|---------|-------------|
| `*.cer` | Certificado público X.509 |
| `*.key` | Llave privada cifrada |

Además se necesita la **contraseña** que protege el archivo `.key`.

### Paso 1 — Crear el directorio seguro en el servidor

El directorio debe quedar **fuera del docroot** de Apache/Nginx para que no sea accesible por HTTP. Ejemplo en Linux:

```bash
mkdir -p /var/datos_pld/efirma
chmod 700 /var/datos_pld/efirma
chown www-data:www-data /var/datos_pld/efirma   # o el usuario del servidor web
```

> **Nunca** colocar estos archivos dentro de `htdocs/` ni de ninguna carpeta que el servidor web sirva públicamente.

### Paso 2 — Copiar los archivos al servidor

```bash
scp certificado_empresa.cer usuario@servidor:/var/datos_pld/efirma/
scp llave_privada.key       usuario@servidor:/var/datos_pld/efirma/
chmod 600 /var/datos_pld/efirma/*.cer /var/datos_pld/efirma/*.key
```

### Paso 3 — Configurar las rutas y contraseña en Dolibarr

En `Configuración > Módulos > Compliance PLD > Ajustes`, registrar:

| Constante | Valor ejemplo |
|-----------|---------------|
| `MODULECOMPLIANCEPLD_RFC_SUJETO` | RFC del sujeto obligado (12 o 13 chars) |
| `MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH` | `/var/datos_pld/efirma/certificado.cer` |
| `MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH` | `/var/datos_pld/efirma/llave_privada.key` |
| `MODULECOMPLIANCEPLD_EFIRMA_PASSWORD` | Contraseña del archivo `.key` |

> La contraseña queda almacenada en la base de datos de Dolibarr (tabla `llx_const`). Asegurarse de que la BD esté protegida y con backups cifrados.

### Consideraciones de seguridad

- La llave privada **nunca debe viajar por la red en texto plano**; usar siempre SCP/SFTP.
- El proceso de firma ocurre enteramente en el servidor; el archivo `.key` nunca sale.
- Renovar la e.firma antes de su vencimiento (4 años desde su emisión) en el SAT.
- Ante pérdida o compromiso de la llave privada, revocar inmediatamente ante el SAT.

### Verificar la configuración

Desde la UI de administración del módulo (`Acerca de > e.firma`) se puede ejecutar una prueba de validación que verifica que los archivos existen y son legibles por PHP sin realizar ninguna firma real.

---

## Documentación

Consulta la [carpeta `docs/`](./docs/README.md) para:

- **[BLUEPRINT_SINTESIS.md](./docs/BLUEPRINT_SINTESIS.md)** — Referencia rápida técnica para agentes IA
- **[plans/](./docs/plans/)** — Planes de implementación por fases (1-4)
- **[architecture/](./docs/architecture/)** — Estándares de código Dolibarr y decisiones técnicas
- **[database/](./docs/database/)** — Mapeo de campos y referencia de esquema
- **[regulatorio/](./docs/regulatorio/)** — Documentación legal SAT/SHCP

---

## Licencias

### Código principal

GPLv3 o (a tu opción) cualquier versión posterior. Ver archivo COPYING para más información.

### Documentación

Todo texto y README está bajo licencia [GFDL](https://www.gnu.org/licenses/fdl-1.3.en.html).

---

## Traducción de términos clave

| Término EN | Término ES-MX |
|---|---|
| Money Laundering Prevention | Prevención de Lavado de Dinero (PLD) |
| Vulnerable Activities | Actividades Vulnerables |
| Compliance Notice | Aviso de Cumplimiento |
| Beneficial Owner | Beneficiario Final |
| Digital Signature | Firma Digital (e.firma) |
| Threshold Alert | Alerta de Umbral |
| SPPLD Portal | Portal SPPLD (SAT) |
