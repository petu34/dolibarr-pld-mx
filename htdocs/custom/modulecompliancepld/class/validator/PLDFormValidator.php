<?php
declare(strict_types=1);

/**
 * @file    class/validator/PLDFormValidator.php
 * @module  modulecompliancepld
 *
 * Composite de reglas de validación para formularios PLD.
 *
 * Permite declarar conjuntos de reglas específicos por contexto mediante
 * un fluent builder y ejecutarlos contra los campos POST en una sola llamada.
 *
 * Patrón: Composite (GoF) — agrega objetos PLDValidationRule y los
 * ejecuta secuencialmente, acumulando mensajes de error para el usuario.
 *
 * Uso típico:
 * ```php
 * $errores = (new PLDFormValidator())
 *     ->addField('options_pld_curp', new CURPRule())
 *     ->addField('options_pld_rfc',  new RFCRule())
 *     ->addField('options_pld_numero_telefono', new TelefonoRule())
 *     ->validatePost();
 *
 * if ($errores > 0) {
 *     $action = '';
 *     return -1;
 * }
 * ```
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/validator/PLDValidationRule.php';

class PLDFormValidator
{
    /** @var array<string, PLDValidationRule> Mapeo campo POST → regla */
    private array $fields = [];

    /**
     * Registra una regla para un campo POST.
     *
     * Los campos vacíos se omiten automáticamente en validatePost()
     * (todos los campos PLD son opcionales individualmente).
     *
     * @param string            $postField Nombre del campo en $_POST (tal como lo lee GETPOST)
     * @param PLDValidationRule $rule      Regla de validación a aplicar
     * @return static Retorna $this para encadenamiento fluent
     */
    public function addField(string $postField, PLDValidationRule $rule): static
    {
        $this->fields[$postField] = $rule;
        return $this;
    }

    /**
     * Valida todos los campos registrados contra $_POST.
     *
     * - Campos vacíos (string vacío) se saltan: son opcionales.
     * - Por cada campo que falla, llama a setEventMessages() con la clave
     *   de error de la regla correspondiente.
     *
     * @return int Número de errores encontrados (0 = todos los campos válidos)
     */
    public function validatePost(): int
    {
        global $langs;

        $errors = 0;
        foreach ($this->fields as $postField => $rule) {
            $value = (string) GETPOST($postField, 'alpha');
            if ($value === '') {
                continue;
            }
            if (!$rule->validate($value)) {
                setEventMessages($langs->trans($rule->errorKey()), null, 'errors');
                $errors++;
            }
        }
        return $errors;
    }
}
