<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2026 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    modulecompliancepld/admin/about.php
 * \ingroup modulecompliancepld
 * \brief   About page of module Modulecompliancepld.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once '../lib/modulecompliancepld.lib.php';

// Translations
$langs->loadLangs(array("errors", "admin", "modulecompliancepld@modulecompliancepld"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "ModulecompliancepldSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', '', '', 'mod-modulecompliancepld page-admin_about');

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = modulecompliancepldAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans($title), 0, 'modulecompliancepld@modulecompliancepld');

dol_include_once('/modulecompliancepld/core/modules/modModulecompliancepld.class.php');
$tmpmodule = new modModulecompliancepld($db);
print $tmpmodule->getDescLong();

// -----------------------------------------------------------------------
// Sección: e.firma (FIEL SAT)
// -----------------------------------------------------------------------
$cert_path = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH');
$key_path  = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH');
$rfc_sujeto = getDolGlobalString('MAIN_INFO_SIREN');

$cert_ok = !empty($cert_path) && file_exists($cert_path);
$key_ok  = !empty($key_path)  && file_exists($key_path);

print '<br>';
print load_fiche_titre('e.firma (FIEL SAT) — Estado de configuración', '', 'fa-shield-alt');
print '<div class="fichecenter">';
print '<table class="border centpercent tableforfield">';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('PLDRFCSujeto').'</td>';
print '<td>'.($rfc_sujeto ? '<strong>'.dol_escape_htmltag($rfc_sujeto).'</strong>' : '<span class="error">No configurado</span>').'</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('PLDEFirmaCert').'</td><td>';
if ($cert_ok) {
    print '<span class="badge badge-status4 badge-status">'.img_picto('', 'check', 'class="pictofixedwidth"').' Archivo encontrado</span>';
    print ' <small class="opacitymedium">'.dol_escape_htmltag($cert_path).'</small>';
} elseif (!empty($cert_path)) {
    print '<span class="badge badge-status8 badge-status">'.img_picto('', 'warning', 'class="pictofixedwidth"').' Archivo no encontrado</span>';
    print ' <small class="opacitymedium">'.dol_escape_htmltag($cert_path).'</small>';
} else {
    print '<span class="badge badge-status8 badge-status">No configurado</span>';
}
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('PLDEFirmaKey').'</td><td>';
if ($key_ok) {
    print '<span class="badge badge-status4 badge-status">'.img_picto('', 'check', 'class="pictofixedwidth"').' Archivo encontrado</span>';
    print ' <small class="opacitymedium">'.dol_escape_htmltag($key_path).'</small>';
} elseif (!empty($key_path)) {
    print '<span class="badge badge-status8 badge-status">'.img_picto('', 'warning', 'class="pictofixedwidth"').' Archivo no encontrado</span>';
    print ' <small class="opacitymedium">'.dol_escape_htmltag($key_path).'</small>';
} else {
    print '<span class="badge badge-status8 badge-status">No configurado</span>';
}
print '</td></tr>';

print '</table>';
print '</div>';

print '<div class="info">';
print '<strong>Instalación de la e.firma:</strong> Los archivos <code>.cer</code> y <code>.key</code> ';
print 'deben colocarse manualmente en el servidor, <strong>fuera del docroot</strong>, ';
print 'y sus rutas configuradas en <a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/admin/setup.php">Ajustes del módulo</a>. ';
print 'Ver el <code>README.md</code> del módulo para instrucciones completas.';
print '</div>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();
