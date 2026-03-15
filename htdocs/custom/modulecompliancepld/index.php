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

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$now       = dol_now();
$thismonth = date('Ym', $now); // YYYYMM del mes actual

/*
 * Contadores del mes actual
 */

// Operaciones vulnerables del mes
$sql_ops_mes  = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_operacion";
$sql_ops_mes .= " WHERE entity IN (".getEntity('modulecompliancepld').")";
$sql_ops_mes .= " AND mes_reportado = '".$db->escape($thismonth)."'";
$res_ops_mes  = $db->query($sql_ops_mes);
$cnt_ops_mes  = ($res_ops_mes ? $db->fetch_object($res_ops_mes)->total : 0);

// Avisos pendientes (estado borrador o pendiente)
$sql_avisos  = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_aviso";
$sql_avisos .= " WHERE entity IN (".getEntity('modulecompliancepld').")";
$sql_avisos .= " AND estado IN ('borrador', 'pendiente')";
$res_avisos  = $db->query($sql_avisos);
$cnt_avisos  = ($res_avisos ? $db->fetch_object($res_avisos)->total : 0);

// Alertas abiertas
$sql_alertas  = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_alerta";
$sql_alertas .= " WHERE entity IN (".getEntity('modulecompliancepld').")";
$sql_alertas .= " AND estado = 'abierta'";
$res_alertas  = $db->query($sql_alertas);
$cnt_alertas  = ($res_alertas ? $db->fetch_object($res_alertas)->total : 0);

// Documentos vencidos (fecha_vencimiento < hoy)
$today_sql    = dol_print_date($now, 'dayrfc');
$sql_docs_venc  = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_documento";
$sql_docs_venc .= " WHERE entity IN (".getEntity('modulecompliancepld').")";
$sql_docs_venc .= " AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento < '".$db->escape($today_sql)."'";
$res_docs_venc  = $db->query($sql_docs_venc);
$cnt_docs_venc  = ($res_docs_venc ? $db->fetch_object($res_docs_venc)->total : 0);

/*
 * View
 */
llxHeader('', $langs->trans("DashPanelControl"), '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-index');

print load_fiche_titre($langs->trans("DashPanelControl"), '', 'fa-shield');

// ---- TARJETAS RESUMEN ----
print '<div class="info-box-wrap">';

// Operaciones del mes
print '<div class="info-box">';
print '<span class="info-box-icon bg-infobox-action"><i class="fa fa-exchange-alt fa-2x"></i></span>';
print '<div class="info-box-content">';
print '<span class="info-box-text">'.$langs->trans('DashOperacionesMes').'</span>';
print '<span class="info-box-number"><a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/operaciones_list.php">'.$cnt_ops_mes.'</a></span>';
print '</div></div>';

// Avisos pendientes
$cls_avisos = $cnt_avisos > 0 ? 'bg-infobox-project' : 'bg-infobox-action';
print '<div class="info-box">';
print '<span class="info-box-icon '.$cls_avisos.'"><i class="fa fa-paper-plane fa-2x"></i></span>';
print '<div class="info-box-content">';
print '<span class="info-box-text">'.$langs->trans('DashAvisosEspera').'</span>';
print '<span class="info-box-number"><a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/avisos_list.php?filtro_estado=pendiente">'.$cnt_avisos.'</a></span>';
print '</div></div>';

// Alertas abiertas
$cls_alert = $cnt_alertas > 0 ? 'bg-infobox-bank' : 'bg-infobox-action';
print '<div class="info-box">';
print '<span class="info-box-icon '.$cls_alert.'"><i class="fa fa-exclamation-triangle fa-2x"></i></span>';
print '<div class="info-box-content">';
print '<span class="info-box-text">'.$langs->trans('DashAlertasAbiertas').'</span>';
print '<span class="info-box-number"><a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/alertas_list.php?filtro_estado=abierta">'.$cnt_alertas.'</a></span>';
print '</div></div>';

// Documentos vencidos
$cls_docs = $cnt_docs_venc > 0 ? 'bg-infobox-bank' : 'bg-infobox-action';
print '<div class="info-box">';
print '<span class="info-box-icon '.$cls_docs.'"><i class="fa fa-file-alt fa-2x"></i></span>';
print '<div class="info-box-content">';
print '<span class="info-box-text">'.$langs->trans('DashDocumentosVencidos').'</span>';
print '<span class="info-box-number"><a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/documentos_list.php">'.$cnt_docs_venc.'</a></span>';
print '</div></div>';

print '</div>'; // info-box-wrap
print '<br>';

// ---- DOS COLUMNAS ----
print '<div class="fichecenter"><div class="fichethirdleft">';

// Últimas operaciones vulnerables
$sql_last_ops  = "SELECT o.rowid, o.folio_interno, o.tipo_operacion, o.monto_mxn, o.supera_umbral, o.estado, s.nom as empresa_nom";
$sql_last_ops .= " FROM ".MAIN_DB_PREFIX."pld_operacion as o";
$sql_last_ops .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = o.fk_societe";
$sql_last_ops .= " WHERE o.entity IN (".getEntity('modulecompliancepld').")";
$sql_last_ops .= " ORDER BY o.datec DESC";
$sql_last_ops .= $db->plimit(10, 0);

$res_last_ops  = $db->query($sql_last_ops);
$num_last_ops  = ($res_last_ops ? $db->num_rows($res_last_ops) : 0);

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
	while ($obj = $db->fetch_object($res_last_ops)) {
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
print '</table><br>';

// Alertas sin resolver
$sql_open_alerts  = "SELECT al.rowid, al.tipo_alerta, al.nivel_riesgo, al.fecha_alerta, s.nom as empresa_nom";
$sql_open_alerts .= " FROM ".MAIN_DB_PREFIX."pld_alerta as al";
$sql_open_alerts .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = al.fk_societe";
$sql_open_alerts .= " WHERE al.entity IN (".getEntity('modulecompliancepld').") AND al.estado = 'abierta'";
$sql_open_alerts .= " ORDER BY CASE al.nivel_riesgo WHEN 'alto' THEN 1 WHEN 'medio' THEN 2 ELSE 3 END, al.fecha_alerta DESC";
$sql_open_alerts .= $db->plimit(10, 0);

$res_open_alerts  = $db->query($sql_open_alerts);
$num_open_alerts  = ($res_open_alerts ? $db->num_rows($res_open_alerts) : 0);

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
	while ($obj = $db->fetch_object($res_open_alerts)) {
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
print '</table><br>';

print '</div><div class="fichetwothirdright">';

// Avisos SAT pendientes
$sql_pending_avisos  = "SELECT a.rowid, a.referencia_aviso, a.tipo_aviso, a.mes_reportado, a.total_operaciones, a.monto_total_mxn";
$sql_pending_avisos .= " FROM ".MAIN_DB_PREFIX."pld_aviso as a";
$sql_pending_avisos .= " WHERE a.entity IN (".getEntity('modulecompliancepld').") AND a.estado IN ('borrador', 'pendiente')";
$sql_pending_avisos .= " ORDER BY a.mes_reportado ASC, a.datec DESC";
$sql_pending_avisos .= $db->plimit(10, 0);

$res_pending  = $db->query($sql_pending_avisos);
$num_pending  = ($res_pending ? $db->num_rows($res_pending) : 0);

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
	while ($obj = $db->fetch_object($res_pending)) {
		print '<tr class="oddeven">';
		print '<td><a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/aviso.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->referencia_aviso ?: '#'.$obj->rowid).'</a></td>';
		print '<td>'.dol_escape_htmltag($tipo_labels[$obj->tipo_aviso] ?? $obj->tipo_aviso).'</td>';
		print '<td>'.dol_escape_htmltag($obj->mes_reportado).'</td>';
		print '<td class="right">'.((int) $obj->total_operaciones).' ops.</td>';
		print '<td class="right nowrap">'.price($obj->monto_total_mxn).'</td>';
		print '</tr>';
	}
}
print '</table><br>';

print '</div></div>';

llxFooter();
$db->close();
