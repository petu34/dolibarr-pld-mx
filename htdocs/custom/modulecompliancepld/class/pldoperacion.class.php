<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/repository/PLDOperacionRepository.php';

class PLDOperacion extends CommonObject
{
    public $element = 'pld_operacion';
    public $table_element = 'pld_operacion';
    public $module = 'modulecompliancepld';
    
    public $id;
    public $rowid;
    public $entity;
    
    public $fk_facture;
    public $fk_propal;
    public $fk_societe;
    public $fk_product;
    
    public $tipo_operacion;
    public $tipo_actividad_vulnerable;
    
    public $fecha_operacion;
    public $mes_reportado;
    public $folio_interno;
    
    public $moneda;
    public $monto_mxn;
    /** Monto sin IVA — usado para comparar con umbral (Art. 6 DOF 27/03/2026) */
    public $monto_sin_impuestos;
    /** Tasa de IVA aplicada (default 0.16). monto_mxn = monto_sin_impuestos * (1 + tasa_impuesto) */
    public $tasa_impuesto;
    /** Fecha inicio custodia 10 años (Art. 20 + Trans. 7º DOF). NULL = usa fecha_operacion con tope 2025-07-17 */
    public $fecha_inicio_custodia;
    /** Clave de importación masiva. SEED_PLD_TEST = dato de prueba eliminable */
    public $import_key;

    public $supera_umbral;
    public $cliente_identificado;
    public $documentacion_completa;
    
    public $requiere_aviso;
    public $aviso_presentado;
    public $fk_pld_aviso;
    
    public $genera_alerta;
    public $fk_pld_alerta;
    
    public $estado;
    
    public $datec;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    
    const UMBRAL_VEHICULO_NUEVO = 377778.20;
    const UMBRAL_VEHICULO_USADO = 117310.00;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = 1;
        $this->tipo_operacion = 'venta_vehiculo';
        $this->moneda = 'MXN';
        $this->estado = 'borrador';
        $this->supera_umbral = 0;
        $this->cliente_identificado = 0;
        $this->documentacion_completa = 0;
        $this->requiere_aviso = 0;
        $this->aviso_presentado = 0;
        $this->genera_alerta = 0;
        $this->tasa_impuesto = 0.16;
    }
    
    public function create($user, $notrigger = 0): int
    {
        global $conf, $langs;
        
        $error = 0;
        $now = dol_now();
        
        $this->db->begin();
        
        $this->datec = $now;
        $this->fk_user_creat = $user->id;
        
        if (empty($this->mes_reportado) && !empty($this->fecha_operacion)) {
            $this->mes_reportado = date('Ym', strtotime($this->fecha_operacion));
        }
        
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= " entity, fk_facture, fk_propal, fk_societe, fk_product,";
        $sql .= " tipo_operacion, tipo_actividad_vulnerable,";
        $sql .= " fecha_operacion, mes_reportado, folio_interno,";
        $sql .= " moneda, monto_mxn, monto_sin_impuestos, tasa_impuesto,";
        $sql .= " supera_umbral, cliente_identificado, documentacion_completa,";
        $sql .= " requiere_aviso, aviso_presentado, fk_pld_aviso,";
        $sql .= " genera_alerta, fk_pld_alerta,";
        $sql .= " estado,";
        $sql .= " fecha_inicio_custodia, import_key,";
        $sql .= " datec, fk_user_creat";
        $sql .= ") VALUES (";
        $sql .= " ".(int)$this->entity.",";
        $sql .= " ".($this->fk_facture > 0 ? (int)$this->fk_facture : 'NULL').",";
        $sql .= " ".($this->fk_propal > 0 ? (int)$this->fk_propal : 'NULL').",";
        $sql .= " ".(int)$this->fk_societe.",";
        $sql .= " ".($this->fk_product > 0 ? (int)$this->fk_product : 'NULL').",";
        $sql .= " '".$this->db->escape($this->tipo_operacion)."',";
        $sql .= " '".$this->db->escape($this->tipo_actividad_vulnerable)."',";
        $sql .= " '".$this->db->escape($this->fecha_operacion)."',";
        $sql .= " '".$this->db->escape($this->mes_reportado)."',";
        $sql .= " ".($this->folio_interno ? "'".$this->db->escape($this->folio_interno)."'" : 'NULL').",";
        $sql .= " '".$this->db->escape($this->moneda)."',";
        $sql .= " ".(float)$this->monto_mxn.",";
        $sql .= " ".($this->monto_sin_impuestos !== null ? (float)$this->monto_sin_impuestos : 'NULL').",";
        $sql .= " ".(float)($this->tasa_impuesto ?? 0.16).",";
        $sql .= " ".(int)$this->supera_umbral.",";
        $sql .= " ".(int)$this->cliente_identificado.",";
        $sql .= " ".(int)$this->documentacion_completa.",";
        $sql .= " ".(int)$this->requiere_aviso.",";
        $sql .= " ".(int)$this->aviso_presentado.",";
        $sql .= " ".($this->fk_pld_aviso > 0 ? (int)$this->fk_pld_aviso : 'NULL').",";
        $sql .= " ".(int)$this->genera_alerta.",";
        $sql .= " ".($this->fk_pld_alerta > 0 ? (int)$this->fk_pld_alerta : 'NULL').",";
        $sql .= " '".$this->db->escape($this->estado)."',";
        $sql .= " ".($this->fecha_inicio_custodia ? "'".$this->db->escape($this->fecha_inicio_custodia)."'" : 'NULL').",";
        $sql .= " ".($this->import_key ? "'".$this->db->escape($this->import_key)."'" : 'NULL').",";
        $sql .= " '".$this->db->idate($now)."',";
        $sql .= " ".(int)$this->fk_user_creat;
        $sql .= ")";
        
        $resql = $this->db->query($sql);
        
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        } else {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            $this->rowid = $this->id;
        }
        
        if (!$error && !$notrigger) {
            $result = $this->call_trigger('PLD_OPERACION_CREATE', $user);
            if ($result < 0) {
                $error++;
            }
        }
        
        if (!$error) {
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }
    
    public function fetch($id, $ref = ''): int
    {
        $sql = "SELECT";
        $sql .= " rowid, entity, fk_facture, fk_propal, fk_societe, fk_product,";
        $sql .= " tipo_operacion, tipo_actividad_vulnerable,";
        $sql .= " fecha_operacion, mes_reportado, folio_interno,";
        $sql .= " moneda, monto_mxn, monto_sin_impuestos, tasa_impuesto,";
        $sql .= " supera_umbral, cliente_identificado, documentacion_completa,";
        $sql .= " requiere_aviso, aviso_presentado, fk_pld_aviso,";
        $sql .= " genera_alerta, fk_pld_alerta,";
        $sql .= " estado,";
        $sql .= " fecha_inicio_custodia, import_key,";
        $sql .= " datec, tms, fk_user_creat, fk_user_modif";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        
        if ($id > 0) {
            $sql .= " WHERE rowid = ".(int)$id;
        } elseif ($ref) {
            $sql .= " WHERE folio_interno = '".$this->db->escape($ref)."'";
        } else {
            return -1;
        }
        
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->rowid = $obj->rowid;
                $this->entity = $obj->entity;
                $this->fk_facture = $obj->fk_facture;
                $this->fk_propal  = $obj->fk_propal;
                $this->fk_societe = $obj->fk_societe;
                $this->fk_product = $obj->fk_product;
                $this->tipo_operacion = $obj->tipo_operacion;
                $this->tipo_actividad_vulnerable = $obj->tipo_actividad_vulnerable;
                $this->fecha_operacion = $obj->fecha_operacion;
                $this->mes_reportado = $obj->mes_reportado;
                $this->folio_interno = $obj->folio_interno;
                $this->moneda = $obj->moneda;
                $this->monto_mxn = $obj->monto_mxn;
                $this->monto_sin_impuestos = $obj->monto_sin_impuestos;
                $this->tasa_impuesto = $obj->tasa_impuesto ?? 0.16;
                $this->fecha_inicio_custodia = $obj->fecha_inicio_custodia;
                $this->import_key = $obj->import_key;
                $this->supera_umbral = $obj->supera_umbral;
                $this->cliente_identificado = $obj->cliente_identificado;
                $this->documentacion_completa = $obj->documentacion_completa;
                $this->requiere_aviso = $obj->requiere_aviso;
                $this->aviso_presentado = $obj->aviso_presentado;
                $this->fk_pld_aviso = $obj->fk_pld_aviso;
                $this->genera_alerta = $obj->genera_alerta;
                $this->fk_pld_alerta = $obj->fk_pld_alerta;
                $this->estado = $obj->estado;
                $this->datec = $this->db->jdate($obj->datec);
                $this->tms = $this->db->jdate($obj->tms);
                $this->fk_user_creat = $obj->fk_user_creat;
                $this->fk_user_modif = $obj->fk_user_modif;
                
                $this->db->free($resql);
                return 1;
            } else {
                $this->db->free($resql);
                return 0;
            }
        } else {
            $this->errors[] = "Error ".$this->db->lasterror();
            return -1;
        }
    }
    
    public function update($user, $notrigger = 0): int
    {
        $error = 0;
        
        $this->db->begin();
        
        $this->fk_user_modif = $user->id;
        
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= " fk_facture = ".($this->fk_facture > 0 ? (int)$this->fk_facture : 'NULL').",";
        $sql .= " fk_propal = ".($this->fk_propal > 0 ? (int)$this->fk_propal : 'NULL').",";
        $sql .= " fk_societe = ".(int)$this->fk_societe.",";
        $sql .= " fk_product = ".($this->fk_product > 0 ? (int)$this->fk_product : 'NULL').",";
        $sql .= " tipo_operacion = '".$this->db->escape($this->tipo_operacion)."',";
        $sql .= " tipo_actividad_vulnerable = '".$this->db->escape($this->tipo_actividad_vulnerable)."',";
        $sql .= " fecha_operacion = '".$this->db->escape($this->fecha_operacion)."',";
        $sql .= " mes_reportado = '".$this->db->escape($this->mes_reportado)."',";
        $sql .= " folio_interno = ".($this->folio_interno ? "'".$this->db->escape($this->folio_interno)."'" : 'NULL').",";
        $sql .= " moneda = '".$this->db->escape($this->moneda)."',";
        $sql .= " monto_mxn = ".(float)$this->monto_mxn.",";
        $sql .= " monto_sin_impuestos = ".($this->monto_sin_impuestos !== null ? (float)$this->monto_sin_impuestos : 'NULL').",";
        $sql .= " tasa_impuesto = ".(float)($this->tasa_impuesto ?? 0.16).",";
        $sql .= " fecha_inicio_custodia = ".($this->fecha_inicio_custodia ? "'".$this->db->escape($this->fecha_inicio_custodia)."'" : 'NULL').",";
        $sql .= " import_key = ".($this->import_key ? "'".$this->db->escape($this->import_key)."'" : 'NULL').",";
        $sql .= " supera_umbral = ".(int)$this->supera_umbral.",";
        $sql .= " cliente_identificado = ".(int)$this->cliente_identificado.",";
        $sql .= " documentacion_completa = ".(int)$this->documentacion_completa.",";
        $sql .= " requiere_aviso = ".(int)$this->requiere_aviso.",";
        $sql .= " aviso_presentado = ".(int)$this->aviso_presentado.",";
        $sql .= " fk_pld_aviso = ".($this->fk_pld_aviso > 0 ? (int)$this->fk_pld_aviso : 'NULL').",";
        $sql .= " genera_alerta = ".(int)$this->genera_alerta.",";
        $sql .= " fk_pld_alerta = ".($this->fk_pld_alerta > 0 ? (int)$this->fk_pld_alerta : 'NULL').",";
        $sql .= " estado = '".$this->db->escape($this->estado)."',";
        $sql .= " fk_user_modif = ".(int)$this->fk_user_modif;
        $sql .= " WHERE rowid = ".(int)$this->id;
        
        $resql = $this->db->query($sql);
        
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }
        
        if (!$error && !$notrigger) {
            $result = $this->call_trigger('PLD_OPERACION_MODIFY', $user);
            if ($result < 0) {
                $error++;
            }
        }
        
        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }
    
    public function delete($user, $notrigger = 0): int
    {
        $error = 0;
        
        $this->db->begin();
        
        if (!$error && !$notrigger) {
            $result = $this->call_trigger('PLD_OPERACION_DELETE', $user);
            if ($result < 0) {
                $error++;
            }
        }
        
        if (!$error) {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
            $sql .= " WHERE rowid = ".(int)$this->id;
            
            $resql = $this->db->query($sql);
            
            if (!$resql) {
                $error++;
                $this->errors[] = "Error ".$this->db->lasterror();
            }
        }
        
        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }
    
    /**
     * Devuelve el monto sin IVA para comparar con el umbral (Art. 6 DOF 27/03/2026).
     * Si monto_sin_impuestos no está capturado, asume que monto_mxn ya es sin IVA.
     */
    public function getMontoBruto(): float
    {
        return ($this->monto_sin_impuestos !== null)
            ? (float)$this->monto_sin_impuestos
            : (float)$this->monto_mxn;
    }

    /**
     * Devuelve el monto con IVA para reportar en el XML del SAT (Art. 6 DOF 27/03/2026).
     * monto_mxn siempre representa el total con impuestos.
     */
    public function getMontoXML(): float
    {
        return (float)$this->monto_mxn;
    }

    /**
     * Recalcula monto_mxn a partir de monto_sin_impuestos y tasa_impuesto.
     * Llamar cuando se edita cualquiera de los dos campos en el formulario.
     */
    public function calcularMontoTotal(): void
    {
        if ($this->monto_sin_impuestos !== null) {
            $this->monto_mxn = round((float)$this->monto_sin_impuestos * (1 + (float)($this->tasa_impuesto ?? 0.16)), 2);
        }
    }

    /**
     * Evalúa si la operación supera el umbral de reporte.
     * Usa monto_sin_impuestos para la comparación (Art. 6 DOF 2026).
     * También evalúa acumulación de 6 meses para el mismo cliente (Art. 7 Regl.).
     *
     * @param string $tipo_vehiculo  'nuevo' | 'usado'
     * @return array  supera_umbral, umbral_aplicado, requiere_aviso, diferencia, motivo
     */
    public function evaluarUmbral(string $tipo_vehiculo): array
    {
        $umbral = ($tipo_vehiculo === 'nuevo')
            ? (float)getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_NUEVO', (string)self::UMBRAL_VEHICULO_NUEVO)
            : (float)getDolGlobalString('MODULECOMPLIANCEPLD_UMBRAL_VEH_USADO', (string)self::UMBRAL_VEHICULO_USADO);

        $monto_bruto = $this->getMontoBruto();
        $supera_individual = $monto_bruto >= $umbral;
        $motivo = '';

        // Verificar acumulación 6 meses para el mismo cliente (Art. 7 Regl. LFPIORPI)
        $supera_acumulado = false;
        if ($this->fk_societe > 0) {
            $repo = new PLDOperacionRepository($this->db);
            $acumulado_previo = $repo->getAcumuladoSeisMeses(
                (int)$this->fk_societe,
                $this->id > 0 ? (int)$this->id : null
            );
            $total_con_esta = $acumulado_previo + $monto_bruto;
            if (!$supera_individual && $total_con_esta >= $umbral) {
                $supera_acumulado = true;
                $motivo = 'acumulacion_6_meses';
            }
        }

        $supera = $supera_individual || $supera_acumulado;
        if ($supera_individual) {
            $motivo = 'supera_umbral_individual';
        }

        $this->supera_umbral = $supera ? 1 : 0;
        $this->requiere_aviso = $supera ? 1 : 0;

        return [
            'supera_umbral'    => $supera,
            'umbral_aplicado'  => $umbral,
            'requiere_aviso'   => $supera,
            'diferencia'       => $monto_bruto - $umbral,
            'motivo'           => $motivo,
        ];
    }

    /**
     * Calcula la fecha límite de custodia (10 años, Art. 20 + Trans. 7º DOF 2026).
     * El reloj para registros anteriores al 17/07/2025 inicia en esa fecha.
     *
     * @return string  Fecha en formato Y-m-d
     */
    public function getFechaFinCustodia(): string
    {
        $CUSTODIA_INICIO_LEGAL = '2025-07-17'; // Transitorio Séptimo DOF
        $anios = 10;

        if (!empty($this->fecha_inicio_custodia)) {
            $inicio = $this->fecha_inicio_custodia;
        } elseif (!empty($this->fecha_operacion)) {
            $fecha_op = is_numeric($this->fecha_operacion)
                ? date('Y-m-d', (int)$this->fecha_operacion)
                : substr($this->fecha_operacion, 0, 10);
            $inicio = max($fecha_op, $CUSTODIA_INICIO_LEGAL);
        } else {
            $inicio = $CUSTODIA_INICIO_LEGAL;
        }

        return date('Y-m-d', strtotime($inicio.' +'.$anios.' years'));
    }
    
    public function generarFolioInterno(): string
    {
        $year  = date('Y');
        $month = date('m');

        $repo        = new PLDOperacionRepository($this->db);
        $consecutivo = $repo->getUltimoFolioConsecutivo($year, $month) + 1;

        $this->folio_interno = sprintf('PLD-%s-%s-%04d', $year, $month, $consecutivo);

        return $this->folio_interno;
    }

    /**
     * Carga datos del cliente (llx_societe + extrafields PLD)
     * Popula $this->cliente (stdClass) y $this->tiene_beneficiario
     *
     * @return int 1=ok, -1=error
     */
    public function fetchCliente(): int
    {
        if (!$this->fk_societe) {
            return -1;
        }

        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

        $societe = new Societe($this->db);
        $result = $societe->fetch($this->fk_societe);
        if ($result <= 0) {
            $this->errors[] = "fetchCliente: societe ".$this->fk_societe." no encontrada";
            return -1;
        }

        $societe->fetch_optionals();
        $opts = $societe->array_options;

        $this->cliente = new stdClass();
        $this->cliente->id = $societe->id;
        $this->cliente->phone = $societe->phone;

        // Tipo de persona
        $this->cliente->tipo_persona = $opts['options_pld_tipo_persona'] ?? 'fisica';

        // Nombre según tipo
        if ($this->cliente->tipo_persona == 'moral') {
            $this->cliente->nombre = '';
            $this->cliente->apellido_paterno = '';
            $this->cliente->apellido_materno = '';
            $this->cliente->denominacion_razon = $opts['options_pld_denominacion_razon'] ?: $societe->name;
        } else {
            // PF: firstname=nombre, name=apellidos (puede ser "Pat Mat")
            $this->cliente->nombre = $societe->firstname ?: $societe->name;
            // TODO: revertir cast (string) una vez que se garantice que firstname nunca sea null en llx_societe
            $apellidos = trim(str_replace((string)$societe->firstname, '', (string)$societe->name));
            $partes = preg_split('/\s+/', trim($societe->name), 2);
            $this->cliente->apellido_paterno = $partes[0] ?? '';
            $this->cliente->apellido_materno = $partes[1] ?? '';
            $this->cliente->denominacion_razon = '';
        }

        // Identificación
        $this->cliente->rfc = strtoupper($opts['options_pld_rfc_validado'] ?? ($societe->idprof2 ?? ''));
        $this->cliente->curp = strtoupper($opts['options_pld_curp'] ?? '');
        $this->cliente->fecha_nacimiento = $opts['options_pld_fecha_nacimiento'] ?? '';
        $this->cliente->pais_nacimiento = $opts['options_pld_pais_nacimiento'] ?? 'MX';
        $this->cliente->nacionalidad = $opts['options_pld_nacionalidad'] ?? 'MX';
        $this->cliente->actividad_economica = $opts['options_pld_actividad_economica'] ?? '';

        // Domicilio PLD (fallback a campos nativos de societe)
        $this->cliente->calle = $opts['options_pld_calle'] ?: $societe->address;
        $this->cliente->numero_exterior = $opts['options_pld_numero_exterior'] ?? '';
        $this->cliente->numero_interior = $opts['options_pld_numero_interior'] ?? '';
        $this->cliente->colonia = $opts['options_pld_colonia'] ?? '';
        $this->cliente->codigo_postal = $opts['options_pld_codigo_postal'] ?: $societe->zip;
        $this->cliente->municipio = $opts['options_pld_municipio'] ?: $societe->town;
        $this->cliente->estado = $opts['options_pld_estado'] ?? '';
        $this->cliente->pais = $opts['options_pld_pais'] ?? 'MX';
        $this->cliente->es_domicilio_extranjero = !empty($opts['options_pld_es_domicilio_extranjero']);

        // PM adicionales
        $this->cliente->fecha_constitucion = $opts['options_pld_fecha_constitucion'] ?? '';
        $this->cliente->giro_mercantil = $opts['options_pld_giro_mercantil'] ?? '';

        // Control PLD
        $this->tiene_beneficiario = !empty($opts['options_pld_tiene_beneficiario']);
        $this->cliente->tiene_beneficiario = $this->tiene_beneficiario;

        return 1;
    }

    /**
     * Carga datos del vehículo (llx_product + extrafields PLD)
     * Popula $this->vehiculo (stdClass)
     *
     * @return int 1=ok, -1=error
     */
    public function fetchVehiculo(): int
    {
        if (!$this->fk_product) {
            return -1;
        }

        require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

        $product = new Product($this->db);
        $result = $product->fetch($this->fk_product);
        if ($result <= 0) {
            $this->errors[] = "fetchVehiculo: product ".$this->fk_product." no encontrado";
            return -1;
        }

        $product->fetch_optionals();
        $opts = $product->array_options;

        $this->vehiculo = new stdClass();
        $this->vehiculo->id = $product->id;
        $this->vehiculo->label = $product->label;

        $this->vehiculo->tipo_vehiculo = $opts['options_pld_tipo_vehiculo'] ?? 'terrestre';
        $this->vehiculo->marca = $opts['options_pld_marca'] ?? '';
        $this->vehiculo->modelo = $opts['options_pld_modelo'] ?? '';
        $this->vehiculo->anio_modelo = $opts['options_pld_anio_modelo'] ?? date('Y');
        $this->vehiculo->vin = strtoupper($opts['options_pld_vin'] ?? '');
        $this->vehiculo->numero_serie = strtoupper($opts['options_pld_numero_serie'] ?? '');
        $this->vehiculo->placas = $opts['options_pld_placas'] ?? '';
        $this->vehiculo->origen = $opts['options_pld_origen'] ?? 'nacional';
        $this->vehiculo->estado_vehiculo = $opts['options_pld_estado_vehiculo'] ?? 'nuevo';
        $this->vehiculo->uso_destino = $opts['options_pld_uso_destino'] ?? 'particular';
        $this->vehiculo->nivel_blindaje = $opts['options_pld_nivel_blindaje'] ?? '0';
        $this->vehiculo->kilometraje = (int)($opts['options_pld_kilometraje'] ?? 0);

        // Descripcion operacion generada
        $this->descripcion_operacion = trim(
            'Compraventa de vehículo '.$this->vehiculo->marca.' '
            .$this->vehiculo->modelo.' '.$this->vehiculo->anio_modelo
        );

        return 1;
    }

    /**
     * Carga beneficiarios controladores de la empresa (llx_pld_beneficiario)
     * Popula $this->beneficiarios (array de PLDBeneficiario)
     *
     * @return int 1=ok (aunque no haya registros), -1=error
     */
    public function fetchBeneficiarios(): int
    {
        if (!$this->fk_societe) {
            return -1;
        }

        require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldbeneficiario.class.php';

        $repo = new PLDOperacionRepository($this->db);
        $ids  = $repo->fetchBeneficiarioIds((int)$this->fk_societe);

        $this->beneficiarios = [];
        foreach ($ids as $id) {
            $ben = new PLDBeneficiario($this->db);
            if ($ben->fetch($id) > 0) {
                $this->beneficiarios[] = $ben;
            }
        }

        return 1;
    }

    /**
     * Carga pagos y sus extrafields PLD para la factura asociada
     * Popula $this->formas_pago, $this->forma_pago_principal,
     *         $this->usa_transferencia, $this->banco_destino,
     *         $this->cuenta_destino, $this->tipo_cambio
     *
     * @return int 1=ok, -1=error
     */
    public function fetchFormasPago(): int
    {
        $this->formas_pago          = [];
        $this->forma_pago_principal = '';
        $this->usa_transferencia    = false;
        $this->banco_destino        = '';
        $this->cuenta_destino       = '';
        $this->tipo_cambio          = 1.0;

        if (!$this->fk_facture) {
            return -1;
        }

        $repo  = new PLDOperacionRepository($this->db);
        $pagos = $repo->fetchFormasPago((int)$this->fk_facture);

        $first = true;
        foreach ($pagos as $pago) {
            if ($first) {
                $this->forma_pago_principal = $pago->pld_forma_pago;
                if ($this->forma_pago_principal == '04') {
                    $this->usa_transferencia = true;
                    $this->banco_destino     = $pago->pld_banco_destino;
                    $this->cuenta_destino    = $pago->pld_cuenta_destino;
                }
                $first = false;
            }
            $this->formas_pago[] = $pago;
        }

        return 1;
    }
}
