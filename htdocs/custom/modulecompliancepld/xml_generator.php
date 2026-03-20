<?php
/* Copyright (C) 2026 Ouroboros — License GNU/GPL v3+ */

/**
 * @file    modulecompliancepld/xml_generator.php
 * @brief   Generador de XML de avisos PLD para el portal SPPLD del SAT
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once __DIR__.'/class/pldxmlgenerator.class.php';
require_once __DIR__.'/class/pldefirmaintegration.class.php';
require_once __DIR__.'/class/pldaviso.class.php';

$langs->loadLangs(array("modulecompliancepld@modulecompliancepld"));

if (!$user->hasRight('modulecompliancepld', 'write')) {
	accessforbidden();
}

$action     = GETPOST('action', 'aZ09');
$mes_input  = GETPOST('mes_reportado', 'alpha');   // YYYYMM desde el form
$firmar     = GETPOST('firmar', 'int');

// Derivar mes por defecto: mes anterior completo
$mes_default = date('Ym', mktime(0, 0, 0, (int)date('m') - 1, 1, (int)date('Y')));

$form = new Form($db);

// -----------------------------------------------------------------------
// ACCIONES
// -----------------------------------------------------------------------

$xml_resultado = '';
$xml_filepath  = '';
$msgs_ok       = array();
$msgs_err      = array();

if ($action == 'generar' && $mes_input) {
	$mes_reportado = preg_replace('/[^0-9]/', '', $mes_input);
	if (strlen($mes_reportado) != 6) {
		$msgs_err[] = "Formato de mes incorrecto. Use YYYYMM (ej: 202602).";
	} else {
		$generator = new PLDXMLGenerator($db);
		$xml_content = $generator->generarXMLMensual($mes_reportado);

		if ($xml_content === false) {
			$msgs_err[] = "Error al generar XML: ".$generator->error;
		} else {
			// Firma opcional
			if ($firmar) {
				$efirma = new PLDEFirmaIntegration($db);
				$firma = $efirma->firmarXML($xml_content);
				if ($firma === false) {
					$msgs_err[] = "Error al firmar: ".$efirma->error." (el XML no firmado sigue disponible)";
				} else {
					$xml_content = $efirma->incrustarSello($xml_content, $firma);
					$msgs_ok[] = "XML firmado correctamente con e.firma. Certificado #".$firma['numero_certificado'];
				}
			}

			// Guardar en disco
			$filepath = $generator->guardarXML($xml_content, $mes_reportado, $user);
			if ($filepath === false) {
				$msgs_err[] = "Error al guardar el archivo: ".$generator->error;
			} else {
				$xml_filepath = $filepath;
				$msgs_ok[] = "XML generado: ".basename($filepath);
			}

			$xml_resultado = $xml_content;
		}
	}
}

if ($action == 'descargar' && GETPOST('filepath', 'alpha')) {
	// Descarga segura del XML generado
	$filepath = GETPOST('filepath', 'alpha');
	// Validar que el path esté dentro del directorio permitido
	$dir_allowed = DOL_DATA_ROOT.'/modulecompliancepld/xml';
	$real = realpath($filepath);
	if ($real && strpos($real, realpath($dir_allowed)) === 0 && file_exists($real)) {
		$filename = basename($real);
		header('Content-Type: application/xml; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Length: '.filesize($real));
		readfile($real);
		exit;
	} else {
		$msgs_err[] = "Ruta de archivo no válida.";
	}
}

// -----------------------------------------------------------------------
// VISTA
// -----------------------------------------------------------------------

$title = $langs->trans('PLDAvisosGenerar');
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-xml-generator');

print load_fiche_titre($title, '', 'fa-file-code');

// Mensajes
foreach ($msgs_ok  as $m) { setEventMessages($m, null, 'mesgs'); }
foreach ($msgs_err as $m) { setEventMessages($m, null, 'errors'); }

// Estado de e.firma
$cert_path  = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH');
$key_path   = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH');
$rfc_sujeto = getDolGlobalString('MODULECOMPLIANCEPLD_RFC_SUJETO');
$efirma_ok  = !empty($cert_path) && file_exists($cert_path)
           && !empty($key_path)  && file_exists($key_path);

// ---- Panel de estado ----
print '<div class="fichecenter">';
print '<table class="border centpercent">';
print '<tr class="oddeven"><td class="titlefield" style="width:220px">'.$langs->trans('PLDRFCSujeto').'</td>';
print '<td>'.($rfc_sujeto ? dol_escape_htmltag($rfc_sujeto) : '<span class="error">No configurado — ir a <a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/admin/setup.php">Ajustes</a></span>').'</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans('PLDEFirmaEstado').'</td><td>';
if ($efirma_ok) {
	print '<span class="badge badge-status4 badge-status">'.img_picto('', 'check', 'class="pictofixedwidth"').' Certificados configurados</span>';
} else {
	print '<span class="badge badge-status8 badge-status">'.img_picto('', 'warning', 'class="pictofixedwidth"').' e.firma no configurada — XML sin firma</span>';
}
print '</td></tr>';
print '</table></div><br>';

// ---- Formulario de generación ----
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="generar">';

print '<div class="fichecenter">';
print '<table class="border centpercent">';

// Mes reportado
print '<tr><td class="titlefield fieldrequired">'.$langs->trans('PLDMesReportado').'</td>';
print '<td><input type="text" name="mes_reportado" class="minwidth100" maxlength="6" placeholder="YYYYMM" value="'.dol_escape_htmltag($mes_input ?: $mes_default).'"></td></tr>';

// Checkbox firma
print '<tr><td>'.$langs->trans('PLDFirmarXML').'</td>';
print '<td><input type="checkbox" name="firmar" value="1"'.($efirma_ok ? '' : ' disabled title="Configure la e.firma primero"').'>';
if (!$efirma_ok) {
	print ' <small class="opacitymedium">(requiere e.firma configurada en <a href="'.DOL_URL_ROOT.'/custom/modulecompliancepld/admin/setup.php">Ajustes</a>)</small>';
}
print '</td></tr>';

print '</table>';
print '</div>';

print '<div class="center" style="margin:16px 0">';
print '<input type="submit" class="butAction" value="'.$langs->trans('PLDBtnGenerarXML').'">';
print '</div>';
print '</form>';

// ---- Resultado ----
if ($xml_resultado) {
	print '<div class="div-table-responsive">';
	print load_fiche_titre($langs->trans('PLDXMLGenerado'), '', 'fa-code');

	// Botón de descarga
	if ($xml_filepath) {
		print '<p><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=descargar&filepath='.urlencode($xml_filepath).'&token='.newToken().'">';
		print img_picto('', 'download', 'class="pictofixedwidth"').' '.$langs->trans('PLDDescargarXML').'</a></p>';
	}

	// Vista previa del XML (primeras 80 líneas)
	$preview_lines = array_slice(explode("\n", htmlspecialchars($xml_resultado, ENT_QUOTES, 'UTF-8')), 0, 80);
	print '<pre style="background:#f8f8f8;border:1px solid #ddd;padding:12px;font-size:11px;max-height:400px;overflow:auto">';
	print implode("\n", $preview_lines);
	if (substr_count($xml_resultado, "\n") > 80) {
		print "\n<em>... (truncado para visualización)</em>";
	}
	print '</pre>';
	print '</div>';
}

// ---- Historial de XMLs generados ----
$dir_xml = DOL_DATA_ROOT.'/modulecompliancepld/xml';
if (is_dir($dir_xml)) {
	$archivos = glob($dir_xml.'/PLD_VEH_*.xml');
	if ($archivos) {
		rsort($archivos); // más reciente primero
		print '<br>';
		print load_fiche_titre($langs->trans('PLDHistorialXML'), '', 'fa-history');
		print '<div class="div-table-responsive">';
		print '<table class="tagtable nobottomiftotal liste">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('PLDArchivoXML').'</td>';
		print '<td>'.$langs->trans('Date').'</td>';
		print '<td>'.$langs->trans('Size').'</td>';
		print '<td></td>';
		print '</tr>';
		foreach (array_slice($archivos, 0, 20) as $f) {
			$fname = basename($f);
			$fsize = number_format(filesize($f) / 1024, 1).' KB';
			$fdate = dol_print_date(filemtime($f), 'dayhour');
			print '<tr class="oddeven">';
			print '<td>'.dol_escape_htmltag($fname).'</td>';
			print '<td>'.$fdate.'</td>';
			print '<td>'.$fsize.'</td>';
			print '<td><a href="'.$_SERVER['PHP_SELF'].'?action=descargar&filepath='.urlencode($f).'&token='.newToken().'">';
			print img_picto('', 'download').'</a></td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
	}
}

llxFooter();
$db->close();
