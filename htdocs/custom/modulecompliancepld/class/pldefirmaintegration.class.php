<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

/**
 * Integración con e.firma del SAT
 * Firma digital de XMLs PLD/LFPIORPI con certificado .cer y llave privada .key
 */
class PLDEFirmaIntegration
{
    private $db;
    public $error = '';
    public $errors = array();

    private $cert_path = '';
    private $key_path = '';
    private $password = '';

    public function __construct($db)
    {
        $this->db = $db;
        $this->cert_path = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH');
        $this->key_path  = getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH');
        $this->password  = dolDecrypt(getDolGlobalString('MODULECOMPLIANCEPLD_EFIRMA_PASSWORD'));
    }

    /**
     * Firmar XML con e.firma SAT
     *
     * @param string $xml_content  XML a firmar
     * @return array|false  Array con sello_digital, cadena_original, numero_certificado; o false
     */
    public function firmarXML(string $xml_content)
    {
        dol_syslog(__METHOD__." Iniciando firma", LOG_INFO);

        if (!$this->validarCertificados()) {
            return false;
        }

        $cert_content = file_get_contents($this->cert_path);
        if ($cert_content === false) {
            $this->error = "No se pudo leer el certificado: ".$this->cert_path;
            return false;
        }

        $key_content = file_get_contents($this->key_path);
        if ($key_content === false) {
            $this->error = "No se pudo leer la llave privada: ".$this->key_path;
            return false;
        }

        $cadena_original = $this->generarCadenaOriginal($xml_content);
        if ($cadena_original === false) {
            return false;
        }

        $private_key = openssl_pkey_get_private($key_content, $this->password);
        if (!$private_key) {
            $this->error = "No se pudo cargar la llave privada. Verifique la contraseña. OpenSSL: ".openssl_error_string();
            return false;
        }

        $signature = '';
        $result = openssl_sign($cadena_original, $signature, $private_key, OPENSSL_ALGO_SHA256);

        if (!$result) {
            $this->error = "Error al generar la firma digital: ".openssl_error_string();
            return false;
        }

        $firma_base64 = base64_encode($signature);

        // Número de serie del certificado (número de certificado SAT)
        $cert_data = openssl_x509_parse($cert_content);
        $numero_certificado = $cert_data ? ($cert_data['serialNumber'] ?? '') : '';

        dol_syslog(__METHOD__." Firma generada OK. Cert#: $numero_certificado", LOG_INFO);

        return array(
            'sello_digital'       => $firma_base64,
            'cadena_original'     => $cadena_original,
            'numero_certificado'  => $numero_certificado,
            'fecha_firma'         => dol_now(),
        );
    }

    /**
     * Validar que los archivos de certificado y llave existen y son legibles
     */
    public function validarCertificados(): bool
    {
        if (empty($this->cert_path)) {
            $this->error = "Ruta del certificado e.firma no configurada (MODULECOMPLIANCEPLD_EFIRMA_CERT_PATH)";
            return false;
        }
        if (empty($this->key_path)) {
            $this->error = "Ruta de la llave privada e.firma no configurada (MODULECOMPLIANCEPLD_EFIRMA_KEY_PATH)";
            return false;
        }
        if (!file_exists($this->cert_path)) {
            $this->error = "Certificado no encontrado: ".$this->cert_path;
            return false;
        }
        if (!file_exists($this->key_path)) {
            $this->error = "Llave privada no encontrada: ".$this->key_path;
            return false;
        }
        return true;
    }

    /**
     * Generar cadena original para firma según especificaciones SAT
     * Extrae valores clave del XML en el orden requerido por el XSD VEH
     *
     * @param string $xml_content
     * @return string|false
     */
    public function generarCadenaOriginal(string $xml_content)
    {
        $dom = new DOMDocument();
        if (!@$dom->loadXML($xml_content)) {
            $this->error = "XML inválido para generar cadena original";
            return false;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('veh', 'http://www.uif.shcp.gob.mx/recepcion/veh');

        $partes = array();

        // Mes reportado
        $nodo = $xpath->query('//veh:mes_reportado');
        if ($nodo && $nodo->length > 0) {
            $partes[] = $nodo->item(0)->textContent;
        }

        // Clave sujeto obligado
        $nodo = $xpath->query('//veh:clave_sujeto_obligado');
        if ($nodo && $nodo->length > 0) {
            $partes[] = $nodo->item(0)->textContent;
        }

        // Por cada aviso: referencia, monto, fecha operación
        $avisos = $xpath->query('//veh:aviso');
        if ($avisos) {
            foreach ($avisos as $aviso) {
                $campos = array('referencia_aviso', 'monto_operacion', 'fecha_operacion');
                foreach ($campos as $campo) {
                    $nodos = $xpath->query('veh:'.$campo, $aviso);
                    if (!$nodos) {
                        // Buscar en acto_operacion
                        $nodos = $xpath->query('veh:acto_operacion/veh:'.$campo, $aviso);
                    }
                    if ($nodos && $nodos->length > 0) {
                        $partes[] = $nodos->item(0)->textContent;
                    }
                }
            }
        }

        // Cadena original formato SAT: ||campo1|campo2|...|campoN||
        return '||'.implode('|', $partes).'||';
    }

    /**
     * Incrustar el sello digital en el XML (elemento <sello_digital> al final del informe)
     *
     * @param string $xml_content  XML original
     * @param array  $firma        Resultado de firmarXML()
     * @return string XML firmado
     */
    public function incrustarSello(string $xml_content, array $firma): string
    {
        $dom = new DOMDocument();
        $dom->formatOutput = true;
        @$dom->loadXML($xml_content);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('veh', 'http://www.uif.shcp.gob.mx/recepcion/veh');

        $informe = $xpath->query('//veh:informe')->item(0);
        if ($informe) {
            $sello = $dom->createElement('sello_digital', $firma['sello_digital']);
            $informe->appendChild($sello);

            $cert = $dom->createElement('numero_certificado', $firma['numero_certificado']);
            $informe->appendChild($cert);
        }

        return $dom->saveXML();
    }
}
