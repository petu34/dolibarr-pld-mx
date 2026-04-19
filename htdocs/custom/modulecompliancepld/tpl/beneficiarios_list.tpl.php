<?php
/**
 * @file    tpl/beneficiarios_list.tpl.php
 * @module  modulecompliancepld
 *
 * Plantilla de vista — lista de beneficiarios controladores PLD.
 *
 * Variables esperadas del controlador beneficiarios_list.php:
 *   string   $title
 *   Form     $form
 *   array    $societes       [rowid => nom] para dropdown empresa
 *   int      $filtro_empresa
 *   string   $filtro_pep
 *   int      $nbtotal
 *   int      $page, $limit
 *   string   $sortfield, $sortorder, $param
 *   stdClass[] $rows
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-beneficiarios-list');

$newbtn = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
    $newbtn = dolGetButtonTitle($langs->trans('BtnNuevoBeneficiario'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/beneficiario.php?action=create');
}
print load_fiche_titre($title, $newbtn, 'fa-shield');

// ── Filtros ───────────────────────────────────────────────────────────────────
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="divsearchfield"><table class="noborder" style="margin-bottom:8px"><tr class="liste_titre_filter">';
print '<td>'.$form->selectarray('filtro_empresa', $societes, $filtro_empresa).'</td>';
$pep_opts = array('' => '-- '.$langs->trans('FiltroTodos').' --', '1' => $langs->trans('Yes'), '0' => $langs->trans('No'));
print '<td>'.$form->selectarray('filtro_pep', $pep_opts, $filtro_pep).'</td>';
print '<td><input type="submit" class="button" value="'.$langs->trans('Search').'"> ';
print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a></td>';
print '</tr></table></div></form>';

// ── Tabla ─────────────────────────────────────────────────────────────────────
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColEmpresa'), $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColNombreCompleto'), $_SERVER["PHP_SELF"], 'b.apellido_paterno', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColCURP'), $_SERVER["PHP_SELF"], 'b.curp', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColRFC'), $_SERVER["PHP_SELF"], 'b.rfc', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColTipoBeneficiario'), $_SERVER["PHP_SELF"], 'b.tipo_beneficiario', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColPorcentaje'), $_SERVER["PHP_SELF"], 'b.porcentaje_participacion', '', $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEsPEP'), $_SERVER["PHP_SELF"], 'b.es_pep', '', $param, 'class="center"', $sortfield, $sortorder);
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
    $trclass = ($obj->es_pep ? 'trwarning' : 'oddeven');
    print '<tr class="'.$trclass.'">';
    print '<td>';
    if ($obj->fk_societe) {
        print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->fk_societe.'">'.dol_escape_htmltag($obj->empresa_nom).'</a>';
    }
    print '</td>';
    print '<td><a href="beneficiario.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->nombre_completo).'</a></td>';
    print '<td>'.dol_escape_htmltag($obj->curp).'</td>';
    print '<td>'.dol_escape_htmltag($obj->rfc).'</td>';
    print '<td>'.dol_escape_htmltag($obj->tipo_beneficiario).'</td>';
    print '<td class="right">'.number_format((float) $obj->porcentaje_participacion, 2).' %</td>';
    print '<td class="center">'.($obj->es_pep ? '<span class="badge badge-status6">PEP</span>' : '').'</td>';
    print '<td class="right nowrap"><a href="beneficiario.php?id='.$obj->rowid.'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a></td>';
    print '</tr>';
    $i++;
}

print '</table>';
print '</div>';
print '<br>';

llxFooter();
