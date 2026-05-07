#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * @file        scripts/demo_evaluar_operaciones.php
 * @module      CompliancePLD
 * @description Proceso batch para evaluar facturas y crear operaciones PLD.
 *              Lee facturas validadas, evalúa umbrales según tipo de vehículo
 *              y crea registros en llx_pld_operacion marcando supera_umbral
 *              y requiere_aviso.
 * @author      Atlas / Prometheus
 * @version     1.0.0
 * @date        2026-05-06
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 * @license     GNU/GPL
 *
 * Uso:
 *   docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/demo_evaluar_operaciones.php
 */

// ──────────────────── Bootstrap Dolibarr ────────────────────
if (!defined('NOSESSION'))    { define('NOSESSION', '1'); }
if (!defined('NOLOGIN'))      { define('NOLOGIN', '1'); }
@set_time_limit(0);
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1);

$paths = [
    '/var/www/html/main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../../../main.inc.php',
];
$found = false;
foreach ($paths as $p) {
    if (file_exists($p)) { require $p; $found = true; break; }
}
if (!$found) { echo "ERROR: main.inc.php no encontrado\n"; exit(1); }

require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/modulecompliancepld/class/pldoperacion.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/modulecompliancepld/class/compliancepld.class.php';

global $db, $user;

function logInfo(string $msg): void  { echo "[INFO]  {$msg}\n"; }
function logOk(string $msg): void    { echo "[OK]    {$msg}\n"; }
function logSkip(string $msg): void  { echo "[SKIP]  {$msg}\n"; }
function logErr(string $msg): void   { echo "[ERROR] {$msg}\n"; }

// ──────────────────── Umbrales ────────────────────
$umbralNuevo = (float) getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO', '377778.20');
$umbralUsado = (float) getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO', '117310.00');

logInfo("Umbrales configurados:");
logInfo("  - Vehículo nuevo: \${$umbralNuevo} MXN");
logInfo("  - Vehículo usado: \${$umbralUsado} MXN");
echo "\n";

// ──────────────────── Obtener admin ────────────────────
$adminRes = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "user WHERE admin = 1 LIMIT 1");
$adminObj = $db->fetch_object($adminRes);
$admin = new User($db);
$admin->fetch((int)$adminObj->rowid);

// ──────────────────── Leer facturas DEMO ────────────────────
$sql = "SELECT f.rowid AS facture_id, f.fk_soc AS societe_id, f.total_ttc, f.fk_statut,
               fd.fk_product AS product_id, fd.total_ht
        FROM " . MAIN_DB_PREFIX . "facture f
        JOIN " . MAIN_DB_PREFIX . "facturedet fd ON fd.fk_facture = f.rowid
        WHERE f.ref LIKE 'DEMO%'
          AND f.fk_statut = 1
        ORDER BY f.rowid";

$res = $db->query($sql);
if (!$res) {
    logErr("Error consultando facturas: {$db->lasterror()}");
    exit(1);
}

// ──────────────────── Procesar cada factura ────────────────────
$totalCreadas = 0;
$totalSuperan = 0;
$totalNoSuperan = 0;
$totalSaltadas = 0;

$extrafields = new ExtraFields($db);

while ($obj = $db->fetch_object($res)) {
    $factureId  = (int)$obj->facture_id;
    $societeId  = (int)$obj->societe_id;
    $productId  = (int)$obj->product_id;
    $montoTTL   = (float)$obj->total_ttc;
    $montoHT    = (float)$obj->total_ht;

    // Verificar si ya existe operación para esta factura
    $checkSql = "SELECT COUNT(*) AS cnt FROM " . MAIN_DB_PREFIX . "pld_operacion WHERE fk_facture = {$factureId}";
    $checkRes = $db->query($checkSql);
    $checkObj = $db->fetch_object($checkRes);
    if ((int)$checkObj->cnt > 0) {
        $totalSaltadas++;
        logSkip("Factura #{$factureId}: operación PLD ya existe");
        continue;
    }

    // Determinar tipo de vehículo (nuevo/usado) del extrafield
    $tipoVehiculo = 'nuevo'; // Default
    $extrafields->fetch_name_optionals_label('product');
    if (!empty($extrafields->attributes['product']['label'])) {
        $productObj = new Product($db);
        $productObj->fetch($productId);
        $productObj->fetch_optionals();
        $tipoField = $productObj->array_options['options_pld_tipo_vehiculo'] ?? '';
        if (strtolower($tipoField) === 'usado') {
            $tipoVehiculo = 'usado';
        }
    }

    // Calcular monto sin impuestos
    $montoSinImpuestos = ($montoHT > 0) ? $montoHT : round($montoTTL / 1.16, 2);

    // Evaluar umbral
    $umbral = ($tipoVehiculo === 'nuevo') ? $umbralNuevo : $umbralUsado;
    $superaUmbral = $montoSinImpuestos >= $umbral;

    // Crear operación PLD
    $op = new PLDOperacion($db);
    $op->fk_facture = $factureId;
    $op->fk_societe = $societeId;
    $op->fk_product = $productId;
    $op->tipo_actividad_vulnerable = '808'; // Vehículos
    $op->fecha_operacion = date('Y-m-d');
    $op->mes_reportado = date('Ym');
    $op->moneda = 'MXN';
    $op->monto_mxn = $montoTTL;
    $op->monto_sin_impuestos = $montoSinImpuestos;
    $op->tasa_impuesto = 0.16;
    $op->supera_umbral = $superaUmbral ? 1 : 0;
    $op->requiere_aviso = $superaUmbral ? 1 : 0;
    $op->estado = $superaUmbral ? 'pendiente_documentacion' : 'borrador';
    $op->cliente_identificado = 1;
    $op->documentacion_completa = 0;

    $opId = $op->create($admin);
    if ($opId > 0) {
        $totalCreadas++;
        if ($superaUmbral) {
            $totalSuperan++;
            logOk("Op #{$opId}: Factura #{$factureId} | monto=\${$montoSinImpuestos} | SUPER umbral \${$umbral} ({$tipoVehiculo}) | requiere_aviso=1");
        } else {
            $totalNoSuperan++;
            logInfo("Op #{$opId}: Factura #{$factureId} | monto=\${$montoSinImpuestos} | NO supera umbral \${$umbral} ({$tipoVehiculo}) | requiere_aviso=0");
        }
    } else {
        logErr("Error creando operación para factura #{$factureId}: {$op->error}");
        if (!empty($op->errors)) {
            foreach ($op->errors as $e) {
                logErr("  -> {$e}");
            }
        }
    }
}

echo "\n";
echo "═══════════════════════════════════════════\n";
echo "  RESUMEN DE EVALUACIÓN\n";
echo "═══════════════════════════════════════════\n";
logInfo("Total operaciones creadas: {$totalCreadas}");
logInfo("  - Superan umbral (requieren aviso): {$totalSuperan}");
logInfo("  - No superan umbral:                {$totalNoSuperan}");
logInfo("  - Saltadas (ya existían):           {$totalSaltadas}");
echo "\n";

if ($totalSuperan === 5 && $totalNoSuperan === 5) {
    logOk("¡Resultado esperado! 5 superan + 5 no superan = 10 facturas procesadas correctamente");
} else {
    logErr("Resultado inesperado: {$totalSuperan} superan + {$totalNoSuperan} no superan (esperado 5 + 5)");
}

echo "\n";
echo "Próximo paso: php demo_verificar_resultados.php\n";
