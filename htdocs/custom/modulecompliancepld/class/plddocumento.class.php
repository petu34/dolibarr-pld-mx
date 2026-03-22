<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

class PLDDocumento extends CommonObject
{
    public $element = 'pld_documento';
    public $table_element = 'pld_documento';
    public $module = 'modulecompliancepld';
    
    public $id;
    public $rowid;
    public $entity;
    
    public $fk_ecm_files;
    
    public $fk_societe;
    public $fk_socpeople;
    
    public $tipo_documento_pld;
    
    public $numero_documento;
    public $fecha_emision;
    public $fecha_vencimiento;
    public $autoridad_emite;
    
    public $verificado;
    public $fecha_verificacion;
    public $fk_user_verificador;
    
    public $fecha_retencion_hasta;
    
    public $datec;
    public $tms;
    public $fk_user_creat;
    
    const ANIOS_RETENCION = 5;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = 1;
        $this->verificado = 0;
    }
    
    public function create($user, $notrigger = 0): int
    {
        global $conf, $langs;
        
        $error = 0;
        $now = dol_now();
        
        $this->db->begin();
        
        if (empty($this->fecha_retencion_hasta) && !empty($this->fecha_emision)) {
            $this->calcularFechaRetencion();
        }
        
        $this->datec = $now;
        $this->fk_user_creat = $user->id;
        
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= " entity, fk_ecm_files,";
        $sql .= " fk_societe, fk_socpeople,";
        $sql .= " tipo_documento_pld,";
        $sql .= " numero_documento, fecha_emision, fecha_vencimiento, autoridad_emite,";
        $sql .= " verificado, fecha_verificacion, fk_user_verificador,";
        $sql .= " fecha_retencion_hasta,";
        $sql .= " datec, fk_user_creat";
        $sql .= ") VALUES (";
        $sql .= " ".(int)$this->entity.",";
        $sql .= " ".(int)$this->fk_ecm_files.",";
        $sql .= " ".($this->fk_societe > 0 ? (int)$this->fk_societe : 'NULL').",";
        $sql .= " ".($this->fk_socpeople > 0 ? (int)$this->fk_socpeople : 'NULL').",";
        $sql .= " '".$this->db->escape($this->tipo_documento_pld)."',";
        $sql .= " ".($this->numero_documento ? "'".$this->db->escape($this->numero_documento)."'" : 'NULL').",";
        $sql .= " ".($this->fecha_emision ? "'".$this->db->escape($this->fecha_emision)."'" : 'NULL').",";
        $sql .= " ".($this->fecha_vencimiento ? "'".$this->db->escape($this->fecha_vencimiento)."'" : 'NULL').",";
        $sql .= " ".($this->autoridad_emite ? "'".$this->db->escape($this->autoridad_emite)."'" : 'NULL').",";
        $sql .= " ".(int)$this->verificado.",";
        $sql .= " ".($this->fecha_verificacion ? "'".$this->db->escape($this->fecha_verificacion)."'" : 'NULL').",";
        $sql .= " ".($this->fk_user_verificador > 0 ? (int)$this->fk_user_verificador : 'NULL').",";
        $sql .= " ".($this->fecha_retencion_hasta ? "'".$this->db->escape($this->fecha_retencion_hasta)."'" : 'NULL').",";
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
            $result = $this->call_trigger('PLD_DOCUMENTO_CREATE', $user);
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
        $sql .= " rowid, entity, fk_ecm_files,";
        $sql .= " fk_societe, fk_socpeople,";
        $sql .= " tipo_documento_pld,";
        $sql .= " numero_documento, fecha_emision, fecha_vencimiento, autoridad_emite,";
        $sql .= " verificado, fecha_verificacion, fk_user_verificador,";
        $sql .= " fecha_retencion_hasta,";
        $sql .= " datec, tms, fk_user_creat";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        
        if ($id > 0) {
            $sql .= " WHERE rowid = ".(int)$id;
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
                $this->fk_ecm_files = $obj->fk_ecm_files;
                $this->fk_societe = $obj->fk_societe;
                $this->fk_socpeople = $obj->fk_socpeople;
                $this->tipo_documento_pld = $obj->tipo_documento_pld;
                $this->numero_documento = $obj->numero_documento;
                $this->fecha_emision = $obj->fecha_emision;
                $this->fecha_vencimiento = $obj->fecha_vencimiento;
                $this->autoridad_emite = $obj->autoridad_emite;
                $this->verificado = $obj->verificado;
                $this->fecha_verificacion = $obj->fecha_verificacion;
                $this->fk_user_verificador = $obj->fk_user_verificador;
                $this->fecha_retencion_hasta = $obj->fecha_retencion_hasta;
                $this->datec = $this->db->jdate($obj->datec);
                $this->tms = $this->db->jdate($obj->tms);
                $this->fk_user_creat = $obj->fk_user_creat;
                
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
        
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= " fk_ecm_files = ".(int)$this->fk_ecm_files.",";
        $sql .= " fk_societe = ".($this->fk_societe > 0 ? (int)$this->fk_societe : 'NULL').",";
        $sql .= " fk_socpeople = ".($this->fk_socpeople > 0 ? (int)$this->fk_socpeople : 'NULL').",";
        $sql .= " tipo_documento_pld = '".$this->db->escape($this->tipo_documento_pld)."',";
        $sql .= " numero_documento = ".($this->numero_documento ? "'".$this->db->escape($this->numero_documento)."'" : 'NULL').",";
        $sql .= " fecha_emision = ".($this->fecha_emision ? "'".$this->db->escape($this->fecha_emision)."'" : 'NULL').",";
        $sql .= " fecha_vencimiento = ".($this->fecha_vencimiento ? "'".$this->db->escape($this->fecha_vencimiento)."'" : 'NULL').",";
        $sql .= " autoridad_emite = ".($this->autoridad_emite ? "'".$this->db->escape($this->autoridad_emite)."'" : 'NULL').",";
        $sql .= " verificado = ".(int)$this->verificado.",";
        $sql .= " fecha_verificacion = ".($this->fecha_verificacion ? "'".$this->db->escape($this->fecha_verificacion)."'" : 'NULL').",";
        $sql .= " fk_user_verificador = ".($this->fk_user_verificador > 0 ? (int)$this->fk_user_verificador : 'NULL').",";
        $sql .= " fecha_retencion_hasta = ".($this->fecha_retencion_hasta ? "'".$this->db->escape($this->fecha_retencion_hasta)."'" : 'NULL');
        $sql .= " WHERE rowid = ".(int)$this->id;
        
        $resql = $this->db->query($sql);
        
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }
        
        if (!$error && !$notrigger) {
            $result = $this->call_trigger('PLD_DOCUMENTO_MODIFY', $user);
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
            $result = $this->call_trigger('PLD_DOCUMENTO_DELETE', $user);
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
    
    public function calcularFechaRetencion(): void
    {
        if (!empty($this->fecha_emision)) {
            $timestamp = strtotime($this->fecha_emision);
            $timestamp_retencion = strtotime('+'.self::ANIOS_RETENCION.' years', $timestamp);
            $this->fecha_retencion_hasta = date('Y-m-d', $timestamp_retencion);
        }
    }
    
    public function getEcmFile(): ?EcmFiles
    {
        if (empty($this->fk_ecm_files)) {
            return null;
        }
        
        $ecmfile = new EcmFiles($this->db);
        $result = $ecmfile->fetch($this->fk_ecm_files);
        
        return $result > 0 ? $ecmfile : null;
    }
    
    public function estaVencido(): bool
    {
        if (empty($this->fecha_vencimiento)) {
            return false;
        }
        
        $hoy = date('Y-m-d');
        return $this->fecha_vencimiento < $hoy;
    }
}
