<?php
declare(strict_types=1);

/**
 * @file    class/validator/TelefonoRule.php
 * @module  modulecompliancepld
 *
 * Regla de validación para número de teléfono PLD (10-12 dígitos).
 *
 * Aplica normalización previa al regex: elimina espacios, guiones y
 * paréntesis para aceptar formatos comunes de captura humana
 * (ej: "(55) 1234-5678" → "5512345678").
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/validator/PLDValidationRule.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldvalidator.class.php';

final class TelefonoRule implements PLDValidationRule
{
    public function validate(string $value): bool
    {
        $normalized = preg_replace('/[\s\-\(\)]/', '', trim($value));
        return (bool) preg_match(PLDValidator::REGEX_TELEFONO, $normalized);
    }

    public function errorKey(): string
    {
        return 'PLDErrorTelefonoInvalido';
    }
}
