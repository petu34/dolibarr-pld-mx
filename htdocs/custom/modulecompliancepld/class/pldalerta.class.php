<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class PLDAlerta extends CommonObject
{
    public $element = 'pld_alerta';
    public $table_element = 'pld_alerta';
    public $module = 'modulecompliancepld';
    
    public $id;
    public $rowid;
    public $entity;
    
    public $fk_pld_operacion;
    public $fk_societe;
    
    public $tipo_alerta;
    public $nivel_riesgo;
    
    public $titulo;
    public $descripcion;
    
    public $involucra_pep;
    
    public $requiere_analisis;
    public $fecha_analisis;
    public $fk_user_analista;
    public $decision;
    
    public $estado;
    public $fecha_resolucion;
    
    public $datec;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = 1;
        $this->estado = 'nueva';
        $this->requiere_analisis = 1;
        $this->involucra_pep = 0;
    }
    
    public function create($user, $notrigger = 0): int
    {
        global $conf, $langs;
        
        $error = 0;
        $now = dol_now();
        
        $this->db->begin();
        
        $this->datec = $now;
        $this->fk_user_creat = $user->id;
        
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= " entity, fk_pld_operacion, fk_societe,";
        $sql .= " tipo_alerta, nivel_riesgo,";
        $sql .= " titulo, descripcion,";
        $sql .= " involucra_pep,";
        $sql .= " requiere_analisis, fecha_analisis, fk_user_analista, decision,";
        $sql .= " estado, fecha_resolucion,";
        $sql .= " datec, fk_user_creat";
        $sql .= ") VALUES (";
        $sql .= " ".(int)$this->entity.",";
        $sql .= " ".(int)$this->fk_pld_operacion.",";
        $sql .= " ".(int)$this->fk_societe.",";
        $sql .= " '".$this->db->escape($this->tipo_alerta)."',";
        $sql .= " '".$this->db->escape($this->nivel_riesgo)."',";
        $sql .= " '".$this->db->escape($this->titulo)."',";
        $sql .= " '".$this->db->escape($this->descripcion)."',";
        $sql .= " ".(int)$this->involucra_pep.",";
        $sql .= " ".(int)$this->requiere_analisis.",";
        $sql .= " ".($this->fecha_analisis ? "'".$this->db->escape($this->fecha_analisis)."'" : 'NULL').",";
        $sql .= " ".($this->fk_user_analista > 0 ? (int)$this->fk_user_analista : 'NULL').",";
        $sql .= " ".($this->decision ? "'".$this->db->escape($this->decision)."'" : 'NULL').",";
        $sql .= " '".$this->db->escape($this->estado)."',";
        $sql .= " ".($this->fecha_resolucion ? "'".$this->db->escape($this->fecha_resolucion)."'" : 'NULL').",";
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
            $result = $this->call_trigger('PLD_ALERTA_CREATE', $user);
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
        $sql .= " rowid, entity, fk_pld_operacion, fk_societe,";
        $sql .= " tipo_alerta, nivel_riesgo,";
        $sql .= " titulo, descripcion,";
        $sql .= " involucra_pep,";
        $sql .= " requiere_analisis, fecha_analisis, fk_user_analista, decision,";
        $sql .= " estado, fecha_resolucion,";
        $sql .= " datec, tms, fk_user_creat, fk_user_modif";
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
                $this->fk_pld_operacion = $obj->fk_pld_operacion;
                $this->fk_societe = $obj->fk_societe;
                $this->tipo_alerta = $obj->tipo_alerta;
                $this->nivel_riesgo = $obj->nivel_riesgo;
                $this->titulo = $obj->titulo;
                $this->descripcion = $obj->descripcion;
                $this->involucra_pep = $obj->involucra_pep;
                $this->requiere_analisis = $obj->requiere_analisis;
                $this->fecha_analisis = $obj->fecha_analisis;
                $this->fk_user_analista = $obj->fk_user_analista;
                $this->decision = $obj->decision;
                $this->estado = $obj->estado;
                $this->fecha_resolucion = $obj->fecha_resolucion;
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
        $sql .= " fk_pld_operacion = ".(int)$this->fk_pld_operacion.",";
        $sql .= " fk_societe = ".(int)$this->fk_societe.",";
        $sql .= " tipo_alerta = '".$this->db->escape($this->tipo_alerta)."',";
        $sql .= " nivel_riesgo = '".$this->db->escape($this->nivel_riesgo)."',";
        $sql .= " titulo = '".$this->db->escape($this->titulo)."',";
        $sql .= " descripcion = '".$this->db->escape($this->descripcion)."',";
        $sql .= " involucra_pep = ".(int)$this->involucra_pep.",";
        $sql .= " requiere_analisis = ".(int)$this->requiere_analisis.",";
        $sql .= " fecha_analisis = ".($this->fecha_analisis ? "'".$this->db->escape($this->fecha_analisis)."'" : 'NULL').",";
        $sql .= " fk_user_analista = ".($this->fk_user_analista > 0 ? (int)$this->fk_user_analista : 'NULL').",";
        $sql .= " decision = ".($this->decision ? "'".$this->db->escape($this->decision)."'" : 'NULL').",";
        $sql .= " estado = '".$this->db->escape($this->estado)."',";
        $sql .= " fecha_resolucion = ".($this->fecha_resolucion ? "'".$this->db->escape($this->fecha_resolucion)."'" : 'NULL').",";
        $sql .= " fk_user_modif = ".(int)$this->fk_user_modif;
        $sql .= " WHERE rowid = ".(int)$this->id;
        
        $resql = $this->db->query($sql);
        
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }
        
        if (!$error && !$notrigger) {
            $result = $this->call_trigger('PLD_ALERTA_MODIFY', $user);
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
            $result = $this->call_trigger('PLD_ALERTA_DELETE', $user);
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
    
    public function resolver($user, string $decision, string $estado = 'resuelta'): int
    {
        $this->decision = $decision;
        $this->estado = $estado;
        $this->fecha_resolucion = date('Y-m-d');
        $this->fk_user_analista = $user->id;
        $this->fecha_analisis = date('Y-m-d');
        
        return $this->update($user);
    }
}
