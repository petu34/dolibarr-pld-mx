<?php
/* Copyright (C) 2026 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
// NOREQUIREDB removido — necesario para cargar traducciones PLD
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
// NOREQUIRETRAN removido — necesario para cargar traducciones PLD
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


/**
 * \file    modulecompliancepld/js/modulecompliancepld.js.php
 * \ingroup modulecompliancepld
 * \brief   JavaScript file for module Modulecompliancepld.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

// Cargar traducciones para JavaScript
$langs->load('modulecompliancepld@modulecompliancepld');
?>

/* Javascript library of module Modulecompliancepld — Validación PLD client-side */
/* @compliance LFPIORPI Art. 17 Fracc. VIII — PLD México */

/* Mensajes de validación PLD para cliente */
var pldMessages = {
    curpError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorCURPFormato")); ?>',
    rfcError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorRFCFormato")); ?>',
    cpError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorCPFormato")); ?>',
    telefonoError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorTelefonoFormato")); ?>',
    correoError: '<?php echo dol_escape_js($langs->trans("PLDJSErrorCorreoFormato")); ?>',
    valido: '<?php echo dol_escape_js($langs->trans("PLDJSCampoValido")); ?>'
};

/* Regex de validación PLD — espejo de PLDValidator::REGEX_CURP y constantes PHP */
var pldRegex = {
    curp: /^[A-Z]{1}[AEIOU]{1}[A-Z]{2}[0-9]{2}(0[1-9]|1[0-2])(0[1-9]|1[0-9]|2[0-9]|3[0-1])[HM]{1}(AS|BC|BS|CC|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TL|TS|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]{1}[0-9]{1}$/,
    rfcFisica: /^[A-ZÑ&]{4}[0-9]{6}[A-Z0-9]{3}$/,
    rfcMoral: /^[A-ZÑ&]{3}[0-9]{6}[A-Z0-9]{3}$/,
    cp: /^[0-9]{5}$/,
    telefono: /^[0-9]{10,12}$/,
    correo: /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,60}$/
};

/**
 * Muestra u oculta mensaje de error junto a un campo PLD
 *
 * @param {jQuery} $field  Campo de formulario
 * @param {boolean} isValid  Resultado de la validación
 * @param {string} errorMsg  Mensaje de error a mostrar
 */
function pldValidateField($field, isValid, errorMsg) {
    var $msg = $field.next('.pld-validation-msg');

    if ($msg.length === 0) {
        $field.after('<span class="pld-validation-msg" style="margin-left:8px;font-size:0.85em;"></span>');
        $msg = $field.next('.pld-validation-msg');
    }

    if (isValid) {
        $field.css('border-color', '');
        $msg.css('color', '#28a745').text(pldMessages.valido);
        setTimeout(function() { $msg.text(''); }, 2000);
    } else {
        $field.css('border-color', '#dc3545');
        $msg.css('color', '#dc3545').text(errorMsg);
    }
}

$(document).ready(function() {
    /* Validación CURP al perder foco */
    $('input[name="options_pld_curp"]').on('blur', function() {
        var val = $(this).val().toUpperCase().trim();
        if (val === '') return;
        pldValidateField($(this), pldRegex.curp.test(val), pldMessages.curpError);
    });

    /* Validación RFC al perder foco */
    $('input[name="options_pld_rfc"], input[name="options_pld_rfc_validado"]').on('blur', function() {
        var val = $(this).val().toUpperCase().trim();
        if (val === '') return;
        var isValid = false;
        if (val.length === 13) {
            isValid = pldRegex.rfcFisica.test(val);
        } else if (val.length === 12) {
            isValid = pldRegex.rfcMoral.test(val);
        }
        pldValidateField($(this), isValid, pldMessages.rfcError);
    });

    /* Validación Código Postal al perder foco */
    $('input[name="options_pld_codigo_postal"]').on('blur', function() {
        var val = $(this).val().trim();
        if (val === '') return;
        pldValidateField($(this), pldRegex.cp.test(val), pldMessages.cpError);
    });

    /* Validación Teléfono al perder foco */
    $('input[name="options_pld_numero_telefono"]').on('blur', function() {
        var val = $(this).val().replace(/[\s\-\(\)]/g, '').trim();
        if (val === '') return;
        pldValidateField($(this), pldRegex.telefono.test(val), pldMessages.telefonoError);
    });

    /* Validación Correo al perder foco */
    $('input[name="options_pld_correo_electronico"]').on('blur', function() {
        var val = $(this).val().trim();
        if (val === '') return;
        pldValidateField($(this), pldRegex.correo.test(val) && val.length <= 60, pldMessages.correoError);
    });

    /* Auto-uppercase para CURP y RFC al escribir */
    $('input[name="options_pld_curp"], input[name="options_pld_rfc"], input[name="options_pld_rfc_validado"]').on('input', function() {
        var pos = this.selectionStart;
        $(this).val($(this).val().toUpperCase());
        this.setSelectionRange(pos, pos);
    });
});
