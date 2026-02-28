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
        $sql .= " entity, fk_facture, fk_societe, fk_product,";
        $sql .= " tipo_operacion, tipo_actividad_vulnerable,";
        $sql .= " fecha_operacion, mes_reportado, folio_interno,";
        $sql .= " moneda, monto_mxn,";
        $sql .= " supera_umbral, cliente_identificado, documentacion_completa,";
        $sql .= " requiere_aviso, aviso_presentado, fk_pld_aviso,";
        $sql .= " genera_alerta, fk_pld_alerta,";
        $sql .= " estado,";
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
        $sql .= " ".(int)$this->supera_umbral.",";
        $sql .= " ".(int)$this->cliente_identificado.",";
        $sql .= " ".(int)$this->documentacion_completa.",";
        $sql .= " ".(int)$this->requiere_aviso.",";
        $sql .= " ".(int)$this->aviso_presentado.",";
        $sql .= " ".($this->fk_pld_aviso > 0 ? (int)$this->fk_pld_aviso : 'NULL').",";
        $sql .= " ".(int)$this->genera_alerta.",";
        $sql .= " ".($this->fk_pld_alerta > 0 ? (int)$this->fk_pld_alerta : 'NULL').",";
        $sql .= " '".$this->db->escape($this->estado)."',";
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
        $sql .= " moneda, monto_mxn,";
        $sql .= " supera_umbral, cliente_identificado, documentacion_completa,";
        $sql .= " requiere_aviso, aviso_presentado, fk_pld_aviso,";
        $sql .= " genera_alerta, fk_pld_alerta,";
        $sql .= " estado,";
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
        $sql .= " fk_societe = ".(int)$this->fk_societe.",";
        $sql .= " fk_product = ".($this->fk_product > 0 ? (int)$this->fk_product : 'NULL').",";
        $sql .= " tipo_operacion = '".$this->db->escape($this->tipo_operacion)."',";
        $sql .= " tipo_actividad_vulnerable = '".$this->db->escape($this->tipo_actividad_vulnerable)."',";
        $sql .= " fecha_operacion = '".$this->db->escape($this->fecha_operacion)."',";
        $sql .= " mes_reportado = '".$this->db->escape($this->mes_reportado)."',";
        $sql .= " folio_interno = ".($this->folio_interno ? "'".$this->db->escape($this->folio_interno)."'" : 'NULL').",";
        $sql .= " moneda = '".$this->db->escape($this->moneda)."',";
        $sql .= " monto_mxn = ".(float)$this->monto_mxn.",";
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
    
    public function evaluarUmbral(string $tipo_vehiculo): array
    {
        $umbral = ($tipo_vehiculo === 'nuevo') 
            ? self::UMBRAL_VEHICULO_NUEVO 
            : self::UMBRAL_VEHICULO_USADO;
        
        $supera = $this->monto_mxn >= $umbral;
        
        $this->supera_umbral = $supera ? 1 : 0;
        $this->requiere_aviso = $supera ? 1 : 0;
        
        return [
            'supera_umbral' => $supera,
            'umbral_aplicado' => $umbral,
            'requiere_aviso' => $supera,
            'diferencia' => $this->monto_mxn - $umbral
        ];
    }
    
    public function generarFolioInterno(): string
    {
        $prefix = 'PLD';
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE mes_reportado = '".$year.$month."'";
        
        $resql = $this->db->query($sql);
        $obj = $this->db->fetch_object($resql);
        $consecutivo = $obj->total + 1;
        
        $this->folio_interno = sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $consecutivo);
        
        return $this->folio_interno;
    }
}
