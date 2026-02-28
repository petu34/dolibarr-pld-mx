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
require_once __DIR__.'/class/plddocumento.class.php';
require_once __DIR__.'/class/plddocumentouploader.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');
$fk_societe = GETPOST('fk_societe', 'int');
$fk_socpeople = GETPOST('fk_socpeople', 'int');
$cancel = GETPOST('cancel', 'alpha');

$object = new PLDDocumento($db);
$uploader = new PLDDocumentoUploader($db);

if ($id > 0) {
    $result = $object->fetch($id);
    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

if ($action == 'upload' && !$cancel) {
    if (!empty($_FILES['documento_file']['tmp_name'])) {
        $result = $uploader->uploadDocumento(
            $user,
            $_FILES['documento_file']['tmp_name'],
            GETPOST('tipo_documento_pld', 'alpha'),
            GETPOST('fk_societe', 'int'),
            GETPOST('fk_socpeople', 'int'),
            GETPOST('numero_documento', 'alpha'),
            GETPOST('fecha_emision', 'alpha'),
            GETPOST('fecha_vencimiento', 'alpha'),
            GETPOST('autoridad_emite', 'alpha')
        );
        
        if ($result > 0) {
            setEventMessages($langs->trans('DocumentoSubidoCorrectamente'), null, 'mesgs');
            header("Location: ".$_SERVER["PHP_SELF"]."?id=".$result);
            exit;
        } else {
            setEventMessages($uploader->errors, null, 'errors');
            $action = 'create';
        }
    } else {
        setEventMessages($langs->trans('NoSeSeleccionoArchivo'), null, 'errors');
        $action = 'create';
    }
}

if ($action == 'verificar') {
    $result = $uploader->verificarDocumento($user, $id);
    
    if ($result) {
        setEventMessages($langs->trans('DocumentoVerificado'), null, 'mesgs');
        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
        exit;
    } else {
        setEventMessages($uploader->errors, null, 'errors');
    }
}

llxHeader('', $langs->trans('PLDDocumento'));

$form = new Form($db);

if ($action == 'create' || !empty($fk_societe) || !empty($fk_socpeople)) {
    print load_fiche_titre($langs->trans('SubirDocumentoPLD'));
    
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="upload">';
    
    print '<table class="border centpercent">';
    
    if ($fk_societe > 0) {
        print '<input type="hidden" name="fk_societe" value="'.$fk_societe.'">';
    } elseif ($fk_socpeople > 0) {
        print '<input type="hidden" name="fk_socpeople" value="'.$fk_socpeople.'">';
    } else {
        print '<tr><td>'.$langs->trans('Empresa').'</td><td>';
        print $form->select_company(GETPOST('fk_societe', 'int'), 'fk_societe', '', 1);
        print '</td></tr>';
    }
    
    print '<tr><td class="fieldrequired">'.$langs->trans('TipoDocumento').'</td><td>';
    print '<select name="tipo_documento_pld">';
    print '<option value="INE">INE</option>';
    print '<option value="pasaporte">Pasaporte</option>';
    print '<option value="acta_constitutiva">Acta Constitutiva</option>';
    print '<option value="comprobante_domicilio">Comprobante Domicilio</option>';
    print '<option value="rfc_certificado">RFC Certificado</option>';
    print '</select>';
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans('NumeroDocumento').'</td><td>';
    print '<input type="text" name="numero_documento" size="30" value="'.GETPOST('numero_documento', 'alpha').'">';
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans('FechaEmision').'</td><td>';
    print $form->selectDate('', 'fecha_emision', 0, 0, 1, '', 1, 0);
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans('FechaVencimiento').'</td><td>';
    print $form->selectDate('', 'fecha_vencimiento', 0, 0, 1, '', 1, 0);
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans('AutoridadEmite').'</td><td>';
    print '<input type="text" name="autoridad_emite" size="30" value="'.GETPOST('autoridad_emite', 'alpha').'">';
    print '</td></tr>';
    
    print '<tr><td class="fieldrequired">'.$langs->trans('Archivo').'</td><td>';
    print '<input type="file" name="documento_file" accept=".pdf,.jpg,.jpeg,.png">';
    print '</td></tr>';
    
    print '</table>';
    
    print '<div class="center">';
    print '<input type="submit" class="button" value="'.$langs->trans('Subir').'">';
    print ' &nbsp; ';
    print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
    print '</div>';
    
    print '</form>';
    
} elseif ($id > 0) {
    print load_fiche_titre($langs->trans('DocumentoPLD'));
    
    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';
    
    print '<table class="border centpercent tableforfield">';
    
    print '<tr><td class="titlefield">'.$langs->trans('TipoDocumento').'</td><td>'.$object->tipo_documento_pld.'</td></tr>';
    print '<tr><td>'.$langs->trans('NumeroDocumento').'</td><td>'.$object->numero_documento.'</td></tr>';
    print '<tr><td>'.$langs->trans('FechaEmision').'</td><td>'.dol_print_date($object->fecha_emision, 'day').'</td></tr>';
    print '<tr><td>'.$langs->trans('FechaVencimiento').'</td><td>'.dol_print_date($object->fecha_vencimiento, 'day').'</td></tr>';
    print '<tr><td>'.$langs->trans('AutoridadEmite').'</td><td>'.$object->autoridad_emite.'</td></tr>';
    print '<tr><td>'.$langs->trans('Verificado').'</td><td>'.yn($object->verificado).'</td></tr>';
    
    if ($object->estaVencido()) {
        print '<tr><td colspan="2"><div class="warning">'.$langs->trans('DocumentoVencido').'</div></td></tr>';
    }
    
    print '</table>';
    
    if ($object->verificado == 0 && $user->hasRight('modulecompliancepld', 'write')) {
        print '<div class="center" style="margin-top: 20px;">';
        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=verificar&token='.newToken().'">';
        print $langs->trans('VerificarDocumento').'</a>';
        print '</div>';
    }
    
    print '</div>';
}

llxFooter();
$db->close();
