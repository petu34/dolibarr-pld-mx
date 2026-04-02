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
    setEventMessages($langs->trans('PLDOperacionSoloAutomatica'), null, 'warnings');
    header("Location: operaciones_list.php");
    exit;
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
    setEventMessages($langs->trans('PLDOperacionSoloAutomatica'), null, 'warnings');
    header("Location: operaciones_list.php");
    exit;
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
