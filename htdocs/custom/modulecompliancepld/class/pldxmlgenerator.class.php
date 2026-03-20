<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldoperacion.class.php';

/**
 * Motor de generación de XML de avisos PLD/LFPIORPI
 * Actividad Vulnerable: Compraventa de Vehículos (Fracción VIII)
 * Namespace SAT: http://www.uif.shcp.gob.mx/recepcion/veh
 */
class PLDXMLGenerator
{
    private $db;
    public $error = '';
    public $errors = array();

    private $namespace = 'http://www.uif.shcp.gob.mx/recepcion/veh';
    private $xsd_location = 'https://sppld.sat.gob.mx/pld/documentos/links/xsd/veh.xsd';

    private $rfc_sujeto = '';
    private $clave_actividad = 'VIII';

    public function __construct($db)
    {
        $this->db = $db;
        $this->rfc_sujeto = getDolGlobalString('MODULECOMPLIANCEPLD_RFC_SUJETO');
        $this->clave_actividad = getDolGlobalString('MODULECOMPLIANCEPLD_ACTIVIDAD_VULNERABLE') ?: 'VIII';
    }

    /**
     * Generar XML mensual con todos los avisos del período
     *
     * @param string $mes_reportado  Formato YYYYMM
     * @return string|false  XML string o false si error
     */
    public function generarXMLMensual(string $mes_reportado)
    {
        dol_syslog(__METHOD__." mes=$mes_reportado", LOG_INFO);

        $operaciones = $this->getOperacionesMes($mes_reportado);

        if (empty($operaciones)) {
            $this->error = "No hay operaciones pendientes de aviso para el mes $mes_reportado";
            return false;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;

        // Raíz: <archivo>
        $archivo = $dom->createElementNS($this->namespace, 'archivo');
        $archivo->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            $this->namespace.' '.$this->xsd_location
        );
        $dom->appendChild($archivo);

        // <informe>
        $informe = $dom->createElement('informe');
        $archivo->appendChild($informe);

        $informe->appendChild($dom->createElement('mes_reportado', $mes_reportado));
        $informe->appendChild($this->crearSujetoObligado($dom));

        foreach ($operaciones as $operacion) {
            $aviso = $this->crearAviso($dom, $operacion);
            if ($aviso) {
                $informe->appendChild($aviso);
            }
        }

        return $dom->saveXML();
    }

    /**
     * Obtener operaciones del mes pendientes de aviso
     */
    private function getOperacionesMes(string $mes_reportado): array
    {
        $sql = "SELECT o.rowid";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion as o";
        $sql .= " WHERE o.mes_reportado = '".$this->db->escape($mes_reportado)."'";
        $sql .= " AND o.requiere_aviso = 1";
        $sql .= " AND o.aviso_presentado = 0";
        $sql .= " AND o.estado != 'cancelada'";
        $sql .= " ORDER BY o.fecha_operacion ASC";

        $resql = $this->db->query($sql);

        $operaciones = array();
        if (!$resql) {
            $this->errors[] = "getOperacionesMes: ".$this->db->lasterror();
            return $operaciones;
        }

        $num = $this->db->num_rows($resql);
        for ($i = 0; $i < $num; $i++) {
            $obj = $this->db->fetch_object($resql);
            $operacion = new PLDOperacion($this->db);
            if ($operacion->fetch($obj->rowid) <= 0) {
                continue;
            }
            $operacion->fetchCliente();
            $operacion->fetchVehiculo();
            $operacion->fetchBeneficiarios();
            $operacion->fetchFormasPago();
            $operaciones[] = $operacion;
        }
        $this->db->free($resql);

        return $operaciones;
    }

    // -----------------------------------------------------------------------
    // Nodos XML
    // -----------------------------------------------------------------------

    private function crearSujetoObligado(DOMDocument $dom): DOMElement
    {
        $sujeto = $dom->createElement('sujeto_obligado');
        $sujeto->appendChild($dom->createElement('clave_sujeto_obligado', $this->rfc_sujeto));
        $sujeto->appendChild($dom->createElement('clave_actividad', $this->clave_actividad));
        return $sujeto;
    }

    private function crearAviso(DOMDocument $dom, PLDOperacion $operacion): ?DOMElement
    {
        $aviso = $dom->createElement('aviso');

        // Referencia: VEH-YYYYMM-NNNN derivada del folio_interno
        $ref = $this->buildReferenciaAviso($operacion);
        $aviso->appendChild($dom->createElement('referencia_aviso', $ref));
        $aviso->appendChild($dom->createElement('prioridad', '1'));
        $aviso->appendChild($this->crearAlerta($dom, $operacion));

        if ($operacion->cliente) {
            $aviso->appendChild($this->crearPersonaAviso($dom, $operacion));
        }

        if (!empty($operacion->tiene_beneficiario) && !empty($operacion->beneficiarios)) {
            $aviso->appendChild($this->crearBeneficiario($dom, $operacion));
        }

        $aviso->appendChild($this->crearActoOperacion($dom, $operacion));

        return $aviso;
    }

    private function crearAlerta(DOMDocument $dom, PLDOperacion $operacion): DOMElement
    {
        $alerta = $dom->createElement('alerta');
        // 01=operación inusual, 02=sin información suficiente
        $tipo = $operacion->genera_alerta ? '01' : '01';
        $alerta->appendChild($dom->createElement('tipo_alerta', $tipo));
        return $alerta;
    }

    private function crearPersonaAviso(DOMDocument $dom, PLDOperacion $operacion): DOMElement
    {
        $cliente = $operacion->cliente;
        $persona_aviso = $dom->createElement('persona_aviso');

        $tipo_persona = $dom->createElement('tipo_persona');
        if ($cliente->tipo_persona == 'moral') {
            $tipo_persona->appendChild($this->crearPersonaMoral($dom, $cliente));
        } else {
            $tipo_persona->appendChild($this->crearPersonaFisica($dom, $cliente));
        }
        $persona_aviso->appendChild($tipo_persona);

        $persona_aviso->appendChild($this->crearDomicilio($dom, $cliente));

        if (!empty($cliente->phone)) {
            $persona_aviso->appendChild($this->crearTelefono($dom, $cliente->phone));
        }

        return $persona_aviso;
    }

    private function crearPersonaFisica(DOMDocument $dom, stdClass $cliente): DOMElement
    {
        $pf = $dom->createElement('persona_fisica');

        $pf->appendChild($dom->createElement('nombre', $this->cleanXML($cliente->nombre)));
        $pf->appendChild($dom->createElement('apellido_paterno', $this->cleanXML($cliente->apellido_paterno)));

        if (!empty($cliente->apellido_materno)) {
            $pf->appendChild($dom->createElement('apellido_materno', $this->cleanXML($cliente->apellido_materno)));
        }

        if (!empty($cliente->fecha_nacimiento)) {
            $ts = is_numeric($cliente->fecha_nacimiento) ? $cliente->fecha_nacimiento : strtotime($cliente->fecha_nacimiento);
            $pf->appendChild($dom->createElement('fecha_nacimiento', date('Ymd', $ts)));
        }

        if (!empty($cliente->pais_nacimiento)) {
            $pf->appendChild($dom->createElement('pais_nacimiento', $cliente->pais_nacimiento));
        }

        $pf->appendChild($dom->createElement('nacionalidad', $cliente->nacionalidad ?: 'MX'));
        $pf->appendChild($dom->createElement('rfc', $cliente->rfc));

        if (!empty($cliente->curp)) {
            $pf->appendChild($dom->createElement('curp', $cliente->curp));
        }

        if (!empty($cliente->actividad_economica)) {
            $pf->appendChild($dom->createElement('actividad_economica', $cliente->actividad_economica));
        }

        return $pf;
    }

    private function crearPersonaMoral(DOMDocument $dom, stdClass $cliente): DOMElement
    {
        $pm = $dom->createElement('persona_moral');

        $pm->appendChild($dom->createElement('denominacion_razon', $this->cleanXML($cliente->denominacion_razon)));
        $pm->appendChild($dom->createElement('rfc', $cliente->rfc));

        if (!empty($cliente->fecha_constitucion)) {
            $ts = is_numeric($cliente->fecha_constitucion) ? $cliente->fecha_constitucion : strtotime($cliente->fecha_constitucion);
            $pm->appendChild($dom->createElement('fecha_constitucion', date('Ymd', $ts)));
        }

        if (!empty($cliente->giro_mercantil)) {
            $pm->appendChild($dom->createElement('giro_mercantil', $cliente->giro_mercantil));
        }

        return $pm;
    }

    private function crearDomicilio(DOMDocument $dom, stdClass $cliente): DOMElement
    {
        $tipo_domicilio = $dom->createElement('tipo_domicilio');

        if (!empty($cliente->es_domicilio_extranjero)) {
            $ext = $dom->createElement('extranjero');
            if (!empty($cliente->estado_provincia_ext)) {
                $ext->appendChild($dom->createElement('estado_provincia', $this->cleanXML($cliente->estado_provincia_ext)));
            }
            if (!empty($cliente->ciudad_poblacion_ext)) {
                $ext->appendChild($dom->createElement('ciudad_poblacion', $this->cleanXML($cliente->ciudad_poblacion_ext)));
            }
            $ext->appendChild($dom->createElement('pais', $cliente->pais ?: 'MX'));
            $tipo_domicilio->appendChild($ext);
        } else {
            $nac = $dom->createElement('nacional');
            $nac->appendChild($dom->createElement('colonia', $this->cleanXML($cliente->colonia)));
            $nac->appendChild($dom->createElement('calle', $this->cleanXML($cliente->calle)));
            $nac->appendChild($dom->createElement('numero_exterior', $this->cleanXML($cliente->numero_exterior)));
            if (!empty($cliente->numero_interior)) {
                $nac->appendChild($dom->createElement('numero_interior', $this->cleanXML($cliente->numero_interior)));
            }
            $nac->appendChild($dom->createElement('codigo_postal', $cliente->codigo_postal));
            $nac->appendChild($dom->createElement('municipio', $this->cleanXML($cliente->municipio)));
            $nac->appendChild($dom->createElement('entidad_federativa', $this->cleanXML($cliente->estado)));
            $tipo_domicilio->appendChild($nac);
        }

        return $tipo_domicilio;
    }

    private function crearTelefono(DOMDocument $dom, string $phone): DOMElement
    {
        $telefono = $dom->createElement('telefono');
        // Extraer clave de país y número
        $numero = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numero) > 10) {
            $clave_pais = substr($numero, 0, strlen($numero) - 10);
            $numero = substr($numero, -10);
        } else {
            $clave_pais = '52';
        }
        $telefono->appendChild($dom->createElement('clave_pais', $clave_pais));
        $telefono->appendChild($dom->createElement('numero_telefono', $numero));
        return $telefono;
    }

    private function crearBeneficiario(DOMDocument $dom, PLDOperacion $operacion): DOMElement
    {
        $dueno = $dom->createElement('dueno_beneficiario');

        // Usar el primer beneficiario con mayor porcentaje
        $ben = $operacion->beneficiarios[0];

        $tipo_persona = $dom->createElement('tipo_persona');
        $pf = $dom->createElement('persona_fisica');

        $pf->appendChild($dom->createElement('nombre', $this->cleanXML($ben->nombre)));
        $pf->appendChild($dom->createElement('apellido_paterno', $this->cleanXML($ben->apellido_paterno)));
        if (!empty($ben->apellido_materno)) {
            $pf->appendChild($dom->createElement('apellido_materno', $this->cleanXML($ben->apellido_materno)));
        }
        if (!empty($ben->fecha_nacimiento)) {
            $ts = is_numeric($ben->fecha_nacimiento) ? $ben->fecha_nacimiento : strtotime($ben->fecha_nacimiento);
            $pf->appendChild($dom->createElement('fecha_nacimiento', date('Ymd', $ts)));
        }
        if (!empty($ben->rfc)) {
            $pf->appendChild($dom->createElement('rfc', strtoupper($ben->rfc)));
        }
        $pf->appendChild($dom->createElement('porcentaje_participacion', number_format((float)$ben->porcentaje_participacion, 0, '.', '')));

        $tipo_persona->appendChild($pf);
        $dueno->appendChild($tipo_persona);

        return $dueno;
    }

    private function crearActoOperacion(DOMDocument $dom, PLDOperacion $operacion): DOMElement
    {
        $acto = $dom->createElement('acto_operacion');

        // Fecha operación YYYYMMDD
        $ts_op = is_numeric($operacion->fecha_operacion) ? $operacion->fecha_operacion : strtotime($operacion->fecha_operacion);
        $acto->appendChild($dom->createElement('fecha_operacion', date('Ymd', $ts_op)));
        $acto->appendChild($dom->createElement('fecha_deteccion_operacion', date('Ymd', $ts_op)));

        // Monto y moneda
        $acto->appendChild($dom->createElement('monto_operacion', number_format((float)$operacion->monto_mxn, 2, '.', '')));
        $acto->appendChild($dom->createElement('moneda', $operacion->moneda ?: 'MXN'));
        $acto->appendChild($dom->createElement('tipo_cambio', number_format((float)$operacion->tipo_cambio, 4, '.', '')));

        // Forma de pago
        $fp_code = $this->mapFormaPago($operacion->forma_pago_principal);
        $acto->appendChild($dom->createElement('forma_pago', $fp_code));

        // Instrumento monetario (primer pago)
        $instrumento = '01';
        if (!empty($operacion->formas_pago[0]->pld_instrumento_monetario)) {
            $instrumento = $operacion->formas_pago[0]->pld_instrumento_monetario;
        }
        $acto->appendChild($dom->createElement('instrumento_monetario', $instrumento));

        // Datos bancarios (transferencia)
        if (!empty($operacion->usa_transferencia)) {
            if (!empty($operacion->cuenta_destino)) {
                $acto->appendChild($dom->createElement('numero_cuenta', $operacion->cuenta_destino));
            }
            if (!empty($operacion->banco_destino)) {
                $acto->appendChild($dom->createElement('institucion_financiera', $this->cleanXML($operacion->banco_destino)));
            }
        }

        // Datos del vehículo
        if ($operacion->vehiculo) {
            $acto->appendChild($this->crearVehiculo($dom, $operacion));
        }

        // Descripción del acto
        $desc = $operacion->descripcion_operacion ?? '';
        if (empty($desc) && $operacion->vehiculo) {
            $desc = 'Compraventa de vehículo '.$operacion->vehiculo->marca.' '
                .$operacion->vehiculo->modelo.' '.$operacion->vehiculo->anio_modelo;
        }
        $acto->appendChild($dom->createElement('descripcion_acto', $this->cleanXML(trim($desc))));

        return $acto;
    }

    private function crearVehiculo(DOMDocument $dom, PLDOperacion $operacion): DOMElement
    {
        $veh = $operacion->vehiculo;
        $vehiculo = $dom->createElement('vehiculo');

        $vehiculo->appendChild($dom->createElement('tipo_vehiculo', $this->mapTipoVehiculo($veh->tipo_vehiculo)));
        $vehiculo->appendChild($dom->createElement('clase_vehiculo', $this->mapClaseVehiculo($veh->tipo_vehiculo)));
        $vehiculo->appendChild($dom->createElement('marca', strtoupper($this->cleanXML($veh->marca))));
        $vehiculo->appendChild($dom->createElement('modelo', strtoupper($this->cleanXML($veh->modelo))));
        $vehiculo->appendChild($dom->createElement('anio', (string)$veh->anio_modelo));

        // VIN (terrestre) o número de serie (marítimo/aéreo)
        if (!empty($veh->vin)) {
            $vehiculo->appendChild($dom->createElement('numero_serie', strtoupper($veh->vin)));
        } elseif (!empty($veh->numero_serie)) {
            $vehiculo->appendChild($dom->createElement('numero_serie', strtoupper($veh->numero_serie)));
        }

        $vehiculo->appendChild($dom->createElement('origen', $this->mapOrigen($veh->origen)));
        $vehiculo->appendChild($dom->createElement('uso', $this->mapUso($veh->uso_destino)));

        return $vehiculo;
    }

    // -----------------------------------------------------------------------
    // Catálogos SAT
    // -----------------------------------------------------------------------

    private function mapFormaPago(string $forma): string
    {
        $map = array(
            '1' => '01', 'efectivo' => '01', '01' => '01',
            '4' => '04', 'transferencia' => '04', '04' => '04',
            '6' => '06', 'cheque' => '06', '06' => '06',
            '9' => '09', 'tarjeta_credito' => '09', '09' => '09',
            '10' => '10', 'tarjeta_debito' => '10', '10' => '10',
        );
        return $map[strtolower($forma)] ?? '01';
    }

    private function mapTipoVehiculo(string $tipo): string
    {
        $map = array(
            'terrestre' => '01', '01' => '01',
            'aereo' => '02', 'aéreo' => '02', '02' => '02',
            'maritimo' => '03', 'marítimo' => '03', '03' => '03',
        );
        return $map[strtolower($tipo)] ?? '01';
    }

    private function mapClaseVehiculo(string $tipo): string
    {
        // 01=Automóvil, 02=Camioneta, 03=Motocicleta
        // Sin subtipo disponible, default automóvil para terrestres
        if (in_array(strtolower($tipo), array('aereo', 'aéreo', '02'))) {
            return '01'; // aeronave
        }
        if (in_array(strtolower($tipo), array('maritimo', 'marítimo', '03'))) {
            return '01'; // embarcación
        }
        return '01'; // automóvil
    }

    private function mapOrigen(string $origen): string
    {
        $map = array(
            'nacional' => '01', '01' => '01',
            'importado' => '02', '02' => '02',
        );
        return $map[strtolower($origen)] ?? '01';
    }

    private function mapUso(string $uso): string
    {
        $map = array(
            'particular' => '01', '01' => '01',
            'comercial' => '02', '02' => '02',
            'transporte' => '03', '03' => '03',
        );
        return $map[strtolower($uso)] ?? '01';
    }

    // -----------------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------------

    private function cleanXML(string $text): string
    {
        $text = strip_tags($text);
        $text = htmlspecialchars_decode($text, ENT_QUOTES);
        // Reemplazar chars inválidos en XML 1.0
        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);
        return trim($text);
    }

    private function buildReferenciaAviso(PLDOperacion $operacion): string
    {
        // Formato: VEH-YYYYMM-NNNN
        if (!empty($operacion->folio_interno)) {
            // PLD-YYYY-MM-0001 → VEH-YYYYMM-0001
            $parts = explode('-', $operacion->folio_interno);
            if (count($parts) == 4) {
                return 'VEH-'.$parts[1].$parts[2].'-'.$parts[3];
            }
        }
        return 'VEH-'.$operacion->mes_reportado.'-'.sprintf('%04d', $operacion->id);
    }

    /**
     * Guardar XML generado en disco y actualizar el aviso en BD
     *
     * @param string $xml_content  Contenido XML
     * @param string $mes_reportado  YYYYMM
     * @param object $user  Usuario Dolibarr
     * @return string|false  Ruta relativa del archivo o false
     */
    public function guardarXML(string $xml_content, string $mes_reportado, $user)
    {
        global $conf;

        $dir = $conf->modulecompliancepld->dir_output ?? DOL_DATA_ROOT.'/modulecompliancepld/xml';
        if (!is_dir($dir)) {
            dol_mkdir($dir);
        }

        $filename = 'PLD_VEH_'.$mes_reportado.'_'.dol_print_date(dol_now(), '%Y%m%d%H%M%S').'.xml';
        $filepath = $dir.'/'.$filename;

        $bytes = file_put_contents($filepath, $xml_content);
        if ($bytes === false) {
            $this->error = "No se pudo escribir el archivo $filepath";
            return false;
        }

        return $filepath;
    }
}
