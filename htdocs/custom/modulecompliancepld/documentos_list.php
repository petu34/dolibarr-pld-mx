<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/documentos_list.php
 * @brief   Controlador — lista de documentos PLD
 *
 * Mejora 4: SQL movido a PLDReporteService::getListaDocumentos() / countDocumentos().
 * Mejora 8: HTML renderizado en tpl/documentos_list.tpl.php.
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
require_once __DIR__.'/class/services/PLDReporteService.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

// ── Parámetros ────────────────────────────────────────────────────────────────
$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 'd.datec';
$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'DESC';
$page      = max(0, (int) GETPOST('page', 'int'));
$limit     = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 25);
$offset    = $limit * $page;

$filtro_empresa = (int) GETPOST('filtro_empresa', 'int');
$filtro_tipo    = GETPOST('filtro_tipo', 'alpha');
$filtro_verif   = GETPOST('filtro_verif', 'alpha');

// ── Datos ─────────────────────────────────────────────────────────────────────
$svc     = new PLDReporteService($db);
$filtros = ['empresa' => $filtro_empresa, 'tipo' => $filtro_tipo, 'verif' => $filtro_verif];
$nbtotal  = $svc->countDocumentos($filtros);
$rows     = $svc->getListaDocumentos($filtros, $limit, $offset, $sortfield, $sortorder);
$societes = array(0 => '-- '.$langs->trans('FiltroTodos').' --') + $svc->getSocietesParaFiltro();

// ── Variables de vista ────────────────────────────────────────────────────────
$form  = new Form($db);
$title = $langs->trans("ListaDocumentos");
$now   = dol_now();

$param = '';
if ($filtro_empresa > 0)  { $param .= '&filtro_empresa='.urlencode((string) $filtro_empresa); }
if (!empty($filtro_tipo)) { $param .= '&filtro_tipo='.urlencode($filtro_tipo); }
if ($filtro_verif !== '')  { $param .= '&filtro_verif='.urlencode($filtro_verif); }

include __DIR__.'/tpl/documentos_list.tpl.php';

$db->close();
