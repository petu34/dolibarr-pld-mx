<?php
declare(strict_types=1);

/**
 * @file    class/validator/PLDValidationRule.php
 * @module  modulecompliancepld
 *
 * Contrato Strategy para reglas de validación PLD.
 *
 * Cada regla concreta implementa esta interfaz, lo que permite:
 *   - Instanciar y testear reglas de forma aislada sin depender del
 *     monolito PLDValidator.
 *   - Componer conjuntos de reglas específicos por contexto
 *     (formulario de contacto, empresa, XML) mediante PLDFormValidator.
 *   - Agregar nuevas reglas regulatorias sin modificar código existente.
 *
 * Patrón: Strategy (GoF).
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

interface PLDValidationRule
{
    /**
     * Valida el valor dado.
     *
     * Los valores vacíos se consideran opcionales; el Composite (PLDFormValidator)
     * los omite antes de llamar a este método. Las implementaciones pueden
     * asumir que $value no es una cadena vacía.
     *
     * @param string $value Valor a validar (ya sin espacios extremos)
     * @return bool true si pasa la regla, false si falla
     */
    public function validate(string $value): bool;

    /**
     * Clave de traducción del mensaje de error que se mostrará al usuario.
     *
     * Debe corresponder a una clave existente en modulecompliancepld.lang.
     *
     * @return string Clave i18n (ej: 'PLDErrorCURPInvalida')
     */
    public function errorKey(): string;
}
