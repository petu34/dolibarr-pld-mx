#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * @file        scripts/demo_verificar_resultados.php
 * @module      CompliancePLD
 * @description Verifica los resultados del sistema demo PLD:
 *              lista operaciones creadas, valida umbrales y
 *              detecta discrepancias en el marcado de supera_umbral.
 * @author      Atlas / Prometheus
 * @version     1.0.0
 * @date        2026-05-06
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 * @license     GNU/GPL
 *
 * Uso:
 *   docker exec doli20 php /var/www/html/custom/modulecompliancepld/scripts/demo_verificar_resultados.php
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

require_once DOL_DOCUMENT_ROOT . '/custom/modulecompliancepld/class/compliancepld.class.php';

global $db;

// ──────────────────── Umbrales ────────────────────
$umbralNuevo = (float) getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO', '377778.20');
$umbralUsado = (float) getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO', '117310.00');

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN DE OPERACIONES PLD — SISTEMA DEMO\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

printf("  Umbral vehículo nuevo:  \$%10.2f MXN\n", $umbralNuevo);
printf("  Umbral vehículo usado:  \$%10.2f MXN\n", $umbralUsado);
echo "\n";

// ──────────────────── Consultar operaciones ────────────────────
$sql = "SELECT o.rowid, o.fk_facture, o.fk_societe, o.fk_product,
               o.monto_mxn, o.supera_umbral, o.requiere_aviso, o.estado,
               o.tipo_operacion, o.fecha_operacion,
               f.ref AS factura_ref, f.total_ttc,
               s.nom AS cliente_nombre,
               p.label AS vehiculo_label
        FROM " . MAIN_DB_PREFIX . "pld_operacion o
        LEFT JOIN " . MAIN_DB_PREFIX . "facture f ON f.rowid = o.fk_facture
        LEFT JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = o.fk_societe
        LEFT JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = o.fk_product
        WHERE s.nom LIKE 'DEMO %'
        ORDER BY o.monto_mxn DESC";

$res = $db->query($sql);
if (!$res) {
    echo "ERROR: {$db->lasterror()}\n";
    exit(1);
}

// ──────────────────── Tabla de resultados ────────────────────
echo "┌──────┬──────────────────┬────────────────────────────┬──────────────────────────┬──────────────┬──────────┬───────────────┬────────────────┐\n";
echo "│ OpID │ Factura          │ Cliente                    │ Vehículo                 │ Monto (s/IVA)│ Supera?  │ RequiereAviso │ Estado         │\n";
echo "├──────┼──────────────────┼────────────────────────────┼──────────────────────────┼──────────────┼──────────┼───────────────┼────────────────┤\n";

$totalSuperan = 0;
$totalNoSuperan = 0;
$discrepancias = 0;

while ($obj = $db->fetch_object($res)) {
    $montoSinIVA = (float)$obj->monto_mxn;
    $supera = (int)$obj->supera_umbral;
    $requiere = (int)$obj->requiere_aviso;

    // Validar umbral
    $umbral = $umbralNuevo; // default
    $deberiaSuperar = $montoSinIVA >= $umbralNuevo;

    $superaStr = $supera ? 'SÍ ⚠️' : 'No ';
    $requiereStr = $requiere ? 'SÍ ⚠️' : 'No ';

    // Detectar discrepancias
    $icono = '  ';
    if ($deberiaSuperar && !$supera) {
        $icono = '❌ ';
        $discrepancias++;
    } elseif (!$deberiaSuperar && $supera) {
        $icono = '⚠️ ';
        $discrepancias++;
    } elseif ($deberiaSuperar) {
        $icono = '✅ ';
        $totalSuperan++;
    } else {
        $icono = '   ';
        $totalNoSuperan++;
    }

    printf(
        "│ %s%-4d │ %-16s │ %-26s │ %-24s │ \$%10.2f │ %-8s │ %-13s │ %-14s │\n",
        $icono,
        (int)$obj->rowid,
        substr($obj->factura_ref ?? 'N/A', 0, 16),
        substr($obj->cliente_nombre ?? 'N/A', 0, 26),
        substr($obj->vehiculo_label ?? 'N/A', 0, 24),
        $montoSinIVA,
        $superaStr,
        $requiereStr,
        $obj->estado
    );
}

echo "└──────┴──────────────────┴────────────────────────────┴──────────────────────────┴──────────────┴──────────┴───────────────┴────────────────┘\n";
echo "\n";

// ──────────────────── Resumen ────────────────────
echo "═══════════════════════════════════\n";
echo "  RESUMEN\n";
echo "═══════════════════════════════════\n";
echo "\n";
printf("  Operaciones que superan umbral:  %d\n", $totalSuperan);
printf("  Operaciones que NO superan:      %d\n", $totalNoSuperan);
printf("  Discrepancias detectadas:        %d\n", $discrepancias);
echo "\n";

// ──────────────────── Veredicto ────────────────────
if ($totalSuperan === 5 && $totalNoSuperan === 5 && $discrepancias === 0) {
    echo "  ✅ VEREDICTO: Sistema funcionando correctamente\n";
    echo "     Las 5 operaciones que superan umbral están correctamente\n";
    echo "     marcadas con requiere_aviso=1 y las 5 que no superan\n";
    echo "     están correctamente marcadas con requiere_aviso=0.\n";
} elseif ($discrepancias > 0) {
    echo "  ❌ VEREDICTO: Se encontraron {$discrepancias} discrepancias\n";
    echo "     Revisar la lógica de evaluación de umbrales en el \n";
    echo "     sistema demo o en PLDOperacion::evaluarUmbral().\n";
} else {
    echo "  ⚠️  VEREDICTO: Conteo inesperado\n";
    echo "     Superan: {$totalSuperan} (esperado 5), No superan: {$totalNoSuperan} (esperado 5)\n";
    echo "     Verificar escenarios de datos de prueba.\n";
}

// ──────────────────── Verificación de datos de prueba ────────────────────
echo "\n";
echo "────────────────────────────────────────\n";
echo "  DATOS DE PRUEBA EN EL SISTEMA\n";
echo "────────────────────────────────────────\n";
echo "\n";

$tablas = [
    'Clientes PF'     => "SELECT COUNT(*) FROM " . MAIN_DB_PREFIX . "societe WHERE nom LIKE 'DEMO %'",
    'Vehículos'       => "SELECT COUNT(*) FROM " . MAIN_DB_PREFIX . "product WHERE ref LIKE 'DEMO-VEH-%'",
    'Facturas'        => "SELECT COUNT(*) FROM " . MAIN_DB_PREFIX . "facture f JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = f.fk_soc WHERE s.nom LIKE 'DEMO %' AND f.fk_statut = 1",
    'Ops PLD (total)' => "SELECT COUNT(*) FROM " . MAIN_DB_PREFIX . "pld_operacion o JOIN " . MAIN_DB_PREFIX . "societe s ON s.rowid = o.fk_societe WHERE s.nom LIKE 'DEMO %'",
];

foreach ($tablas as $nombre => $query) {
    $r = $db->query($query);
    $o = $db->fetch_array($r);
    $cnt = (int)$o[0];
    $status = ($cnt > 0) ? "✅ {$cnt}" : "❌ {$cnt}";
    printf("  %-25s %s\n", $nombre . ':', $status);
}

echo "\n";
echo "Para limpiar datos de prueba: php demo_generar_datos_prueba.php --clean\n";
echo "\n";
