<?php
/**
 * @file    tpl/alertas_list.tpl.php
 * @module  modulecompliancepld
 *
 * Plantilla de vista — lista de alertas PLD.
 *
 * Variables esperadas del controlador alertas_list.php:
 *   string   $title
 *   Form     $form
 *   string   $filtro_nivel, $filtro_estado, $filtro_tipo
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

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-alertas-list');

$newbtn = '';
if ($user->hasRight('modulecompliancepld', 'write')) {
    $newbtn = dolGetButtonTitle($langs->trans('BtnNuevaAlerta'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/modulecompliancepld/alerta.php?action=create');
}
print load_fiche_titre($title, $newbtn, 'fa-shield');

// ── Filtros ───────────────────────────────────────────────────────────────────
print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
print '<div class="divsearchfield"><table class="noborder" style="margin-bottom:8px"><tr class="liste_titre_filter">';

$niveles = array(
    ''       => '-- '.$langs->trans('FiltroTodos').' --',
    'alto'   => $langs->trans('NivelRiesgoAlto'),
    'medio'  => $langs->trans('NivelRiesgoMedio'),
    'bajo'   => $langs->trans('NivelRiesgoBajo'),
);
print '<td>'.$form->selectarray('filtro_nivel', $niveles, $filtro_nivel).'</td>';

$estados = array(
    ''            => '-- '.$langs->trans('FiltroTodos').' --',
    'abierta'     => $langs->trans('EstadoAbierta'),
    'en_revision' => $langs->trans('EstadoEnRevision'),
    'resuelta'    => $langs->trans('EstadoResuelta'),
    'archivada'   => $langs->trans('EstadoArchivada'),
);
print '<td>'.$form->selectarray('filtro_estado', $estados, $filtro_estado).'</td>';
print '<td><input type="text" name="filtro_tipo" class="flat minwidth100" placeholder="'.$langs->trans('ColTipoAlerta').'" value="'.dol_escape_htmltag($filtro_tipo).'"></td>';
print '<td><input type="submit" class="button" value="'.$langs->trans('Search').'"> ';
print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans('Clear').'</a></td>';
print '</tr></table></div></form>';

// ── Tabla ─────────────────────────────────────────────────────────────────────
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $nbtotal, $nbtotal, 'fa-shield', 0, '', '', $limit);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('ColTipoAlerta'), $_SERVER["PHP_SELF"], 'al.tipo_alerta', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColNivelRiesgo'), $_SERVER["PHP_SELF"], 'al.nivel_riesgo', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEmpresa'), $_SERVER["PHP_SELF"], 's.nom', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColDescripcion'), '', '', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColEstado'), $_SERVER["PHP_SELF"], 'al.estado', '', $param, 'class="center"', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('ColFechaAlerta'), $_SERVER["PHP_SELF"], 'al.datec', '', $param, '', $sortfield, $sortorder);
print_liste_field_titre('', '', '');
print '</tr>';

$nivel_colors  = array('alto' => 'badge-status6', 'medio' => 'badge-status5', 'bajo' => 'badge-status1');
$nivel_labels  = array('alto' => $langs->trans('NivelRiesgoAlto'), 'medio' => $langs->trans('NivelRiesgoMedio'), 'bajo' => $langs->trans('NivelRiesgoBajo'));
$estado_colors = array('abierta' => 'badge-status1', 'en_revision' => 'badge-status5', 'resuelta' => 'badge-status4', 'archivada' => 'badge-status9');
$estado_labels = array('abierta' => $langs->trans('EstadoAbierta'), 'en_revision' => $langs->trans('EstadoEnRevision'), 'resuelta' => $langs->trans('EstadoResuelta'), 'archivada' => $langs->trans('EstadoArchivada'));

$num = count($rows);
if ($num === 0) {
    print '<tr><td colspan="7" class="opacitymedium center">'.$langs->trans('DashSinRegistros').'</td></tr>';
}

$i = 0;
foreach ($rows as $obj) {
    if ($i >= $limit) {
        break;
    }
    $trclass = ($obj->nivel_riesgo == 'alto' ? 'trwarning' : 'oddeven');
    print '<tr class="'.$trclass.'">';
    print '<td><a href="alerta.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->tipo_alerta).'</a></td>';
    $nc = $nivel_colors[$obj->nivel_riesgo] ?? 'badge-status0';
    $nl = $nivel_labels[$obj->nivel_riesgo] ?? $obj->nivel_riesgo;
    print '<td class="center"><span class="badge '.$nc.'">'.dol_escape_htmltag($nl).'</span></td>';
    print '<td>';
    if ($obj->fk_societe) {
        print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->fk_societe.'">'.dol_escape_htmltag($obj->empresa_nom).'</a>';
    }
    print '</td>';
    print '<td>'.dol_trunc(dol_escape_htmltag($obj->descripcion), 60).'</td>';
    $ec = $estado_colors[$obj->estado] ?? 'badge-status0';
    $el = $estado_labels[$obj->estado] ?? $obj->estado;
    print '<td class="center"><span class="badge '.$ec.'">'.dol_escape_htmltag($el).'</span></td>';
    print '<td>'.dol_print_date($db->jdate($obj->fecha_alerta), 'dayhour').'</td>';
    print '<td class="right nowrap"><a href="alerta.php?id='.$obj->rowid.'">'.img_picto($langs->trans('BtnVerDetalle'), 'view').'</a></td>';
    print '</tr>';
    $i++;
}

print '</table>';
print '</div>';
print '<br>';

llxFooter();
