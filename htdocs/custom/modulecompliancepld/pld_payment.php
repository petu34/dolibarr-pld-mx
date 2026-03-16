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
 * @file        pld_payment.php
 * @module      CompliancePLD
 * @description Tab PLD en ficha de pago
 * @author      Ouroboros
 * @compliance  LFPIORPI Art. 17 — Actividad Vulnerable
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payment.lib.php';
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
$result = restrictedArea($user, 'paiement', $id);

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$permwrite = ($user->hasRight('modulecompliancepld', 'write') && $user->hasRight('facture', 'creer'));

// Load objects
$object      = new Paiement($db);
$extrafields = new ExtraFields($db);

// Load extrafield definitions
$extrafields->fetch_name_optionals_label($object->table_element);

// Load payment
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$object->fetch_optionals();
	}
}

$hookmanager->initHooks(array('paymentpldcard', 'globalcard'));


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

$title   = '#'.$object->id.' - '.$langs->trans('PLDTabPayment');
$helpurl = '';
llxHeader('', $title, $helpurl);

$form = new Form($db);

if ($object->id > 0) {
	$head = payment_prepare_head($object);
	print dol_get_fiche_head($head, 'plddata', $langs->trans('Payment'), -1, 'payment');

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?restore_lastsearch_values=1">'.
		$langs->trans('BackToList').'</a>';

	dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'rowid', '', '', 0, '', '', 1);

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
	// SECCIÓN 1: Datos del Pago
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDDatosPago'), '', 'fa-money-bill');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_fecha_pago',
		'pld_forma_pago',
		'pld_instrumento_monetario',
		'pld_moneda',
		'pld_monto_operacion',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 2: Montos por Instrumento
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDMontosPorInstrumento'), '', 'fa-coins');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_monto_efectivo',
		'pld_monto_transferencia',
		'pld_monto_cheque',
		'pld_monto_tarjeta',
		'pld_monto_otros',
	) as $key) {
		$renderField($key);
	}
	print '</table>';
	print '</div>';

	// =========================================================
	// SECCIÓN 3: Datos Bancarios
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDDatosBancarios'), '', 'fa-university');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_banco_origen',
		'pld_cuenta_origen',
		'pld_clabe_origen',
		'pld_banco_destino',
		'pld_cuenta_destino',
		'pld_numero_autorizacion',
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
