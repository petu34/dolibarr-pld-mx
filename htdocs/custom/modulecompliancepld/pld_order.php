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
 * @file        pld_order.php
 * @module      CompliancePLD
 * @description Tab PLD en ficha de pedido
 * @author      Ouroboros
 * @compliance  LFPIORPI Art. 17 — Actividad Vulnerable
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Parameters
$id     = GETPOSTINT('id');
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Load translations
$langs->loadLangs(array('orders', 'modulecompliancepld@modulecompliancepld'));

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'commande', $id);

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$permwrite = ($user->hasRight('modulecompliancepld', 'write') && $user->hasRight('commande', 'creer'));

// Load objects
$object      = new Commande($db);
$extrafields = new ExtraFields($db);

// Load extrafield definitions
$extrafields->fetch_name_optionals_label($object->table_element);

// Load order
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0) {
		$object->fetch_optionals();
	}
}

$hookmanager->initHooks(array('orderpldcard', 'globalcard'));


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

$title   = $object->ref.' - '.$langs->trans('PLDTabOrder');
$helpurl = '';
llxHeader('', $title, $helpurl);

$form = new Form($db);

if ($object->id > 0) {
	$object->fetch_thirdparty();

	$head = commande_prepare_head($object);
	print dol_get_fiche_head($head, 'plddata', $langs->trans('Order'), -1, 'order');

	$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php?restore_lastsearch_values=1'.
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
	// SECCIÓN 1: Datos PLD del Pedido
	// =========================================================
	print '<div class="div-table-responsive-no-min">';
	print load_fiche_titre($langs->trans('PLDDatosPedido'), '', 'fa-shopping-cart');
	print '<table class="border centpercent tableforfield">';
	foreach (array(
		'pld_preventa_identificada',
		'pld_anticipo_estimado',
		'pld_forma_pago_planeada',
		'pld_alerta_previa',
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
