<?php
/* Copyright (C) 2026 Ouroboros
 * License GNU/GPL v3+
 */

/**
 * @file    modulecompliancepld/operaciones_list.php
 * @brief   Lista de operaciones vulnerables PLD
 */

$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once __DIR__.'/class/pldoperacion.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

// Parámetros de filtro, orden y paginación
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page      = (int) GETPOST('page', 'int');
$limit     = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25);
if ($page < 0) {
	$page = 0;
}
$offset = $limit * $page;
if (!$sortfield) {
	$sortfield = 'o.fecha_operacion';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

$filtro_empresa   = (int) GETPOST('filtro_empresa', 'int');
$filtro_tipo      = GETPOST('filtro_tipo', 'alpha');
$filtro_estado    = GETPOST('filtro_estado', 'alpha');
$filtro_supera    = GETPOST('filtro_supera', 'alpha');
$filtro_fecha_ini = GETPOST('filtro_fecha_ini', 'alpha');
$filtro_fecha_fin = GETPOST('filtro_fecha_fin', 'alpha');

/*
 * Acciones
 */

$action = GETPOST('action', 'aZ09');

if ($action == 'delete' && $user->hasRight('modulecompliancepld', 'delete')) {
	$obj = new PLDOperacion($db);
	$obj->fetch((int) GETPOST('id', 'int'));
	$result = $obj->delete($user);
	if ($result > 0) {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	} else {
		setEventMessages($obj->error, $obj->errors, 'errors');
	}
}

/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("ListaOperaciones");
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-operaciones-list');

$newcardbutton = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
	$newcardbutton = dolGetButtonTitle($langs->trans('BtnNuevaOperacion'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/operacion.php?action=create');
}

print load_fiche_titre($title, $newcardbutton, 'fa-shield');

// Barra de filtros
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="divsearchfield">';
print '<table class="noborder" style="margin-bottom:8px">';
print '<tr class="liste_titre_filter">';

// Filtro empresa
print '<td>';
$societes = array(0 => '-- '.$langs->trans('FiltroTodos').' --');
$sqlsoc = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."societe WHERE entity IN (".getEntity('societe').") ORDER BY nom";
$ressoc = $db->query($sqlsoc);
if ($ressoc) {
	while ($obj = $db->fetch_object($ressoc)) {
		$societes[$obj->rowid] = $obj->nom;
	}
}
print $form->selectarray('filtro_empresa', $societes, $filtro_empresa, 0, 0, 0, '', 0, 0, 0, '', 'minwidth150');
print '</td>';

// Filtro tipo operación
print '<td>';
$tipos = array(
	''               => '-- '.$langs->trans('FiltroTodos').' --',
	'venta_vehiculo' => $langs->trans('TipoOpVentaVehiculo'),
	'arrendamiento'  => $langs->trans('TipoOpArrendamiento'),
	'permuta'        => $langs->trans('TipoOpPermuta'),
	'consignacion'   => $langs->trans('TipoOpConsignacion'),
);
print $form->selectarray('filtro_tipo', $tipos, $filtro_tipo, 0, 0, 0, '', 0, 0, 0, '', 'minwidth150');
print '</td>';

// Filtro estado
print '<td>';
$estados = array(
	''           => '-- '.$langs->trans('FiltroTodos').' --',
	'borrador'   => $langs->trans('EstadoBorrador'),
	'pendiente'  => $langs->trans('EstadoPendiente'),
	'completado' => $langs->trans('EstadoCompletado'),
	'cancelado'  => $langs->trans('EstadoCancelado'),
);
print $form->selectarray('filtro_estado', $estados, $filtro_estado, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
print '</td>';

// Filtro supera umbral
print '<td>';
$umbral_opts = array(
	''  => '-- '.$langs->trans('FiltroTodos').' --',
	'1' => $langs->trans('Yes'),
	'0' => $langs->trans('No'),
);
print $form->selectarray('filtro_supera', $umbral_opts, $filtro_supera, 0, 0, 0, '', 0, 0, 0, '', '');
print '</td>';

print '<td>';
print '<input type="submit" class="button" value="'.$langs->trans('Search').'">';
print ' <a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a>';
print '</td>';
print '</tr>';
print '</table>';
print '</div>';
print '</form>';

// Query principal
$sql  = "SELECT o.rowid, o.folio_interno, o.fk_societe, o.tipo_operacion, o.fecha_operacion,";
$sql .= " o.monto_mxn, o.supera_umbral, o.estado, s.nom as empresa_nom";
$sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion as o";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = o.fk_societe";
$sql .= " WHERE o.entity IN (".getEntity('modulecompliancepld').")";

if ($filtro_empresa > 0) {
	$sql .= " AND o.fk_societe = ".$filtro_empresa;
}
if (!empty($filtro_tipo)) {
	$sql .= " AND o.tipo_operacion = '".$db->escape($filtro_tipo)."'";
}
if (!empty($filtro_estado)) {
	$sql .= " AND o.estado = '".$db->escape($filtro_estado)."'";
}
if ($filtro_supera !== '') {
	$sql .= " AND o.supera_umbral = ".((int) $filtro_supera);
}

$sqlcount  = "SELECT COUNT(o.rowid) as total FROM ".MAIN_DB_PREFIX."pld_operacion as o";
$sqlcount .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = o.fk_societe";
$sqlcount .= " WHERE o.entity IN (".getEntity('modulecompliancepld').")";
if ($filtro_empresa > 0) {
	$sqlcount .= " AND o.fk_societe = ".$filtro_empresa;
}
if (!empty($filtro_tipo)) {
	$sqlcount .= " AND o.tipo_operacion = '".$db->escape($filtro_tipo)."'";
}
if (!empty($filtro_estado)) {
	$sqlcount .= " AND o.estado = '".$db->escape($filtro_estado)."'";
}
if ($filtro_supera !== '') {
	$sqlcount .= " AND o.supera_umbral = ".((int) $filtro_supera);
}

$rescount = $db->query($sqlcount);
$nbtotal  = 0;
if ($rescount) {
	$obj     = $db->fetch_object($rescount);
	$nbtotal = $obj->total;
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);

$param = '';
if ($filtro_empresa > 0) {
	$param .= '&filtro_empresa='.urlencode((string) $filtro_empresa);
}
if (!empty($filtro_tipo)) {
	$param .= '&filtro_tipo='.urlencode($filtro_tipo);
}
if (!empty($filtro_estado)) {
	$param .= '&filtro_estado='.urlencode($filtro_estado);
}
if ($filtro_supera !== '') {
	$param .= '&filtro_supera='.urlencode($filtro_supera);
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColFolioInterno'), $_SERVER["PHP_SELF"], 'o.folio_interno', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEmpresa'), $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColTipoOperacion'), $_SERVER["PHP_SELF"], 'o.tipo_operacion', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFecha'), $_SERVER["PHP_SELF"], 'o.fecha_operacion', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColMonto'), $_SERVER["PHP_SELF"], 'o.monto_mxn', '', $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColSuperaUmbral'), $_SERVER["PHP_SELF"], 'o.supera_umbral', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEstado'), $_SERVER["PHP_SELF"], 'o.estado', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('', '', '');
print '</tr>';

if ($resql) {
	$num = $db->num_rows($resql);
	$i   = 0;
	if ($num == 0) {
		print '<tr><td colspan="8" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>';
	}
	while ($i < $num && $i < $limit) {
		$obj = $db->fetch_object($resql);

		$trclass = ($obj->supera_umbral ? 'trwarning' : 'oddeven');
		print '<tr class="'.$trclass.'">';

		// Folio (link)
		print '<td>';
		print '<a href="operacion.php?id='.((int) $obj->rowid).'">'.dol_escape_htmltag($obj->folio_interno ?: '#'.$obj->rowid).'</a>';
		print '</td>';

		// Empresa
		print '<td>';
		if ($obj->fk_societe) {
			print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.((int) $obj->fk_societe).'">'.dol_escape_htmltag($obj->empresa_nom).'</a>';
		}
		print '</td>';

		// Tipo operación
		$tipo_label = array(
			'venta_vehiculo' => $langs->trans('TipoOpVentaVehiculo'),
			'arrendamiento'  => $langs->trans('TipoOpArrendamiento'),
			'permuta'        => $langs->trans('TipoOpPermuta'),
			'consignacion'   => $langs->trans('TipoOpConsignacion'),
		);
		print '<td>'.dol_escape_htmltag($tipo_label[$obj->tipo_operacion] ?? $obj->tipo_operacion).'</td>';

		// Fecha
		print '<td>'.dol_print_date($db->jdate($obj->fecha_operacion), 'day').'</td>';

		// Monto
		print '<td class="right nowrap">'.price($obj->monto_mxn).' MXN</td>';

		// Supera umbral
		$umbral_html = $obj->supera_umbral
			? '<span class="badge badge-status6">'.$langs->trans('Yes').'</span>'
			: '<span class="badge badge-status0">'.$langs->trans('No').'</span>';
		print '<td class="center">'.$umbral_html.'</td>';

		// Estado
		$estado_colors = array(
			'borrador'   => 'badge-status0',
			'pendiente'  => 'badge-status1',
			'completado' => 'badge-status4',
			'cancelado'  => 'badge-status9',
		);
		$estado_color  = $estado_colors[$obj->estado] ?? 'badge-status0';
		$estado_labels = array(
			'borrador'   => $langs->trans('EstadoBorrador'),
			'pendiente'  => $langs->trans('EstadoPendiente'),
			'completado' => $langs->trans('EstadoCompletado'),
			'cancelado'  => $langs->trans('EstadoCancelado'),
		);
		print '<td class="center"><span class="badge '.$estado_color.'">'.dol_escape_htmltag($estado_labels[$obj->estado] ?? $obj->estado).'</span></td>';

		// Acciones
		print '<td class="right nowrap">';
		print '<a href="operacion.php?id='.$obj->rowid.'" title="'.$langs->trans('BtnVerDetalle').'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a>';
		if ($user->hasRight('modulecompliancepld', 'write')) {
			print ' <a href="operacion.php?id='.$obj->rowid.'&action=edit&token='.newToken().'" title="'.$langs->trans('BtnEditar').'">'.img_picto($langs->trans('BtnEditar'), 'edit').'</a>';
		}
		if ($user->hasRight('modulecompliancepld', 'delete')) {
			print ' <a href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$obj->rowid.'&token='.newToken().'" title="'.$langs->trans('BtnEliminar').'" onclick="return confirm(\''.$langs->trans('ConfirmDelete').'\')">'.img_picto($langs->trans('BtnEliminar'), 'delete').'</a>';
		}
		print '</td>';

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
