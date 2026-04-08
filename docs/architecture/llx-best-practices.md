# Logiciels Libres eXpertise (LLX) - Resumen y Mejores Prácticas

## 🏛️ Historia y Origen del Prefijo "llx_"

### ¿Qué significa LLX?
**LLX** es el acrónimo de **"Logiciels Libres eXpertise"** (Software Libre y Experiencia en francés), que se convirtió en el prefijo estándar para todas las tablas de base de datos en Dolibarr.

### Contexto Histórico
- **2002**: Rodolphe Quiédeville inicia el proyecto Dolibarr en abril
- **2003**: Primera versión 1.0 lanzada en septiembre
- **2008**: Laurent Destailleur (creador de AWStats) asume el liderazgo principal del proyecto
- **Actualidad**: Comunidad global con más de 1 millón de usuarios estimados

El prefijo "llx_" es una **herencia histórica** que se mantiene por:
1. **Compatibilidad retroactiva** con instalaciones antiguas
2. **Identificación clara** de tablas Dolibarr en bases de datos compartidas
3. **Tradición** de la comunidad open source francesa

---

## 🎯 Filosofía del Proyecto Dolibarr

### Principios Fundamentales

#### 1. **Simplicidad sobre Complejidad**
- Diseño intuitivo y fácil de usar
- Sin frameworks pesados (PHP puro)
- Interfaz accesible para usuarios sin conocimientos técnicos

#### 2. **Modularidad**
- Activa solo lo que necesitas
- Crecimiento orgánico según las necesidades del negocio
- Sin funcionalidades forzadas

#### 3. **Pragmatismo**
El proyecto privilegia soluciones prácticas sobre la perfección teórica:
- PHP/HTML como sistema de plantillas (no Smarty ni Twig)
- Active Record Pattern modificado (equilibrio entre simplicidad y organización)
- Enfoque en resultados, no en arquitecturas complejas

#### 4. **Comunidad y Colaboración**
- Desarrollo comunitario transparente
- Documentación extensiva en Wiki
- Soporte activo en foros internacionales

---

## 💻 Mejores Prácticas de Desarrollo

### 1. **Arquitectura de Base de Datos**

#### Convenciones de Nomenclatura
```
Patrón general: llx_[nombre_tabla]

Sufijos comunes:
- _det     → Líneas de detalle (ej: llx_facturedet)
- _log     → Historial de cambios
- _rec     → Plantillas recurrentes
- _fourn   → Relacionado con proveedores
- c_       → Tablas de diccionarios/catálogos
```

#### Claves y Relaciones
```sql
-- Clave primaria estándar
rowid BIGINT PRIMARY KEY AUTO_INCREMENT

-- Claves únicas alternativas (patrón)
uk_[tabla]_[campo]
Ejemplo: uk_societe_code_client

-- NO usar claves foráneas físicas (FOREIGN KEY)
-- Solo claves foráneas "soft" gestionadas por código
```

**Razón**: Las claves foráneas físicas rompen las herramientas de actualización, reparación y respaldo de Dolibarr.

---

### 2. **Patrón de Diseño: Active Record Modificado**

Dolibarr utiliza una variante del patrón Active Record que combina:

#### ✅ Ventajas del Enfoque Dolibarr
```php
class Facture extends CommonObject
{
    // 1. CRUD básico (como Active Record puro)
    public function create($user)
    public function fetch($id)
    public function update($user)
    public function delete($user)
    
    // 2. Lógica de negocio en la misma clase
    public function validate($user)
    public function setDraft($user)
    public function setPaid()
    public function generateDocument($template)
}
```

#### Características Principales
1. **Una clase = Una tabla** principal
2. **Una instancia = Un registro**
3. **Métodos de negocio** permitidos en la clase CRUD
4. **Más productivo** que separar completamente datos y lógica

---

### 3. **Estructura de Archivos PHP**

#### Separación Controller-View
```php
<?php
/* ========== SECTION 1: CONTROLLER (Actions) ========== */
// Inicio del archivo, lógica de negocio

require 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/facture.class.php';

// Variables
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

// Permisos
if (!$user->rights->facture->lire) {
    accessforbidden();
}

// Acciones
if ($action == 'create') {
    $object->create($user);
}


/* ========== SECTION 2: VIEW (Presentation) ========== */
// Después del comentario /* View */, código de presentación

llxHeader();

print '<div class="container">';
print '<h1>Factura #'.$object->ref.'</h1>';
// ... HTML y presentación ...

llxFooter();
?>
```

**Principio**: Separar claramente lógica de negocio y presentación **dentro del mismo archivo PHP**.

---

### 4. **Desarrollo de Módulos Externos**

#### Ubicación Estándar
```
htdocs/
  custom/
    mimodulo/           ← Módulos personalizados aquí
      core/
        modules/
        triggers/
        boxes/
      langs/
        en_US/
        es_ES/
      class/
      sql/
      css/
      js/
      img/
```

#### Archivo Descriptor del Módulo
```php
// core/modules/modMiModulo.class.php

class modMiModulo extends DolibarrModules
{
    public function __construct($db)
    {
        $this->numero = 500000; // ID único del módulo
        $this->family = "other";
        $this->name = "MiModulo";
        $this->description = "Descripción del módulo";
        
        // Definir permisos
        $this->rights = array();
        $r = 0;
        
        $this->rights[$r][0] = 500001; // ID permiso único
        $this->rights[$r][1] = 'Leer MiModulo';
        $this->rights[$r][2] = 'r'; // tipo: r/w/d
        $this->rights[$r][4] = 'mimodulo';
        $this->rights[$r][5] = 'leer';
    }
}
```

---

### 5. **Sistema de Hooks y Triggers**

#### Hooks (Modificar UI sin tocar el core)
```php
// En tu módulo: class/actions_mimodulo.class.php

class ActionsMiModulo
{
    public function formObjectOptions($parameters, &$object, &$action)
    {
        global $langs;
        
        $contexts = explode(':', $parameters['context']);
        
        if (in_array('invoicecard', $contexts)) {
            // Agregar campos extra en factura
            print '<tr><td>Mi campo extra</td>';
            print '<td><input type="text" name="micampo"></td></tr>';
        }
        
        return 0;
    }
}
```

#### Triggers (Ejecutar código en eventos de negocio)
```php
// core/triggers/interface_99_modMiModulo_MiTrigger.class.php

class InterfaceMyTrigger extends DolibarrTriggers
{
    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        if ($action == 'BILL_VALIDATE') {
            // Ejecutar cuando se valida una factura
            dol_syslog("Factura validada: ".$object->ref);
            
            // Tu código personalizado aquí
        }
        
        return 0;
    }
}
```

---

### 6. **Estándares de Código**

#### PSR-12 Coding Standard
Desde 2024, Dolibarr adopta PSR-12 con algunas excepciones:

```php
// ✅ CORRECTO
class MiClase extends CommonObject
{
    public function myMethod($param1, $param2)
    {
        if ($param1 > 0) {
            return $param2;
        }
        return 0;
    }
}

// ❌ INCORRECTO
class MiClase extends CommonObject {
  function myMethod($param1,$param2) {
    if($param1>0) return $param2;
    return 0;
  }
}
```

#### Pre-commit Hooks
Instalar hooks de Git para auto-formatear:
```bash
cd dolibarr/
cp dev/setup/pre-commit .git/hooks/
chmod +x .git/hooks/pre-commit
```

---

### 7. **Nombres de Clases CSS**

#### Nueva Convención (2024): Inspirada en Tailwind
```html
<!-- Usar nombres de clases semánticas similares a Tailwind -->
<div class="flex justify-between items-center p-4 mb-2">
    <span class="text-lg font-bold">Título</span>
    <button class="btn btn-primary">Acción</button>
</div>
```

**Nota**: No se incluye Tailwind completo, solo se adopta la nomenclatura como estándar.

---

### 8. **Versionado y Compatibilidad**

#### Política de Versiones
- **2 versiones principales por año**
- Desde 2024: Versiones **LTS (Long Term Support)** etiquetadas
- Actualización opcional (si funciona, no es obligatorio actualizar)

#### Compatibilidad de Módulos Certificados
Los módulos certificados deben:
- Seguir los cambios del core de Dolibarr
- Usar hooks y permisos estandarizados
- Actualizar cuando Dolibarr cambia de versión mayor

---

### 9. **Seguridad y Validación**

#### Reglas Obligatorias
```php
// 1. Validar TODAS las entradas
$id = GETPOST('id', 'int');          // Entero
$action = GETPOST('action', 'alpha');  // Solo letras
$email = GETPOST('email', 'email');   // Email válido

// 2. Protección SQL (usar prepared statements)
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."facture";
$sql.= " WHERE rowid = ".((int) $id);

// 3. Protección CSRF
if (!$user->rights->facture->creer) {
    accessforbidden();
}

// 4. NO construir SQL con concatenación directa
// ❌ NUNCA: $sql = "WHERE email = '".$_POST['email']."'";
```

---

### 10. **Documentación y Traducción**

#### Archivos de Idioma (.lang)
```ini
# langs/es_ES/mimodulo.lang

# Sección de traducciones
Module500000Name=Mi Módulo
Module500000Desc=Descripción de mi módulo

# Permisos
Permission500001=Leer datos de Mi Módulo
Permission500002=Crear/Modificar Mi Módulo

# Etiquetas
MyField=Mi Campo
MyButton=Mi Botón
```

#### Uso en Código
```php
$langs->load("mimodulo@mimodulo");
print $langs->trans("MyField");
```

---

## 🛠️ Herramientas Recomendadas

### IDEs y Editores
1. **Visual Studio Code** + extensiones:
   - Intelephense (PHP)
   - PHP CS Fixer
   - GitLens

2. **Eclipse PDT**
   - Configurar con perfil PSR-12 incluido

3. **PhpStorm**
   - Configuración automática de estándares

### Gestión de Base de Datos
- **DBeaver** (recomendado sobre phpMyAdmin)
- **MySQL Workbench**
- **HeidiSQL** (Windows)

### Entorno de Desarrollo
- **WAMP** (Windows): Apache + MariaDB + PHP
- **XAMPP**: Multiplataforma
- **Docker**: Contenedores predefinidos disponibles

---

## 📚 Recursos Oficiales

### Documentación Principal
- **Wiki oficial**: https://wiki.dolibarr.org
- **Developer docs**: https://wiki.dolibarr.org/index.php/Developer_documentation
- **GitHub**: https://github.com/Dolibarr/dolibarr

### Mercado de Módulos
- **DoliStore**: https://www.dolistore.com
- Cientos de módulos certificados y gratuitos
- Documentación adicional en PDF

### Comunidad
- **Foro internacional**: https://www.dolibarr.org/forum
- **YouTube**: Canal oficial con tutoriales
- **DevCamp**: Eventos anuales de desarrolladores

---

## ✅ Checklist de Mejores Prácticas

### Al Desarrollar un Módulo:
- [ ] Usar `htdocs/custom/` para módulos externos
- [ ] Crear descriptor `modMiModulo.class.php`
- [ ] Asignar ID único al módulo (> 100000)
- [ ] NO usar claves foráneas físicas
- [ ] Implementar permisos granulares (read/write/delete)
- [ ] Separar Controller y View en archivos PHP
- [ ] Usar hooks para modificar UI existente
- [ ] Usar triggers para eventos de negocio
- [ ] Seguir PSR-12 (usar pre-commit hooks)
- [ ] Crear archivos .lang para traducciones
- [ ] Validar todas las entradas con GETPOST()
- [ ] Documentar en código y Wiki
- [ ] Probar con diferentes roles de usuario
- [ ] Verificar compatibilidad con última versión LTS

### Al Trabajar con Bases de Datos:
- [ ] Prefijo `llx_` en todas las tablas
- [ ] Usar sufijos estándar (_det, _log, _rec, etc.)
- [ ] Clave primaria siempre `rowid`
- [ ] Campos de auditoría: `datec`, `tms`, `fk_user_creat`, `fk_user_modif`
- [ ] Índices en campos de búsqueda frecuente
- [ ] NO foreign keys físicas, solo lógicas

---

## 🎓 Conclusión

El prefijo **llx_** (Logiciels Libres eXpertise) es mucho más que una convención técnica: representa la **filosofía del software libre y la experiencia colaborativa** que caracteriza a Dolibarr desde sus inicios.

Las mejores prácticas de Dolibarr priorizan:
1. **Pragmatismo** sobre arquitecturas teóricas complejas
2. **Simplicidad** de código y mantenimiento
3. **Modularidad** sin imponer funcionalidades innecesarias
4. **Comunidad** como motor de innovación
5. **Accesibilidad** para empresas de todos los tamaños

Siguiendo estas prácticas, contribuirás a mantener Dolibarr como un ERP/CRM **robusto, mantenible y accesible** para millones de usuarios en todo el mundo.
