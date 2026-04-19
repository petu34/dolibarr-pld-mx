<?php
/* Copyright (C) 2026 Ouroboros
 * License GNU/GPL v3+
 */

/**
 * @file    modulecompliancepld/operaciones_list.php
 * @brief   Controlador — lista de operaciones vulnerables PLD
 *
 * Mejora 4: SQL movido a PLDReporteService::getListaOperaciones() / countOperaciones().
 * Mejora 8: HTML renderizado en tpl/operaciones_list.tpl.php.
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
require_once __DIR__.'/class/services/PLDReporteService.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

// ── Parámetros ────────────────────────────────────────────────────────────────
$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'o.fecha_operacion';
$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'DESC';
$page      = max(0, (int) GETPOST('page', 'int'));
$limit     = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25);
$offset    = $limit * $page;

$filtro_empresa = (int) GETPOST('filtro_empresa', 'int');
$filtro_tipo    = GETPOST('filtro_tipo', 'alpha');
$filtro_estado  = GETPOST('filtro_estado', 'alpha');
$filtro_supera  = GETPOST('filtro_supera', 'alpha');

// ── Acciones ──────────────────────────────────────────────────────────────────
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

// ── Datos ─────────────────────────────────────────────────────────────────────
$svc     = new PLDReporteService($db);
$filtros = [
	'empresa' => $filtro_empresa,
	'tipo'    => $filtro_tipo,
	'estado'  => $filtro_estado,
	'supera'  => $filtro_supera,
];
$nbtotal  = $svc->countOperaciones($filtros);
$rows     = $svc->getListaOperaciones($filtros, $limit, $offset, $sortfield, $sortorder);
$societes = array(0 => '-- '.$langs->trans('FiltroTodos').' --') + $svc->getSocietesParaFiltro();

// ── Variables de vista ────────────────────────────────────────────────────────
$form  = new Form($db);
$title = $langs->trans("ListaOperaciones");

$param = '';
if ($filtro_empresa > 0)      { $param .= '&filtro_empresa='.urlencode((string) $filtro_empresa); }
if (!empty($filtro_tipo))     { $param .= '&filtro_tipo='.urlencode($filtro_tipo); }
if (!empty($filtro_estado))   { $param .= '&filtro_estado='.urlencode($filtro_estado); }
if ($filtro_supera !== '')    { $param .= '&filtro_supera='.urlencode($filtro_supera); }

include __DIR__.'/tpl/operaciones_list.tpl.php';

$db->close();
