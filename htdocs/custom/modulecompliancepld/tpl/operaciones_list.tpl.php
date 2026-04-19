<?php
/**
 * @file    tpl/operaciones_list.tpl.php
 * @module  modulecompliancepld
 *
 * Plantilla de vista — lista de operaciones vulnerables PLD.
 *
 * Variables esperadas del controlador operaciones_list.php:
 *   string   $title
 *   Form     $form
 *   array    $societes       [rowid => nom] para dropdown empresa
 *   int      $filtro_empresa
 *   string   $filtro_tipo
 *   string   $filtro_estado
 *   string   $filtro_supera
 *   int      $nbtotal
 *   int      $page, $limit, $offset
 *   string   $sortfield, $sortorder
 *   string   $param
 *   stdClass[] $rows
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-operaciones-list');

$newcardbutton = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
    $newcardbutton = dolGetButtonTitle($langs->trans('BtnNuevaOperacion'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/operacion.php?action=create');
}

print load_fiche_titre($title, $newcardbutton, 'fa-shield');

// ── Filtros ───────────────────────────────────────────────────────────────────
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="divsearchfield">';
print '<table class="noborder" style="margin-bottom:8px">';
print '<tr class="liste_titre_filter">';

print '<td>';
print $form->selectarray('filtro_empresa', $societes, $filtro_empresa, 0, 0, 0, '', 0, 0, 0, '', 'minwidth150');
print '</td>';

$tipos = array(
    ''               => '-- '.$langs->trans('FiltroTodos').' --',
    'venta_vehiculo' => $langs->trans('TipoOpVentaVehiculo'),
    'arrendamiento'  => $langs->trans('TipoOpArrendamiento'),
    'permuta'        => $langs->trans('TipoOpPermuta'),
    'consignacion'   => $langs->trans('TipoOpConsignacion'),
);
print '<td>';
print $form->selectarray('filtro_tipo', $tipos, $filtro_tipo, 0, 0, 0, '', 0, 0, 0, '', 'minwidth150');
print '</td>';

$estados = array(
    ''           => '-- '.$langs->trans('FiltroTodos').' --',
    'borrador'   => $langs->trans('EstadoBorrador'),
    'pendiente_documentacion' => $langs->trans('EstadoPendiente'),
    'completada' => $langs->trans('EstadoCompletado'),
    'cancelada'  => $langs->trans('EstadoCancelado'),
);
print '<td>';
print $form->selectarray('filtro_estado', $estados, $filtro_estado, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
print '</td>';

$umbral_opts = array(
    ''  => '-- '.$langs->trans('FiltroTodos').' --',
    '1' => $langs->trans('Yes'),
    '0' => $langs->trans('No'),
);
print '<td>';
print $form->selectarray('filtro_supera', $umbral_opts, $filtro_supera, 0, 0, 0, '', 0, 0, 0, '', '');
print '</td>';

print '<td>';
print '<input type="submit" class="button" value="'.$langs->trans('Search').'">';
print ' <a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a>';
print '</td>';
print '</tr>';
print '</table>';
print '</div>';
print '</form>';

// ── Tabla ─────────────────────────────────────────────────────────────────────
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColFolioInterno'), $_SERVER["PHP_SELF"], 'o.folio_interno', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEmpresa'), $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColTipoOperacion'), $_SERVER["PHP_SELF"], 'o.tipo_operacion', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFecha'), $_SERVER["PHP_SELF"], 'o.fecha_operacion', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColMonto'), $_SERVER["PHP_SELF"], 'o.monto_mxn', '', $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColSuperaUmbral'), $_SERVER["PHP_SELF"], 'o.supera_umbral', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEstado'), $_SERVER["PHP_SELF"], 'o.estado', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('', '', '');
print '</tr>';

$tipo_labels = array(
    'venta_vehiculo' => $langs->trans('TipoOpVentaVehiculo'),
    'arrendamiento'  => $langs->trans('TipoOpArrendamiento'),
    'permuta'        => $langs->trans('TipoOpPermuta'),
    'consignacion'   => $langs->trans('TipoOpConsignacion'),
);
$estado_colors = array(
    'borrador'                => 'badge-status0',
    'pendiente_documentacion' => 'badge-status1',
    'completada'              => 'badge-status4',
    'cancelada'               => 'badge-status9',
);
$estado_labels = array(
    'borrador'                => $langs->trans('EstadoBorrador'),
    'pendiente_documentacion' => $langs->trans('EstadoPendiente'),
    'completada'              => $langs->trans('EstadoCompletado'),
    'cancelada'               => $langs->trans('EstadoCancelado'),
);

$num = count($rows);
if ($num === 0) {
    print '<tr><td colspan="8" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>';
}

$i = 0;
foreach ($rows as $obj) {
    if ($i >= $limit) {
        break;
    }
    $trclass = ($obj->supera_umbral ? 'trwarning' : 'oddeven');
    print '<tr class="'.$trclass.'">';

    print '<td><a href="operacion.php?id='.((int) $obj->rowid).'">'.dol_escape_htmltag($obj->folio_interno ?: '#'.$obj->rowid).'</a></td>';

    print '<td>';
    if ($obj->fk_societe) {
        print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.((int) $obj->fk_societe).'">'.dol_escape_htmltag($obj->empresa_nom).'</a>';
    }
    print '</td>';

    print '<td>'.dol_escape_htmltag($tipo_labels[$obj->tipo_operacion] ?? $obj->tipo_operacion).'</td>';
    print '<td>'.dol_print_date($db->jdate($obj->fecha_operacion), 'day').'</td>';
    print '<td class="right nowrap">'.price($obj->monto_mxn).' MXN</td>';

    $umbral_html = $obj->supera_umbral
        ? '<span class="badge badge-status6">'.$langs->trans('Yes').'</span>'
        : '<span class="badge badge-status0">'.$langs->trans('No').'</span>';
    print '<td class="center">'.$umbral_html.'</td>';

    $ec = $estado_colors[$obj->estado] ?? 'badge-status0';
    $el = $estado_labels[$obj->estado] ?? $obj->estado;
    print '<td class="center"><span class="badge '.$ec.'">'.dol_escape_htmltag($el).'</span></td>';

    print '<td class="right nowrap">';
    print '<a href="operacion.php?id='.$obj->rowid.'" title="'.$langs->trans('BtnVerDetalle').'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a>';
    if ($user->hasRight('modulecompliancepld', 'write')) {
        print ' <a href="operacion.php?id='.$obj->rowid.'&action=edit&token='.newToken().'" title="'.$langs->trans('BtnEditar').'">'.img_picto($langs->trans('BtnEditar'), 'edit').'</a>';
    }
    if ($user->hasRight('modulecompliancepld', 'delete')) {
        print ' <a href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$obj->rowid.'&token='.newToken().'" title="'.$langs->trans('BtnEliminar').'" onclick="return confirm(\''.dol_escape_js($langs->trans('ConfirmDelete')).'\')">'.img_picto($langs->trans('BtnEliminar'), 'delete').'</a>';
    }
    print '</td>';

    print '</tr>';
    $i++;
}

print '</table>';
print '</div>';
print '<br>';

llxFooter();
