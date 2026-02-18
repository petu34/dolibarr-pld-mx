# SQL - Scripts de Base de Datos PLD

## Descripción

Scripts SQL para las **7 tablas especializadas PLD** y migraciones de extrafields.

## Estructura

```
/sql/
├── llx_pld_operaciones.sql       # Operaciones vulnerables
├── llx_pld_beneficiarios.sql     # Beneficiarios finales
├── llx_pld_documentos.sql        # Documentos digitalizados
├── llx_pld_alertas.sql           # Alertas automáticas
├── llx_pld_envios_sat.sql        # Log envíos SAT
├── llx_pld_configuracion.sql     # Configuración módulo
├── llx_pld_periodos_reporte.sql  # Períodos de reporte
└── migrations/                    # Scripts de migración
    ├── migration_001_extrafields_socpeople.sql
    ├── migration_002_extrafields_societe.sql
    └── ...
```

## Convenciones

- Prefijo obligatorio: `llx_` para todas las tablas
- Migraciones numeradas secuencialmente: `migration_NNN_descripcion.sql`
- Siempre incluir `IF NOT EXISTS` en CREATE TABLE
- Siempre incluir `IF NOT EXISTS` en ALTER TABLE ADD COLUMN

## Extrafields

Los extrafields se agregan a través de migraciones que insertan en `llx_extrafields`:

- `llx_socpeople_extrafields` - Personas físicas (contactos)
- `llx_societe_extrafields` - Personas morales (empresas)
- `llx_propal_extrafields` - Propuestas comerciales
- `llx_commande_extrafields` - Órdenes de venta
- `llx_facture_extrafields` - Facturas
- `llx_product_extrafields` - Vehículos (productos)

**Total**: 152 extrafields distribuidos en 6 tablas
