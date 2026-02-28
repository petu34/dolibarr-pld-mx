<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class PLDBeneficiario extends CommonObject
{
    public $element = 'pld_beneficiario';
    public $table_element = 'pld_beneficiario';
    public $module = 'modulecompliancepld';
    
    public $id;
    public $rowid;
    public $entity;
    
    public $fk_societe;
    public $fk_socpeople;
    
    public $tipo_beneficiario;
    
    public $nombre;
    public $apellido_paterno;
    public $apellido_materno;
    
    public $curp;
    public $rfc;
    public $fecha_nacimiento;
    public $nacionalidad;
    
    public $porcentaje_participacion;
    
    public $es_pep;
    public $cargo_pep;
    
    public $verificado;
    public $fecha_verificacion;
    public $fk_user_verificador;
    
    public $activo;
    
    public $datec;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = 1;
        $this->activo = 1;
        $this->verificado = 0;
        $this->es_pep = 0;
    }
    
    public function create($user, $notrigger = 0): int
    {
        global $conf, $langs;
        
        $error = 0;
        $now = dol_now();
        
        $this->db->begin();
        
        if (!$this->validarPorcentajes($this->fk_societe, $this->porcentaje_participacion)) {
            $this->errors[] = "La suma de porcentajes de participación excede 100%";
            $this->db->rollback();
            return -1;
        }
        
        $this->datec = $now;
        $this->fk_user_creat = $user->id;
        
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= " entity, fk_societe, fk_socpeople,";
        $sql .= " tipo_beneficiario,";
        $sql .= " nombre, apellido_paterno, apellido_materno,";
        $sql .= " curp, rfc, fecha_nacimiento, nacionalidad,";
        $sql .= " porcentaje_participacion,";
        $sql .= " es_pep, cargo_pep,";
        $sql .= " verificado, fecha_verificacion, fk_user_verificador,";
        $sql .= " activo,";
        $sql .= " datec, fk_user_creat";
        $sql .= ") VALUES (";
        $sql .= " ".(int)$this->entity.",";
        $sql .= " ".(int)$this->fk_societe.",";
        $sql .= " ".($this->fk_socpeople > 0 ? (int)$this->fk_socpeople : 'NULL').",";
        $sql .= " '".$this->db->escape($this->tipo_beneficiario)."',";
        $sql .= " ".($this->nombre ? "'".$this->db->escape($this->nombre)."'" : 'NULL').",";
        $sql .= " ".($this->apellido_paterno ? "'".$this->db->escape($this->apellido_paterno)."'" : 'NULL').",";
        $sql .= " ".($this->apellido_materno ? "'".$this->db->escape($this->apellido_materno)."'" : 'NULL').",";
        $sql .= " ".($this->curp ? "'".$this->db->escape($this->curp)."'" : 'NULL').",";
        $sql .= " ".($this->rfc ? "'".$this->db->escape($this->rfc)."'" : 'NULL').",";
        $sql .= " ".($this->fecha_nacimiento ? "'".$this->db->escape($this->fecha_nacimiento)."'" : 'NULL').",";
        $sql .= " ".($this->nacionalidad ? "'".$this->db->escape($this->nacionalidad)."'" : 'NULL').",";
        $sql .= " ".(float)$this->porcentaje_participacion.",";
        $sql .= " ".(int)$this->es_pep.",";
        $sql .= " ".($this->cargo_pep ? "'".$this->db->escape($this->cargo_pep)."'" : 'NULL').",";
        $sql .= " ".(int)$this->verificado.",";
        $sql .= " ".($this->fecha_verificacion ? "'".$this->db->escape($this->fecha_verificacion)."'" : 'NULL').",";
        $sql .= " ".($this->fk_user_verificador > 0 ? (int)$this->fk_user_verificador : 'NULL').",";
        $sql .= " ".(int)$this->activo.",";
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
            $result = $this->call_trigger('PLD_BENEFICIARIO_CREATE', $user);
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
        $sql .= " rowid, entity, fk_societe, fk_socpeople,";
        $sql .= " tipo_beneficiario,";
        $sql .= " nombre, apellido_paterno, apellido_materno,";
        $sql .= " curp, rfc, fecha_nacimiento, nacionalidad,";
        $sql .= " porcentaje_participacion,";
        $sql .= " es_pep, cargo_pep,";
        $sql .= " verificado, fecha_verificacion, fk_user_verificador,";
        $sql .= " activo,";
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
                $this->fk_societe = $obj->fk_societe;
                $this->fk_socpeople = $obj->fk_socpeople;
                $this->tipo_beneficiario = $obj->tipo_beneficiario;
                $this->nombre = $obj->nombre;
                $this->apellido_paterno = $obj->apellido_paterno;
                $this->apellido_materno = $obj->apellido_materno;
                $this->curp = $obj->curp;
                $this->rfc = $obj->rfc;
                $this->fecha_nacimiento = $obj->fecha_nacimiento;
                $this->nacionalidad = $obj->nacionalidad;
                $this->porcentaje_participacion = $obj->porcentaje_participacion;
                $this->es_pep = $obj->es_pep;
                $this->cargo_pep = $obj->cargo_pep;
                $this->verificado = $obj->verificado;
                $this->fecha_verificacion = $obj->fecha_verificacion;
                $this->fk_user_verificador = $obj->fk_user_verificador;
                $this->activo = $obj->activo;
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
        
        if (!$this->validarPorcentajes($this->fk_societe, $this->porcentaje_participacion, $this->id)) {
            $this->errors[] = "La suma de porcentajes de participación excede 100%";
            $this->db->rollback();
            return -1;
        }
        
        $this->fk_user_modif = $user->id;
        
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql .= " fk_societe = ".(int)$this->fk_societe.",";
        $sql .= " fk_socpeople = ".($this->fk_socpeople > 0 ? (int)$this->fk_socpeople : 'NULL').",";
        $sql .= " tipo_beneficiario = '".$this->db->escape($this->tipo_beneficiario)."',";
        $sql .= " nombre = ".($this->nombre ? "'".$this->db->escape($this->nombre)."'" : 'NULL').",";
        $sql .= " apellido_paterno = ".($this->apellido_paterno ? "'".$this->db->escape($this->apellido_paterno)."'" : 'NULL').",";
        $sql .= " apellido_materno = ".($this->apellido_materno ? "'".$this->db->escape($this->apellido_materno)."'" : 'NULL').",";
        $sql .= " curp = ".($this->curp ? "'".$this->db->escape($this->curp)."'" : 'NULL').",";
        $sql .= " rfc = ".($this->rfc ? "'".$this->db->escape($this->rfc)."'" : 'NULL').",";
        $sql .= " fecha_nacimiento = ".($this->fecha_nacimiento ? "'".$this->db->escape($this->fecha_nacimiento)."'" : 'NULL').",";
        $sql .= " nacionalidad = ".($this->nacionalidad ? "'".$this->db->escape($this->nacionalidad)."'" : 'NULL').",";
        $sql .= " porcentaje_participacion = ".(float)$this->porcentaje_participacion.",";
        $sql .= " es_pep = ".(int)$this->es_pep.",";
        $sql .= " cargo_pep = ".($this->cargo_pep ? "'".$this->db->escape($this->cargo_pep)."'" : 'NULL').",";
        $sql .= " verificado = ".(int)$this->verificado.",";
        $sql .= " fecha_verificacion = ".($this->fecha_verificacion ? "'".$this->db->escape($this->fecha_verificacion)."'" : 'NULL').",";
        $sql .= " fk_user_verificador = ".($this->fk_user_verificador > 0 ? (int)$this->fk_user_verificador : 'NULL').",";
        $sql .= " activo = ".(int)$this->activo.",";
        $sql .= " fk_user_modif = ".(int)$this->fk_user_modif;
        $sql .= " WHERE rowid = ".(int)$this->id;
        
        $resql = $this->db->query($sql);
        
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }
        
        if (!$error && !$notrigger) {
            $result = $this->call_trigger('PLD_BENEFICIARIO_MODIFY', $user);
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
            $result = $this->call_trigger('PLD_BENEFICIARIO_DELETE', $user);
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
    
    public function validarPorcentajes(int $fk_societe, float $nuevo_porcentaje, int $exclude_rowid = 0): bool
    {
        $sql = "SELECT SUM(porcentaje_participacion) as total";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE fk_societe = ".(int)$fk_societe;
        $sql .= " AND activo = 1";
        $sql .= " AND rowid != ".(int)$exclude_rowid;
        
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $total_actual = $obj ? (float)$obj->total : 0;
            
            $total_nuevo = $total_actual + $nuevo_porcentaje;
            
            return $total_nuevo <= 100;
        }
        
        return false;
    }
    
    public function obtenerTotalPorcentajes(int $fk_societe): float
    {
        $sql = "SELECT SUM(porcentaje_participacion) as total";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE fk_societe = ".(int)$fk_societe;
        $sql .= " AND activo = 1";
        
        $resql = $this->db->query($sql);
        
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return $obj ? (float)$obj->total : 0;
        }
        
        return 0;
    }
    
    public function obtenerNombreCompleto(): string
    {
        $partes = array_filter([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno
        ]);
        
        return implode(' ', $partes);
    }
}
