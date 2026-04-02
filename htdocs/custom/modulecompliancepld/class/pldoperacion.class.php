<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class PLDOperacion extends CommonObject
{
    public $element = 'pld_operacion';
    public $table_element = 'pld_operacion';
    public $module = 'modulecompliancepld';
    
    public $id;
    public $rowid;
    public $entity;
    
    public $fk_facture;
    public $fk_societe;
    public $fk_product;
    
    public $tipo_operacion;
    public $tipo_actividad_vulnerable;
    
    public $fecha_operacion;
    public $mes_reportado;
    public $folio_interno;
    
    public $moneda;
    public $monto_mxn;
    // Art. 6 DOF 27/03/2026: monto base sin impuestos (para comparar con umbral UMA)
    public $monto_sin_impuestos; // NULL = no aplica IVA distinto / igual a monto_mxn
    public $tasa_impuesto = 0.16;

    // Art. 20 + Transitorio 7º DOF 27/03/2026
    public $fecha_inicio_custodia;

    // Art. 7 Bis DOF 27/03/2026
    public $estado_operacion = 'completada'; // completada | intentada | cancelada
    public $motivo_no_completada;
    public $fecha_deteccion_alerta;

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
    const ESTADOS_OPERACION = array('completada', 'intentada', 'cancelada');

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
        $this->estado_operacion = 'completada';
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
        
        // Validar estado_operacion (Art. 7 Bis)
        if (!empty($this->estado_operacion) && !in_array($this->estado_operacion, self::ESTADOS_OPERACION)) {
            $this->errors[] = "estado_operacion '{$this->estado_operacion}' no valido";
            $this->db->rollback();
            return -1;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= " entity, fk_facture, fk_societe, fk_product,";
        $sql .= " tipo_operacion, tipo_actividad_vulnerable,";
        $sql .= " fecha_operacion, mes_reportado, folio_interno,";
        $sql .= " moneda, monto_mxn, monto_sin_impuestos, tasa_impuesto,";
        $sql .= " supera_umbral, cliente_identificado, documentacion_completa,";
        $sql .= " requiere_aviso, aviso_presentado, fk_pld_aviso,";
        $sql .= " genera_alerta, fk_pld_alerta,";
        $sql .= " estado,";
        $sql .= " fecha_inicio_custodia, estado_operacion, motivo_no_completada, fecha_deteccion_alerta,";
        $sql .= " datec, fk_user_creat";
        $sql .= ") VALUES (";
        $sql .= " ".(int)$this->entity.",";
        $sql .= " ".($this->fk_facture > 0 ? (int)$this->fk_facture : 'NULL').",";
        $sql .= " ".(int)$this->fk_societe.",";
        $sql .= " ".($this->fk_product > 0 ? (int)$this->fk_product : 'NULL').",";
        $sql .= " '".$this->db->escape($this->tipo_operacion)."',";
        $sql .= " '".$this->db->escape($this->tipo_actividad_vulnerable)."',";
        $sql .= " '".$this->db->escape($this->fecha_operacion)."',";
        $sql .= " '".$this->db->escape($this->mes_reportado)."',";
        $sql .= " ".($this->folio_interno ? "'".$this->db->escape($this->folio_interno)."'" : 'NULL').",";
        $sql .= " '".$this->db->escape($this->moneda)."',";
        $sql .= " ".(float)$this->monto_mxn.",";
        $sql .= " ".(isset($this->monto_sin_impuestos) && $this->monto_sin_impuestos !== null ? (float)$this->monto_sin_impuestos : 'NULL').",";
        $sql .= " ".(float)$this->tasa_impuesto.",";
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
        $sql .= " '".$this->db->escape($this->estado_operacion ?: 'completada')."',";
        $sql .= " ".($this->motivo_no_completada ? "'".$this->db->escape($this->motivo_no_completada)."'" : 'NULL').",";
        $sql .= " ".($this->fecha_deteccion_alerta ? "'".$this->db->escape($this->fecha_deteccion_alerta)."'" : 'NULL').",";
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
        $sql .= " rowid, entity, fk_facture, fk_societe, fk_product,";
        $sql .= " tipo_operacion, tipo_actividad_vulnerable,";
        $sql .= " fecha_operacion, mes_reportado, folio_interno,";
        $sql .= " moneda, monto_mxn, monto_sin_impuestos, tasa_impuesto,";
        $sql .= " supera_umbral, cliente_identificado, documentacion_completa,";
        $sql .= " requiere_aviso, aviso_presentado, fk_pld_aviso,";
        $sql .= " genera_alerta, fk_pld_alerta,";
        $sql .= " estado,";
        $sql .= " fecha_inicio_custodia, estado_operacion, motivo_no_completada, fecha_deteccion_alerta,";
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
                $this->tasa_impuesto = $obj->tasa_impuesto;
                $this->supera_umbral = $obj->supera_umbral;
                $this->cliente_identificado = $obj->cliente_identificado;
                $this->documentacion_completa = $obj->documentacion_completa;
                $this->requiere_aviso = $obj->requiere_aviso;
                $this->aviso_presentado = $obj->aviso_presentado;
                $this->fk_pld_aviso = $obj->fk_pld_aviso;
                $this->genera_alerta = $obj->genera_alerta;
                $this->fk_pld_alerta = $obj->fk_pld_alerta;
                $this->estado = $obj->estado;
                $this->fecha_inicio_custodia = $obj->fecha_inicio_custodia;
                $this->estado_operacion = $obj->estado_operacion ?: 'completada';
                $this->motivo_no_completada = $obj->motivo_no_completada;
                $this->fecha_deteccion_alerta = $obj->fecha_deteccion_alerta;
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
        
        // Validar estado_operacion (Art. 7 Bis)
        if (!empty($this->estado_operacion) && !in_array($this->estado_operacion, self::ESTADOS_OPERACION)) {
            $this->errors[] = "estado_operacion '{$this->estado_operacion}' no valido";
            $this->db->rollback();
            return -1;
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= " fk_facture = ".($this->fk_facture > 0 ? (int)$this->fk_facture : 'NULL').",";
        $sql .= " fk_societe = ".(int)$this->fk_societe.",";
        $sql .= " fk_product = ".($this->fk_product > 0 ? (int)$this->fk_product : 'NULL').",";
        $sql .= " tipo_operacion = '".$this->db->escape($this->tipo_operacion)."',";
        $sql .= " tipo_actividad_vulnerable = '".$this->db->escape($this->tipo_actividad_vulnerable)."',";
        $sql .= " fecha_operacion = '".$this->db->escape($this->fecha_operacion)."',";
        $sql .= " mes_reportado = '".$this->db->escape($this->mes_reportado)."',";
        $sql .= " folio_interno = ".($this->folio_interno ? "'".$this->db->escape($this->folio_interno)."'" : 'NULL').",";
        $sql .= " moneda = '".$this->db->escape($this->moneda)."',";
        $sql .= " monto_mxn = ".(float)$this->monto_mxn.",";
        $sql .= " monto_sin_impuestos = ".(isset($this->monto_sin_impuestos) && $this->monto_sin_impuestos !== null ? (float)$this->monto_sin_impuestos : 'NULL').",";
        $sql .= " tasa_impuesto = ".(float)$this->tasa_impuesto.",";
        $sql .= " supera_umbral = ".(int)$this->supera_umbral.",";
        $sql .= " cliente_identificado = ".(int)$this->cliente_identificado.",";
        $sql .= " documentacion_completa = ".(int)$this->documentacion_completa.",";
        $sql .= " requiere_aviso = ".(int)$this->requiere_aviso.",";
        $sql .= " aviso_presentado = ".(int)$this->aviso_presentado.",";
        $sql .= " fk_pld_aviso = ".($this->fk_pld_aviso > 0 ? (int)$this->fk_pld_aviso : 'NULL').",";
        $sql .= " genera_alerta = ".(int)$this->genera_alerta.",";
        $sql .= " fk_pld_alerta = ".($this->fk_pld_alerta > 0 ? (int)$this->fk_pld_alerta : 'NULL').",";
        $sql .= " estado = '".$this->db->escape($this->estado)."',";
        $sql .= " fecha_inicio_custodia = ".($this->fecha_inicio_custodia ? "'".$this->db->escape($this->fecha_inicio_custodia)."'" : 'NULL').",";
        $sql .= " estado_operacion = '".$this->db->escape($this->estado_operacion ?: 'completada')."',";
        $sql .= " motivo_no_completada = ".($this->motivo_no_completada ? "'".$this->db->escape($this->motivo_no_completada)."'" : 'NULL').",";
        $sql .= " fecha_deteccion_alerta = ".($this->fecha_deteccion_alerta ? "'".$this->db->escape($this->fecha_deteccion_alerta)."'" : 'NULL').",";
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
     * Retorna el monto base sin impuestos (para comparar con umbral UMA).
     * Art. 6 DOF 27/03/2026: el umbral se compara contra el monto sin IVA.
     */
    public function getMontoBruto(): float
    {
        return (float)($this->monto_sin_impuestos ?? $this->monto_mxn);
    }

    /**
     * Retorna el monto total con impuestos (para el XML SAT).
     */
    public function getMontoXML(): float
    {
        return (float)$this->monto_mxn;
    }

    /**
     * Evalua si la operacion supera el umbral de aviso.
     * Art. 6 DOF 27/03/2026: umbral se compara contra monto sin impuestos.
     * Art. 7 LFPIORPI / Reglamento: se considera acumulacion de 6 meses.
     */
    public function evaluarUmbral(string $tipo_vehiculo): array
    {
        $umbral = ($tipo_vehiculo === 'nuevo')
            ? self::UMBRAL_VEHICULO_NUEVO
            : self::UMBRAL_VEHICULO_USADO;

        // Comparar monto sin impuestos contra umbral (Art. 6 DOF 27/03/2026)
        $montoBase = $this->getMontoBruto();
        $supera = $montoBase >= $umbral;

        // Acumulacion 6 meses con el mismo cliente (Art. 7 LFPIORPI / Art. 7 Reglamento)
        if (!$supera && $this->fk_societe > 0) {
            $fecha_hace_6m = date('Y-m-d', strtotime('-6 months', strtotime($this->fecha_operacion ?: date('Y-m-d'))));
            $sql  = "SELECT SUM(COALESCE(monto_sin_impuestos, monto_mxn)) as acumulado";
            $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
            $sql .= " WHERE fk_societe = ".(int)$this->fk_societe;
            $sql .= " AND fecha_operacion >= '".$this->db->escape($fecha_hace_6m)."'";
            $sql .= " AND estado_operacion NOT IN ('cancelada')";
            if ($this->id > 0) {
                $sql .= " AND rowid != ".(int)$this->id;
            }
            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                $acumulado = (float)($obj->acumulado ?? 0) + $montoBase;
                $supera = $acumulado >= $umbral;
                $this->db->free($resql);
            }
        }

        $this->supera_umbral = $supera ? 1 : 0;
        $this->requiere_aviso = $supera ? 1 : 0;

        return array(
            'supera_umbral'   => $supera,
            'umbral_aplicado' => $umbral,
            'requiere_aviso'  => $supera,
            'diferencia'      => $montoBase - $umbral,
        );
    }

    /**
     * Calcula la fecha en que vence la obligacion de conservacion (10 anos).
     * El Transitorio 7 del Reglamento (DOF 27/03/2026) fija el inicio
     * del plazo en el 17 de julio de 2025 para registros anteriores a esa fecha.
     * Per LFPIORPI Art. 20.
     */
    public function getFechaFinCustodia(): string
    {
        $inicio = $this->fecha_inicio_custodia;
        if (empty($inicio)) {
            $fop = $this->fecha_operacion ?: date('Y-m-d');
            // Si la operacion es anterior al 17-jul-2025, el reloj inicio ese dia
            $inicio = ($fop < '2025-07-17') ? '2025-07-17' : $fop;
        }
        return date('Y-m-d', strtotime($inicio.' +10 years'));
    }

    /**
     * Calcula la fecha/hora limite para presentar el aviso de operacion intentada.
     * Art. 7 Bis Reglamento LFPIORPI (DOF 27/03/2026): plazo de 24 horas.
     * Activo solo cuando MODULECOMPLIANCEPLD_AVISOS_INTENTADAS_ACTIVO = 1
     * (hasta que el SAT publique el XSD revisado - Transitorio Quinto).
     */
    public function getFechaLimiteAviso(): ?string
    {
        if ($this->estado_operacion !== 'intentada' || empty($this->fecha_deteccion_alerta)) {
            return null;
        }
        $ts = is_numeric($this->fecha_deteccion_alerta)
            ? $this->fecha_deteccion_alerta
            : strtotime($this->fecha_deteccion_alerta);
        return date('Y-m-d H:i:s', $ts + 86400);
    }
    
    public function generarFolioInterno(): string
    {
        $prefix = 'PLD';
        $year = date('Y');
        $month = date('m');
        $mes = $this->db->escape($year.$month);

        $this->db->begin();

        $sql = "SELECT MAX(CAST(".$this->db->ifsql("folio_interno LIKE 'PLD-{$year}-{$month}-%'", "SUBSTRING_INDEX(folio_interno, '-', -1)", "0")." AS UNSIGNED)) as ultimo";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE mes_reportado = '".$mes."'";

        $resql = $this->db->query($sql);
        $obj = $this->db->fetch_object($resql);
        $consecutivo = (int) ($obj->ultimo ?? 0) + 1;

        $this->folio_interno = sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $consecutivo);

        $this->db->commit();

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

        // PEP y riesgo — DOF 27/03/2026 Arts. 45 Bis-Quinquies y Art. 15
        $this->cliente->is_pep = !empty($opts['options_pld_is_pep']);
        $this->cliente->resultado_pep = $opts['options_pld_resultado_pep'] ?? 'no_consultado';
        $this->cliente->nivel_riesgo = $opts['options_pld_nivel_riesgo'] ?? 'bajo';

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

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."pld_beneficiario";
        $sql .= " WHERE fk_societe = ".(int)$this->fk_societe;
        $sql .= " AND activo = 1";
        $sql .= " ORDER BY porcentaje_participacion DESC";

        $resql = $this->db->query($sql);

        $this->beneficiarios = array();

        if (!$resql) {
            $this->errors[] = "fetchBeneficiarios: ".$this->db->lasterror();
            return -1;
        }

        $num = $this->db->num_rows($resql);
        for ($i = 0; $i < $num; $i++) {
            $obj = $this->db->fetch_object($resql);
            $ben = new PLDBeneficiario($this->db);
            $ben->fetch($obj->rowid);
            $this->beneficiarios[] = $ben;
        }
        $this->db->free($resql);

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
        $this->formas_pago = array();
        $this->forma_pago_principal = '';
        $this->usa_transferencia = false;
        $this->banco_destino = '';
        $this->cuenta_destino = '';
        $this->tipo_cambio = 1.0;

        if (!$this->fk_facture) {
            return -1;
        }

        $sql = "SELECT p.rowid, p.datep, p.amount,";
        $sql .= " ef.pld_forma_pago, ef.pld_instrumento_monetario,";
        $sql .= " ef.pld_moneda, ef.pld_monto_operacion,";
        $sql .= " ef.pld_monto_efectivo, ef.pld_monto_transferencia,";
        $sql .= " ef.pld_monto_cheque, ef.pld_monto_tarjeta,";
        $sql .= " ef.pld_banco_destino, ef.pld_cuenta_destino,";
        $sql .= " ef.pld_banco_cheque, ef.pld_numero_cheque,";
        $sql .= " ef.pld_clabe_origen";
        $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_extrafields as ef ON ef.fk_object = p.rowid";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON pf.fk_paiement = p.rowid";
        $sql .= " WHERE pf.fk_facture = ".(int)$this->fk_facture;
        $sql .= " ORDER BY p.datep ASC";

        $resql = $this->db->query($sql);

        if (!$resql) {
            $this->errors[] = "fetchFormasPago: ".$this->db->lasterror();
            return -1;
        }

        $first = true;
        $num = $this->db->num_rows($resql);
        for ($i = 0; $i < $num; $i++) {
            $obj = $this->db->fetch_object($resql);

            $pago = new stdClass();
            $pago->rowid = $obj->rowid;
            $pago->datep = $obj->datep;
            $pago->amount = $obj->amount;
            $pago->pld_forma_pago = $obj->pld_forma_pago ?? '';
            $pago->pld_instrumento_monetario = $obj->pld_instrumento_monetario ?? '01';
            $pago->pld_moneda = $obj->pld_moneda ?? 'MXN';
            $pago->pld_monto_efectivo = (float)($obj->pld_monto_efectivo ?? 0);
            $pago->pld_monto_transferencia = (float)($obj->pld_monto_transferencia ?? 0);
            $pago->pld_banco_destino = $obj->pld_banco_destino ?? '';
            $pago->pld_cuenta_destino = $obj->pld_cuenta_destino ?? '';
            $pago->pld_banco_cheque = $obj->pld_banco_cheque ?? '';
            $pago->pld_numero_cheque = $obj->pld_numero_cheque ?? '';

            if ($first) {
                $this->forma_pago_principal = $obj->pld_forma_pago ?? '';
                if ($this->forma_pago_principal == '04') {
                    $this->usa_transferencia = true;
                    $this->banco_destino = $obj->pld_banco_destino ?? '';
                    $this->cuenta_destino = $obj->pld_cuenta_destino ?? '';
                }
                $first = false;
            }

            $this->formas_pago[] = $pago;
        }
        $this->db->free($resql);

        return 1;
    }

    /**
     * Verifica si ya existe una PLDOperacion para la factura dada.
     * Usado por el trigger paymentCustomerCreate para evitar duplicados.
     *
     * @param int $fk_facture ID de la factura
     * @return bool true si ya existe, false si no
     */
    public function existeOperacionPorFactura(int $fk_facture): bool
    {
        if ($fk_facture <= 0) {
            return false;
        }

        $sql  = "SELECT rowid FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE fk_facture = ".(int)$fk_facture;
        $sql .= " AND entity = ".(int)$this->entity;
        $sql .= " LIMIT 1";

        $resql = $this->db->query($sql);
        if (!$resql) {
            dol_syslog("PLDOperacion::existeOperacionPorFactura error: ".$this->db->lasterror(), LOG_ERR);
            return false;
        }

        $existe = ($this->db->num_rows($resql) > 0);
        $this->db->free($resql);

        return $existe;
    }
}
