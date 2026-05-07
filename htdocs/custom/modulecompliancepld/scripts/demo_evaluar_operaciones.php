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
$sql = "SELECT f.rowid AS facture_id, f.fk_soc AS societe_id, f.total_ttc, f.note_public
        FROM " . MAIN_DB_PREFIX . "facture f
        JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = f.fk_soc
        WHERE s.nom LIKE 'DEMO %'
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

while ($obj = $db->fetch_object($res)) {
    $factureId  = (int)$obj->facture_id;
    $societeId  = (int)$obj->societe_id;
    $montoTTL   = (float)$obj->total_ttc;

    // Extraer monto del note_public: "Factura de prueba PLD - {tipo} - {monto} MXN"
    $montoExtraido = $montoTTL;
    if ($montoTTL <= 0 && !empty($obj->note_public)) {
        if (preg_match('/(\d+(?:\.\d+)?)\s*MXN/', $obj->note_public, $m)) {
            $montoExtraido = (float)$m[1];
        }
    }
    $montoTTL = ($montoExtraido > 0) ? $montoExtraido : $montoTTL;

    // Verificar si ya existe operación para esta factura
    $checkSql = "SELECT COUNT(*) AS cnt FROM " . MAIN_DB_PREFIX . "pld_operacion WHERE fk_facture = {$factureId}";
    $checkRes = $db->query($checkSql);
    $checkObj = $db->fetch_object($checkRes);
    if ((int)$checkObj->cnt > 0) {
        $totalSaltadas++;
        logSkip("Factura #{$factureId}: operación PLD ya existe");
        continue;
    }

    // Determinar tipo de vehículo por monto (simplificado)
    $tipoVehiculo = ($montoTTL >= $umbralNuevo) ? 'nuevo' : 'usado';

    // Calcular monto sin impuestos
    $montoSinImpuestos = round($montoTTL / 1.16, 2);

    // Evaluar umbral (comparar monto total contra umbral)
    $umbral = ($tipoVehiculo === 'nuevo') ? $umbralNuevo : $umbralUsado;
    $superaUmbral = $montoTTL >= $umbral;

    // Crear operación PLD con SQL directo (schema actual no tiene todas las columnas)
    $now = dol_now();
    $estado = $superaUmbral ? 'pendiente_documentacion' : 'borrador';
    $sqlInsert = "INSERT INTO " . MAIN_DB_PREFIX . "pld_operacion (
        entity, fk_facture, fk_societe,
        tipo_operacion, tipo_actividad_vulnerable,
        fecha_operacion, mes_reportado,
        moneda, monto_mxn,
        supera_umbral, cliente_identificado, documentacion_completa,
        requiere_aviso, aviso_presentado,
        estado, datec
    ) VALUES (
        " . $conf->entity . ",
        {$factureId}, {$societeId},
        'venta_vehiculo', '808',
        '" . date('Y-m-d') . "', '" . date('Ym') . "',
        'MXN', " . $montoTTL . ",
        " . ($superaUmbral ? 1 : 0) . ", 1, 0,
        " . ($superaUmbral ? 1 : 0) . ", 0,
        '{$estado}', '{$db->idate($now)}'
    )";

    $resInsert = $db->query($sqlInsert);
    if ($resInsert) {
        $totalCreadas++;
        $opId = $db->last_insert_id(MAIN_DB_PREFIX . 'pld_operacion');
        if ($superaUmbral) {
            $totalSuperan++;
            logOk("Op #{$opId}: Factura #{$factureId} | monto=\${$montoTTL} | SUPER umbral \${$umbral} ({$tipoVehiculo}) | requiere_aviso=1");
        } else {
            $totalNoSuperan++;
            logInfo("Op #{$opId}: Factura #{$factureId} | monto=\${$montoTTL} | NO supera umbral \${$umbral} ({$tipoVehiculo}) | requiere_aviso=0");
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
