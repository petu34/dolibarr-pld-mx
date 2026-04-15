<?php
declare(strict_types=1);

/**
 * @file    class/validator/CorreoRule.php
 * @module  modulecompliancepld
 *
 * Regla de validación para correo electrónico PLD (máximo 60 caracteres).
 *
 * Aplica la restricción de longitud máxima definida por el catálogo SAT
 * (correo_electronico_type) antes de evaluar el regex.
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/validator/PLDValidationRule.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldvalidator.class.php';

final class CorreoRule implements PLDValidationRule
{
    private const MAX_LENGTH = 60;

    public function validate(string $value): bool
    {
        $v = trim($value);
        if (strlen($v) > self::MAX_LENGTH) {
            return false;
        }
        return (bool) preg_match(PLDValidator::REGEX_CORREO, $v);
    }

    public function errorKey(): string
    {
        return 'PLDErrorCorreoInvalido';
    }
}
