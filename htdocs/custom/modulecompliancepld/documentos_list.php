<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/documentos_list.php
 * @brief   Lista de documentos PLD
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
require_once __DIR__.'/class/plddocumento.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'd.datec';
$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'DESC';
$page      = max(0, (int) GETPOST('page', 'int'));
$limit     = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25);
$offset    = $limit * $page;

$filtro_empresa = (int) GETPOST('filtro_empresa', 'int');
$filtro_tipo    = GETPOST('filtro_tipo', 'alpha');
$filtro_verif   = GETPOST('filtro_verif', 'alpha');

$form  = new Form($db);
$title = $langs->trans("ListaDocumentos");
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-documentos-list');

$newbtn = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
	$newbtn = dolGetButtonTitle($langs->trans('BtnNuevoDocumento'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/documento.php?action=create');
}
print load_fiche_titre($title, $newbtn, 'fa-shield');

// Filtros
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="divsearchfield"><table class="noborder" style="margin-bottom:8px"><tr class="liste_titre_filter">';
$societes = array(0 => '-- '.$langs->trans('FiltroTodos').' --');
$sqlsoc   = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."societe WHERE entity IN (".getEntity('societe').") ORDER BY nom";
$ressoc   = $db->query($sqlsoc);
if ($ressoc) { while ($obj = $db->fetch_object($ressoc)) { $societes[$obj->rowid] = $obj->nom; } }
print '<td>'.$form->selectarray('filtro_empresa', $societes, $filtro_empresa).'</td>';
print '<td><input type="text" name="filtro_tipo" class="flat minwidth100" placeholder="'.$langs->trans('ColTipoDocumento').'" value="'.dol_escape_htmltag($filtro_tipo).'"></td>';
$verif_opts = array('' => '-- '.$langs->trans('FiltroTodos').' --', '1' => $langs->trans('Yes'), '0' => $langs->trans('No'));
print '<td>'.$form->selectarray('filtro_verif', $verif_opts, $filtro_verif).'</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans('Search').'"> ';
print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a></td>';
print '</tr></table></div></form>';

$now = dol_now();
$sql  = "SELECT d.rowid, d.fk_societe, d.fk_socpeople, d.tipo_documento_pld, d.numero_documento,";
$sql .= " d.fecha_emision, d.fecha_vencimiento, d.verificado, s.nom as empresa_nom,";
$sql .= " CONCAT(sp.firstname, ' ', sp.lastname) as contacto_nom";
$sql .= " FROM ".MAIN_DB_PREFIX."pld_documento as d";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = d.fk_societe";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON sp.rowid = d.fk_socpeople";
$sql .= " WHERE d.entity IN (".getEntity('modulecompliancepld').")";
if ($filtro_empresa > 0)   { $sql .= " AND d.fk_societe = ".$filtro_empresa; }
if (!empty($filtro_tipo))  { $sql .= " AND d.tipo_documento_pld LIKE '%".$db->escape($filtro_tipo)."%'"; }
if ($filtro_verif !== '')   { $sql .= " AND d.verificado = ".((int) $filtro_verif); }

$sqlcount  = "SELECT COUNT(d.rowid) as total FROM ".MAIN_DB_PREFIX."pld_documento as d WHERE d.entity IN (".getEntity('modulecompliancepld').")";
if ($filtro_empresa > 0)   { $sqlcount .= " AND d.fk_societe = ".$filtro_empresa; }
if (!empty($filtro_tipo))  { $sqlcount .= " AND d.tipo_documento_pld LIKE '%".$db->escape($filtro_tipo)."%'"; }
if ($filtro_verif !== '')   { $sqlcount .= " AND d.verificado = ".((int) $filtro_verif); }

$rescount = $db->query($sqlcount);
$nbtotal  = 0;
if ($rescount) { $obj = $db->fetch_object($rescount); $nbtotal = $obj->total; }

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$param = '';
if ($filtro_empresa > 0)  { $param .= '&filtro_empresa='.urlencode((string) $filtro_empresa); }
if (!empty($filtro_tipo)) { $param .= '&filtro_tipo='.urlencode($filtro_tipo); }
if ($filtro_verif !== '')  { $param .= '&filtro_verif='.urlencode($filtro_verif); }

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColEmpresa'), $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColContacto'), '', '', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColTipoDocumento'), $_SERVER["PHP_SELF"], 'd.tipo_documento_pld', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColNumeroDocumento'), $_SERVER["PHP_SELF"], 'd.numero_documento', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFechaEmision'), $_SERVER["PHP_SELF"], 'd.fecha_emision', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('FechaVencimiento'), $_SERVER["PHP_SELF"], 'd.fecha_vencimiento', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColVerificado'), $_SERVER["PHP_SELF"], 'd.verificado', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('', '', '');
print '</tr>';

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i   = 0;
	if ($num == 0) { print '<tr><td colspan="8" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>'; }
	while ($i < $num && $i < $limit) {
		$obj     = $db->fetch_object($resql);
		$vencido = (!empty($obj->fecha_vencimiento) && $db->jdate($obj->fecha_vencimiento) < $now);
		$trclass = ($vencido ? 'trwarning' : 'oddeven');
		print '<tr class="'.$trclass.'">';
		print '<td>';
		if ($obj->fk_societe) { print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->fk_societe.'">'.dol_escape_htmltag($obj->empresa_nom).'</a>'; }
		print '</td>';
		print '<td>'.dol_escape_htmltag(trim($obj->contacto_nom)).'</td>';
		print '<td><a href="documento.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->tipo_documento).'</a></td>';
		print '<td>'.dol_escape_htmltag($obj->numero_documento).'</td>';
		print '<td>'.dol_print_date($db->jdate($obj->fecha_emision), 'day').'</td>';
		$fv_label = dol_print_date($db->jdate($obj->fecha_vencimiento), 'day');
		if ($vencido) { $fv_label = '<span class="badge badge-status6">'.$fv_label.' &#x26A0;</span>'; }
		print '<td>'.$fv_label.'</td>';
		print '<td class="center">'.($obj->verificado ? img_picto($langs->trans('Yes'), 'tick') : '').'</td>';
		print '<td class="right nowrap"><a href="documento.php?id='.$obj->rowid.'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a></td>';
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
