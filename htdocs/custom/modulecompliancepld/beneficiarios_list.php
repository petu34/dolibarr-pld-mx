<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/beneficiarios_list.php
 * @brief   Controlador — lista de beneficiarios controladores PLD
 *
 * Mejora 4: SQL movido a PLDReporteService::getListaBeneficiarios() / countBeneficiarios().
 * Mejora 8: HTML renderizado en tpl/beneficiarios_list.tpl.php.
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
require_once __DIR__.'/class/services/PLDReporteService.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

// ── Parámetros ────────────────────────────────────────────────────────────────
$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'b.apellido_paterno';
$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'ASC';
$page      = max(0, (int) GETPOST('page', 'int'));
$limit     = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25);
$offset    = $limit * $page;

$filtro_empresa = (int) GETPOST('filtro_empresa', 'int');
$filtro_pep     = GETPOST('filtro_pep', 'alpha');

// ── Datos ─────────────────────────────────────────────────────────────────────
$svc      = new PLDReporteService($db);
$filtros  = ['empresa' => $filtro_empresa, 'pep' => $filtro_pep];
$nbtotal  = $svc->countBeneficiarios($filtros);
$rows     = $svc->getListaBeneficiarios($filtros, $limit, $offset, $sortfield, $sortorder);
$societes = array(0 => '-- '.$langs->trans('FiltroTodos').' --') + $svc->getSocietesParaFiltro();

// ── Variables de vista ────────────────────────────────────────────────────────
$form  = new Form($db);
$title = $langs->trans("ListaBeneficiarios");

$param = '';
if ($filtro_empresa > 0) { $param .= '&filtro_empresa='.urlencode((string) $filtro_empresa); }
if ($filtro_pep !== '')  { $param .= '&filtro_pep='.urlencode($filtro_pep); }

include __DIR__.'/tpl/beneficiarios_list.tpl.php';

$db->close();
