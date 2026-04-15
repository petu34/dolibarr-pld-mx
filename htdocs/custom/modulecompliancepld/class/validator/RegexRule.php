<?php
declare(strict_types=1);

/**
 * @file    class/validator/RegexRule.php
 * @module  modulecompliancepld
 *
 * Regla de validación genérica basada en expresión regular.
 *
 * Cubre todos los campos que sólo requieren coincidencia con un patrón
 * de PLDValidator (CP, país, monto, REPUVE, placas, etc.) sin lógica
 * adicional de normalización especial.
 *
 * Uso:
 *   new RegexRule(PLDValidator::REGEX_CP,   'PLDErrorCPInvalido')
 *   new RegexRule(PLDValidator::REGEX_PAIS, 'PLDErrorPaisInvalido', uppercase: true)
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/validator/PLDValidationRule.php';

final class RegexRule implements PLDValidationRule
{
    /**
     * @param string $pattern   Expresión regular (incluyendo delimitadores)
     * @param string $errorKey  Clave de traducción del error
     * @param bool   $uppercase Normalizar a mayúsculas antes de validar
     */
    public function __construct(
        private string $pattern,
        private string $errorKey,
        private bool   $uppercase = false
    ) {}

    public function validate(string $value): bool
    {
        $v = $this->uppercase ? strtoupper(trim($value)) : trim($value);
        return (bool) preg_match($this->pattern, $v);
    }

    public function errorKey(): string
    {
        return $this->errorKey;
    }
}
