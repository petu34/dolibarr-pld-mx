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
require_once __DIR__.'/class/pldbeneficiario.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');
$fk_societe = GETPOST('fk_societe', 'int');
$cancel = GETPOST('cancel', 'alpha');

// Redirect to list when no action/id
if (empty($action) && empty($id) && empty($fk_societe)) {
    header("Location: beneficiarios_list.php");
    exit;
}

if ($cancel) {
    header("Location: beneficiarios_list.php");
    exit;
}

// Security check
if (!$user->hasRight('modulecompliancepld', 'read')) {
    accessforbidden();
}

$object = new PLDBeneficiario($db);

if ($id > 0) {
    $result = $object->fetch($id);
    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

if ($action == 'add' && !$cancel) {
    $object->fk_societe = GETPOST('fk_societe', 'int');
    $object->fk_socpeople = GETPOST('fk_socpeople', 'int');
    $object->tipo_beneficiario = GETPOST('tipo_beneficiario', 'alpha');
    $object->nombre = GETPOST('nombre', 'alpha');
    $object->apellido_paterno = GETPOST('apellido_paterno', 'alpha');
    $object->apellido_materno = GETPOST('apellido_materno', 'alpha');
    $object->curp = GETPOST('curp', 'alpha');
    $object->rfc = GETPOST('rfc', 'alpha');
    $object->nacionalidad = GETPOST('nacionalidad', 'alpha');
    $object->porcentaje_participacion = price2num(GETPOST('porcentaje_participacion', 'alpha'));
    $object->es_pep = GETPOST('es_pep', 'int');
    
    $result = $object->create($user);
    
    if ($result > 0) {
        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
        exit;
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = 'create';
    }
}

llxHeader('', $langs->trans('PLDBeneficiario'));

$form = new Form($db);

if ($action == 'create' || !empty($fk_societe)) {
    print load_fiche_titre($langs->trans('NuevoBeneficiarioPLD'));
    
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';
    
    print '<table class="border centpercent">';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('Empresa').'</td><td>';
    print $form->select_company($fk_societe, 'fk_societe', '', 1);
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('TipoBeneficiario').'</td><td>';
    print '<select name="tipo_beneficiario">';
    print '<option value="accionista">Accionista</option>';
    print '<option value="fideicomitente">Fideicomitente</option>';
    print '<option value="fideicomisario">Fideicomisario</option>';
    print '<option value="administrador">Administrador</option>';
    print '</select>';
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('Nombre').'</td><td>';
    print '<input type="text" name="nombre" size="30" value="'.GETPOST('nombre', 'alpha').'">';
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('ApellidoPaterno').'</td><td>';
    print '<input type="text" name="apellido_paterno" size="30" value="'.GETPOST('apellido_paterno', 'alpha').'">';
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans('ApellidoMaterno').'</td><td>';
    print '<input type="text" name="apellido_materno" size="30" value="'.GETPOST('apellido_materno', 'alpha').'">';
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('CURP').'</td><td>';
    print '<input type="text" name="curp" size="18" maxlength="18" value="'.GETPOST('curp', 'alpha').'">';
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('RFC').'</td><td>';
    print '<input type="text" name="rfc" size="13" maxlength="13" value="'.GETPOST('rfc', 'alpha').'">';
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans('Nacionalidad').'</td><td>';
    print '<input type="text" name="nacionalidad" size="30" value="México">';
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('PorcentajeParticipacion').'</td><td>';
    print '<input type="number" name="porcentaje_participacion" step="0.01" min="0" max="100" value="'.GETPOST('porcentaje_participacion', 'alpha').'"> %';
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans('EsPEP').'</td><td>';
    print '<input type="checkbox" name="es_pep" value="1">';
    print '</td></tr>';
    
    print '</table>';
    
    print '<div class="center">';
    print '<input type="submit" class="button" value="'.$langs->trans('Crear').'">';
    print ' &nbsp; ';
    print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
    print '</div>';
    
    print '</form>';
    
} elseif ($id > 0) {
    print load_fiche_titre($langs->trans('BeneficiarioPLD').' - '.$object->obtenerNombreCompleto());

    print '<div class="tabsAction" style="padding-bottom:5px">';
    print '<a href="beneficiarios_list.php" class="butActionRefused">&larr; '.$langs->trans('BackToList').'</a>';
    print '</div>';

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border centpercent tableforfield">';

    print '<tr><td class="titlefield">'.$langs->trans('NombreCompleto').'</td><td>'.$object->obtenerNombreCompleto().'</td></tr>';
    print '<tr><td>'.$langs->trans('TipoBeneficiario').'</td><td>'.$object->tipo_beneficiario.'</td></tr>';
    print '<tr><td>'.$langs->trans('CURP').'</td><td>'.$object->curp.'</td></tr>';
    print '<tr><td>'.$langs->trans('RFC').'</td><td>'.$object->rfc.'</td></tr>';
    print '<tr><td>'.$langs->trans('PorcentajeParticipacion').'</td><td>'.$object->porcentaje_participacion.' %</td></tr>';
    print '<tr><td>'.$langs->trans('EsPEP').'</td><td>'.yn($object->es_pep).'</td></tr>';
    print '<tr><td>'.$langs->trans('Verificado').'</td><td>'.yn($object->verificado).'</td></tr>';

    print '</table>';

    print '</div>';

    print '<div class="tabsAction">';
    if ($user->hasRight('modulecompliancepld', 'write')) {
        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=edit&token='.newToken().'" class="butAction">'.$langs->trans('Modify').'</a>';
    }
    print '<a href="beneficiarios_list.php" class="butAction">'.$langs->trans('BackToList').'</a>';
    print '</div>';
}

llxFooter();
$db->close();
