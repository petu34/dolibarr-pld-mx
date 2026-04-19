<?php
/**
 * @file    tpl/avisos_list.tpl.php
 * @module  modulecompliancepld
 *
 * Plantilla de vista — lista de avisos SAT PLD.
 *
 * Variables esperadas del controlador avisos_list.php:
 *   string   $title
 *   Form     $form
 *   string   $filtro_tipo, $filtro_estado, $filtro_mes
 *   int      $nbtotal
 *   int      $page, $limit, $offset
 *   string   $sortfield, $sortorder, $param
 *   stdClass[] $rows
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-avisos-list');

$newbtn = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
    $newbtn = dolGetButtonTitle($langs->trans('BtnNuevoAviso'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/aviso/card.php?action=create');
}
print load_fiche_titre($title, $newbtn, 'fa-shield');

// ── Filtros ───────────────────────────────────────────────────────────────────
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="divsearchfield"><table class="noborder" style="margin-bottom:8px"><tr class="liste_titre_filter">';

$tipos_aviso = array(
    ''    => '-- '.$langs->trans('FiltroTodos').' --',
    'MEN' => $langs->trans('PLDTipoAvisoMensual'),
    '24H' => $langs->trans('PLDTipoAviso24hrs'),
    'ACU' => $langs->trans('PLDTipoAvisoAcumulado'),
);
print '<td>'.$form->selectarray('filtro_tipo', $tipos_aviso, $filtro_tipo).'</td>';

$estados_aviso = array(
    ''           => '-- '.$langs->trans('FiltroTodos').' --',
    'borrador'   => $langs->trans('EstadoBorrador'),
    'pendiente'  => $langs->trans('EstadoPendiente'),
    'presentado' => $langs->trans('EstadoPresentado'),
    'cancelado'  => $langs->trans('EstadoCancelado'),
);
print '<td>'.$form->selectarray('filtro_estado', $estados_aviso, $filtro_estado).'</td>';
print '<td><input type="text" name="filtro_mes" class="flat minwidth100" placeholder="YYYYMM" value="'.dol_escape_htmltag($filtro_mes).'"></td>';
print '<td><input type="submit" class="button" value="'.$langs->trans('Search').'"> ';
print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a></td>';
print '</tr></table></div></form>';

// ── Tabla ─────────────────────────────────────────────────────────────────────
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColReferenciaAviso'), $_SERVER["PHP_SELF"], 'a.referencia_aviso', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColTipoAviso'), $_SERVER["PHP_SELF"], 'a.tipo_aviso', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColMesReportado'), $_SERVER["PHP_SELF"], 'a.mes_reportado', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEstado'), $_SERVER["PHP_SELF"], 'a.estado', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFechaPresentacion'), $_SERVER["PHP_SELF"], 'a.fecha_presentacion', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFolioSAT'), $_SERVER["PHP_SELF"], 'a.folio_sat', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('TotalOperaciones'), $_SERVER["PHP_SELF"], 'a.numero_operaciones', '', $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('', '', '');
print '</tr>';

$tipo_labels   = array('MEN' => $langs->trans('PLDTipoAvisoMensual'), '24H' => $langs->trans('PLDTipoAviso24hrs'), 'ACU' => $langs->trans('PLDTipoAvisoAcumulado'));
$estado_colors = array('borrador' => 'badge-status0', 'pendiente' => 'badge-status1', 'presentado' => 'badge-status4', 'cancelado' => 'badge-status9');
$estado_labels = array('borrador' => $langs->trans('EstadoBorrador'), 'pendiente' => $langs->trans('EstadoPendiente'), 'presentado' => $langs->trans('EstadoPresentado'), 'cancelado' => $langs->trans('EstadoCancelado'));

$num = count($rows);
if ($num === 0) {
    print '<tr><td colspan="8" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>';
}

$i = 0;
foreach ($rows as $obj) {
    if ($i >= $limit) {
        break;
    }
    print '<tr class="oddeven">';
    print '<td><a href="aviso/card.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->referencia_aviso ?: '#'.$obj->rowid).'</a></td>';
    print '<td>'.dol_escape_htmltag($tipo_labels[$obj->tipo_aviso] ?? $obj->tipo_aviso).'</td>';
    print '<td>'.dol_escape_htmltag($obj->mes_reportado).'</td>';
    $ec = $estado_colors[$obj->estado] ?? 'badge-status0';
    $el = $estado_labels[$obj->estado] ?? $obj->estado;
    print '<td class="center"><span class="badge '.$ec.'">'.dol_escape_htmltag($el).'</span></td>';
    print '<td>'.dol_print_date($db->jdate($obj->fecha_presentacion), 'day').'</td>';
    print '<td>'.dol_escape_htmltag($obj->folio_sat).'</td>';
    print '<td class="right">'.((int) $obj->numero_operaciones).'</td>';
    print '<td class="right nowrap"><a href="aviso/card.php?id='.$obj->rowid.'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a></td>';
    print '</tr>';
    $i++;
}

print '</table>';
print '</div>';
print '<br>';

llxFooter();
