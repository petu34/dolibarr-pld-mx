<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/avisos_list.php
 * @brief   Lista de avisos SAT PLD
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
require_once __DIR__.'/class/pldaviso.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'a.datec';
$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'DESC';
$page      = max(0, (int) GETPOST('page', 'int'));
$limit     = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25);
$offset    = $limit * $page;

$filtro_tipo   = GETPOST('filtro_tipo', 'alpha');
$filtro_estado = GETPOST('filtro_estado', 'alpha');
$filtro_mes    = GETPOST('filtro_mes', 'alpha');

$form  = new Form($db);
$title = $langs->trans("ListaAvisos");
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-avisos-list');

$newbtn = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
	$newbtn = dolGetButtonTitle($langs->trans('BtnNuevoAviso'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/aviso.php?action=create');
}
print load_fiche_titre($title, $newbtn, 'fa-shield');

// Filtros
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="divsearchfield"><table class="noborder" style="margin-bottom:8px"><tr class="liste_titre_filter">';

$tipos_aviso = array(
	''    => '-- '.$langs->trans('FiltroTodos').' --',
	'MEN' => $langs->trans('PLDTipoAvisoMensual'),
	'24H' => $langs->trans('PLDTipoAviso24hrs'),
	'ACU' => $langs->trans('PLDTipoAvisoAcumulado'),
);
print '<td>'.$form->selectarray('filtro_tipo', $tipos_aviso, $filtro_tipo).'</td>';

$estados_aviso = array(
	''           => '-- '.$langs->trans('FiltroTodos').' --',
	'borrador'   => $langs->trans('EstadoBorrador'),
	'pendiente'  => $langs->trans('EstadoPendiente'),
	'presentado' => $langs->trans('EstadoPresentado'),
	'cancelado'  => $langs->trans('EstadoCancelado'),
);
print '<td>'.$form->selectarray('filtro_estado', $estados_aviso, $filtro_estado).'</td>';

print '<td><input type="text" name="filtro_mes" class="flat minwidth100" placeholder="YYYYMM" value="'.dol_escape_htmltag($filtro_mes).'"></td>';
print '<td><input type="submit" class="button" value="'.$langs->trans('Search').'"> ';
print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a></td>';
print '</tr></table></div></form>';

// Query
$sql  = "SELECT a.rowid, a.referencia_aviso, a.tipo_aviso, a.mes_reportado, a.estado,";
$sql .= " a.fecha_presentacion, a.folio_sat, a.numero_operaciones, a.monto_total_operaciones";
$sql .= " FROM ".MAIN_DB_PREFIX."pld_aviso as a";
$sql .= " WHERE a.entity IN (".getEntity('modulecompliancepld').")";
if (!empty($filtro_tipo))   { $sql .= " AND a.tipo_aviso = '".$db->escape($filtro_tipo)."'"; }
if (!empty($filtro_estado)) { $sql .= " AND a.estado = '".$db->escape($filtro_estado)."'"; }
if (!empty($filtro_mes))    { $sql .= " AND a.mes_reportado = '".$db->escape($filtro_mes)."'"; }

$sqlcount  = "SELECT COUNT(a.rowid) as total FROM ".MAIN_DB_PREFIX."pld_aviso as a WHERE a.entity IN (".getEntity('modulecompliancepld').")";
if (!empty($filtro_tipo))   { $sqlcount .= " AND a.tipo_aviso = '".$db->escape($filtro_tipo)."'"; }
if (!empty($filtro_estado)) { $sqlcount .= " AND a.estado = '".$db->escape($filtro_estado)."'"; }
if (!empty($filtro_mes))    { $sqlcount .= " AND a.mes_reportado = '".$db->escape($filtro_mes)."'"; }

$rescount = $db->query($sqlcount);
$nbtotal  = 0;
if ($rescount) { $obj = $db->fetch_object($rescount); $nbtotal = $obj->total; }

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$param = '';
if (!empty($filtro_tipo))   { $param .= '&filtro_tipo='.urlencode($filtro_tipo); }
if (!empty($filtro_estado)) { $param .= '&filtro_estado='.urlencode($filtro_estado); }
if (!empty($filtro_mes))    { $param .= '&filtro_mes='.urlencode($filtro_mes); }

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColReferenciaAviso'), $_SERVER["PHP_SELF"], 'a.referencia_aviso', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColTipoAviso'), $_SERVER["PHP_SELF"], 'a.tipo_aviso', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColMesReportado'), $_SERVER["PHP_SELF"], 'a.mes_reportado', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEstado'), $_SERVER["PHP_SELF"], 'a.estado', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFechaPresentacion'), $_SERVER["PHP_SELF"], 'a.fecha_presentacion', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFolioSAT'), $_SERVER["PHP_SELF"], 'a.folio_sat', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('TotalOperaciones'), $_SERVER["PHP_SELF"], 'a.numero_operaciones', '', $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('', '', '');
print '</tr>';

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i   = 0;
	if ($num == 0) {
		print '<tr><td colspan="8" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>';
	}
	$tipo_labels   = array('MEN' => $langs->trans('PLDTipoAvisoMensual'), '24H' => $langs->trans('PLDTipoAviso24hrs'), 'ACU' => $langs->trans('PLDTipoAvisoAcumulado'));
	$estado_colors = array('borrador' => 'badge-status0', 'pendiente' => 'badge-status1', 'presentado' => 'badge-status4', 'cancelado' => 'badge-status9');
	$estado_labels = array('borrador' => $langs->trans('EstadoBorrador'), 'pendiente' => $langs->trans('EstadoPendiente'), 'presentado' => $langs->trans('EstadoPresentado'), 'cancelado' => $langs->trans('EstadoCancelado'));
	while ($i < $num && $i < $limit) {
		$obj = $db->fetch_object($resql);
		print '<tr class="oddeven">';
		print '<td><a href="aviso.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->referencia_aviso ?: '#'.$obj->rowid).'</a></td>';
		print '<td>'.dol_escape_htmltag($tipo_labels[$obj->tipo_aviso] ?? $obj->tipo_aviso).'</td>';
		print '<td>'.dol_escape_htmltag($obj->mes_reportado).'</td>';
		$ec = $estado_colors[$obj->estado] ?? 'badge-status0';
		$el = $estado_labels[$obj->estado] ?? $obj->estado;
		print '<td class="center"><span class="badge '.$ec.'">'.dol_escape_htmltag($el).'</span></td>';
		print '<td>'.dol_print_date($db->jdate($obj->fecha_presentacion), 'day').'</td>';
		print '<td>'.dol_escape_htmltag($obj->folio_sat).'</td>';
		print '<td class="right">'.((int) $obj->numero_operaciones).'</td>';
		print '<td class="right nowrap"><a href="aviso.php?id='.$obj->rowid.'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a></td>';
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
