<?php
declare(strict_types=1);

/**
 * @file    class/vo/VIN.php
 * @module  modulecompliancepld
 *
 * Value Object para VIN (Vehicle Identification Number).
 *
 * Valida exactamente 17 caracteres alfanuméricos conforme al esquema
 * del SAT (veh.xsd referencia_17_type). Garantiza que cualquier instancia
 * contiene un VIN estructuralmente correcto para su inclusión en el XML
 * de aviso al SPPLD.
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldvalidator.class.php';

final class VIN
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Construye un VIN válido o lanza excepción.
     *
     * Normaliza a mayúsculas y elimina espacios antes de validar.
     *
     * @throws \InvalidArgumentException Si el valor no tiene exactamente 17 caracteres
     *                                    alfanuméricos según veh.xsd referencia_17_type
     */
    public static function from(string $raw): self
    {
        $clean = strtoupper(trim($raw));
        if (!preg_match(PLDValidator::REGEX_VIN, $clean)) {
            throw new \InvalidArgumentException("VIN inválido: '{$raw}' (se requieren exactamente 17 caracteres alfanuméricos)");
        }
        return new self($clean);
    }

    /**
     * Construye un VIN válido o devuelve null si el valor es inválido/vacío.
     *
     * Usar cuando el VIN es opcional (ej.: vehículos sin VIN asignado) o
     * al leer de fuentes existentes donde puede haber datos incompletos.
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
