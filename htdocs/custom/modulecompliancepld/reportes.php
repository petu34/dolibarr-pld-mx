<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/reportes.php
 * @brief   Reportes PLD Compliance
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$reporte = GETPOST('reporte', 'aZ09');
$mes     = (int) GETPOST('mes', 'int') ?: (int) date('m');
$anio    = (int) GETPOST('anio', 'int') ?: (int) date('Y');

$form  = new Form($db);
$title = $langs->trans("ReportesPLD");
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-reportes');

print load_fiche_titre($title, '', 'fa-chart-bar');

// Selector de reporte
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="tabBar"><table class="noborder centpercent"><tr>';
print '<td class="titlefieldmiddle">'.$langs->trans('SeleccionarReporte').'</td>';
print '<td>';
$reportes = array(
	''                 => '-- '.$langs->trans('SeleccionarReporte').' --',
	'resumen_mensual'  => $langs->trans('ReporteResumenMensual'),
	'ops_por_cliente'  => $langs->trans('ReporteOperacionesPorCliente'),
	'estado_avisos'    => $langs->trans('ReporteEstadoAvisos'),
	'alertas_por_tipo' => $langs->trans('ReporteAlertasPorTipo'),
);
print $form->selectarray('reporte', $reportes, $reporte, 0, 0, 0, '', 0, 0, 0, '', 'minwidth250');
print '</td>';

// Parámetros de período
print '<td>'.$langs->trans('ParametroMes').':</td>';
$meses = array(1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre');
print '<td>'.$form->selectarray('mes', $meses, $mes).'</td>';
print '<td>'.$langs->trans('ParametroAnio').':</td>';
$anios = array();
for ($y = (int) date('Y') - 3; $y <= (int) date('Y') + 1; $y++) { $anios[$y] = $y; }
print '<td>'.$form->selectarray('anio', $anios, $anio).'</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans('GenerarReporte').'"></td>';
print '</tr></table></div>';
print '</form><br>';

// Mostrar reporte seleccionado
$mes_str       = str_pad($mes, 2, '0', STR_PAD_LEFT);
$periodo       = $anio.$mes_str;
$periodo_label = $meses[$mes].' '.$anio;

if ($reporte == 'resumen_mensual') {
	print '<h3>'.$langs->trans('ReporteResumenMensual').' &mdash; '.$periodo_label.'</h3>';

	$sql  = "SELECT COUNT(rowid) as total_ops, SUM(monto_mxn) as total_monto,";
	$sql .= " SUM(CASE WHEN supera_umbral = 1 THEN 1 ELSE 0 END) as ops_umbral,";
	$sql .= " SUM(CASE WHEN requiere_aviso = 1 THEN 1 ELSE 0 END) as ops_aviso";
	$sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion";
	$sql .= " WHERE entity IN (".getEntity('modulecompliancepld').") AND mes_reportado = '".$db->escape($periodo)."'";
	$res  = $db->query($sql);

	if ($res && $obj = $db->fetch_object($res)) {
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th>'.$langs->trans("Indicador").'</th><th>'.$langs->trans("Value").'</th></tr>';
		print '<tr class="oddeven"><td>'.$langs->trans('TotalOperaciones').'</td><td class="right"><b>'.((int) $obj->total_ops).'</b></td></tr>';
		print '<tr class="oddeven"><td>'.$langs->trans('TotalMonto').' (MXN)</td><td class="right"><b>'.price($obj->total_monto).'</b></td></tr>';
		print '<tr class="oddeven"><td>Operaciones que superan umbral</td><td class="right"><b>'.((int) $obj->ops_umbral).'</b></td></tr>';
		print '<tr class="oddeven"><td>Operaciones que requieren aviso</td><td class="right"><b>'.((int) $obj->ops_aviso).'</b></td></tr>';
		print '</table><br>';
	}

	$sql2  = "SELECT COUNT(rowid) as total, estado FROM ".MAIN_DB_PREFIX."pld_aviso";
	$sql2 .= " WHERE entity IN (".getEntity('modulecompliancepld').") AND mes_reportado = '".$db->escape($periodo)."'";
	$sql2 .= " GROUP BY estado";
	$res2  = $db->query($sql2);
	if ($res2 && $db->num_rows($res2) > 0) {
		print '<h4>'.$langs->trans('AvisosPresen').' / '.$langs->trans('AvisosPend').'</h4>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th>'.$langs->trans('ColEstado').'</th><th>'.$langs->trans('TotalOperaciones').'</th></tr>';
		while ($obj2 = $db->fetch_object($res2)) {
			print '<tr class="oddeven"><td>'.dol_escape_htmltag($obj2->estado).'</td><td class="right">'.$obj2->total.'</td></tr>';
		}
		print '</table>';
	}

} elseif ($reporte == 'ops_por_cliente') {
	print '<h3>'.$langs->trans('ReporteOperacionesPorCliente').' &mdash; '.$periodo_label.'</h3>';

	$sql  = "SELECT s.rowid, s.nom, COUNT(o.rowid) as total_ops, SUM(o.monto_mxn) as total_monto";
	$sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion as o";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = o.fk_societe";
	$sql .= " WHERE o.entity IN (".getEntity('modulecompliancepld').") AND o.mes_reportado = '".$db->escape($periodo)."'";
	$sql .= " GROUP BY s.rowid, s.nom ORDER BY total_monto DESC";
	$res  = $db->query($sql);

	print '<table class="tagtable liste">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans('ColEmpresa').'</th>';
	print '<th class="right">'.$langs->trans('TotalOperaciones').'</th>';
	print '<th class="right">'.$langs->trans('TotalMonto').' (MXN)</th>';
	print '<th class="center">Supera $500K</th>';
	print '</tr>';

	if ($res && $db->num_rows($res) > 0) {
		while ($obj = $db->fetch_object($res)) {
			$supera500k = ((float) $obj->total_monto >= 500000);
			$trclass    = $supera500k ? 'trwarning' : 'oddeven';
			print '<tr class="'.$trclass.'">';
			print '<td><a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->rowid.'">'.dol_escape_htmltag($obj->nom).'</a></td>';
			print '<td class="right">'.((int) $obj->total_ops).'</td>';
			print '<td class="right">'.price($obj->total_monto).'</td>';
			print '<td class="center">'.($supera500k ? '<span class="badge badge-status6">'.$langs->trans('Yes').'</span>' : '').'</td>';
			print '</tr>';
		}
	} else {
		print '<tr><td colspan="4" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>';
	}
	print '</table>';

} elseif ($reporte == 'estado_avisos') {
	print '<h3>'.$langs->trans('ReporteEstadoAvisos').'</h3>';

	$sql  = "SELECT mes_reportado, estado, COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_aviso";
	$sql .= " WHERE entity IN (".getEntity('modulecompliancepld').")";
	$sql .= " GROUP BY mes_reportado, estado ORDER BY mes_reportado DESC, estado";
	$res  = $db->query($sql);

	print '<table class="tagtable liste">';
	print '<tr class="liste_titre"><th>'.$langs->trans('ColMesReportado').'</th><th>'.$langs->trans('ColEstado').'</th><th class="right">'.$langs->trans('TotalOperaciones').'</th></tr>';
	if ($res && $db->num_rows($res) > 0) {
		while ($obj = $db->fetch_object($res)) {
			print '<tr class="oddeven">';
			print '<td>'.dol_escape_htmltag($obj->mes_reportado).'</td>';
			print '<td>'.dol_escape_htmltag($obj->estado).'</td>';
			print '<td class="right">'.$obj->total.'</td>';
			print '</tr>';
		}
	} else {
		print '<tr><td colspan="3" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>';
	}
	print '</table>';

} elseif ($reporte == 'alertas_por_tipo') {
	print '<h3>'.$langs->trans('ReporteAlertasPorTipo').'</h3>';

	$sql  = "SELECT tipo_alerta, nivel_riesgo, COUNT(rowid) as total,";
	$sql .= " SUM(CASE WHEN estado = 'abierta' THEN 1 ELSE 0 END) as abiertas,";
	$sql .= " SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas";
	$sql .= " FROM ".MAIN_DB_PREFIX."pld_alerta";
	$sql .= " WHERE entity IN (".getEntity('modulecompliancepld').")";
	$sql .= " GROUP BY tipo_alerta, nivel_riesgo ORDER BY nivel_riesgo, total DESC";
	$res  = $db->query($sql);

	print '<table class="tagtable liste">';
	print '<tr class="liste_titre"><th>'.$langs->trans('ColTipoAlerta').'</th><th>'.$langs->trans('ColNivelRiesgo').'</th><th class="right">Total</th><th class="right">Abiertas</th><th class="right">Resueltas</th></tr>';
	if ($res && $db->num_rows($res) > 0) {
		$nivel_colors = array('alto' => 'badge-status6', 'medio' => 'badge-status5', 'bajo' => 'badge-status1');
		while ($obj = $db->fetch_object($res)) {
			$nc = $nivel_colors[$obj->nivel_riesgo] ?? 'badge-status0';
			print '<tr class="oddeven">';
			print '<td>'.dol_escape_htmltag($obj->tipo_alerta).'</td>';
			print '<td><span class="badge '.$nc.'">'.dol_escape_htmltag($obj->nivel_riesgo).'</span></td>';
			print '<td class="right">'.$obj->total.'</td>';
			print '<td class="right">'.($obj->abiertas > 0 ? '<b class="error">'.$obj->abiertas.'</b>' : '0').'</td>';
			print '<td class="right">'.$obj->resueltas.'</td>';
			print '</tr>';
		}
	} else {
		print '<tr><td colspan="5" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>';
	}
	print '</table>';

} else {
	// Pantalla inicial con tarjetas de reportes disponibles
	print '<div class="info-box-wrap">';
	$report_list = array(
		'resumen_mensual'  => array('icon' => 'fa-chart-bar', 'label' => $langs->trans('ReporteResumenMensual'), 'desc' => 'Total operaciones, monto acumulado, avisos del mes'),
		'ops_por_cliente'  => array('icon' => 'fa-users', 'label' => $langs->trans('ReporteOperacionesPorCliente'), 'desc' => 'Acumulado por tercero — detecta operaciones > $500K'),
		'estado_avisos'    => array('icon' => 'fa-paper-plane', 'label' => $langs->trans('ReporteEstadoAvisos'), 'desc' => 'Avisos presentados vs pendientes por mes'),
		'alertas_por_tipo' => array('icon' => 'fa-exclamation-triangle', 'label' => $langs->trans('ReporteAlertasPorTipo'), 'desc' => 'Distribucion y tiempo de resolucion de alertas'),
	);
	foreach ($report_list as $rkey => $rinfo) {
		print '<div class="info-box" style="cursor:pointer" onclick="window.location=\''.$_SERVER["PHP_SELF"].'?reporte='.$rkey.'&mes='.$mes.'&anio='.$anio.'\'">';
		print '<span class="info-box-icon bg-infobox-action"><i class="fa '.$rinfo['icon'].' fa-2x"></i></span>';
		print '<div class="info-box-content">';
		print '<span class="info-box-text"><b>'.dol_escape_htmltag($rinfo['label']).'</b></span>';
		print '<span class="info-box-number" style="font-size:0.85em">'.dol_escape_htmltag($rinfo['desc']).'</span>';
		print '</div></div>';
	}
	print '</div>';
}

llxFooter();
$db->close();
