<?php
/**
 * @file    tpl/documentos_list.tpl.php
 * @module  modulecompliancepld
 *
 * Plantilla de vista — lista de documentos PLD.
 *
 * Variables esperadas del controlador documentos_list.php:
 *   string   $title
 *   Form     $form
 *   array    $societes       [rowid => nom] para dropdown empresa
 *   int      $filtro_empresa
 *   string   $filtro_tipo, $filtro_verif
 *   int      $nbtotal, $now
 *   int      $page, $limit
 *   string   $sortfield, $sortorder, $param
 *   stdClass[] $rows
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-documentos-list');

$newbtn = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
    $newbtn = dolGetButtonTitle($langs->trans('BtnNuevoDocumento'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/documento.php?action=create');
}
print load_fiche_titre($title, $newbtn, 'fa-shield');

// ── Filtros ───────────────────────────────────────────────────────────────────
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="divsearchfield"><table class="noborder" style="margin-bottom:8px"><tr class="liste_titre_filter">';
print '<td>'.$form->selectarray('filtro_empresa', $societes, $filtro_empresa).'</td>';
print '<td><input type="text" name="filtro_tipo" class="flat minwidth100" placeholder="'.$langs->trans('ColTipoDocumento').'" value="'.dol_escape_htmltag($filtro_tipo).'"></td>';
$verif_opts = array('' => '-- '.$langs->trans('FiltroTodos').' --', '1' => $langs->trans('Yes'), '0' => $langs->trans('No'));
print '<td>'.$form->selectarray('filtro_verif', $verif_opts, $filtro_verif).'</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans('Search').'"> ';
print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a></td>';
print '</tr></table></div></form>';

// ── Tabla ─────────────────────────────────────────────────────────────────────
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColEmpresa'), $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColContacto'), '', '', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColTipoDocumento'), $_SERVER["PHP_SELF"], 'd.tipo_documento_pld', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColNumeroDocumento'), $_SERVER["PHP_SELF"], 'd.numero_documento', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFechaEmision'), $_SERVER["PHP_SELF"], 'd.fecha_emision', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('FechaVencimiento'), $_SERVER["PHP_SELF"], 'd.fecha_vencimiento', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColVerificado'), $_SERVER["PHP_SELF"], 'd.verificado', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre('', '', '');
print '</tr>';

$num = count($rows);
if ($num === 0) {
    print '<tr><td colspan="8" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>';
}

$i = 0;
foreach ($rows as $obj) {
    if ($i >= $limit) {
        break;
    }
    $vencido = (!empty($obj->fecha_vencimiento) && $db->jdate($obj->fecha_vencimiento) < $now);
    $trclass = ($vencido ? 'trwarning' : 'oddeven');
    print '<tr class="'.$trclass.'">';
    print '<td>';
    if ($obj->fk_societe) {
        print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->fk_societe.'">'.dol_escape_htmltag($obj->empresa_nom).'</a>';
    }
    print '</td>';
    print '<td>'.dol_escape_htmltag(trim($obj->contacto_nom)).'</td>';
    print '<td><a href="documento.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->tipo_documento_pld).'</a></td>';
    print '<td>'.dol_escape_htmltag($obj->numero_documento).'</td>';
    print '<td>'.dol_print_date($db->jdate($obj->fecha_emision), 'day').'</td>';
    $fv_label = dol_print_date($db->jdate($obj->fecha_vencimiento), 'day');
    if ($vencido) {
        $fv_label = '<span class="badge badge-status6">'.$fv_label.' &#x26A0;</span>';
    }
    print '<td>'.$fv_label.'</td>';
    print '<td class="center">'.($obj->verificado ? img_picto($langs->trans('Yes'), 'tick') : '').'</td>';
    print '<td class="right nowrap"><a href="documento.php?id='.$obj->rowid.'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a></td>';
    print '</tr>';
    $i++;
}

print '</table>';
print '</div>';
print '<br>';

llxFooter();
