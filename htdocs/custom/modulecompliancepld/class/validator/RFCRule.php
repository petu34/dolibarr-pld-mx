<?php
declare(strict_types=1);

/**
 * @file    class/validator/RFCRule.php
 * @module  modulecompliancepld
 *
 * Regla de validación para RFC mexicano (física o moral).
 *
 * Delega al Value Object RFC, que detecta automáticamente el tipo
 * por longitud (12 = moral, 13 = física) y valida con el regex SAT.
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/validator/PLDValidationRule.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/vo/RFC.php';

final class RFCRule implements PLDValidationRule
{
    public function validate(string $value): bool
    {
        return RFC::tryFrom($value) !== null;
    }

    public function errorKey(): string
    {
        return 'PLDErrorRFCInvalido';
    }
}
