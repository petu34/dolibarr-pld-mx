<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class PLDAviso extends CommonObject
{
    public $element = 'pld_aviso';
    public $table_element = 'pld_aviso';
    public $module = 'modulecompliancepld';
    
    public $id;
    public $rowid;
    public $entity;
    
    public $tipo_aviso;
    
    public $mes_reportado;
    public $fecha_inicio_periodo;
    public $fecha_fin_periodo;
    
    public $referencia_aviso;
    
    public $numero_operaciones;
    public $monto_total_operaciones;
    
    public $archivo_xml_ruta;
    public $archivo_xml_hash;
    public $fecha_generacion_xml;
    
    public $presentado;
    public $fecha_presentacion;
    public $fk_user_presento;
    
    public $folio_sat;
    public $fecha_acuse;
    public $estado_acuse;
    
    public $estado;
    
    public $observaciones;
    
    public $datec;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = 1;
        $this->estado = 'borrador';
        $this->presentado = 0;
        $this->numero_operaciones = 0;
        $this->monto_total_operaciones = 0;
    }
    
    public function create($user, $notrigger = 0): int
    {
        global $conf, $langs;
        
        $error = 0;
        $now = dol_now();
        
        $this->db->begin();
        
        if (empty($this->referencia_aviso)) {
            $this->generarReferenciaUnica();
        }
        
        $this->datec = $now;
        $this->fk_user_creat = $user->id;
        
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= " entity, tipo_aviso,";
        $sql .= " mes_reportado, fecha_inicio_periodo, fecha_fin_periodo,";
        $sql .= " referencia_aviso,";
        $sql .= " numero_operaciones, monto_total_operaciones,";
        $sql .= " archivo_xml_ruta, archivo_xml_hash, fecha_generacion_xml,";
        $sql .= " presentado, fecha_presentacion, fk_user_presento,";
        $sql .= " folio_sat, fecha_acuse, estado_acuse,";
        $sql .= " estado, observaciones,";
        $sql .= " datec, fk_user_creat";
        $sql .= ") VALUES (";
        $sql .= " ".(int)$this->entity.",";
        $sql .= " '".$this->db->escape($this->tipo_aviso)."',";
        $sql .= " '".$this->db->escape($this->mes_reportado)."',";
        $sql .= " ".($this->fecha_inicio_periodo ? "'".$this->db->escape($this->fecha_inicio_periodo)."'" : 'NULL').",";
        $sql .= " ".($this->fecha_fin_periodo ? "'".$this->db->escape($this->fecha_fin_periodo)."'" : 'NULL').",";
        $sql .= " '".$this->db->escape($this->referencia_aviso)."',";
        $sql .= " ".(int)$this->numero_operaciones.",";
        $sql .= " ".(float)$this->monto_total_operaciones.",";
        $sql .= " ".($this->archivo_xml_ruta ? "'".$this->db->escape($this->archivo_xml_ruta)."'" : 'NULL').",";
        $sql .= " ".($this->archivo_xml_hash ? "'".$this->db->escape($this->archivo_xml_hash)."'" : 'NULL').",";
        $sql .= " ".($this->fecha_generacion_xml ? "'".$this->db->escape($this->fecha_generacion_xml)."'" : 'NULL').",";
        $sql .= " ".(int)$this->presentado.",";
        $sql .= " ".($this->fecha_presentacion ? "'".$this->db->escape($this->fecha_presentacion)."'" : 'NULL').",";
        $sql .= " ".($this->fk_user_presento > 0 ? (int)$this->fk_user_presento : 'NULL').",";
        $sql .= " ".($this->folio_sat ? "'".$this->db->escape($this->folio_sat)."'" : 'NULL').",";
        $sql .= " ".($this->fecha_acuse ? "'".$this->db->escape($this->fecha_acuse)."'" : 'NULL').",";
        $sql .= " ".($this->estado_acuse ? "'".$this->db->escape($this->estado_acuse)."'" : 'NULL').",";
        $sql .= " '".$this->db->escape($this->estado)."',";
        $sql .= " ".($this->observaciones ? "'".$this->db->escape($this->observaciones)."'" : 'NULL').",";
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
            $result = $this->call_trigger('PLD_AVISO_CREATE', $user);
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
        $sql .= " rowid, entity, tipo_aviso,";
        $sql .= " mes_reportado, fecha_inicio_periodo, fecha_fin_periodo,";
        $sql .= " referencia_aviso,";
        $sql .= " numero_operaciones, monto_total_operaciones,";
        $sql .= " archivo_xml_ruta, archivo_xml_hash, fecha_generacion_xml,";
        $sql .= " presentado, fecha_presentacion, fk_user_presento,";
        $sql .= " folio_sat, fecha_acuse, estado_acuse,";
        $sql .= " estado, observaciones,";
        $sql .= " datec, tms, fk_user_creat, fk_user_modif";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        
        if ($id > 0) {
            $sql .= " WHERE rowid = ".(int)$id;
        } elseif ($ref) {
            $sql .= " WHERE referencia_aviso = '".$this->db->escape($ref)."'";
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
                $this->tipo_aviso = $obj->tipo_aviso;
                $this->mes_reportado = $obj->mes_reportado;
                $this->fecha_inicio_periodo = $obj->fecha_inicio_periodo;
                $this->fecha_fin_periodo = $obj->fecha_fin_periodo;
                $this->referencia_aviso = $obj->referencia_aviso;
                $this->numero_operaciones = $obj->numero_operaciones;
                $this->monto_total_operaciones = $obj->monto_total_operaciones;
                $this->archivo_xml_ruta = $obj->archivo_xml_ruta;
                $this->archivo_xml_hash = $obj->archivo_xml_hash;
                $this->fecha_generacion_xml = $obj->fecha_generacion_xml;
                $this->presentado = $obj->presentado;
                $this->fecha_presentacion = $obj->fecha_presentacion;
                $this->fk_user_presento = $obj->fk_user_presento;
                $this->folio_sat = $obj->folio_sat;
                $this->fecha_acuse = $obj->fecha_acuse;
                $this->estado_acuse = $obj->estado_acuse;
                $this->estado = $obj->estado;
                $this->observaciones = $obj->observaciones;
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
        $sql .= " tipo_aviso = '".$this->db->escape($this->tipo_aviso)."',";
        $sql .= " mes_reportado = '".$this->db->escape($this->mes_reportado)."',";
        $sql .= " fecha_inicio_periodo = ".($this->fecha_inicio_periodo ? "'".$this->db->escape($this->fecha_inicio_periodo)."'" : 'NULL').",";
        $sql .= " fecha_fin_periodo = ".($this->fecha_fin_periodo ? "'".$this->db->escape($this->fecha_fin_periodo)."'" : 'NULL').",";
        $sql .= " referencia_aviso = '".$this->db->escape($this->referencia_aviso)."',";
        $sql .= " numero_operaciones = ".(int)$this->numero_operaciones.",";
        $sql .= " monto_total_operaciones = ".(float)$this->monto_total_operaciones.",";
        $sql .= " archivo_xml_ruta = ".($this->archivo_xml_ruta ? "'".$this->db->escape($this->archivo_xml_ruta)."'" : 'NULL').",";
        $sql .= " archivo_xml_hash = ".($this->archivo_xml_hash ? "'".$this->db->escape($this->archivo_xml_hash)."'" : 'NULL').",";
        $sql .= " fecha_generacion_xml = ".($this->fecha_generacion_xml ? "'".$this->db->escape($this->fecha_generacion_xml)."'" : 'NULL').",";
        $sql .= " presentado = ".(int)$this->presentado.",";
        $sql .= " fecha_presentacion = ".($this->fecha_presentacion ? "'".$this->db->escape($this->fecha_presentacion)."'" : 'NULL').",";
        $sql .= " fk_user_presento = ".($this->fk_user_presento > 0 ? (int)$this->fk_user_presento : 'NULL').",";
        $sql .= " folio_sat = ".($this->folio_sat ? "'".$this->db->escape($this->folio_sat)."'" : 'NULL').",";
        $sql .= " fecha_acuse = ".($this->fecha_acuse ? "'".$this->db->escape($this->fecha_acuse)."'" : 'NULL').",";
        $sql .= " estado_acuse = ".($this->estado_acuse ? "'".$this->db->escape($this->estado_acuse)."'" : 'NULL').",";
        $sql .= " estado = '".$this->db->escape($this->estado)."',";
        $sql .= " observaciones = ".($this->observaciones ? "'".$this->db->escape($this->observaciones)."'" : 'NULL').",";
        $sql .= " fk_user_modif = ".(int)$this->fk_user_modif;
        $sql .= " WHERE rowid = ".(int)$this->id;
        
        $resql = $this->db->query($sql);
        
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }
        
        if (!$error && !$notrigger) {
            $result = $this->call_trigger('PLD_AVISO_MODIFY', $user);
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
            $result = $this->call_trigger('PLD_AVISO_DELETE', $user);
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
    
    public function generarReferenciaUnica(): string
    {
        $prefix = 'AVISO';
        $year = substr($this->mes_reportado, 0, 4);
        $month = substr($this->mes_reportado, 4, 2);
        
        $sql = "SELECT COUNT(*) as total FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE mes_reportado = '".$this->db->escape($this->mes_reportado)."'";
        
        $resql = $this->db->query($sql);
        $obj = $this->db->fetch_object($resql);
        $consecutivo = $obj->total + 1;
        
        $this->referencia_aviso = sprintf('%s-%s-%s-%04d', $prefix, $year, $month, $consecutivo);
        
        return $this->referencia_aviso;
    }
    
    public function calcularHashXML(string $xml_content): string
    {
        return hash('sha256', $xml_content);
    }
}
