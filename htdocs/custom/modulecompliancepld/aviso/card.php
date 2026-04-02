<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/aviso/card.php
 * @brief   Ficha de detalle y cambio de estado del aviso SAT
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php"))  { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php"; }
if (!$res && file_exists("../../../../main.inc.php")) { $res = @include "../../../../main.inc.php"; }
if (!$res) { die("Include of main fails"); }

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once __DIR__.'/../class/pldaviso.class.php';
require_once __DIR__.'/../class/pldxmlgenerator.class.php';
require_once __DIR__.'/../class/pldefirmaintegration.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

$id              = GETPOST('id', 'int');
$action          = GETPOST('action', 'aZ09');
$cancel          = GETPOST('cancel', 'alpha');
$fk_op_agregar   = GETPOST('fk_operacion_agregar', 'int');
$fk_op_quitar    = GETPOST('fk_operacion_quitar', 'int');

if (!$user->hasRight('modulecompliancepld', 'read')) {
	accessforbidden();
}

$aviso = new PLDAviso($db);

if ($id > 0) {
	$result = $aviso->fetch($id);
	if ($result <= 0) {
		setEventMessages($langs->trans('RecordNotFound'), null, 'errors');
		header("Location: ../avisos_list.php");
		exit;
	}
}

if ($cancel) {
	header("Location: ../avisos_list.php");
	exit;
}

// ── Acciones de cambio de estado ──────────────────────────────────────────────

if ($action == 'setpendiente' && $user->hasRight('modulecompliancepld', 'write') && $id > 0) {
	$aviso->estado = 'pendiente';
	$result = $aviso->update($user);
	if ($result > 0) {
		setEventMessages($langs->trans('EstadoActualizado'), null, 'mesgs');
	} else {
		setEventMessages($aviso->error, $aviso->errors, 'errors');
	}
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"])."?id=".$id);
	exit;
}

if ($action == 'setborrador' && $user->hasRight('modulecompliancepld', 'write') && $id > 0) {
	$aviso->estado = 'borrador';
	$result = $aviso->update($user);
	if ($result > 0) {
		setEventMessages($langs->trans('EstadoActualizado'), null, 'mesgs');
	} else {
		setEventMessages($aviso->error, $aviso->errors, 'errors');
	}
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"])."?id=".$id);
	exit;
}

if ($action == 'setpresentado' && $user->hasRight('modulecompliancepld', 'write') && $id > 0) {
	$folio    = GETPOST('folio_sat', 'alphanohtml');
	$fecha_ts = dol_mktime(0, 0, 0, GETPOST('fecha_presentacionmonth', 'int'), GETPOST('fecha_presentacionday', 'int'), GETPOST('fecha_presentacionyear', 'int'));
	if (empty($folio) || empty($fecha_ts)) {
		setEventMessages($langs->trans('ErrorFolioFechaRequeridos'), null, 'errors');
	} else {
		$aviso->folio_sat           = $folio;
		$aviso->fecha_presentacion  = $db->idate($fecha_ts);
		$aviso->fk_user_presento    = $user->id;
		$aviso->estado              = 'presentado';
		$aviso->presentado          = 1;
		$result = $aviso->update($user);
		if ($result > 0) {
			setEventMessages($langs->trans('AcuseRegistrado'), null, 'mesgs');
		} else {
			setEventMessages($aviso->error, $aviso->errors, 'errors');
		}
	}
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"])."?id=".$id);
	exit;
}

if ($action == 'setcancelado' && $user->hasRight('modulecompliancepld', 'write') && $id > 0) {
	$obs = GETPOST('observaciones', 'restricthtml');
	if (!empty($obs)) {
		$aviso->observaciones = $obs;
	}
	$aviso->estado = 'cancelado';
	$result = $aviso->update($user);
	if ($result > 0) {
		setEventMessages($langs->trans('EstadoActualizado'), null, 'mesgs');
	} else {
		setEventMessages($aviso->error, $aviso->errors, 'errors');
	}
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"])."?id=".$id);
	exit;
}

// ── Vincular operación al aviso ───────────────────────────────────────────────
if ($action == 'agregar_operacion' && $user->hasRight('modulecompliancepld', 'write') && $id > 0 && $fk_op_agregar > 0) {
	$result = $aviso->agregarOperacion($fk_op_agregar, $user);
	if ($result > 0) {
		setEventMessages($langs->trans('OperacionVinculada'), null, 'mesgs');
	} else {
		setEventMessages(implode(', ', $aviso->errors), null, 'errors');
	}
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"])."?id=".$id);
	exit;
}

// ── Quitar operación del aviso ────────────────────────────────────────────────
if ($action == 'quitar_operacion' && $user->hasRight('modulecompliancepld', 'write') && $id > 0 && $fk_op_quitar > 0) {
	$result = $aviso->quitarOperacion($fk_op_quitar, $user);
	if ($result > 0) {
		setEventMessages($langs->trans('OperacionDesvinculada'), null, 'mesgs');
	} else {
		setEventMessages(implode(', ', $aviso->errors), null, 'errors');
	}
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"])."?id=".$id);
	exit;
}

// ── Generar XML para este aviso ───────────────────────────────────────────────
if ($action == 'generar_xml' && $user->hasRight('modulecompliancepld', 'generate') && $id > 0) {
	$aviso->fetchOperaciones();
	$ids_ops = array_map(fn($o) => (int)$o->rowid, $aviso->operaciones ?? []);

	if (empty($ids_ops)) {
		setEventMessages($langs->trans('PLDAvisoSinOperaciones'), null, 'errors');
	} else {
		$generator = new PLDXMLGenerator($db);
		$xml = $generator->generarXMLMensual($aviso->mes_reportado, $ids_ops);

		if ($xml === false) {
			setEventMessages(implode(', ', $generator->errors), null, 'errors');
		} else {
			$firmar = GETPOST('firmar_xml', 'int');
			if ($firmar) {
				$efirma = new PLDEFirmaIntegration($db);
				$xml = $efirma->firmarXMLContent($xml) ?: $xml;
				if ($efirma->error) {
					setEventMessages($efirma->error, null, 'warnings');
				}
			}

			$ruta = $generator->guardarXML($xml, $aviso->mes_reportado, $user);

			if ($ruta) {
				$aviso->archivo_xml_ruta = $ruta;
				$aviso->archivo_xml_hash = $aviso->calcularHashXML($xml);
				$aviso->fecha_generacion_xml = $db->idate(dol_now());
				if ($aviso->estado == 'borrador') {
					$aviso->estado = 'pendiente';
				}
				$aviso->update($user);
				setEventMessages($langs->trans('XMLGeneradoCorrectamente'), null, 'mesgs');
			} else {
				setEventMessages($generator->error, null, 'errors');
			}
		}
	}
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"])."?id=".$id);
	exit;
}

if ($action == 'update' && $user->hasRight('modulecompliancepld', 'write') && $id > 0) {
	$aviso->observaciones = GETPOST('observaciones', 'restricthtml');
	if ($aviso->estado != 'presentado') {
		$folio = GETPOST('folio_sat', 'alphanohtml');
		if (!empty($folio)) {
			$aviso->folio_sat = $folio;
		}
		$fecha_ts = dol_mktime(0, 0, 0, GETPOST('fecha_presentacionmonth', 'int'), GETPOST('fecha_presentacionday', 'int'), GETPOST('fecha_presentacionyear', 'int'));
		if (!empty($fecha_ts)) {
			$aviso->fecha_presentacion = $db->idate($fecha_ts);
		}
	}
	$result = $aviso->update($user);
	if ($result > 0) {
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
	} else {
		setEventMessages($aviso->error, $aviso->errors, 'errors');
	}
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"])."?id=".$id);
	exit;
}

// ── Vista ─────────────────────────────────────────────────────────────────────

$form = new Form($db);

$tipo_labels = array(
	'MEN' => $langs->trans('PLDTipoAvisoMensual'),
	'24H' => $langs->trans('PLDTipoAviso24hrs'),
	'ACU' => $langs->trans('PLDTipoAvisoAcumulado'),
);
$estado_colors = array(
	'borrador'   => 'badge-status0',
	'pendiente'  => 'badge-status1',
	'presentado' => 'badge-status4',
	'cancelado'  => 'badge-status9',
);
$estado_labels = array(
	'borrador'   => $langs->trans('EstadoBorrador'),
	'pendiente'  => $langs->trans('EstadoPendiente'),
	'presentado' => $langs->trans('EstadoPresentado'),
	'cancelado'  => $langs->trans('EstadoCancelado'),
);

$title = $langs->trans('PLDAvisoDetalle').' — '.dol_escape_htmltag($aviso->referencia_aviso ?: '#'.$aviso->id);
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-aviso-card');

print load_fiche_titre($title, '', 'fa-shield');

// Mensajes de acción
dol_htmloutput_events();

// ── Ficha de datos ────────────────────────────────────────────────────────────
print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

print '<table class="border centpercent tableforfield">';

// Tipo de aviso
print '<tr>';
print '<td class="titlefield">'.$langs->trans('ColTipoAviso').'</td>';
print '<td>'.dol_escape_htmltag($tipo_labels[$aviso->tipo_aviso] ?? $aviso->tipo_aviso).'</td>';
print '</tr>';

// Mes reportado
print '<tr>';
print '<td>'.$langs->trans('ColMesReportado').'</td>';
print '<td>'.dol_escape_htmltag($aviso->mes_reportado).'</td>';
print '</tr>';

// Período
print '<tr>';
print '<td>'.$langs->trans('LblPeriodo').'</td>';
$periodo = '';
if ($aviso->fecha_inicio_periodo) {
	$periodo = dol_print_date($db->jdate($aviso->fecha_inicio_periodo), 'day');
}
if ($aviso->fecha_fin_periodo) {
	$periodo .= ' — '.dol_print_date($db->jdate($aviso->fecha_fin_periodo), 'day');
}
print '<td>'.dol_escape_htmltag($periodo).'</td>';
print '</tr>';

// Referencia
print '<tr>';
print '<td>'.$langs->trans('ColReferenciaAviso').'</td>';
print '<td>'.dol_escape_htmltag($aviso->referencia_aviso).'</td>';
print '</tr>';

// N° operaciones
print '<tr>';
print '<td>'.$langs->trans('TotalOperaciones').'</td>';
print '<td>'.((int)$aviso->numero_operaciones).'</td>';
print '</tr>';

// Monto total
print '<tr>';
print '<td>'.$langs->trans('MontoTotal').'</td>';
print '<td>'.price($aviso->monto_total_operaciones).'</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<div class="fichehalfright">';
print '<table class="border centpercent tableforfield">';

// Estado (badge)
print '<tr>';
print '<td class="titlefield">'.$langs->trans('ColEstado').'</td>';
$ec = $estado_colors[$aviso->estado] ?? 'badge-status0';
$el = $estado_labels[$aviso->estado] ?? dol_escape_htmltag($aviso->estado);
print '<td><span class="badge '.$ec.'">'.$el.'</span></td>';
print '</tr>';

// XML generado
print '<tr>';
print '<td>'.$langs->trans('LblXMLGenerado').'</td>';
print '<td>';
if (!empty($aviso->archivo_xml_ruta)) {
	$fecha_xml = $aviso->fecha_generacion_xml ? dol_print_date($db->jdate($aviso->fecha_generacion_xml), 'dayhour') : '';
	print dol_escape_htmltag($fecha_xml);
	print ' <a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/xml_generator.php?action=descargar&filepath='.urlencode($aviso->archivo_xml_ruta).'&token='.newToken().'" class="button smallpaddingimp">'.img_picto($langs->trans('Download'), 'download').' XML</a>';
} else {
	print '<span class="opacitymedium">'.$langs->trans('NoXMLGenerado').'</span>';
}
print '</td>';
print '</tr>';

// Folio SAT
print '<tr>';
print '<td>'.$langs->trans('LblFolioSAT').'</td>';
print '<td>';
if ($aviso->estado == 'presentado') {
	print dol_escape_htmltag($aviso->folio_sat);
} else {
	print '<input type="text" id="folio_sat_inline" name="folio_sat_inline" class="flat" value="'.dol_escape_htmltag($aviso->folio_sat).'" readonly>';
}
print '</td>';
print '</tr>';

// Fecha presentación
print '<tr>';
print '<td>'.$langs->trans('LblFechaPresentacion').'</td>';
print '<td>';
if ($aviso->fecha_presentacion) {
	print dol_print_date($db->jdate($aviso->fecha_presentacion), 'day');
} else {
	print '<span class="opacitymedium">—</span>';
}
print '</td>';
print '</tr>';

// Fecha creación
print '<tr>';
print '<td>'.$langs->trans('DateCreation').'</td>';
print '<td>'.dol_print_date($aviso->datec, 'dayhour').'</td>';
print '</tr>';

print '</table>';
print '</div>';
print '</div>'; // fichecenter

// Observaciones
print '<div class="clearboth"></div>';
print '<br>';
print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<table class="border centpercent tableforfield">';
print '<tr>';
print '<td class="titlefield" style="width:20%">'.$langs->trans('Observations').'</td>';
print '<td><textarea name="observaciones" class="flat centpercent" rows="3">'.dol_escape_htmltag($aviso->observaciones, 1).'</textarea></td>';
print '</tr>';
print '</table>';
if ($user->hasRight('modulecompliancepld', 'write') && !in_array($aviso->estado, array('presentado', 'cancelado'))) {
	print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Save').'"></div>';
}
print '</form>';

// ── Sección: Operaciones vinculadas al aviso ──────────────────────────────────
$aviso->fetchOperaciones();
$ops_vinculadas = $aviso->operaciones ?? [];

print '<br>';
print load_fiche_titre($langs->trans('PLDOperacionesDelAviso'), '', '');

// Tabla de operaciones vinculadas
if (!empty($ops_vinculadas)) {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Folio').'</td>';
	print '<td>'.$langs->trans('Date').'</td>';
	print '<td class="right">'.$langs->trans('Amount').'</td>';
	print '<td>'.$langs->trans('Status').'</td>';
	if ($user->hasRight('modulecompliancepld', 'write') && !in_array($aviso->estado, ['presentado', 'cancelado'])) {
		print '<td></td>';
	}
	print '</tr>';
	foreach ($ops_vinculadas as $op) {
		print '<tr class="oddeven">';
		print '<td><a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/operacion.php?id='.(int)$op->rowid.'">'.dol_escape_htmltag($op->folio_interno ?: '#'.$op->rowid).'</a></td>';
		print '<td>'.dol_print_date($db->jdate($op->fecha_operacion), 'day').'</td>';
		print '<td class="right">'.price($op->monto_mxn).'</td>';
		print '<td>'.dol_escape_htmltag($op->estado).'</td>';
		if ($user->hasRight('modulecompliancepld', 'write') && !in_array($aviso->estado, ['presentado', 'cancelado'])) {
			print '<td class="right"><a href="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'&action=quitar_operacion&fk_operacion_quitar='.(int)$op->rowid.'&token='.newToken().'" class="redt">'.img_picto($langs->trans('Remove'), 'delete').'</a></td>';
		}
		print '</tr>';
	}
	print '</table>';
} else {
	print '<p class="opacitymedium">'.$langs->trans('PLDAvisoSinOperaciones').'</p>';
}

// Selector para agregar operaciones (solo en estados editables)
if ($user->hasRight('modulecompliancepld', 'write') && !in_array($aviso->estado, ['presentado', 'cancelado'])) {
	// Buscar operaciones disponibles del mismo mes que no estén en otro aviso
	$ids_ya = array_map(fn($o) => (int)$o->rowid, $ops_vinculadas);
	$sql_disp = "SELECT o.rowid, o.folio_interno, o.monto_mxn, o.fecha_operacion";
	$sql_disp .= " FROM ".MAIN_DB_PREFIX."pld_operacion o";
	$sql_disp .= " WHERE o.mes_reportado = '".$db->escape($aviso->mes_reportado)."'";
	$sql_disp .= " AND o.requiere_aviso = 1";
	$sql_disp .= " AND o.aviso_presentado = 0";
	$sql_disp .= " AND o.estado != 'cancelada'";
	if (!empty($ids_ya)) {
		$sql_disp .= " AND o.rowid NOT IN (".implode(',', $ids_ya).")";
	}
	// Excluir las que ya tienen aviso distinto
	$sql_disp .= " AND (o.fk_pld_aviso IS NULL OR o.fk_pld_aviso = ".(int)$aviso->id.")";
	$sql_disp .= " ORDER BY o.fecha_operacion ASC";

	$res_disp = $db->query($sql_disp);
	$disponibles = [];
	if ($res_disp) {
		while ($row = $db->fetch_object($res_disp)) {
			$disponibles[] = $row;
		}
		$db->free($res_disp);
	}

	if (!empty($disponibles)) {
		print '<br>';
		print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="agregar_operacion">';
		print '<select name="fk_operacion_agregar" class="flat minwidth250">';
		foreach ($disponibles as $dop) {
			$label = ($dop->folio_interno ?: '#'.$dop->rowid).' — '.price($dop->monto_mxn);
			print '<option value="'.(int)$dop->rowid.'">'.dol_escape_htmltag($label).'</option>';
		}
		print '</select> ';
		print '<input type="submit" class="button smallpaddingimp" value="'.$langs->trans('PLDAgregarOperacion').'">';
		print '</form>';
	}

	// Botón Generar XML (solo si hay operaciones vinculadas)
	if (!empty($ops_vinculadas)) {
		print '<br>';
		print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="generar_xml">';
		$efirma_cert = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH');
		if (!empty($efirma_cert) && file_exists($efirma_cert)) {
			print '<label><input type="checkbox" name="firmar_xml" value="1" checked> '.$langs->trans('PLDFirmarConEfirma').'</label> ';
		}
		print '<input type="submit" class="butAction" value="'.$langs->trans('PLDGenerarXML').'">';
		print '</form>';
	}
}

print '<br>';

// ── Form inline "Registrar Acuse SAT" (solo visible cuando estado = pendiente) ─
if ($aviso->estado == 'pendiente' && $user->hasRight('modulecompliancepld', 'write')) {
	print '<br>';
	print '<div id="form-acuse" style="border:1px solid #ccc;padding:12px;max-width:480px;background:#f9f9f9;">';
	print '<h3 style="margin-top:0">'.$langs->trans('BtnRegistrarAcuse').'</h3>';
	print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setpresentado">';
	print '<table class="noborder">';
	print '<tr><td>'.$langs->trans('LblFolioSAT').'</td>';
	print '<td><input type="text" name="folio_sat" class="flat minwidth200" required></td></tr>';
	print '<tr><td>'.$langs->trans('LblFechaAcuse').'</td>';
	print '<td>'.$form->selectDate('', 'fecha_presentacion', 0, 0, 0, 'formacuse', 1, 1).'</td></tr>';
	print '</table>';
	print '<br><input type="submit" class="button" value="'.$langs->trans('BtnRegistrarAcuse').'">';
	print ' <a class="button button-cancel" href="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'">'.$langs->trans('Cancel').'</a>';
	print '</form>';
	print '</div>';
}

// ── Botones de cambio de estado ───────────────────────────────────────────────
print '<div class="tabsAction">';

if ($user->hasRight('modulecompliancepld', 'write')) {
	if ($aviso->estado == 'borrador') {
		print dolGetButtonAction('', $langs->trans('BtnMarcarEnviado'), 'default', dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'&action=setpendiente&token='.newToken(), '', true);
		print dolGetButtonAction('', $langs->trans('BtnCancelarAviso'), 'delete', dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'&action=setcancelado&token='.newToken(), '', true);
	} elseif ($aviso->estado == 'pendiente') {
		// El form de acuse ya está arriba — scroll a él
		print '<a class="butAction" href="#form-acuse">'.$langs->trans('BtnRegistrarAcuse').'</a>';
		print dolGetButtonAction('', $langs->trans('BtnVolverBorrador'), 'default', dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'&action=setborrador&token='.newToken(), '', true);
		print dolGetButtonAction('', $langs->trans('BtnCancelarAviso'), 'delete', dol_escape_htmltag($_SERVER["PHP_SELF"]).'?id='.$aviso->id.'&action=setcancelado&token='.newToken(), '', true);
	}
}

// Botón volver a lista
print dolGetButtonAction('', $langs->trans('BackToList'), 'default', DOL_URL_ROOT.'/custom/modulecompliancepld/avisos_list.php', '', true);

print '</div>';

// ── Preview XML ───────────────────────────────────────────────────────────────
if (!empty($aviso->archivo_xml_ruta)) {
	$dir_allowed = DOL_DATA_ROOT.'/modulecompliancepld/xml';
	$real        = realpath($aviso->archivo_xml_ruta);
	if ($real && strpos($real, realpath($dir_allowed)) === 0 && file_exists($real)) {
		$xml_content = file_get_contents($real);
		if ($xml_content !== false) {
			print '<br>';
			print load_fiche_titre($langs->trans('PLDXMLGenerado'), '', 'fa-code');
			$preview_lines = array_slice(explode("\n", htmlspecialchars($xml_content, ENT_QUOTES, 'UTF-8')), 0, 80);
			print '<pre style="background:#f8f8f8;border:1px solid #ddd;padding:12px;font-size:11px;max-height:400px;overflow:auto">';
			print implode("\n", $preview_lines);
			if (substr_count($xml_content, "\n") > 80) {
				print "\n<em>... (truncado para visualización)</em>";
			}
			print '</pre>';
		}
	}
}

llxFooter();
$db->close();
