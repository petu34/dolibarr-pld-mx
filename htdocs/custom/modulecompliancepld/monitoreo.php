<?php
/* Copyright (C) 2026 Ouroboros
 * License GNU/GPL v3+
 */

$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && file_exists("../main.inc.php")) { $res = @include "../main.inc.php"; }
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php"; }
if (!$res) { die("Include of main fails"); }

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

require_once __DIR__.'/class/services/PLDReporteService.php';

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$reporteSvc     = new PLDReporteService($db);
$contadores     = $reporteSvc->getContadoresMonitoreo();
$clientesPEP    = $reporteSvc->getClientesPEP(20);
$clientesRiesgo = $reporteSvc->getClientesAltoRiesgo(20);
$opsFueraPerfil = $reporteSvc->getOperacionesFueraPerfil(20);
$logMonitoreo   = $reporteSvc->getLogMonitoreo(30);
$perfilesRiesgo = $reporteSvc->getResumenPerfilesRiesgo();

llxHeader('', $langs->trans("PLDMonitoreoDashboard"), '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-monitoreo');

print load_fiche_titre($langs->trans("PLDMonitoreoDashboard"), '', 'fa-eye');

// ---- CONTADORES ----
print '<div class="fichecenter" style="margin-bottom:12px">';
print '<div style="display:flex;flex-wrap:wrap;gap:12px;justify-content:center">';

$counters = array(
	array(
		'label' => $langs->trans("PLDClientesPEP"),
		'value' => $contadores['tiene_pep'],
		'picto' => 'fa-user-secret',
		'class' => $contadores['tiene_pep'] > 0 ? 'oddb' : '',
		'url'   => dol_buildpath('/custom/modulecompliancepld/beneficiarios_list.php', 1),
	),
	array(
		'label' => $langs->trans("PLDAltoRiesgo"),
		'value' => $contadores['alto_riesgo'],
		'picto' => 'fa-exclamation-triangle',
		'class' => $contadores['alto_riesgo'] > 0 ? 'oddb' : '',
		'url'   => '',
	),
	array(
		'label' => $langs->trans("PLDFueraPerfil"),
		'value' => $contadores['fuera_perfil'],
		'picto' => 'fa-chart-line',
		'class' => $contadores['fuera_perfil'] > 0 ? 'oddb' : '',
		'url'   => '',
	),
	array(
		'label' => $langs->trans("PLDMonitoreosHoy"),
		'value' => $contadores['monitoreos_hoy'],
		'picto' => 'fa-clock',
		'class' => '',
		'url'   => '',
	),
);

foreach ($counters as $c) {
	print '<div class="box-flex-item">';
	print '<a '.($c['url'] ? 'href="'.$c['url'].'"' : '').' class="boxstats">
		<span style="font-size: 20px" class="boxstats_'.$c['class'].' picto"><i class="fa '.$c['picto'].'"></i></span>
		<span class="boxstats_' . $c['class'] . ' amount">'.$c['value'].'</span>
		<span style="font-size: 13px" class="boxstats_' . $c['class'] . ' subtitle">'.$c['label'].'</span>
	</a>';
	print '</div>';
}

print '</div></div>';

// ---- SEMÁFORO DE RIESGO ----
if (!empty($perfilesRiesgo)) {
	print '<div class="fichecenter" style="margin-bottom:12px">';
	print '<table class="noborder centpercent"><tr class="liste_titre"><td colspan="4">'.$langs->trans("PLDSemaforoRiesgo").'</td></tr>';
	print '<tr class="liste_titre"><td>'.$langs->trans("PLDNivelRiesgo").'</td><td>'.$langs->trans("PLDClientes").'</td><td>'.$langs->trans("PLDOperaciones").'</td><td>'.$langs->trans("PLDMontoTotal").'</td></tr>';

	foreach ($perfilesRiesgo as $perfil) {
		$badgeClass = match($perfil->nivel_riesgo_perfil) {
			'critico' => 'badge-important',
			'alto' => 'badge-warning',
			'medio' => 'badge-info',
			default => 'badge-success',
		};
		$rowClass = match($perfil->nivel_riesgo_perfil) {
			'critico' => 'bg_imp',
			'alto' => 'bg_warning',
			default => '',
		};

		print '<tr class="'.$rowClass.'">';
		print '<td><span class="badge '.$badgeClass.'">'.$langs->trans('PLDRiesgo_'.$perfil->nivel_riesgo_perfil).'</span></td>';
		print '<td>'.$perfil->total.'</td>';
		print '<td>'.$perfil->total_ops.'</td>';
		print '<td>'.price($perfil->monto_total).'</td>';
		print '</tr>';
	}

	print '</table></div>';
}

// ---- CLIENTES PEP ----
if (!empty($clientesPEP)) {
	print '<div class="fichecenter" style="margin-bottom:12px">';
	print '<table class="noborder centpercent"><tr class="liste_titre"><td colspan="6">'.$langs->trans("PLDClientesPEP").' ('.$contadores['tiene_pep'].')</td></tr>';
	print '<tr class="liste_titre"><td>'.$langs->trans("PLDEmpresa").'</td><td>'.$langs->trans("PLDNivelRiesgo").'</td><td>'.$langs->trans("PLDNivelDiligencia").'</td><td>'.$langs->trans("PLDOperacionesPeriodo").'</td><td>'.$langs->trans("PLDMontoAcumulado").'</td><td>'.$langs->trans("PLDUltimaEvaluacion").'</td></tr>';

	foreach ($clientesPEP as $row) {
		$badgeClass = match($row->nivel_riesgo_perfil) {
			'critico' => 'badge-important',
			'alto' => 'badge-warning',
			'medio' => 'badge-info',
			default => 'badge-success',
		};
		$rowClass = match($row->nivel_riesgo_perfil) {
			'critico' => 'bg_imp',
			'alto' => 'bg_warning',
			default => '',
		};

		print '<tr class="'.$rowClass.'">';
		print '<td>'.dol_escape_htmltag($row->empresa_nom).'</td>';
		print '<td><span class="badge '.$badgeClass.'">'.$langs->trans('PLDRiesgo_'.$row->nivel_riesgo_perfil).'</span></td>';
		print '<td>'.$langs->trans('PLDDiligencia_'.$row->nivel_diligencia).'</td>';
		print '<td>'.$row->num_operaciones_periodo.'</td>';
		print '<td>'.price($row->monto_acumulado_periodo).'</td>';
		print '<td>'.dol_print_date($db->jdate($row->fecha_ultima_evaluacion), 'dayhour').'</td>';
		print '</tr>';
	}

	print '</table></div>';
}

// ---- CLIENTES ALTO RIESGO ----
if (!empty($clientesRiesgo)) {
	print '<div class="fichecenter" style="margin-bottom:12px">';
	print '<table class="noborder centpercent"><tr class="liste_titre"><td colspan="6">'.$langs->trans("PLDAltoRiesgo").' ('.$contadores['alto_riesgo'].')</td></tr>';
	print '<tr class="liste_titre"><td>'.$langs->trans("PLDEmpresa").'</td><td>'.$langs->trans("PLDNivelRiesgo").'</td><td>'.$langs->trans("PLDFrecuenciaMensual").'</td><td>'.$langs->trans("PLDPromedioMonto").'</td><td>'.$langs->trans("PLDMaxHistorico").'</td><td>'.$langs->trans("PLDMontoAcumulado").'</td></tr>';

	foreach ($clientesRiesgo as $row) {
		$badgeClass = match($row->nivel_riesgo_perfil) {
			'critico' => 'badge-important',
			'alto' => 'badge-warning',
			default => 'badge-info',
		};
		$rowClass = match($row->nivel_riesgo_perfil) {
			'critico' => 'bg_imp',
			'alto' => 'bg_warning',
			default => '',
		};

		print '<tr class="'.$rowClass.'">';
		print '<td>'.dol_escape_htmltag($row->empresa_nom).'</td>';
		print '<td><span class="badge '.$badgeClass.'">'.$langs->trans('PLDRiesgo_'.$row->nivel_riesgo_perfil).'</span></td>';
		print '<td>'.$row->frecuencia_mensual.'/mes</td>';
		print '<td>'.price($row->promedio_monto).'</td>';
		print '<td>'.price($row->max_monto_historico).'</td>';
		print '<td>'.price($row->monto_acumulado_periodo).'</td>';
		print '</tr>';
	}

	print '</table></div>';
}

// ---- OPERACIONES FUERA DE PERFIL ----
if (!empty($opsFueraPerfil)) {
	print '<div class="fichecenter" style="margin-bottom:12px">';
	print '<table class="noborder centpercent"><tr class="liste_titre"><td colspan="7">'.$langs->trans("PLDFueraPerfil").' ('.$contadores['fuera_perfil'].')</td></tr>';
	print '<tr class="liste_titre"><td>'.$langs->trans("PLDEmpresa").'</td><td>'.$langs->trans("PLDFolioInterno").'</td><td>'.$langs->trans("PLDMontoMXN").'</td><td>'.$langs->trans("PLDZScore").'</td><td>'.$langs->trans("PLDVariacionPct").'</td><td>'.$langs->trans("PLDNivelRiesgo").'</td><td>'.$langs->trans("PLDFechaDeteccion").'</td></tr>';

	foreach ($opsFueraPerfil as $row) {
		$badgeClass = match($row->nivel_riesgo_detectado) {
			'critico' => 'badge-important',
			'alto' => 'badge-warning',
			'medio' => 'badge-info',
			default => 'badge-success',
		};

		print '<tr>';
		print '<td>'.dol_escape_htmltag($row->empresa_nom).'</td>';
		print '<td>'.$row->folio_interno.'</td>';
		print '<td>'.price($row->monto_mxn).'</td>';
		print '<td>'.number_format($row->z_score, 2).'</td>';
		print '<td>'.number_format($row->variacion_porcentual, 1).'%</td>';
		print '<td><span class="badge '.$badgeClass.'">'.$langs->trans('PLDRiesgo_'.$row->nivel_riesgo_detectado).'</span></td>';
		print '<td>'.dol_print_date($db->jdate($row->fecha_deteccion), 'dayhour').'</td>';
		print '</tr>';
	}

	print '</table></div>';
}

// ---- LOG DE MONITOREO ----
if (!empty($logMonitoreo)) {
	print '<div class="fichecenter" style="margin-bottom:12px">';
	print '<table class="noborder centpercent"><tr class="liste_titre"><td colspan="6">'.$langs->trans("PLDLogMonitoreo").' ('.$contadores['monitoreos_hoy'].' '.$langs->trans("PLDHoy").')</td></tr>';
	print '<tr class="liste_titre"><td>'.$langs->trans("PLDFecha").'</td><td>'.$langs->trans("PLDTipoEvaluacion").'</td><td>'.$langs->trans("PLDEmpresa").'</td><td>'.$langs->trans("PLDResultado").'</td><td>'.$langs->trans("PLDNivelRiesgo").'</td><td>'.$langs->trans("PLDDetalle").'</td></tr>';

	foreach ($logMonitoreo as $row) {
		$resultClass = match($row->resultado) {
			'anomalia' => 'warning',
			'alerta', 'escalado' => 'error',
			default => 'success',
		};
		$badgeClass = match($row->nivel_riesgo_detectado) {
			'critico' => 'badge-important',
			'alto' => 'badge-warning',
			'medio' => 'badge-info',
			default => '',
		};

		print '<tr>';
		print '<td>'.dol_print_date($db->jdate($row->datec), 'dayhour').'</td>';
		print '<td>'.$langs->trans('PLDTipoEval_'.$row->tipo_evaluacion).'</td>';
		print '<td>'.($row->empresa_nom ? dol_escape_htmltag($row->empresa_nom) : '—').'</td>';
		print '<td><span class="badge badge-'.$resultClass.'">'.$langs->trans('PLDResultado_'.$row->resultado).'</span></td>';
		print '<td>'.($row->nivel_riesgo_detectado ? '<span class="badge '.$badgeClass.'">'.$langs->trans('PLDRiesgo_'.$row->nivel_riesgo_detectado).'</span>' : '—').'</td>';
		print '<td>'.dol_trunc($row->detalle, 80).'</td>';
		print '</tr>';
	}

	print '</table></div>';
}

llxFooter();
