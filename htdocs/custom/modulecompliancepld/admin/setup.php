<?php
/* Copyright (C) 2026 Ouroboros
 * License GNU/GPL v3+
 */

/**
 * @file    modulecompliancepld/admin/setup.php
 * @brief   Configuración del módulo Compliance PLD México
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
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php"; }
if (!$res) { die("Include of main fails"); }

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once '../lib/modulecompliancepld.lib.php';

$langs->loadLangs(array("admin", "modulecompliancepld@modulecompliancepld"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$error  = 0;

/*
 * Acciones
 */
if ($action == 'update') {
	$params = array(
		'MODULECOMPLIANCEPLD_UMA_VALOR'            => array('type' => 'float',  'default' => 117.31),
		'MODULECOMPLIANCEPLD_UMA_ANIO'             => array('type' => 'int',    'default' => 2026),
		'MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO'     => array('type' => 'float',  'default' => 377778.20),
		'MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO'     => array('type' => 'float',  'default' => 117310.00),
		'MODULECOMPLIANCEPLD_DIAS_ALERTA_ID'       => array('type' => 'int',    'default' => 30),
		'MODULECOMPLIANCEPLD_OFICIAL_CUMPLIMIENTO' => array('type' => 'int',    'default' => 0),
		'MODULECOMPLIANCEPLD_PERIODO_CONSERVACION' => array('type' => 'int',    'default' => 5),
		'MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE' => array('type' => 'chaine', 'default' => 'VIII'),
		// Sujeto obligado y e.firma
		'MODULECOMPLIANCEPLD_RFC_SUJETO'           => array('type' => 'chaine', 'default' => ''),
		'MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH'     => array('type' => 'chaine', 'default' => ''),
		'MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH'      => array('type' => 'chaine', 'default' => ''),
		'MODULECOMPLIANCEPLD_EFIRMA_PASSWORD'      => array('type' => 'chaine', 'default' => ''),
	);

	foreach ($params as $constname => $info) {
		$val = GETPOST($constname, 'alpha');
		if ($info['type'] == 'float') {
			$val = (float) str_replace(',', '.', $val);
		} elseif ($info['type'] == 'int') {
			$val = (int) $val;
		}
		$resset = dolibarr_set_const($db, $constname, $val, $info['type'], 0, '', $conf->entity);
		if ($resset <= 0) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorSavingSetup"), null, 'errors');
	}
}

/*
 * View
 */
$form = new Form($db);

$title = $langs->trans("AdminSetupPLD");
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-admin');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = modulecompliancepldAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $title, -1, 'fa-shield@modulecompliancepld');

echo '<p class="opacitymedium">'.$langs->trans("AdminSetupDesc").'</p>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// ======= SECCIÓN UMA y UMBRALES =======
print load_fiche_titre($langs->trans("SeccionUMA"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

$uma_valor = getDolGlobalString('MODULECOMPLIANCEPLD_UMA_VALOR', '117.31');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_UMA_VALOR">'.$langs->trans("UMAValor").'</label></td>';
print '<td><input type="number" step="0.01" id="MODULECOMPLIANCEPLD_UMA_VALOR" name="MODULECOMPLIANCEPLD_UMA_VALOR" class="flat minwidth150" value="'.dol_escape_htmltag($uma_valor).'"></td>';
print '<td class="opacitymedium">Valor de la Unidad de Medida y Actualizacion publicado por INEGI. Vigente 2026: $117.31</td>';
print '</tr>';

$uma_anio = getDolGlobalInt('MODULECOMPLIANCEPLD_UMA_ANIO', 2026);
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_UMA_ANIO">'.$langs->trans("UMAAnio").'</label></td>';
print '<td><input type="number" id="MODULECOMPLIANCEPLD_UMA_ANIO" name="MODULECOMPLIANCEPLD_UMA_ANIO" class="flat minwidth100" value="'.$uma_anio.'"></td>';
print '<td class="opacitymedium">Año fiscal del valor UMA configurado</td>';
print '</tr>';

$umbral_nuevo = getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO', '377778.20');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO">'.$langs->trans("UmbralVehNuevo").'</label></td>';
print '<td><input type="number" step="0.01" id="MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO" name="MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO" class="flat minwidth150" value="'.dol_escape_htmltag($umbral_nuevo).'"></td>';
print '<td class="opacitymedium">3,220 UMAs x $117.31 = $377,778.20 MXN (Art. 17 LFPIORPI)</td>';
print '</tr>';

$umbral_usado = getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO', '117310.00');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO">'.$langs->trans("UmbralVehUsado").'</label></td>';
print '<td><input type="number" step="0.01" id="MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO" name="MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO" class="flat minwidth150" value="'.dol_escape_htmltag($umbral_usado).'"></td>';
print '<td class="opacitymedium">1,000 UMAs x $117.31 = $117,310.00 MXN</td>';
print '</tr>';

print '</table><br>';

// ======= SECCIÓN ALERTAS =======
print load_fiche_titre($langs->trans("SeccionAlertas"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

$dias_alerta = getDolGlobalInt('MODULECOMPLIANCEPLD_DIAS_ALERTA_ID', 30);
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_DIAS_ALERTA_ID">'.$langs->trans("DiasAlertaID").'</label></td>';
print '<td><input type="number" id="MODULECOMPLIANCEPLD_DIAS_ALERTA_ID" name="MODULECOMPLIANCEPLD_DIAS_ALERTA_ID" class="flat minwidth100" value="'.$dias_alerta.'"></td>';
print '<td class="opacitymedium">Dias antes del vencimiento de identificacion para generar alerta automatica</td>';
print '</tr>';

print '</table><br>';

// ======= SECCIÓN GENERAL =======
print load_fiche_titre($langs->trans("SeccionGeneral"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

$oficial_id = getDolGlobalInt('MODULECOMPLIANCEPLD_OFICIAL_CUMPLIMIENTO', 0);
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_OFICIAL_CUMPLIMIENTO">'.$langs->trans("OficialCumplimiento").'</label></td>';
print '<td>';
$form->select_dolusers($oficial_id, 'MODULECOMPLIANCEPLD_OFICIAL_CUMPLIMIENTO', 1, array(), 0, '', 0, 0, 0, 0, '', 0, '', 'minwidth200');
print '</td>';
print '<td class="opacitymedium">Usuario designado como Oficial de Cumplimiento PLD (recibe notificaciones)</td>';
print '</tr>';

$periodo = getDolGlobalInt('MODULECOMPLIANCEPLD_PERIODO_CONSERVACION', 5);
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_PERIODO_CONSERVACION">'.$langs->trans("PeriodoConservacion").'</label></td>';
print '<td><input type="number" id="MODULECOMPLIANCEPLD_PERIODO_CONSERVACION" name="MODULECOMPLIANCEPLD_PERIODO_CONSERVACION" class="flat minwidth100" value="'.$periodo.'"></td>';
print '<td class="opacitymedium">Años de conservacion de expedientes PLD (Art. 18 LFPIORPI). Minimo legal: 5 años</td>';
print '</tr>';

$actividad = getDolGlobalString('MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE', 'VIII');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE">'.$langs->trans("ActividadVulnerable").'</label></td>';
print '<td><input type="text" id="MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE" name="MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE" class="flat minwidth100" value="'.dol_escape_htmltag($actividad).'"></td>';
print '<td class="opacitymedium">Fraccion del Art. 17 LFPIORPI aplicable. Para vehiculos: VIII</td>';
print '</tr>';

print '</table><br>';

print '<div class="tabsAction">';
print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
