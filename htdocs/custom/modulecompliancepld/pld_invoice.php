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
 * @file        pld_invoice.php
 * @module      CompliancePLD
 * @description Tab PLD en ficha de factura — muestra y edita los 32 extrafields PLD
 * @author      Ouroboros
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — Operación Vulnerable
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Parameters
$id     = GETPOSTINT('id');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Load translations
$langs->loadLangs(array('bills', 'modulecompliancepld@modulecompliancepld'));

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'facture', $id);

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$permwrite = ($user->hasRight('modulecompliancepld', 'write') && $user->hasRight('facture', 'creer'));

// Load objects
$object     = new Facture($db);
$extrafields = new ExtraFields($db);

// Load extrafield definitions (all for this element)
$extrafields->fetch_name_optionals_label($object->table_element);

// Load invoice
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$object->fetch_optionals();
	}
}

$hookmanager->initHooks(array('invoicepldcard', 'globalcard'));


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
		// Load current values, then override with POST data
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

$title   = $object->ref.' - '.$langs->trans('PLDTabInvoice');
$helpurl = '';
llxHeader('', $title, $helpurl);

$form = new Form($db);

if ($object->id > 0) {
	$object->fetch_thirdparty();

	$head = facture_prepare_head($object);
	print dol_get_fiche_head($head, 'plddata', $langs->trans('InvoiceCustomer'), -1, 'bill');

	// Banner with invoice header
	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.
		(!empty($socid) ? '&socid='.$socid : '').'">'.
		$langs->trans('BackToList').'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= $object->thirdparty->getNomUrl(1, 'customer');
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

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
	// SECCIÓN 1: Control PLD
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDControlOperacion'), '', 'fa-shield');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_es_actividad_vulnerable',
		'pld_clave_actividad',
		'pld_tipo_operacion',
		'pld_supera_umbral_id',
		'pld_supera_umbral_aviso',
		'pld_requiere_aviso',
		'pld_tipo_aviso',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 2: Datos de la Operación
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDDatosOperacion'), '', 'fa-file-invoice');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_fecha_operacion',
		'pld_codigo_postal_operacion',
		'pld_monto_moneda_nacional',
		'pld_tipo_cambio_aplicado',
		'pld_descripcion_operacion',
		'pld_razon_operacion',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 3: Referencia del Aviso SAT
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDAvisoSAT'), '', 'fa-paper-plane');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_referencia_aviso',
		'pld_prioridad',
		'pld_aviso_presentado',
		'pld_fecha_presentacion',
		'pld_folio_aviso',
		'pld_mes_reportado',
		'pld_acuse_sat',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 4: Aviso Modificatorio
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDAvisoModificatorio'), '', 'fa-edit');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_es_modificatorio',
		'pld_folio_modificacion',
		'pld_descripcion_modificacion',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 5: Operación Acumulada
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDOperacionAcumulada'), '', 'fa-layer-group');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_es_operacion_acumulada',
		'pld_fecha_inicio_acumulacion',
		'pld_fecha_fin_acumulacion',
		'pld_monto_acumulado_total',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 6: Alertas y Aviso 24 Horas
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDAlertasInternas'), '', 'fa-bell');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_tipo_alerta',
		'pld_descripcion_alerta',
		'pld_genera_alerta',
		'pld_requiere_aviso_24hrs',
		'pld_razon_24hrs',
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
