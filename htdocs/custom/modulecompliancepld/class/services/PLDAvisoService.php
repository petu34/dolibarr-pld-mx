<?php
declare(strict_types=1);

/**
 * @file    class/services/PLDAvisoService.php
 * @module  modulecompliancepld
 *
 * Servicio de orquestación para avisos SAT PLD.
 *
 * Centraliza la generación de XML que antes estaba duplicada
 * entre aviso/card.php y xml_generator.php (Mejora 1 — Service Layer).
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldaviso.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldxmlgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/repository/PLDOperacionRepository.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldefirmaintegration.class.php';

class PLDAvisoService
{
    public string $error   = '';
    public array  $errors  = [];
    public string $xmlRuta = '';

    public function __construct(private $db) {}

    /**
     * Genera, firma opcionalmente y persiste el XML de un aviso.
     *
     * Orquesta los pasos que antes vivían inline en aviso/card.php:
     *   1. fetchOperaciones()             — carga las operaciones del aviso
     *   2. PLDXMLGenerator::generar()     — produce el XML SAT
     *   3. PLDEFirmaIntegration::firmar() — firma con e.firma (opcional)
     *   4. guardarXML()                   — escribe al disco
     *   5. $aviso->update()               — actualiza ruta, hash y estado
     *
     * @param PLDAviso $aviso   Aviso ya cargado (fetch completado)
     * @param bool     $firmar  true = intentar firma con e.firma configurada
     * @param object   $user    Usuario Dolibarr autenticado
     * @return bool true=ok, false=error (detalle en $this->error / $this->errors)
     */
    public function generarXML(PLDAviso $aviso, bool $firmar, object $user): bool
    {
        $aviso->fetchOperaciones();
        $ids_ops = array_map(fn($o) => (int)$o->rowid, $aviso->operaciones ?? []);

        if (empty($ids_ops)) {
            $this->error = 'PLDAvisoSinOperaciones';
            return false;
        }

        $repo   = new PLDOperacionRepository($this->db);
        $config = [
            'rfc_sujeto'      => getDolGlobalString('MAIN_INFO_SIREN'),
            'clave_actividad' => getDolGlobalString('MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE') ?: 'VIII',
        ];
        $generator = new PLDXMLGenerator($repo, $config);
        $xml = $generator->generarXMLMensual($aviso->mes_reportado, $ids_ops);

        if ($xml === false) {
            $this->error  = $generator->error;
            $this->errors = $generator->errors;
            return false;
        }

        if ($firmar) {
            $efirma = new PLDEFirmaIntegration($this->db);
            $firma  = $efirma->firmarXML($xml);
            if ($firma !== false) {
                $xml = $efirma->incrustarSello($xml, $firma);
            } elseif ($efirma->error) {
                // La firma falló pero el XML sigue siendo válido; continúa con advertencia
                $this->errors[] = $efirma->error;
            }
        }

        $ruta = $generator->guardarXML($xml, $aviso->mes_reportado, $user);
        if (!$ruta) {
            $this->error = $generator->error;
            return false;
        }

        $this->xmlRuta = $ruta;

        $aviso->archivo_xml_ruta     = $ruta;
        $aviso->archivo_xml_hash     = $aviso->calcularHashXML($xml);
        $aviso->fecha_generacion_xml = $this->db->idate(dol_now());
        if ($aviso->estado === 'borrador') {
            $aviso->estado = 'pendiente';
        }
        $aviso->update($user);

        return true;
    }
}
