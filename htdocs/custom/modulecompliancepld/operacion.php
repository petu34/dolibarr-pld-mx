<?php
declare(strict_types=1);

$res = 0;
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once __DIR__.'/class/pldoperacion.class.php';
require_once __DIR__.'/class/compliancepld.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');
$cancel = GETPOST('cancel', 'alpha');

// Redirect to list when no action/id
if (empty($action) && empty($id)) {
    header("Location: operaciones_list.php");
    exit;
}

// Security check
if (!$user->hasRight('modulecompliancepld', 'read')) {
    accessforbidden();
}

$object = new PLDOperacion($db);
$compliance = new CompliancePLD($db);

if ($id > 0) {
    $result = $object->fetch($id);
    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

if ($cancel) {
    header("Location: operaciones_list.php");
    exit;
}

if ($action == 'add' && !$cancel) {
    $object->fk_societe = GETPOST('fk_societe', 'int');
    $object->fk_facture = GETPOST('fk_facture', 'int');
    $object->tipo_operacion = GETPOST('tipo_operacion', 'alpha');
    $object->tipo_actividad_vulnerable = GETPOST('tipo_actividad_vulnerable', 'alpha');
    $object->fecha_operacion = dol_mktime(0, 0, 0, GETPOST('fecha_operacionmonth', 'int'), GETPOST('fecha_operacionday', 'int'), GETPOST('fecha_operacionyear', 'int'));
    $object->monto_mxn = price2num(GETPOST('monto_mxn', 'alpha'));
    
    $tipo_vehiculo = GETPOST('tipo_vehiculo', 'alpha');
    $resultado_umbral = $object->evaluarUmbral($tipo_vehiculo);
    
    $object->generarFolioInterno();
    
    $result = $object->create($user);
    
    if ($result > 0) {
        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
        exit;
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = 'create';
    }
}

if ($action == 'update' && !$cancel) {
    $object->monto_mxn = price2num(GETPOST('monto_mxn', 'alpha'));
    $object->estado = GETPOST('estado', 'alpha');
    $object->cliente_identificado = GETPOST('cliente_identificado', 'int');
    $object->documentacion_completa = GETPOST('documentacion_completa', 'int');
    
    $result = $object->update($user);
    
    if ($result > 0) {
        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
        exit;
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = 'edit';
    }
}

llxHeader('', $langs->trans('PLDOperacion'));

$form = new Form($db);

if ($action == 'create') {
    print load_fiche_titre($langs->trans('NuevaOperacionPLD'));
    
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';
    
    print '<table class="border centpercent">';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('Empresa').'</td><td>';
    print $form->select_company(GETPOST('fk_societe', 'int'), 'fk_societe', '', 1);
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans('Factura').'</td><td>';
    print '<input type="number" name="fk_facture" value="'.GETPOST('fk_facture', 'int').'">';
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('TipoOperacion').'</td><td>';
    print '<select name="tipo_operacion">';
    print '<option value="venta_vehiculo">Venta Vehículo</option>';
    print '<option value="compra_vehiculo">Compra Vehículo</option>';
    print '</select>';
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('TipoVehiculo').'</td><td>';
    print '<select name="tipo_vehiculo">';
    print '<option value="nuevo">Nuevo</option>';
    print '<option value="usado">Usado</option>';
    print '</select>';
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans('ActividadVulnerable').'</td><td>';
    print '<input type="text" name="tipo_actividad_vulnerable" value="VIII" size="5">';
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('FechaOperacion').'</td><td>';
    print $form->selectDate(dol_now(), 'fecha_operacion', 0, 0, 0, '', 1, 1);
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('MontoMXN').'</td><td>';
    print '<input type="text" name="monto_mxn" size="15" value="'.GETPOST('monto_mxn', 'alpha').'">';
    print '</td></tr>';
    
    print '</table>';
    
    print '<div class="center">';
    print '<input type="submit" class="button" value="'.$langs->trans('Crear').'">';
    print ' &nbsp; ';
    print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
    print '</div>';
    
    print '</form>';
    
} elseif ($id > 0) {
    print load_fiche_titre($langs->trans('OperacionPLD').' '.$object->folio_interno);

    print '<div class="tabsAction" style="padding-bottom:5px">';
    print '<a href="operaciones_list.php" class="butActionRefused">&larr; '.$langs->trans('BackToList').'</a>';
    print '</div>';

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent tableforfield">';

    print '<tr><td class="titlefield">'.$langs->trans('Folio').'</td><td>'.$object->folio_interno.'</td></tr>';
    print '<tr><td>'.$langs->trans('FechaOperacion').'</td><td>'.dol_print_date($object->fecha_operacion, 'day').'</td></tr>';
    print '<tr><td>'.$langs->trans('MontoMXN').'</td><td>'.price($object->monto_mxn, 0, $langs, 1, -1, -1, 'MXN').'</td></tr>';
    print '<tr><td>'.$langs->trans('SuperaUmbral').'</td><td>'.yn($object->supera_umbral).'</td></tr>';
    print '<tr><td>'.$langs->trans('RequiereAviso').'</td><td>'.yn($object->requiere_aviso).'</td></tr>';
    print '<tr><td>'.$langs->trans('Estado').'</td><td>'.$object->estado.'</td></tr>';

    print '</table>';

    print '</div>';

    print '<div class="tabsAction">';
    if ($user->hasRight('modulecompliancepld', 'write')) {
        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=edit&token='.newToken().'" class="butAction">'.$langs->trans('Modify').'</a>';
    }
    print '<a href="operaciones_list.php" class="butAction">'.$langs->trans('BackToList').'</a>';
    print '</div>';
}

llxFooter();
$db->close();
