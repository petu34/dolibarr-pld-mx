<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/beneficiarios_list.php
 * @brief   Lista de beneficiarios controladores PLD
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
require_once __DIR__.'/class/pldbeneficiario.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'b.apellido_paterno';
$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'ASC';
$page      = max(0, (int) GETPOST('page', 'int'));
$limit     = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25);
$offset    = $limit * $page;

$filtro_empresa = (int) GETPOST('filtro_empresa', 'int');
$filtro_pep     = GETPOST('filtro_pep', 'alpha');

$form  = new Form($db);
$title = $langs->trans("ListaBeneficiarios");
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-beneficiarios-list');

$newbtn = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
	$newbtn = dolGetButtonTitle($langs->trans('BtnNuevoBeneficiario'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/beneficiario.php?action=create');
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
$pep_opts = array('' => '-- '.$langs->trans('FiltroTodos').' --', '1' => $langs->trans('Yes'), '0' => $langs->trans('No'));
print '<td>'.$form->selectarray('filtro_pep', $pep_opts, $filtro_pep).'</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans('Search').'"> ';
print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a></td>';
print '</tr></table></div></form>';

$sql  = "SELECT b.rowid, b.fk_societe, CONCAT(b.nombre, ' ', b.apellido_paterno, CASE WHEN b.apellido_materno IS NOT NULL THEN CONCAT(' ', b.apellido_materno) ELSE '' END) as nombre_completo, b.curp, b.rfc, b.tipo_beneficiario, b.porcentaje_participacion, b.es_pep, b.activo, s.nom as empresa_nom";
$sql .= " FROM ".MAIN_DB_PREFIX."pld_beneficiario as b";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = b.fk_societe";
$sql .= " WHERE b.entity IN (".getEntity('modulecompliancepld').")";
if ($filtro_empresa > 0) { $sql .= " AND b.fk_societe = ".$filtro_empresa; }
if ($filtro_pep !== '')  { $sql .= " AND b.es_pep = ".((int) $filtro_pep); }

$sqlcount  = "SELECT COUNT(b.rowid) as total FROM ".MAIN_DB_PREFIX."pld_beneficiario as b WHERE b.entity IN (".getEntity('modulecompliancepld').")";
if ($filtro_empresa > 0) { $sqlcount .= " AND b.fk_societe = ".$filtro_empresa; }
if ($filtro_pep !== '')  { $sqlcount .= " AND b.es_pep = ".((int) $filtro_pep); }

$rescount = $db->query($sqlcount);
$nbtotal  = 0;
if ($rescount) { $obj = $db->fetch_object($rescount); $nbtotal = $obj->total; }

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$param = '';
if ($filtro_empresa > 0) { $param .= '&filtro_empresa='.urlencode((string) $filtro_empresa); }
if ($filtro_pep !== '')  { $param .= '&filtro_pep='.urlencode($filtro_pep); }

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColEmpresa'), $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColNombreCompleto'), $_SERVER["PHP_SELF"], 'b.apellido_paterno', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColCURP'), $_SERVER["PHP_SELF"], 'b.curp', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColRFC'), $_SERVER["PHP_SELF"], 'b.rfc', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColTipoBeneficiario'), $_SERVER["PHP_SELF"], 'b.tipo_beneficiario', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColPorcentaje'), $_SERVER["PHP_SELF"], 'b.porcentaje_participacion', '', $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEsPEP'), $_SERVER["PHP_SELF"], 'b.es_pep', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('', '', '');
print '</tr>';

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i   = 0;
	if ($num == 0) { print '<tr><td colspan="8" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>'; }
	while ($i < $num && $i < $limit) {
		$obj     = $db->fetch_object($resql);
		$trclass = ($obj->es_pep ? 'trwarning' : 'oddeven');
		print '<tr class="'.$trclass.'">';
		print '<td>';
		if ($obj->fk_societe) { print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->fk_societe.'">'.dol_escape_htmltag($obj->empresa_nom).'</a>'; }
		print '</td>';
		print '<td><a href="beneficiario.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->nombre_completo).'</a></td>';
		print '<td>'.dol_escape_htmltag($obj->curp).'</td>';
		print '<td>'.dol_escape_htmltag($obj->rfc).'</td>';
		print '<td>'.dol_escape_htmltag($obj->tipo_beneficiario).'</td>';
		print '<td class="right">'.number_format((float) $obj->porcentaje_participacion, 2).' %</td>';
		print '<td class="center">'.($obj->es_pep ? '<span class="badge badge-status6">PEP</span>' : '').'</td>';
		print '<td class="right nowrap"><a href="beneficiario.php?id='.$obj->rowid.'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a></td>';
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
