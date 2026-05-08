#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * @file        scripts/demo_generar_datos_prueba.php
 * @module      CompliancePLD
 * @description Script CLI para generar datos de prueba PLD:
 *              clientes PF (con extrafields), vehículos, cotizaciones,
 *              facturas y pagos. Diseñado para probar detección de
 *              operaciones vulnerables por umbral individual.
 * @author      Atlas / Prometheus
 * @version     1.0.0
 * @date        2026-05-06
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 * @license     GNU/GPL
 *
 * Uso:
 *   docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/demo_generar_datos_prueba.php
 *   docker exec doli20 php .../scripts/demo_generar_datos_prueba.php --clientes
 *   docker exec doli20 php .../scripts/demo_generar_datos_prueba.php --vehiculos
 *   docker exec doli20 php .../scripts/demo_generar_datos_prueba.php --facturas
 *   docker exec doli20 php .../scripts/demo_generar_datos_prueba.php --all
 *   docker exec doli20 php .../scripts/demo_generar_datos_prueba.php --clean
 */

// ──────────────────── Bootstrap Dolibarr ────────────────────
if (!defined('NOSESSION')) {
    define('NOSESSION', '1');
}
if (!defined('NOLOGIN')) {
    define('NOLOGIN', '1');
}

$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) === 'cgi') {
    echo "Error: Ejecutar con PHP CLI, no CGI.\n";
    exit(1);
}

@set_time_limit(0);
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1);

$paths = [
    '/var/www/html/main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../../../main.inc.php',
];

$mainFound = false;
foreach ($paths as $mainPath) {
    if (file_exists($mainPath)) {
        require $mainPath;
        $mainFound = true;
        break;
    }
}

if (!$mainFound) {
    echo "ERROR: No se encontró main.inc.php. Asegúrate de ejecutar desde el directorio correcto.\n";
    exit(1);
}

require_once __DIR__ . '/DemoDataGenerator.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';

use ModuleCompliancePLD\Demo\DemoDataGenerator;

global $db, $user, $conf, $langs;

// ──────────────────── Configuración ────────────────────
const DEMO_PREFIX = 'DEMO';
const NUM_CLIENTES = 10;
const NUM_VEHICULOS = 10;
const NUM_FACTURAS = 10;

/** @var array Escenarios de facturación: 5 superan umbral nuevo, 5 no superan */
const ESCENARIOS = [
    // ── Superan umbral vehículo nuevo ($377,778.20) ──
    ['tipo' => 'nuevo', 'monto' => 450000.00, 'expected' => 'supera'],
    ['tipo' => 'nuevo', 'monto' => 520000.00, 'expected' => 'supera'],
    ['tipo' => 'nuevo', 'monto' => 380000.00, 'expected' => 'supera'],
    ['tipo' => 'nuevo', 'monto' => 610000.00, 'expected' => 'supera'],
    ['tipo' => 'nuevo', 'monto' => 400000.00, 'expected' => 'supera'],
    // ── NO superan umbral (montos bajos) ──
    ['tipo' => 'usado', 'monto' => 95000.00,  'expected' => 'no_supera'],
    ['tipo' => 'usado', 'monto' => 80000.00,  'expected' => 'no_supera'],
    ['tipo' => 'usado', 'monto' => 50000.00,  'expected' => 'no_supera'],
    ['tipo' => 'usado', 'monto' => 110000.00, 'expected' => 'no_supera'],
    ['tipo' => 'usado', 'monto' => 70000.00,  'expected' => 'no_supera'],
];

// ──────────────────── Helpers ────────────────────

function logInfo(string $msg): void { echo "[INFO]  {$msg}\n"; }
function logOk(string $msg): void   { echo "[OK]    {$msg}\n"; }
function logSkip(string $msg): void { echo "[SKIP]  {$msg}\n"; }
function logErr(string $msg): void  { echo "[ERROR] {$msg}\n"; }

/**
 * Obtiene el primer usuario admin disponible para asignar como creador.
 */
function getAdminUser(): object
{
    global $db;
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE admin = 1 LIMIT 1";
    $res = $db->query($sql);
    $obj = $db->fetch_object($res);
    $user = new User($db);
    $user->fetch((int)$obj->rowid);
    return $user;
}

/**
 * Verifica si un RFC ya existe en llx_societe.
 */
function existeRFC(string $rfc): bool
{
    global $db;
    $sql = "SELECT COUNT(*) AS cnt FROM " . MAIN_DB_PREFIX . "societe WHERE tva_intra = '" . $db->escape($rfc) . "'";
    $res = $db->query($sql);
    $obj = $db->fetch_object($res);
    return (int)$obj->cnt > 0;
}

/**
 * Verifica si un VIN ya existe como referencia de producto.
 */
function existeVIN(string $vin): bool
{
    global $db;
    $sql = "SELECT COUNT(*) AS cnt FROM " . MAIN_DB_PREFIX . "product WHERE ref = '" . $db->escape('DEMO-VEH-' . $vin) . "'";
    $res = $db->query($sql);
    $obj = $db->fetch_object($res);
    return (int)$obj->cnt > 0;
}

// ──────────────────── Generación de Clientes ────────────────────

function generarClientes(): void
{
    global $db;
    $admin = getAdminUser();

    $extrafields = new ExtraFields($db);
    $extrafields->fetch_name_optionals_label('societe');

    logInfo("Generando " . NUM_CLIENTES . " clientes...");

    for ($i = 0; $i < NUM_CLIENTES; $i++) {
        $nombre = DemoDataGenerator::generarNombreCompleto($i);
        $fn = DemoDataGenerator::generarFechaNacimiento(30 + $i);
        $rfc = DemoDataGenerator::generarRFC(
            $nombre['nombre'],
            $nombre['apellidoPaterno'],
            $nombre['apellidoMaterno'],
            $fn
        );

        if (existeRFC($rfc)) {
            logSkip("Cliente RFC={$rfc} ya existe, omitiendo");
            continue;
        }

        $entidad = ['DF', 'NL', 'JC', 'MC', 'GT', 'BS', 'VZ', 'SL', 'HG', 'SR'][$i];
        $sexo = ($i % 2 === 0) ? 'H' : 'M';
        $curp = DemoDataGenerator::generarCURP(
            $nombre['nombre'],
            $nombre['apellidoPaterno'],
            $nombre['apellidoMaterno'],
            $fn,
            $sexo,
            $entidad
        );

        $soc = new Societe($db);
        $soc->name = DEMO_PREFIX . ' ' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT);
        $soc->name_alias = $nombre['razonSocial'];
        $soc->nom = $nombre['razonSocial'];
        $soc->client = 1;
        // No asignar code_client — Dolibarr lo auto-genera según su máscara
        $soc->email = strtolower($nombre['nombre']) . '.' . strtolower($nombre['apellidoPaterno']) . '@demo-pld.mx';
        $soc->tva_intra = $rfc; // Usamos para RFC
        $soc->country_id = 154; // México
        $soc->phone = '55' . rand(1000, 9999) . rand(1000, 9999);
        $soc->status = 1;
        $soc->entity = 1;

        // Si hay extrafields PLD definidos, poblarlos
        if (!empty($extrafields->attributes['societe']['label'])) {
            $municipios = ['Álvaro Obregón','Benito Juárez','Coyoacán','Cuauhtémoc','Miguel Hidalgo'];
            $estados = ['Ciudad de México','Nuevo León','Jalisco','Guanajuato','Estado de México'];
            foreach ($extrafields->attributes['societe']['label'] as $key => $label) {
                    if (strpos($key, 'pld_') === 0) {
                        $soc->array_options['options_' . $key] = match ($key) {
                            'pld_curp'                  => $curp,
                            'pld_rfc'                   => $rfc,
                            'pld_rfc_validado'          => $rfc,
                            'pld_nacionalidad'          => 'MX',
                            'pld_pais_nacimiento'       => 'MX',
                            'pld_pais_residencia'       => 'MX',
                            'pld_tipo_persona'          => 'FI',
                            'pld_calle'                 => 'Calle Ficticia ' . ($i + 1),
                            'pld_numero_exterior'       => (string)(100 + $i),
                            'pld_numero_interior'       => '',
                            'pld_colonia'               => 'Colonia Centro',
                            'pld_codigo_postal'         => '0' . rand(1000, 9999),
                            'pld_municipio'            => $municipios[$i % 5],
                            'pld_estado'                => $estados[$i % 5],
                            'pld_pais'                  => 'MX',
                            'pld_es_domicilio_extranjero' => '0',
                            'pld_actividad_economica'   => '465111',
                            'pld_giro_mercantil'        => '',
                            'pld_cliente_identificado'  => '1',
                            'pld_fecha_identificacion'  => date('Y-m-d', strtotime('-30 days')),
                            'pld_expediente_completo'   => '1',
                            'pld_es_pep'                => '0',
                            'pld_tiene_beneficiario'    => '0',
                            'pld_nivel_riesgo'          => 'bajo',
                            'pld_nivel_diligencia'      => 'simplificada',
                            default                     => '',
                        };
                    }
            }
        }

        $id = $soc->create($admin);
        if ($id > 0) {
            logOk("Cliente #{$id}: {$soc->name} | RFC={$rfc} | CURP={$curp}");
        } else {
            logErr("Error creando cliente: {$soc->error}");
            if (!empty($soc->errors)) {
                foreach ($soc->errors as $e) {
                    logErr("  -> {$e}");
                }
            }
        }
    }
}

// ──────────────────── Generación de Vehículos ────────────────────

function generarVehiculos(): void
{
    global $db;
    $admin = getAdminUser();

    $marcas = ['TOYOTA', 'HONDA', 'NISSAN', 'FORD', 'CHEVROLET', 'VOLKSWAGEN', 'MAZDA', 'KIA', 'HYUNDAI', 'BMW'];
    $modelos = [
        'TOYOTA' => ['Corolla', 'RAV4', 'Hilux'],
        'HONDA' => ['Civic', 'CR-V', 'HR-V'],
        'NISSAN' => ['Versa', 'Sentra', 'X-Trail'],
        'FORD' => ['Escape', 'Explorer', 'Ranger'],
        'CHEVROLET' => ['Aveo', 'Tracker', 'Silverado'],
        'VOLKSWAGEN' => ['Jetta', 'Tiguan', 'Virtus'],
        'MAZDA' => ['Mazda3', 'CX-5', 'MX-5'],
        'KIA' => ['Rio', 'Sportage', 'Seltos'],
        'HYUNDAI' => ['Elantra', 'Tucson', 'Creta'],
        'BMW' => ['Serie 3', 'X3', 'X5'],
    ];

    logInfo("Generando " . NUM_VEHICULOS . " vehículos...");

    for ($i = 0; $i < NUM_VEHICULOS; $i++) {
        $vin = DemoDataGenerator::generarVIN();
        $marca = $marcas[$i];
        $modelo = $modelos[$marca][$i % 3];
        $anio = 2022 + ($i % 4);
        $precio = 180000 + ($i * 45000) + rand(1, 50000);

        if (existeVIN($vin)) {
            logSkip("Vehículo VIN={$vin} ya existe, omitiendo");
            continue;
        }

        $prod = new Product($db);
        $prod->ref = DEMO_PREFIX . '-VEH-' . $vin;
        $prod->label = "{$marca} {$modelo} {$anio}";
        $prod->description = "{$marca} {$modelo} | VIN: {$vin} | Año: {$anio}";
        $prod->type = 0; // Producto
        $prod->status = 1;
        $prod->entity = 1;
        $prod->price = $precio;
        $prod->price_ttc = round($precio * 1.16, 2);
        $prod->tva_tx = 16.0;
        $prod->finished = 1;

        $id = $prod->create($admin);
        if ($id > 0) {
            logOk("Vehículo #{$id}: {$prod->ref} | {$prod->label} | \${$precio} | VIN={$vin}");
        } else {
            logErr("Error creando vehículo: {$prod->error}");
            if (!empty($prod->errors)) {
                foreach ($prod->errors as $e) {
                    logErr("  -> {$e}");
                }
            }
        }
    }
}

// ──────────────────── Generación de Facturas ────────────────────

function obtenerClientePorIndex(int $index): ?Societe
{
    global $db;
    $nombre = DEMO_PREFIX . ' ' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE nom = '" . $db->escape($nombre) . "' LIMIT 1";
    $res = $db->query($sql);
    if ($obj = $db->fetch_object($res)) {
        $soc = new Societe($db);
        $soc->fetch((int)$obj->rowid);
        return $soc;
    }
    return null;
}

function obtenerVehiculoPorIndex(int $index): ?Product
{
    global $db;
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product WHERE ref LIKE 'DEMO-VEH-%' ORDER BY rowid LIMIT 1 OFFSET {$index}";
    $res = $db->query($sql);
    if ($obj = $db->fetch_object($res)) {
        $prod = new Product($db);
        $prod->fetch((int)$obj->rowid);
        return $prod;
    }
    return null;
}

function generarFacturas(): void
{
    global $db;
    $admin = getAdminUser();

    logInfo("Generando " . NUM_FACTURAS . " facturas...");

    for ($i = 0; $i < NUM_FACTURAS; $i++) {
        $escenario = ESCENARIOS[$i];

        // Obtener cliente y vehículo correspondientes
        $cliente = obtenerClientePorIndex($i);
        $vehiculo = obtenerVehiculoPorIndex($i);

        if (!$cliente) {
            logSkip("Factura #{$i}: cliente DEMO " . str_pad((string)($i+1), 3, '0', STR_PAD_LEFT) . " no encontrado, ejecute --clientes primero");
            continue;
        }
        if (!$vehiculo) {
            logSkip("Factura #{$i}: vehículo no encontrado, ejecute --vehiculos primero");
            continue;
        }

        // Verificar si ya existe factura para este par cliente-vehículo
        $checkSql = "SELECT COUNT(*) AS cnt FROM " . MAIN_DB_PREFIX . "facturedet fd "
            . "JOIN " . MAIN_DB_PREFIX . "facture f ON f.rowid = fd.fk_facture "
            . "WHERE f.fk_soc = " . (int)$cliente->id . " AND fd.fk_product = " . (int)$vehiculo->id;
        $checkRes = $db->query($checkSql);
        $checkObj = $db->fetch_object($checkRes);
        if ((int)$checkObj->cnt > 0) {
            logSkip("Factura para {$cliente->name} + {$vehiculo->label} ya existe");
            continue;
        }

        // Crear factura
        $monto = $escenario['monto'];
        $tipo = $escenario['tipo'];

        $facture = new Facture($db);
        $facture->socid = (int)$cliente->id;
        $facture->type = 0; // Standard invoice
        $facture->entity = 1;
        $facture->date = dol_now();
        $facture->note_public = "Factura de prueba PLD - {$tipo} - {$monto} MXN";
        $facture->import_key = 'DEMO_PLD_TEST';

        $id = $facture->create($admin);
        if ($id <= 0) {
            logErr("Error creando factura: {$facture->error}");
            if (!empty($facture->errors)) {
                foreach ($facture->errors as $e) {
                    logErr("  -> {$e}");
                }
            }
            continue;
        }

        // Agregar línea de vehículo
        $facture->addline(
            $vehiculo->label,
            $monto,      // price HT
            1,            // quantity
            16.0,         // TVA rate
            0,            // discount
            $monto * 0.16,// total TVA
            $monto * 1.16,// total TTC
            $vehiculo->id,// fk_product
            0,            // product attr
            '',           // info_bits
            'PT',         // product type
            0,            // rang
            0,            // don't apply discount
            '',           // description
            1,            // localtax1_type
            0,            // localtax1_rate
            0,            // localtax2_type
            0             // localtax2_rate
        );

        // Validar la factura
        $facture->validate($admin);

        logOk("Factura #{$id}: {$cliente->name} | {$vehiculo->label} | \${$monto} | tipo={$tipo} | esperado={$escenario['expected']} | estado=validada");
    }

    // ── Generar pagos para facturas validadas ──
    generarPagos($admin);
}

// ──────────────────── Generación de Pagos ────────────────────

function generarPagos(object $admin): void
{
    global $db;

    logInfo("Generando pagos para facturas DEMO...");

    $sql = "SELECT f.rowid, f.total_ttc, f.ref
            FROM " . MAIN_DB_PREFIX . "facture f
            JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = f.fk_soc
            WHERE s.nom LIKE 'DEMO %'
              AND f.fk_statut = 1
              AND f.paye = 0
              AND f.rowid NOT IN (
                  SELECT pf.fk_facture FROM " . MAIN_DB_PREFIX . "paiement_facture pf
              )
            ORDER BY f.rowid";
    $res = $db->query($sql);

    $pagadas = 0;
    while ($obj = $db->fetch_object($res)) {
        $paiement = new Paiement($db);
        $paiement->datepaye = date('Y-m-d');
        $paiement->amounts = [$obj->rowid => $obj->total_ttc];
        $paiement->paiementid = 3; // Transferencia
        $paiement->paiementcode = 'VIR';
        $paiement->num_paiement = 'DEMO-PAGO-' . date('Ymd') . '-' . $obj->rowid;

        $id = $paiement->create($admin);
        if ($id > 0) {
            $pagadas++;
            logOk("Pago #{$id}: {$obj->ref} -> \${$obj->total_ttc} MXN (Transferencia)");
        } else {
            logErr("Error pagando factura {$obj->ref}: {$paiement->error}");
            if (!empty($paiement->errors)) {
                foreach ($paiement->errors as $e) {
                    logErr("  -> {$e}");
                }
            }
        }
    }

    logInfo("Pagos generados: {$pagadas}");
}

// ──────────────────── Limpieza ────────────────────

function limpiarDatos(): void
{
    global $db;

    logInfo("Eliminando datos de prueba DEMO...");

    $tables = [
        'paiement_facture' => "JOIN " . MAIN_DB_PREFIX . "paiement_facture pf JOIN " . MAIN_DB_PREFIX . "facture f ON f.rowid = pf.fk_facture JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = f.fk_soc WHERE s.nom LIKE 'DEMO %'",
        'paiement' => "WHERE ref LIKE 'DEMO-PAGO-%'",
        'facturedet' => "JOIN " . MAIN_DB_PREFIX . "facture f ON f.rowid = fd.fk_facture JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = f.fk_soc WHERE s.nom LIKE 'DEMO %'",
        'facture' => "JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = fk_soc WHERE s.nom LIKE 'DEMO %'",
        'product' => "WHERE ref LIKE 'DEMO-VEH-%'",
        'societe' => "WHERE nom LIKE 'DEMO %'",
    ];

    // Primero eliminar pld_operacion (requiere JOIN con societe)
    $db->query("DELETE o FROM " . MAIN_DB_PREFIX . "pld_operacion o JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = o.fk_societe WHERE s.nom LIKE 'DEMO %'");
    logInfo("Limpiando pld_operacion...");

    foreach ($tables as $table => $where) {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $table . " " . $where;
        $db->query($sql);
        logInfo("Limpiando {$table}...");
    }

    logOk("Limpieza completada");
}

// ──────────────────── Main ────────────────────

function mostrarUso(): void
{
    echo "Uso: php demo_generar_datos_prueba.php [OPCIÓN]\n";
    echo "\n";
    echo "Opciones:\n";
    echo "  --clientes    Generar solo clientes PF con extrafields PLD\n";
    echo "  --vehiculos   Generar solo vehículos (productos)\n";
    echo "  --facturas    Generar facturas y pagos (requiere clientes y vehículos)\n";
    echo "  --all         Generar todo (clientes, vehículos, facturas, pagos)\n";
    echo "  --clean       Eliminar todos los datos de prueba DEMO\n";
    echo "  --help        Mostrar esta ayuda\n";
    echo "\n";
}

$opcion = $argv[1] ?? '--help';

switch ($opcion) {
    case '--clientes':
        generarClientes();
        break;
    case '--vehiculos':
        generarVehiculos();
        break;
    case '--facturas':
        generarFacturas();
        break;
    case '--all':
        generarClientes();
        echo "\n";
        generarVehiculos();
        echo "\n";
        generarFacturas();
        echo "\n";
        logOk("¡Generación completa! Ejecuta demo_evaluar_operaciones.php para evaluar umbrales");
        break;
    case '--clean':
        limpiarDatos();
        break;
    case '--help':
    default:
        mostrarUso();
        break;
}

echo "\n";
