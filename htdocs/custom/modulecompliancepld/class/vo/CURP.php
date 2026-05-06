<?php
declare(strict_types=1);

/**
 * @file    class/vo/CURP.php
 * @module  modulecompliancepld
 *
 * Value Object para CURP mexicana (Clave Única de Registro de Población).
 *
 * Garantiza que cualquier instancia de CURP contiene un valor estructuralmente
 * válido según el patrón del SAT (ssprof2.xsd curp_type). La validez se
 * verifica en construcción, por lo que no es posible tener un CURP inválido
 * si se usa esta clase en lugar de string crudo.
 *
 * Uso recomendado:
 *   - En fronteras de entrada (formularios): CURP::from() + try/catch → error al usuario
 *   - Al leer de BD/extrafields: CURP::tryFrom() → null si inválido/vacío
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldvalidator.class.php';

final class CURP
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Construye un CURP válido o lanza excepción.
     *
     * Normaliza a mayúsculas y elimina espacios antes de validar.
     *
     * @throws \InvalidArgumentException Si el valor no cumple el formato CURP del SAT
     */
    public static function from(string $raw): self
    {
        $clean = strtoupper(trim($raw));
        if (!preg_match(PLDValidator::REGEX_CURP, $clean)) {
            throw new \InvalidArgumentException("CURP inválido: '{$raw}'");
        }

        $anio2d = (int) substr($clean, 4, 2);
        $mes    = (int) substr($clean, 6, 2);
        $dia    = (int) substr($clean, 8, 2);
        $anio   = $anio2d <= 30 ? 2000 + $anio2d : 1900 + $anio2d;
        if (!checkdate($mes, $dia, $anio)) {
            throw new \InvalidArgumentException("CURP inválido (fecha imposible): '{$raw}'");
        }

        return new self($clean);
    }

    /**
     * Construye un CURP válido o devuelve null si el valor es inválido/vacío.
     *
     * Usar cuando el campo es opcional o cuando se lee de una fuente
     * existente (BD, extrafields) donde puede haber datos históricos sin validar.
     */
    public static function tryFrom(string $raw): ?self
    {
        if (empty(trim($raw))) {
            return null;
        }
        try {
            return self::from($raw);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /** Devuelve el valor normalizado en mayúsculas */
    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
