<?php
/* Copyright (C) 2026 Ouroboros
 * License GNU/GPL v3+
 */

/**
 * @file    modulecompliancepld/admin/setup.php
 * @brief   Configuración del módulo Compliance PLD México
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
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php"; }
if (!$res) { die("Include of main fails"); }

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once '../lib/modulecompliancepld.lib.php';

$langs->loadLangs(array("admin", "modulecompliancepld@modulecompliancepld"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$error  = 0;

/*
 * Acciones
 */
if ($action == 'update') {
	$params = array(
		'MODULECOMPLIANCEPLD_UMA_VALOR'            => array('type' => 'float',  'default' => 117.31),
		'MODULECOMPLIANCEPLD_UMA_ANIO'             => array('type' => 'int',    'default' => 2026),
		'MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO'     => array('type' => 'float',  'default' => 377778.20),
		'MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO'     => array('type' => 'float',  'default' => 117310.00),
		'MODULECOMPLIANCEPLD_UMBRAL_ACUMULADO_6M'  => array('type' => 'float',  'default' => 500000.00),
		'MODULECOMPLIANCEPLD_DIAS_ALERTA_ID'       => array('type' => 'int',    'default' => 30),
		'MODULECOMPLIANCEPLD_OFICIAL_CUMPLIMIENTO' => array('type' => 'int',    'default' => 0),
		'MODULECOMPLIANCEPLD_PERIODO_CONSERVACION' => array('type' => 'int',    'default' => 10),
		'MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE' => array('type' => 'chaine', 'default' => 'VIII'),
		// DOF 27/03/2026 — Art. 6 (monto IVA)
		'MODULECOMPLIANCEPLD_IVA_DEFAULT'          => array('type' => 'chaine', 'default' => '0.16'),
		// DOF 27/03/2026 — Art. 45 Bis (PEPs)
		'MODULECOMPLIANCEPLD_PEP_ENDPOINT'         => array('type' => 'chaine', 'default' => ''),
		// DOF 27/03/2026 — Art. 7 Bis (operaciones intentadas — pendiente XSD SAT)
		'MODULECOMPLIANCEPLD_AVISOS_INTENTADAS_ACTIVO' => array('type' => 'int', 'default' => 0),
		// Sujeto obligado y e.firma (la contraseña se maneja por separado, cifrada)
		'MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH'     => array('type' => 'chaine', 'default' => ''),
		'MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH'      => array('type' => 'chaine', 'default' => ''),
	);

	foreach ($params as $constname => $info) {
		$val = GETPOST($constname, 'alpha');
		if ($info['type'] == 'float') {
			$val = (float) str_replace(',', '.', $val);
		} elseif ($info['type'] == 'int') {
			$val = (int) $val;
		}
		$resset = dolibarr_set_const($db, $constname, $val, $info['type'], 0, '', $conf->entity);
		if ($resset <= 0) {
			$error++;
		}
	}

	// Contraseña e.firma — cifrada con dolEncrypt(); solo se guarda si se envía un nuevo valor
	$efirma_pass_new = GETPOST('MODULECOMPLIANCEPLD_EFIRMA_PASSWORD', 'nohtml');
	if (!empty($efirma_pass_new)) {
		$resset = dolibarr_set_const($db, 'MODULECOMPLIANCEPLD_EFIRMA_PASSWORD', dolEncrypt($efirma_pass_new), 'chaine', 0, '', $conf->entity);
		if ($resset <= 0) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorSavingSetup"), null, 'errors');
	}
}

// ── Acción: cargar datos de prueba ────────────────────────────────────────────
if ($action == 'load_seed' && $user->admin) {
	// Ejecutar seed como función include con $db y $user ya listos
	// El seed usa import_key='SEED_PLD_TEST' en todos sus INSERT
	$seed_file = __DIR__.'/../scripts/seed_datos_prueba.php';
	if (file_exists($seed_file)) {
		// El seed espera ser ejecutado en contexto CLI con $db y $user,
		// lo incluimos desactivando la salida directa al buffer
		ob_start();
		try {
			// Redefinir NOSESSION/NOLOGIN si no están definidos
			if (!defined('NOSESSION')) define('NOSESSION', '1');
			include $seed_file;
		} catch (Exception $e) {
			// continuar
		}
		$seed_output = ob_get_clean();
		setEventMessages($langs->trans('SeedCargado'), null, 'mesgs');
		setEventMessages('<pre style="font-size:0.85em;max-height:200px;overflow:auto">'.dol_escape_htmltag($seed_output).'</pre>', null, 'mesgs');
	} else {
		setEventMessages('Archivo seed no encontrado: '.$seed_file, null, 'errors');
	}
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"]));
	exit;
}

// ── Acción: eliminar datos de prueba ──────────────────────────────────────────
if ($action == 'delete_seed' && $user->admin) {
	$total_deleted = 0;

	// 1. Recopilar IDs de facturas y propals vinculadas a operaciones seed
	$factura_ids = array();
	$propal_ids  = array();
	$sql_fk = "SELECT fk_facture, fk_propal FROM ".MAIN_DB_PREFIX."pld_operacion"
		." WHERE import_key = 'SEED_PLD_TEST'";
	$res_fk = $db->query($sql_fk);
	if ($res_fk) {
		while ($row_fk = $db->fetch_object($res_fk)) {
			if (!empty($row_fk->fk_facture) && $row_fk->fk_facture > 0) {
				$factura_ids[] = (int)$row_fk->fk_facture;
			}
			if (!empty($row_fk->fk_propal) && $row_fk->fk_propal > 0) {
				$propal_ids[] = (int)$row_fk->fk_propal;
			}
		}
		$db->free($res_fk);
	}

	// 2. Eliminar pagos y sus ligaduras (vía facturas seed)
	if (!empty($factura_ids)) {
		$ids = implode(',', $factura_ids);
		// Extrafields PLD del pago
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."paiement_extrafields"
			." WHERE fk_object IN ("
			."  SELECT fk_paiement FROM ".MAIN_DB_PREFIX."paiement_facture"
			."  WHERE fk_facture IN ($ids)"
			." )");
		// Pagos
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."paiement"
			." WHERE rowid IN ("
			."  SELECT fk_paiement FROM ".MAIN_DB_PREFIX."paiement_facture"
			."  WHERE fk_facture IN ($ids)"
			." )");
		// Ligaduras pago→factura
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."paiement_facture WHERE fk_facture IN ($ids)");
		// Líneas de factura
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture IN ($ids)");
		// Facturas
		$res_del = $db->query("DELETE FROM ".MAIN_DB_PREFIX."facture WHERE rowid IN ($ids)");
		if ($res_del) $total_deleted += $db->affected_rows($db->db);
	}

	// 3. Eliminar líneas y cotizaciones seed
	if (!empty($propal_ids)) {
		$ids = implode(',', $propal_ids);
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal IN ($ids)");
		$res_del = $db->query("DELETE FROM ".MAIN_DB_PREFIX."propal WHERE rowid IN ($ids)");
		if ($res_del) $total_deleted += $db->affected_rows($db->db);
	}

	// 4. Eliminar tablas PLD seed
	$pld_tables = array('pld_aviso_operacion', 'pld_operacion', 'pld_aviso', 'pld_alerta', 'pld_beneficiario', 'pld_documento');
	foreach ($pld_tables as $t) {
		$res_del = $db->query("DELETE FROM ".MAIN_DB_PREFIX.$t." WHERE import_key = 'SEED_PLD_TEST'");
		if ($res_del) $total_deleted += $db->affected_rows($db->db);
	}

	setEventMessages($langs->trans('SeedEliminado', $total_deleted), null, 'mesgs');
	header("Location: ".dol_escape_htmltag($_SERVER["PHP_SELF"]));
	exit;
}

/*
 * View
 */
$form = new Form($db);

$title = $langs->trans("AdminSetupPLD");
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-modulecompliancepld page-admin');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = modulecompliancepldAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $title, -1, 'fa-shield@modulecompliancepld');

echo '<p class="opacitymedium">'.$langs->trans("AdminSetupDesc").'</p>';

print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// ======= SECCIÓN UMA y UMBRALES =======
print load_fiche_titre($langs->trans("SeccionUMA"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

$uma_valor = getDolGlobalString('MODULECOMPLIANCEPLD_UMA_VALOR', '117.31');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_UMA_VALOR">'.$langs->trans("UMAValor").'</label></td>';
print '<td><input type="number" step="0.01" id="MODULECOMPLIANCEPLD_UMA_VALOR" name="MODULECOMPLIANCEPLD_UMA_VALOR" class="flat minwidth150" value="'.dol_escape_htmltag($uma_valor).'"></td>';
print '<td class="opacitymedium">Valor de la Unidad de Medida y Actualizacion publicado por INEGI. Vigente 2026: $117.31</td>';
print '</tr>';

$uma_anio = getDolGlobalInt('MODULECOMPLIANCEPLD_UMA_ANIO', 2026);
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_UMA_ANIO">'.$langs->trans("UMAAnio").'</label></td>';
print '<td><input type="number" id="MODULECOMPLIANCEPLD_UMA_ANIO" name="MODULECOMPLIANCEPLD_UMA_ANIO" class="flat minwidth100" value="'.$uma_anio.'"></td>';
print '<td class="opacitymedium">Año fiscal del valor UMA configurado</td>';
print '</tr>';

$umbral_nuevo = getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO', '377778.20');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO">'.$langs->trans("UmbralVehNuevo").'</label></td>';
print '<td><input type="number" step="0.01" id="MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO" name="MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO" class="flat minwidth150" value="'.dol_escape_htmltag($umbral_nuevo).'"></td>';
print '<td class="opacitymedium">3,220 UMAs x $117.31 = $377,778.20 MXN (Art. 17 LFPIORPI)</td>';
print '</tr>';

$umbral_usado = getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO', '117310.00');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO">'.$langs->trans("UmbralVehUsado").'</label></td>';
print '<td><input type="number" step="0.01" id="MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO" name="MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO" class="flat minwidth150" value="'.dol_escape_htmltag($umbral_usado).'"></td>';
print '<td class="opacitymedium">1,000 UMAs x $117.31 = $117,310.00 MXN</td>';
print '</tr>';

$umbral_acum = getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_ACUMULADO_6M', '500000.00');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_UMBRAL_ACUMULADO_6M">'.$langs->trans("UmbralAcumulado6M").'</label></td>';
print '<td><input type="number" step="0.01" id="MODULECOMPLIANCEPLD_UMBRAL_ACUMULADO_6M" name="MODULECOMPLIANCEPLD_UMBRAL_ACUMULADO_6M" class="flat minwidth150" value="'.dol_escape_htmltag($umbral_acum).'"></td>';
print '<td class="opacitymedium">Monto acumulado en 6 meses que activa aviso por el mismo cliente (Art. 7 Regl. LFPIORPI). Default: $500,000.00 MXN</td>';
print '</tr>';

print '</table><br>';

// ======= SECCIÓN ALERTAS =======
print load_fiche_titre($langs->trans("SeccionAlertas"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

$dias_alerta = getDolGlobalInt('MODULECOMPLIANCEPLD_DIAS_ALERTA_ID', 30);
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_DIAS_ALERTA_ID">'.$langs->trans("DiasAlertaID").'</label></td>';
print '<td><input type="number" id="MODULECOMPLIANCEPLD_DIAS_ALERTA_ID" name="MODULECOMPLIANCEPLD_DIAS_ALERTA_ID" class="flat minwidth100" value="'.$dias_alerta.'"></td>';
print '<td class="opacitymedium">Dias antes del vencimiento de identificacion para generar alerta automatica</td>';
print '</tr>';

print '</table><br>';

// ======= SECCIÓN GENERAL =======
print load_fiche_titre($langs->trans("SeccionGeneral"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

$oficial_id = getDolGlobalInt('MODULECOMPLIANCEPLD_OFICIAL_CUMPLIMIENTO', 0);
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_OFICIAL_CUMPLIMIENTO">'.$langs->trans("OficialCumplimiento").'</label></td>';
print '<td>';
$form->select_dolusers($oficial_id, 'MODULECOMPLIANCEPLD_OFICIAL_CUMPLIMIENTO', 1, array(), 0, '', 0, 0, 0, 0, '', 0, '', 'minwidth200');
print '</td>';
print '<td class="opacitymedium">Usuario designado como Oficial de Cumplimiento PLD (recibe notificaciones)</td>';
print '</tr>';

$periodo = getDolGlobalInt('MODULECOMPLIANCEPLD_PERIODO_CONSERVACION', 10);
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_PERIODO_CONSERVACION">'.$langs->trans("PeriodoConservacion").'</label></td>';
print '<td><input type="number" id="MODULECOMPLIANCEPLD_PERIODO_CONSERVACION" name="MODULECOMPLIANCEPLD_PERIODO_CONSERVACION" class="flat minwidth100" value="'.$periodo.'"></td>';
print '<td class="opacitymedium">Años de conservacion de expedientes PLD. Minimo legal: 10 años (Art. 20 + Transitorio Séptimo DOF 27/03/2026)</td>';
print '</tr>';

$actividad = getDolGlobalString('MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE', 'VIII');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE">'.$langs->trans("ActividadVulnerable").'</label></td>';
print '<td><input type="text" id="MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE" name="MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE" class="flat minwidth100" value="'.dol_escape_htmltag($actividad).'"></td>';
print '<td class="opacitymedium">Fraccion del Art. 17 LFPIORPI aplicable. Para vehiculos: VIII</td>';
print '</tr>';

print '</table><br>';

// ======= SECCIÓN DOF 27/03/2026 =======
print load_fiche_titre($langs->trans("SeccionDOF2026"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

$iva_default = getDolGlobalString('MODULECOMPLIANCEPLD_IVA_DEFAULT', '0.16');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_IVA_DEFAULT">'.$langs->trans("IVADefault").'</label></td>';
print '<td><input type="text" id="MODULECOMPLIANCEPLD_IVA_DEFAULT" name="MODULECOMPLIANCEPLD_IVA_DEFAULT" class="flat minwidth100" value="'.dol_escape_htmltag($iva_default).'"></td>';
print '<td class="opacitymedium">Tasa IVA por omisión para cálculo de monto con impuestos (0.16 = 16%). Art. 6 DOF 27/03/2026</td>';
print '</tr>';

$pep_endpoint = getDolGlobalString('MODULECOMPLIANCEPLD_PEP_ENDPOINT', '');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_PEP_ENDPOINT">'.$langs->trans("PEPEndpoint").'</label></td>';
print '<td><input type="text" id="MODULECOMPLIANCEPLD_PEP_ENDPOINT" name="MODULECOMPLIANCEPLD_PEP_ENDPOINT" class="flat minwidth300" value="'.dol_escape_htmltag($pep_endpoint).'"></td>';
print '<td class="opacitymedium">URL del servicio de consulta PEP (UIF/SAT). Art. 45 Bis-Quinquies DOF 27/03/2026. Dejar vacío para registro manual.</td>';
print '</tr>';

$avisos_intentadas = getDolGlobalInt('MODULECOMPLIANCEPLD_AVISOS_INTENTADAS_ACTIVO', 0);
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_AVISOS_INTENTADAS_ACTIVO">'.$langs->trans("AvisosIntentadasActivo").'</label></td>';
print '<td>';
print '<select id="MODULECOMPLIANCEPLD_AVISOS_INTENTADAS_ACTIVO" name="MODULECOMPLIANCEPLD_AVISOS_INTENTADAS_ACTIVO" class="flat">';
print '<option value="0"'.($avisos_intentadas == 0 ? ' selected' : '').'>Desactivado (pendiente XSD SAT)</option>';
print '<option value="1"'.($avisos_intentadas == 1 ? ' selected' : '').'>Activado</option>';
print '</select>';
print '</td>';
print '<td class="opacitymedium">Reportar operaciones intentadas (Art. 7 Bis). En espera de XSD actualizado por SAT (Transitorio Quinto).</td>';
print '</tr>';

print '</table><br>';

// ======= SECCIÓN SUJETO OBLIGADO Y E.FIRMA =======
print load_fiche_titre($langs->trans("SeccionSujetoObligado"), '', '');
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>RFC Sujeto Obligado</td>';
print '<td><strong>'.dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SIREN') ?: '—').'</strong> &nbsp; <a href="'.DOL_URL_ROOT.'/admin/company.php">'.$langs->trans("Modify").'</a></td>';
print '<td class="opacitymedium">RFC de la empresa (campo R.F.C. en <em>Configuración &rsaquo; Empresa</em>)</td>';
print '</tr>';

$efirmaCert = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH', '');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH">Ruta certificado .cer</label></td>';
print '<td><input type="text" id="MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH" name="MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH" class="flat minwidth300" value="'.dol_escape_htmltag($efirmaCert).'"></td>';
print '<td class="opacitymedium">Ruta absoluta al archivo .cer de la e.firma (fuera del docroot)</td>';
print '</tr>';

$efirmaKey = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH', '');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH">Ruta llave privada .key</label></td>';
print '<td><input type="text" id="MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH" name="MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH" class="flat minwidth300" value="'.dol_escape_htmltag($efirmaKey).'"></td>';
print '<td class="opacitymedium">Ruta absoluta al archivo .key de la e.firma (fuera del docroot)</td>';
print '</tr>';

$efirmaPassStored = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_PASSWORD', '');
print '<tr class="oddeven">';
print '<td><label for="MODULECOMPLIANCEPLD_EFIRMA_PASSWORD">Contraseña e.firma</label></td>';
print '<td><input type="password" id="MODULECOMPLIANCEPLD_EFIRMA_PASSWORD" name="MODULECOMPLIANCEPLD_EFIRMA_PASSWORD" class="flat minwidth200" value="" autocomplete="new-password" placeholder="'.($efirmaPassStored ? '(configurada — dejar vacío para no cambiar)' : '').'">';
print '</td>';
print '<td class="opacitymedium">Contraseña de la llave privada de la e.firma (se almacena cifrada)</td>';
print '</tr>';

print '</table><br>';

print '<div class="tabsAction">';
print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// ======= SECCIÓN DATOS DE PRUEBA (solo entornos no-producción) =======
if (empty($conf->global->MAIN_PROD)) {
    print '<br>';
    print load_fiche_titre($langs->trans("SeccionDatosPrueba"), '', 'fa-flask');

    // Contar registros seed en cada tabla relevante
    $seed_counts = array();
    $seed_tables = array('pld_operacion', 'pld_aviso', 'pld_alerta', 'pld_beneficiario', 'pld_documento');
    $total_seed = 0;
    foreach ($seed_tables as $t) {
        $sql_c = "SELECT COUNT(*) as cnt FROM ".MAIN_DB_PREFIX.$t." WHERE import_key = 'SEED_PLD_TEST'";
        $res_c = $db->query($sql_c);
        $cnt = 0;
        if ($res_c) {
            $obj_c = $db->fetch_object($res_c);
            $cnt = (int)($obj_c ? $obj_c->cnt : 0);
            $db->free($res_c);
        }
        $seed_counts[$t] = $cnt;
        $total_seed += $cnt;
    }

    print '<div class="info">';
    if ($total_seed == 0) {
        print '<p>'.$langs->trans("SeedNoData").'</p>';
        print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="load_seed">';
        print '<button type="submit" class="butAction">'.$langs->trans("SeedCargar").'</button>';
        print '</form>';
    } else {
        print '<p>'.$langs->trans("SeedDataPresent").'</p>';
        print '<ul>';
        $labels = array(
            'pld_operacion'   => $langs->trans("PLDOperaciones"),
            'pld_aviso'       => $langs->trans("PLDAvisos"),
            'pld_alerta'      => $langs->trans("PLDAlertas"),
            'pld_beneficiario'=> $langs->trans("PLDBeneficiarios"),
            'pld_documento'   => $langs->trans("PLDDocumentos"),
        );
        foreach ($seed_counts as $t => $cnt) {
            if ($cnt > 0) {
                print '<li>'.dol_escape_htmltag($labels[$t] ?? $t).': <strong>'.$cnt.'</strong></li>';
            }
        }
        print '</ul>';
        print '<form method="POST" action="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'" onsubmit="return confirm(\''.$langs->trans("SeedConfirmDelete").'\');">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="delete_seed">';
        print '<button type="submit" class="butActionDelete">'.$langs->trans("SeedEliminar").'</button>';
        print '</form>';
    }
    print '</div>';
}

print dol_get_fiche_end();

llxFooter();
$db->close();
