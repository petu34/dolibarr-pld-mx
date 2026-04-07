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
require_once __DIR__.'/class/pldpepverificacion.class.php';

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

    // Monto: separar sin IVA y total con IVA (Art. 6 DOF 27/03/2026)
    $monto_sin_iva = price2num(GETPOST('monto_sin_impuestos', 'alpha'));
    $tasa = (float) str_replace(',', '.', GETPOST('tasa_impuesto', 'alpha') ?: '0.16');
    if ($monto_sin_iva > 0) {
        $object->monto_sin_impuestos = $monto_sin_iva;
        $object->tasa_impuesto = $tasa;
        $object->calcularMontoTotal();
    } else {
        // Compatibilidad: si no se captura monto_sin_impuestos, monto_mxn es el total
        $object->monto_mxn = price2num(GETPOST('monto_mxn', 'alpha'));
    }

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
    $monto_sin_iva = price2num(GETPOST('monto_sin_impuestos', 'alpha'));
    $tasa = (float) str_replace(',', '.', GETPOST('tasa_impuesto', 'alpha') ?: '0.16');
    if ($monto_sin_iva > 0) {
        $object->monto_sin_impuestos = $monto_sin_iva;
        $object->tasa_impuesto = $tasa;
        $object->calcularMontoTotal();
    } else {
        $object->monto_mxn = price2num(GETPOST('monto_mxn', 'alpha'));
    }
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
    
    // Campos de monto separados por IVA (Art. 6 DOF 27/03/2026)
    $iva_default = getDolGlobalString('MODULECOMPLIANCEPLD_IVA_DEFAULT', '0.16');
    print '<tr><td class="fieldrequired">'.$langs->trans('MontoSinImpuestos').'</td><td>';
    print '<input type="text" id="monto_sin_impuestos" name="monto_sin_impuestos" size="15" value="'.GETPOST('monto_sin_impuestos', 'alpha').'">';
    print ' <small class="opacitymedium">'.$langs->trans('MontoSinIVAHelp').'</small>';
    print '</td></tr>';
    print '<tr><td>'.$langs->trans('TasaImpuesto').'</td><td>';
    print '<input type="text" id="tasa_impuesto" name="tasa_impuesto" size="6" value="'.dol_escape_htmltag($iva_default).'"> ';
    print '<small class="opacitymedium">(0.16 = IVA 16%)</small>';
    print '</td></tr>';
    print '<tr><td>'.$langs->trans('MontoMXN').' ('.$langs->trans('MontoConIVA').')</td><td>';
    print '<input type="text" id="monto_mxn_calc" name="monto_mxn" size="15" readonly style="background:#f0f0f0" value="'.GETPOST('monto_mxn', 'alpha').'">';
    print ' <small class="opacitymedium">'.$langs->trans('CalculadoAutomatico').'</small>';
    print '</td></tr>';
    print '<script>
(function() {
    var sin = document.getElementById("monto_sin_impuestos");
    var tasa = document.getElementById("tasa_impuesto");
    var total = document.getElementById("monto_mxn_calc");
    function recalc() {
        var s = parseFloat((sin.value+"").replace(",",".")) || 0;
        var t = parseFloat((tasa.value+"").replace(",",".")) || 0;
        total.value = (s * (1 + t)).toFixed(2);
    }
    sin.addEventListener("input", recalc);
    tasa.addEventListener("input", recalc);
    recalc();
})();
</script>';

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

    // Badge PEP del cliente
    $pepChecker = new PLDPepVerificacion($db);
    $pep_resultado = ($object->fk_societe > 0) ? $pepChecker->getResultadoActual((int)$object->fk_societe) : 'no_consultado';
    $pep_badge = '';
    if ($pep_resultado === 'positivo') {
        $pep_badge = ' <span class="badge badge-status8" title="'.$langs->trans('PLDClientePEP').'">PEP</span>';
    } elseif ($pep_resultado === 'no_consultado') {
        $pep_badge = ' <span class="badge badge-status1" title="'.$langs->trans('PLDPEPNoVerificado').'">PEP?</span>';
    }

    print '<table class="border centpercent tableforfield">';

    print '<tr><td class="titlefield">'.$langs->trans('Folio').'</td><td>'.dol_escape_htmltag($object->folio_interno).'</td></tr>';
    print '<tr><td>'.$langs->trans('FechaOperacion').'</td><td>'.dol_print_date($object->fecha_operacion, 'day').'</td></tr>';

    // Línea cliente con badge PEP
    if ($object->fk_societe > 0) {
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $soc = new Societe($db);
        if ($soc->fetch($object->fk_societe) > 0) {
            print '<tr><td>'.$langs->trans('Customer').'</td><td>';
            print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$object->fk_societe.'">'.dol_escape_htmltag($soc->name).'</a>';
            print $pep_badge;
            print '</td></tr>';
        }
    }

    // Montos separados
    print '<tr><td>'.$langs->trans('MontoSinImpuestos').'</td><td>';
    if ($object->monto_sin_impuestos !== null) {
        print price($object->monto_sin_impuestos, 0, $langs, 1, -1, -1, 'MXN');
        print ' <small class="opacitymedium">sin IVA — umbral</small>';
    } else {
        print '<span class="opacitymedium">—</span>';
    }
    print '</td></tr>';
    print '<tr><td>'.$langs->trans('MontoMXN').' (con IVA)</td><td>'.price($object->monto_mxn, 0, $langs, 1, -1, -1, 'MXN').'</td></tr>';
    print '<tr><td>'.$langs->trans('SuperaUmbral').'</td><td>'.yn($object->supera_umbral).'</td></tr>';
    print '<tr><td>'.$langs->trans('RequiereAviso').'</td><td>'.yn($object->requiere_aviso).'</td></tr>';
    print '<tr><td>'.$langs->trans('Estado').'</td><td>'.dol_escape_htmltag($object->estado).'</td></tr>';
    print '<tr><td>'.$langs->trans('CustodiaHasta').'</td><td>'.dol_escape_htmltag($object->getFechaFinCustodia()).'</td></tr>';

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
