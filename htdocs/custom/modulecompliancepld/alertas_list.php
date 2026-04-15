<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/alertas_list.php
 * @brief   Controlador — lista de alertas PLD
 *
 * Mejora 4: SQL movido a PLDReporteService::getListaAlertas() / countAlertas().
 * Mejora 8: HTML renderizado en tpl/alertas_list.tpl.php.
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
require_once __DIR__.'/class/services/PLDReporteService.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

// ── Parámetros ────────────────────────────────────────────────────────────────
$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'al.datec';
$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'DESC';
$page      = max(0, (int) GETPOST('page', 'int'));
$limit     = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25);
$offset    = $limit * $page;

$filtro_nivel  = GETPOST('filtro_nivel', 'alpha');
$filtro_estado = GETPOST('filtro_estado', 'alpha');
$filtro_tipo   = GETPOST('filtro_tipo', 'alpha');

// ── Datos ─────────────────────────────────────────────────────────────────────
$svc     = new PLDReporteService($db);
$filtros = ['nivel' => $filtro_nivel, 'estado' => $filtro_estado, 'tipo' => $filtro_tipo];
$nbtotal = $svc->countAlertas($filtros);
$rows    = $svc->getListaAlertas($filtros, $limit, $offset, $sortfield, $sortorder);

// ── Variables de vista ────────────────────────────────────────────────────────
$form  = new Form($db);
$title = $langs->trans("ListaAlertas");

$param = '';
if (!empty($filtro_nivel))  { $param .= '&filtro_nivel='.urlencode($filtro_nivel); }
if (!empty($filtro_estado)) { $param .= '&filtro_estado='.urlencode($filtro_estado); }
if (!empty($filtro_tipo))   { $param .= '&filtro_tipo='.urlencode($filtro_tipo); }

include __DIR__.'/tpl/alertas_list.tpl.php';

$db->close();
