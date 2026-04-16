<?php
declare(strict_types=1);

/**
 * @file    class/vo/RFC.php
 * @module  modulecompliancepld
 *
 * Value Object para RFC mexicano (Registro Federal de Contribuyentes).
 *
 * Soporta ambos tipos:
 *   - Persona Física: 13 caracteres  (REGEX_RFC_FISICA)
 *   - Persona Moral:  12 caracteres  (REGEX_RFC_MORAL)
 *
 * La detección del tipo se realiza automáticamente por longitud y patrón.
 * Incluye Ñ y & conforme al catálogo SAT (ssprof2.xsd rfc_fisica_type /
 * rfc_moral_type).
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldvalidator.class.php';

final class RFC
{
    /** @var 'fisica'|'moral' */
    private string $tipo;
    private string $value;

    private function __construct(string $value, string $tipo)
    {
        $this->value = $value;
        $this->tipo  = $tipo;
    }

    /**
     * Construye un RFC válido (física o moral) o lanza excepción.
     *
     * Normaliza a mayúsculas (UTF-8 aware para Ñ/&) antes de validar.
     *
     * @throws \InvalidArgumentException Si el valor no cumple ningún patrón RFC del SAT
     */
    public static function from(string $raw): self
    {
        $clean = mb_strtoupper(trim($raw), 'UTF-8');
        $len   = mb_strlen($clean, 'UTF-8');

        if ($len === 13 && preg_match(PLDValidator::REGEX_RFC_FISICA, $clean)) {
            return new self($clean, 'fisica');
        }
        if ($len === 12 && preg_match(PLDValidator::REGEX_RFC_MORAL, $clean)) {
            return new self($clean, 'moral');
        }

        throw new \InvalidArgumentException("RFC inválido: '{$raw}'");
    }

    /**
     * Construye un RFC válido o devuelve null si el valor es inválido/vacío.
     *
     * Usar cuando el campo es opcional o al leer de fuentes existentes (BD,
     * extrafields) donde puede haber datos históricos sin validar.
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

    /** Devuelve 'fisica' o 'moral' */
    public function tipo(): string
    {
        return $this->tipo;
    }

    public function esFisica(): bool
    {
        return $this->tipo === 'fisica';
    }

    public function esMoral(): bool
    {
        return $this->tipo === 'moral';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
