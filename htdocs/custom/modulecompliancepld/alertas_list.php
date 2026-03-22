<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/alertas_list.php
 * @brief   Lista de alertas PLD
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
require_once __DIR__.'/class/pldalerta.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'al.datec';
$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'DESC';
$page      = max(0, (int) GETPOST('page', 'int'));
$limit     = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25);
$offset    = $limit * $page;

$filtro_nivel  = GETPOST('filtro_nivel', 'alpha');
$filtro_estado = GETPOST('filtro_estado', 'alpha');
$filtro_tipo   = GETPOST('filtro_tipo', 'alpha');

$form  = new Form($db);
$title = $langs->trans("ListaAlertas");
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-alertas-list');

$newbtn = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
	$newbtn = dolGetButtonTitle($langs->trans('BtnNuevaAlerta'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/alerta.php?action=create');
}
print load_fiche_titre($title, $newbtn, 'fa-shield');

// Filtros
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="divsearchfield"><table class="noborder" style="margin-bottom:8px"><tr class="liste_titre_filter">';

$niveles = array('' => '-- '.$langs->trans('FiltroTodos').' --', 'alto' => $langs->trans('NivelRiesgoAlto'), 'medio' => $langs->trans('NivelRiesgoMedio'), 'bajo' => $langs->trans('NivelRiesgoBajo'));
print '<td>'.$form->selectarray('filtro_nivel', $niveles, $filtro_nivel).'</td>';

$estados = array('' => '-- '.$langs->trans('FiltroTodos').' --', 'abierta' => $langs->trans('EstadoAbierta'), 'resuelta' => $langs->trans('EstadoResuelta'));
print '<td>'.$form->selectarray('filtro_estado', $estados, $filtro_estado).'</td>';

print '<td><input type="text" name="filtro_tipo" class="flat minwidth100" placeholder="'.$langs->trans('ColTipoAlerta').'" value="'.dol_escape_htmltag($filtro_tipo).'"></td>';
print '<td><input type="submit" class="button" value="'.$langs->trans('Search').'"> ';
print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a></td>';
print '</tr></table></div></form>';

$sql  = "SELECT al.rowid, al.tipo_alerta, al.nivel_riesgo, al.fk_societe, al.descripcion, al.estado, al.datec as fecha_alerta, s.nom as empresa_nom";
$sql .= " FROM ".MAIN_DB_PREFIX."pld_alerta as al";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = al.fk_societe";
$sql .= " WHERE al.entity IN (".getEntity('modulecompliancepld').")";
if (!empty($filtro_nivel))  { $sql .= " AND al.nivel_riesgo = '".$db->escape($filtro_nivel)."'"; }
if (!empty($filtro_estado)) { $sql .= " AND al.estado = '".$db->escape($filtro_estado)."'"; }
if (!empty($filtro_tipo))   { $sql .= " AND al.tipo_alerta LIKE '%".$db->escape($filtro_tipo)."%'"; }

$sqlcount  = "SELECT COUNT(al.rowid) as total FROM ".MAIN_DB_PREFIX."pld_alerta as al WHERE al.entity IN (".getEntity('modulecompliancepld').")";
if (!empty($filtro_nivel))  { $sqlcount .= " AND al.nivel_riesgo = '".$db->escape($filtro_nivel)."'"; }
if (!empty($filtro_estado)) { $sqlcount .= " AND al.estado = '".$db->escape($filtro_estado)."'"; }
if (!empty($filtro_tipo))   { $sqlcount .= " AND al.tipo_alerta LIKE '%".$db->escape($filtro_tipo)."%'"; }

$rescount = $db->query($sqlcount);
$nbtotal  = 0;
if ($rescount) { $obj = $db->fetch_object($rescount); $nbtotal = $obj->total; }

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$param = '';
if (!empty($filtro_nivel))  { $param .= '&filtro_nivel='.urlencode($filtro_nivel); }
if (!empty($filtro_estado)) { $param .= '&filtro_estado='.urlencode($filtro_estado); }
if (!empty($filtro_tipo))   { $param .= '&filtro_tipo='.urlencode($filtro_tipo); }

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColTipoAlerta'), $_SERVER["PHP_SELF"], 'al.tipo_alerta', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColNivelRiesgo'), $_SERVER["PHP_SELF"], 'al.nivel_riesgo', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEmpresa'), $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColDescripcion'), '', '', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEstado'), $_SERVER["PHP_SELF"], 'al.estado', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFechaAlerta'), $_SERVER["PHP_SELF"], 'al.datec', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('', '', '');
print '</tr>';

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i   = 0;
	if ($num == 0) { print '<tr><td colspan="7" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>'; }
	$nivel_colors  = array('alto' => 'badge-status6', 'medio' => 'badge-status5', 'bajo' => 'badge-status1');
	$nivel_labels  = array('alto' => $langs->trans('NivelRiesgoAlto'), 'medio' => $langs->trans('NivelRiesgoMedio'), 'bajo' => $langs->trans('NivelRiesgoBajo'));
	$estado_colors = array('abierta' => 'badge-status1', 'resuelta' => 'badge-status4');
	$estado_labels = array('abierta' => $langs->trans('EstadoAbierta'), 'resuelta' => $langs->trans('EstadoResuelta'));
	while ($i < $num && $i < $limit) {
		$obj     = $db->fetch_object($resql);
		$trclass = ($obj->nivel_riesgo == 'alto' ? 'trwarning' : 'oddeven');
		print '<tr class="'.$trclass.'">';
		print '<td><a href="alerta.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->tipo_alerta).'</a></td>';
		$nc = $nivel_colors[$obj->nivel_riesgo] ?? 'badge-status0';
		$nl = $nivel_labels[$obj->nivel_riesgo] ?? $obj->nivel_riesgo;
		print '<td class="center"><span class="badge '.$nc.'">'.dol_escape_htmltag($nl).'</span></td>';
		print '<td>';
		if ($obj->fk_societe) { print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->fk_societe.'">'.dol_escape_htmltag($obj->empresa_nom).'</a>'; }
		print '</td>';
		print '<td>'.dol_trunc(dol_escape_htmltag($obj->descripcion), 60).'</td>';
		$ec = $estado_colors[$obj->estado] ?? 'badge-status0';
		$el = $estado_labels[$obj->estado] ?? $obj->estado;
		print '<td class="center"><span class="badge '.$ec.'">'.dol_escape_htmltag($el).'</span></td>';
		print '<td>'.dol_print_date($db->jdate($obj->fecha_alerta), 'dayhour').'</td>';
		print '<td class="right nowrap"><a href="alerta.php?id='.$obj->rowid.'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a></td>';
		print '</tr>';
		$i++;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}
print '</table>';
print '</div>';
print '<br>';
llxFooter();
$db->close();
