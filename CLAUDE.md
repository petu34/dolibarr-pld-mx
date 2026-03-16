# CLAUDE.md — Proyecto PLD Dolibarr México

## Entorno local

### Dolibarr
- **URL (host):** http://localhost:8088
- **URL (MCP browser / Docker interno):** http://172.18.0.2:80
- **Versión:** 20.0.4
- **Container:** `doli20`
- **Base de datos:** PostgreSQL (driver `pgsql`) en `host.docker.internal:5432`
- **DB name:** `doli20_db` / **DB user:** `doli20_user`

> Nota: el MCP browser corre dentro de Docker, por lo que debe usar la IP `172.18.0.2` en lugar de `localhost`.

### Credenciales Dolibarr
- **Usuario:** `doli20_user`
- **Contraseña:** `tirejkandani`

## Proyecto

**Módulo de Prevención de Lavado de Dinero (PLD)** para Dolibarr ERP.
Cumplimiento **LFPIORPI Art. 17 Fracciones V, VIII, XI, XII, XIII y XV** — actividades vulnerables de compra-venta de vehículos en México.

### Módulo
- **Nombre interno:** `modulecompliancepld`
- **Ruta en repo:** `htdocs/custom/modulecompliancepld/`
- **Rama principal del módulo:** `fase1/extrafields`

### Documentación disponible en `docs/`

| Ruta | Contenido |
|------|-----------|
| `docs/plans/` | Planes de implementación por fases (1, 2, 2.1, …) |
| `docs/architecture/llx-best-practices.md` | Estándares de código Dolibarr para este proyecto |
| `docs/architecture/DECISIONS.md` | Decisiones técnicas tomadas y su justificación |
| `docs/database/field-mapping-analysis.md` | Gap analysis entre campos Dolibarr nativos y requerimientos PLD |
| `docs/database/dolibarr-tables-reference.md` | Referencia de tablas `llx_*` relevantes |
| `docs/regulatorio/RESOLUCION-Avisos.pdf` | Resolución SAT/SHCP sobre avisos (referencia legal) |

> Antes de implementar algo nuevo, consultar `docs/architecture/` para respetar las decisiones ya tomadas.

## Flujo de trabajo

### Sincronizar cambios del repo al container Docker
Los archivos del módulo viven en el repo local y en un volumen Docker separado.
Después de cada edición, copiar al container:

```bash
docker cp htdocs/custom/modulecompliancepld/. doli20:/var/www/html/custom/modulecompliancepld/
```

Para un solo archivo:
```bash
docker cp htdocs/custom/modulecompliancepld/index.php doli20:/var/www/html/custom/modulecompliancepld/index.php
```

### Browser en vivo (Playwright headed — ventana visible)

Playwright está instalado en `/tmp/` con Chromium. Para abrir una sesión navegable
en tiempo real que el usuario puede ver:

1. Crear script en `/tmp/pld_tour.mjs` con:

```js
import { chromium } from 'playwright';

const BASE = 'http://localhost:8088';
const USER = 'doli20_user';
const PASS = 'tirejkandani';

const browser = await chromium.launch({ headless: false, slowMo: 600 });
const page = await browser.newPage();
await page.setViewportSize({ width: 1400, height: 900 });

// Login
await page.goto(BASE);
await page.fill('input[name="username"]', USER);
await page.fill('input[name="password"]', PASS);
await page.click('input[type="submit"]');
await page.waitForURL(/index\.php/, { timeout: 10000 });

// Navegar a página deseada
await page.goto(BASE + '/custom/modulecompliancepld/index.php');
await page.waitForLoadState('networkidle');

// Mantener abierto
console.log('Listo. Ventana queda abierta.');
```

2. Ejecutar desde `/tmp/`:

```bash
cd /tmp && node pld_tour.mjs &
```

> **Notas:**
> - `headless: false` es lo que abre la ventana visible.
> - `slowMo: 600` ralentiza las acciones para que sean visibles.
> - `playwright` debe estar instalado en `/tmp/` (`npm install playwright` dentro de `/tmp/`).
> - No tomar screenshots (`browser_take_screenshot`) — poco almacenamiento disponible.
> - Para navegar interactivamente, NO cerrar el browser al final del script.
