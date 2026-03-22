<?php
/* Copyright (C) 2026 Ouroboros
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * @file        pld_thirdparty.php
 * @module      CompliancePLD
 * @description Tab PLD en ficha de tercero
 * @author      Ouroboros
 * @compliance  LFPIORPI Art. 17 — Actividad Vulnerable
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Parameters
$id     = GETPOSTINT('id');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Load translations
$langs->loadLangs(array('companies', 'modulecompliancepld@modulecompliancepld'));

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'societe', $id);

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$permwrite = ($user->hasRight('modulecompliancepld', 'write') && $user->hasRight('societe', 'creer'));

// Load objects
$object      = new Societe($db);
$extrafields = new ExtraFields($db);

// Load extrafield definitions
$extrafields->fetch_name_optionals_label($object->table_element);

// Load thirdparty
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$object->fetch_optionals();
	}
}

$hookmanager->initHooks(array('companypldcard', 'globalcard'));


/*
 * Actions
 */

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action === 'update' && $permwrite) {
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);

		$result = $object->insertExtraFields();
		if ($result >= 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'edit';
		}
	}
}


/*
 * View
 */

$title   = $object->name.' - '.$langs->trans('PLDTabThirdparty');
$helpurl = '';
llxHeader('', $title, $helpurl);

$form = new Form($db);

if ($object->id > 0) {
	$head = societe_prepare_head($object);
	print dol_get_fiche_head($head, 'plddata', $langs->trans('ThirdParty'), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.
		$langs->trans('BackToList').'</a>';

	dol_banner_tab($object, 'name', $linkback, 1, 'nom', 'name', '', '', 0, '', '', 1);

	print dol_get_fiche_end();

	// --- Edit / View form ---
	$editing = ($action === 'edit' && $permwrite);

	if ($editing) {
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
	}

	// Helper: render a single extrafield row (view or edit)
	$renderField = function ($key) use ($object, $extrafields, $editing, $langs) {
		if (!isset($extrafields->attributes[$object->table_element]['label'][$key])) {
			return;
		}
		$label = $extrafields->attributes[$object->table_element]['label'][$key];
		$val   = isset($object->array_options['options_'.$key]) ? $object->array_options['options_'.$key] : '';

		print '<tr class="field_'.$key.'">';
		print '<td class="titlefield fieldname_'.$key.'">'.$label.'</td>';
		print '<td class="valuefield fieldname_'.$key.'">';
		if ($editing) {
			print $extrafields->showInputField($key, $val, '', '', '', $object->table_element, 0);
		} else {
			print $extrafields->showOutputField($key, $val, '', $object->table_element);
		}
		print '</td>';
		print '</tr>';
	};

	// =========================================================
	// SECCIÓN 1: Datos de Identificación PLD
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDIdentificacion'), '', 'fa-id-card');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_tipo_persona',
		'pld_curp',
		'pld_rfc_validado',
		'pld_fecha_nacimiento',
		'pld_fecha_constitucion',
		'pld_nacionalidad',
		'pld_pais_nacimiento',
		'pld_estado_nacimiento',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 2: Domicilio PLD
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDDomicilio'), '', 'fa-map-marker');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_calle',
		'pld_numero_exterior',
		'pld_numero_interior',
		'pld_colonia',
		'pld_codigo_postal',
		'pld_municipio',
		'pld_estado',
		'pld_pais',
		'pld_es_domicilio_extranjero',
		'pld_estado_provincia_ext',
		'pld_ciudad_poblacion_ext',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 3: Actividad Económica
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDActividadEconomica'), '', 'fa-briefcase');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_actividad_economica',
		'pld_giro_mercantil',
		'pld_ocupacion',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 4: Persona Moral
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDPersonaMoral'), '', 'fa-building');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_denominacion_razon',
		'pld_numero_escritura',
		'pld_fecha_escritura',
		'pld_notario_numero',
		'pld_notario_nombre',
		'pld_notario_estado',
		'pld_folio_mercantil',
		'pld_identificador_fideicomiso',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 5: Cumplimiento PLD
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDCumplimiento'), '', 'fa-shield');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_cliente_identificado',
		'pld_fecha_identificacion',
		'pld_expediente_completo',
		'pld_es_pep',
		'pld_relacion_pep',
		'pld_tiene_beneficiario',
		'pld_observaciones',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// Action buttons
	if ($editing) {
		print '<div class="center">';
		print '<input type="submit" class="button button-save" value="'.$langs->trans('Save').'">';
		print ' &nbsp; ';
		print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" class="button button-cancel">';
		print $langs->trans('Cancel');
		print '</a>';
		print '</div>';
		print '</form>';
	} elseif ($permwrite) {
		print '<div class="tabsAction">';
		print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit&token='.newToken().'" class="butAction">';
		print $langs->trans('Modify');
		print '</a>';
		print '</div>';
	}
} else {
	print '<div class="error">'.$langs->trans('ErrorRecordNotFound').'</div>';
}

// End of page
llxFooter();
$db->close();
