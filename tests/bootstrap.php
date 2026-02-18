<?php
/**
 * @file        bootstrap.php
 * @module      CompliancePLD
 * @description Bootstrap para tests PHPUnit del módulo PLD
 * @author      Claude Code (Agente Generador)
 * @version     1.0
 * @date        2026-02-18
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 *
 * @license     GNU/GPL
 */

// Autoload de Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Definir constantes para simular entorno Dolibarr en tests
if (!defined('DOL_VERSION')) {
    define('DOL_VERSION', '20.0.0');
}

// Simular ruta base de Dolibarr para tests
if (!defined('DOL_DOCUMENT_ROOT')) {
    define('DOL_DOCUMENT_ROOT', __DIR__ . '/../htdocs');
}

// Configuración de zona horaria para tests
date_default_timezone_set('America/Mexico_City');

// Configuración de reportes de errores para tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Mock de base de datos para tests (si es necesario)
// Se puede extender con PDO SQLite para tests de integración

/**
 * Clase mock básica para simular objeto $db de Dolibarr en tests
 */
class MockDolibarrDB
{
    public function query($sql)
    {
        return true;
    }
    
    public function fetch_object($result)
    {
        return new stdClass();
    }
    
    public function escape($string)
    {
        return addslashes($string);
    }
}

/**
 * Clase mock básica para simular objeto $user de Dolibarr en tests
 */
class MockDolibarrUser
{
    public $id = 1;
    public $rights;
    
    public function __construct()
    {
        $this->rights = new stdClass();
    }
    
    public function hasRight($module, $permission)
    {
        return true;
    }
}

// Variables globales mock para tests
global $db, $user, $conf, $langs;

$db = new MockDolibarrDB();
$user = new MockDolibarrUser();
$conf = new stdClass();
$langs = new stdClass();

// Mensaje de confirmación para debug
if (php_sapi_name() === 'cli') {
    echo "✅ Bootstrap PHPUnit cargado correctamente\n";
    echo "   Proyecto: PLD Compliance Dolibarr México\n";
    echo "   Timezone: " . date_default_timezone_set('America/Mexico_City') . "\n";
}
