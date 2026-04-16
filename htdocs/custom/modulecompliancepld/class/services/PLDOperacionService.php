<?php
declare(strict_types=1);

/**
 * @file    class/services/PLDOperacionService.php
 * @module  modulecompliancepld
 *
 * Servicio de orquestación para operaciones PLD.
 *
 * Extrae la lógica de negocio del controlador operacion.php hacia
 * esta capa de servicio: evaluación de umbrales, generación de folio
 * y persistencia se coordinan aquí en lugar de en la vista
 * (Mejora 1 — Service Layer).
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldoperacion.class.php';

class PLDOperacionService
{
    public string $error  = '';
    public array  $errors = [];

    public function __construct(private $db) {}

    /**
     * Crea una operación evaluando umbral y generando folio automáticamente.
     *
     * Orquesta tres pasos que antes vivían en operacion.php:
     *   1. evaluarUmbral($tipo_vehiculo)  — calcula supera_umbral y requiere_aviso
     *   2. generarFolioInterno()          — asigna folio PLD-YYYY-MM-NNNN
     *   3. create($user)                  — inserta en BD y dispara trigger
     *
     * @param PLDOperacion $op            Operación ya poblada con datos del formulario
     * @param string       $tipo_vehiculo 'nuevo' | 'usado'
     * @param object       $user          Usuario Dolibarr autenticado
     * @return int ID del registro creado (>0), -1 en caso de error
     */
    public function crearOperacion(PLDOperacion $op, string $tipo_vehiculo, object $user): int
    {
        $op->evaluarUmbral($tipo_vehiculo);
        $op->generarFolioInterno();

        $result = $op->create($user);
        if ($result <= 0) {
            $this->error  = $op->error;
            $this->errors = $op->errors;
            return -1;
        }

        return $result;
    }

    /**
     * Actualiza una operación existente.
     *
     * Envuelve $op->update() para centralizar el punto de extensión;
     * si en el futuro el formulario de edición expone tipo_vehiculo,
     * la re-evaluación del umbral puede añadirse aquí sin tocar las vistas.
     *
     * @param PLDOperacion $op   Operación con campos modificados
     * @param object       $user Usuario Dolibarr autenticado
     * @return int 1=ok, -1=error
     */
    public function actualizarOperacion(PLDOperacion $op, object $user): int
    {
        $result = $op->update($user);
        if ($result <= 0) {
            $this->error  = $op->error;
            $this->errors = $op->errors;
            return -1;
        }

        return 1;
    }
}
