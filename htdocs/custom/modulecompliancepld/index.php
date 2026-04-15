<?php
/* Copyright (C) 2026 Ouroboros
 * License GNU/GPL v3+
 */

/**
 * @file    modulecompliancepld/index.php
 * @brief   Dashboard PLD — Panel de Control Compliance
 */

$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) { $res = @include "../main.inc.php"; }
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php"; }
if (!$res) { die("Include of main fails"); }

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

require_once __DIR__.'/class/services/PLDReporteService.php';

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$now       = dol_now();
$thismonth = date('Ym', $now); // YYYYMM del mes actual

/*
 * Contadores y listados vía Service Layer
 */
$reporteSvc  = new PLDReporteService($db);
$contadores  = $reporteSvc->getContadoresDashboard($thismonth);

$cnt_ops_mes  = $contadores['ops_mes'];
$cnt_avisos   = $contadores['avisos_pendientes'];
$cnt_alertas  = $contadores['alertas_abiertas'];
$cnt_docs_venc = $contadores['docs_vencidos'];

/*
 * View
 */
llxHeader('', $langs->trans("DashPanelControl"), '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-index');

print load_fiche_titre($langs->trans("DashPanelControl"), '', 'fa-shield');

// ---- CONTADORES (estilo boxstat Dolibarr) ----
print '<div class="fichecenter" style="margin-bottom:12px">';
print '<div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center">';

$counters = array(
	array(
		'label' => $langs->trans('DashOperacionesMes'),
		'count' => $cnt_ops_mes,
		'icon'  => 'fa-exchange-alt',
		'color' => 'bg-infobox-action',
		'url'   => DOL_URL_ROOT.'/custom/modulecompliancepld/operaciones_list.php',
	),
	array(
		'label' => $langs->trans('DashAvisosEspera'),
		'count' => $cnt_avisos,
		'icon'  => 'fa-paper-plane',
		'color' => $cnt_avisos > 0 ? 'bg-infobox-project' : 'bg-infobox-contract',
		'url'   => DOL_URL_ROOT.'/custom/modulecompliancepld/avisos_list.php?filtro_estado=pendiente',
	),
	array(
		'label' => $langs->trans('DashAlertasAbiertas'),
		'count' => $cnt_alertas,
		'icon'  => 'fa-exclamation-triangle',
		'color' => $cnt_alertas > 0 ? 'bg-infobox-bank' : 'bg-infobox-action',
		'url'   => DOL_URL_ROOT.'/custom/modulecompliancepld/alertas_list.php?filtro_estado=abierta',
	),
	array(
		'label' => $langs->trans('DashDocumentosVencidos'),
		'count' => $cnt_docs_venc,
		'icon'  => 'fa-file-alt',
		'color' => $cnt_docs_venc > 0 ? 'bg-infobox-bank' : 'bg-infobox-action',
		'url'   => DOL_URL_ROOT.'/custom/modulecompliancepld/documentos_list.php',
	),
);

foreach ($counters as $c) {
	print '<div class="boxstat" style="min-width:160px;max-width:200px;flex:1">';
	print '<div class="boxstaticonarea '.$c['color'].'" style="text-align:center;display:flex;align-items:center;justify-content:center;margin-bottom:0.35rem">';
	print '<span class="fa '.$c['icon'].'" style="font-size:1.1rem"></span>';
	print '</div>';
	print '<div class="boxstatcontent">';
	print '<span class="boxstatnum"><a href="'.dol_escape_htmltag($c['url']).'">'.$c['count'].'</a></span><br>';
	print '<span class="boxstatdesc opacitymedium">'.dol_escape_htmltag($c['label']).'</span>';
	print '</div>';
	print '</div>';
}

print '</div>'; // flex wrapper
print '</div>'; // fichecenter
print '<br>';

// ---- DOS COLUMNAS ----
print '<div class="fichecenter"><div class="fichethirdleft">';

// Últimas operaciones vulnerables
$last_ops     = $reporteSvc->getUltimasOperaciones(10);
$num_last_ops = count($last_ops);

print '<div style="background:#f6f6f6;border-radius:4px;padding:4px 8px 8px 8px;margin-bottom:10px">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="4">';
print $langs->trans('DashUltimasOperaciones');
print ' <a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/operaciones_list.php" class="badge marginleftonlyshort">'.$langs->trans('BtnVerDetalle').' &rarr;</a>';
print '</th>';
print '</tr>';
if ($num_last_ops == 0) {
	print '<tr><td colspan="4" class="opacitymedium">'.$langs->trans('DashSinRegistros').'</td></tr>';
} else {
	foreach ($last_ops as $obj) {
		$trclass = $obj->supera_umbral ? 'trwarning' : 'oddeven';
		print '<tr class="'.$trclass.'">';
		print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/operacion.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->folio_interno ?: '#'.$obj->rowid).'</a></td>';
		print '<td>'.dol_escape_htmltag($obj->empresa_nom).'</td>';
		print '<td class="right nowrap">'.price($obj->monto_mxn).'</td>';
		$badge_color = array('borrador' => 'badge-status0', 'pendiente' => 'badge-status1', 'completado' => 'badge-status4', 'cancelado' => 'badge-status9');
		$bc = $badge_color[$obj->estado] ?? 'badge-status0';
		print '<td class="center"><span class="badge '.$bc.'">'.dol_escape_htmltag($obj->estado).'</span></td>';
		print '</tr>';
	}
}
print '</table>';
print '</div>'; // gray bg operaciones

// Alertas sin resolver
$open_alerts     = $reporteSvc->getAlertasAbiertas(10);
$num_open_alerts = count($open_alerts);

print '<div style="background:#f6f6f6;border-radius:4px;padding:4px 8px 8px 8px;margin-bottom:10px">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="4">';
print $langs->trans('DashAlertasSinResolver');
if ($cnt_alertas > 0) { print ' <span class="badge marginleftonlyshort">'.$cnt_alertas.'</span>'; }
print ' <a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/alertas_list.php?filtro_estado=abierta" class="badge marginleftonlyshort">&rarr;</a>';
print '</th>';
print '</tr>';
if ($num_open_alerts == 0) {
	print '<tr><td colspan="4" class="opacitymedium">'.$langs->trans('DashSinRegistros').'</td></tr>';
} else {
	$nivel_colors = array('alto' => 'badge-status6', 'medio' => 'badge-status5', 'bajo' => 'badge-status1');
	foreach ($open_alerts as $obj) {
		$trclass = ($obj->nivel_riesgo == 'alto' ? 'trwarning' : 'oddeven');
		print '<tr class="'.$trclass.'">';
		print '<td><a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/alerta.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->tipo_alerta).'</a></td>';
		$nc = $nivel_colors[$obj->nivel_riesgo] ?? 'badge-status0';
		print '<td><span class="badge '.$nc.'">'.dol_escape_htmltag($obj->nivel_riesgo).'</span></td>';
		print '<td>'.dol_escape_htmltag($obj->empresa_nom).'</td>';
		print '<td class="right nowrap">'.dol_print_date($db->jdate($obj->fecha_alerta), 'day').'</td>';
		print '</tr>';
	}
}
print '</table>';
print '</div>'; // gray bg alertas

print '</div><div class="fichetwothirdright">';

// Avisos SAT pendientes
$pending_avisos = $reporteSvc->getAvisosPendientes(10);
$num_pending    = count($pending_avisos);

print '<div style="background:#f6f6f6;border-radius:4px;padding:4px 8px 8px 8px;margin-bottom:10px">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="5">';
print $langs->trans('DashAvisosSATEspera');
if ($cnt_avisos > 0) { print ' <span class="badge marginleftonlyshort">'.$cnt_avisos.'</span>'; }
print ' <a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/avisos_list.php" class="badge marginleftonlyshort">&rarr;</a>';
print '</th>';
print '</tr>';
if ($num_pending == 0) {
	print '<tr><td colspan="5" class="opacitymedium">'.$langs->trans('DashSinRegistros').'</td></tr>';
} else {
	$tipo_labels = array('MEN' => $langs->trans('PLDTipoAvisoMensual'), '24H' => $langs->trans('PLDTipoAviso24hrs'), 'ACU' => $langs->trans('PLDTipoAvisoAcumulado'));
	foreach ($pending_avisos as $obj) {
		print '<tr class="oddeven">';
		print '<td><a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/aviso/card.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->referencia_aviso ?: '#'.$obj->rowid).'</a></td>';
		print '<td>'.dol_escape_htmltag($tipo_labels[$obj->tipo_aviso] ?? $obj->tipo_aviso).'</td>';
		print '<td>'.dol_escape_htmltag($obj->mes_reportado).'</td>';
		print '<td class="right">'.((int) $obj->numero_operaciones).' ops.</td>';
		print '<td class="right nowrap">'.price($obj->monto_total_operaciones).'</td>';
		print '</tr>';
	}
}
print '</table>';
print '</div>'; // gray bg avisos

print '</div></div>';

llxFooter();
$db->close();
