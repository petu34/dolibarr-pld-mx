<?php
declare(strict_types=1);

/**
 * @file    class/validator/CURPRule.php
 * @module  modulecompliancepld
 *
 * Regla de validación para CURP mexicana.
 *
 * Delega la validación al Value Object CURP, evitando duplicar la lógica
 * de checkdate y el regex RENAPO que ya viven en esa clase.
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/validator/PLDValidationRule.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/vo/CURP.php';

final class CURPRule implements PLDValidationRule
{
    public function validate(string $value): bool
    {
        return CURP::tryFrom($value) !== null;
    }

    public function errorKey(): string
    {
        return 'PLDErrorCURPInvalida';
    }
}
