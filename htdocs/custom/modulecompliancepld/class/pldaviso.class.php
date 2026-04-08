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

    /**
     * Carga las operaciones vinculadas a este aviso (llx_pld_aviso_operacion).
     * Popula $this->operaciones (array de stdClass con id, folio, monto, fecha, estado).
     *
     * @return int 1=ok (incluso si no hay operaciones), -1=error
     */
    public function fetchOperaciones(): int
    {
        $this->operaciones = array();

        if (!$this->id) {
            return -1;
        }

        $sql = "SELECT o.rowid, o.folio_interno, o.fecha_operacion,";
        $sql .= " o.monto_mxn, o.monto_sin_impuestos, o.estado, o.fk_societe";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_aviso_operacion ao";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."pld_operacion o ON o.rowid = ao.fk_pld_operacion";
        $sql .= " WHERE ao.fk_pld_aviso = ".(int)$this->id;
        $sql .= " ORDER BY o.fecha_operacion ASC";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = "fetchOperaciones: ".$this->db->lasterror();
            return -1;
        }

        $num = $this->db->num_rows($resql);
        for ($i = 0; $i < $num; $i++) {
            $this->operaciones[] = $this->db->fetch_object($resql);
        }
        $this->db->free($resql);

        return 1;
    }

    /**
     * Vincula una operación a este aviso (INSERT en llx_pld_aviso_operacion).
     * Actualiza también numero_operaciones y monto_total_operaciones en el aviso.
     *
     * @param int  $fk_operacion  rowid de llx_pld_operacion
     * @param object $user
     * @return int 1=ok, -1=error
     */
    public function agregarOperacion(int $fk_operacion, $user): int
    {
        if (!$this->id || $fk_operacion <= 0) {
            return -1;
        }

        $this->db->begin();

        // Verificar que no esté ya vinculada
        $sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."pld_aviso_operacion";
        $sql_check .= " WHERE fk_pld_aviso = ".(int)$this->id." AND fk_pld_operacion = ".(int)$fk_operacion;
        $rescheck = $this->db->query($sql_check);
        if ($rescheck && $this->db->num_rows($rescheck) > 0) {
            $this->db->free($rescheck);
            $this->db->rollback();
            $this->errors[] = "La operación $fk_operacion ya está vinculada al aviso";
            return -1;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."pld_aviso_operacion (fk_pld_aviso, fk_pld_operacion, datec, fk_user_creat)";
        $sql .= " VALUES (".(int)$this->id.", ".(int)$fk_operacion.", '".$this->db->idate(dol_now())."', ".(int)$user->id.")";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = "agregarOperacion: ".$this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        // Actualizar totales del aviso
        $this->recalcularTotales($user);

        $this->db->commit();
        return 1;
    }

    /**
     * Desvincula una operación de este aviso (DELETE en llx_pld_aviso_operacion).
     *
     * @param int  $fk_operacion  rowid de llx_pld_operacion
     * @param object $user
     * @return int 1=ok, -1=error
     */
    public function quitarOperacion(int $fk_operacion, $user): int
    {
        if (!$this->id || $fk_operacion <= 0) {
            return -1;
        }

        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."pld_aviso_operacion";
        $sql .= " WHERE fk_pld_aviso = ".(int)$this->id." AND fk_pld_operacion = ".(int)$fk_operacion;

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = "quitarOperacion: ".$this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        // Actualizar totales del aviso
        $this->recalcularTotales($user);

        $this->db->commit();
        return 1;
    }

    /**
     * Recalcula numero_operaciones y monto_total_operaciones desde la tabla de relación.
     * Se llama internamente después de agregar/quitar operaciones.
     *
     * @param object $user
     * @return int 1=ok, -1=error
     */
    private function recalcularTotales($user): int
    {
        $sql = "SELECT COUNT(*) as num, SUM(o.monto_mxn) as total";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_aviso_operacion ao";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."pld_operacion o ON o.rowid = ao.fk_pld_operacion";
        $sql .= " WHERE ao.fk_pld_aviso = ".(int)$this->id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $this->numero_operaciones = (int)($obj->num ?? 0);
            $this->monto_total_operaciones = (float)($obj->total ?? 0);
            $this->db->free($resql);
        }

        return $this->update($user, 1); // notrigger=1 para no generar eventos extra
    }
}
