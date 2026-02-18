# Módulo CompliancePLD - Dolibarr México

## Descripción

Módulo de **Prevención de Lavado de Dinero (PLD)** para Dolibarr ERP, cumpliendo con la **LFPIORPI** (Ley Federal para la Prevención e Identificación de Operaciones con Recursos de Procedencia Ilícita).

**Artículo aplicable**: Art. 17 Fracción VIII - Compraventa de vehículos

## Estructura del Módulo

```
/htdocs/custom/modulecompliancepld/
├── modulecompliancepld.class.php    # Clase principal del módulo
├── class/                           # Clases de objetos PLD
│   ├── compliancepld.class.php      # Objeto principal PLD
│   ├── avisosat.class.php           # Generación de avisos SAT
│   └── efirma.class.php             # Integración e.firma
├── core/
│   ├── modules/                     # Clases de módulo Dolibarr
│   └── triggers/                    # Triggers automáticos
├── css/                             # Estilos del módulo
│   └── compliancepld.css
└── langs/
    └── es_MX/                       # Traducciones español México
        └── modulecompliancepld.lang
```

## Estado del Desarrollo

- [ ] Fase 1: 152 extrafields en 6 tablas (0/152)
- [ ] Fase 2: 7 tablas especializadas PLD (0/7)
- [ ] Fase 3: Generación XML SAT + e.firma

## Versión

**v0.1.0-dev** - Estructura inicial

## Licencia

GNU/GPL v3
