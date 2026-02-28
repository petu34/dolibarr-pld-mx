<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once __DIR__.'/plddocumento.class.php';

class PLDDocumentoUploader
{
    private $db;
    public $errors = array();
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    public function uploadDocumento(
        $user,
        string $file_path,
        string $tipo_documento_pld,
        int $fk_societe = 0,
        int $fk_socpeople = 0,
        string $numero_documento = '',
        string $fecha_emision = '',
        string $fecha_vencimiento = '',
        string $autoridad_emite = ''
    ): int {
        global $conf;
        
        if (!file_exists($file_path)) {
            $this->errors[] = "Archivo no encontrado: {$file_path}";
            return -1;
        }
        
        $file_name = basename($file_path);
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $file_size = filesize($file_path);
        $file_content = file_get_contents($file_path);
        
        if ($file_content === false) {
            $this->errors[] = "Error al leer archivo: {$file_path}";
            return -2;
        }
        
        $this->db->begin();
        
        $ecmfile = new EcmFiles($this->db);
        $ecmfile->entity = $conf->entity;
        $ecmfile->filepath = 'modulecompliancepld/documentos';
        $ecmfile->filename = time().'_'.$file_name;
        $ecmfile->label = md5($file_content);
        $ecmfile->gen_or_uploaded = 'uploaded';
        $ecmfile->description = 'Documento PLD: '.$tipo_documento_pld;
        
        if ($fk_societe > 0) {
            $ecmfile->src_object_type = 'societe';
            $ecmfile->src_object_id = $fk_societe;
            $ecmfile->filepath = 'modulecompliancepld/documentos/societe/'.$fk_societe;
        } elseif ($fk_socpeople > 0) {
            $ecmfile->src_object_type = 'socpeople';
            $ecmfile->src_object_id = $fk_socpeople;
            $ecmfile->filepath = 'modulecompliancepld/documentos/socpeople/'.$fk_socpeople;
        }
        
        $ecmfile_id = $ecmfile->create($user);
        
        if ($ecmfile_id <= 0) {
            $this->errors[] = "Error al crear registro ECM: ".implode(', ', $ecmfile->errors);
            $this->db->rollback();
            return -3;
        }
        
        $dest_dir = $conf->ecm->dir_output.'/'.$ecmfile->filepath;
        if (!is_dir($dest_dir)) {
            if (!dol_mkdir($dest_dir)) {
                $this->errors[] = "Error al crear directorio: {$dest_dir}";
                $this->db->rollback();
                return -4;
            }
        }
        
        $dest_file = $dest_dir.'/'.$ecmfile->filename;
        if (!copy($file_path, $dest_file)) {
            $this->errors[] = "Error al copiar archivo a: {$dest_file}";
            $this->db->rollback();
            return -5;
        }
        
        $pld_doc = new PLDDocumento($this->db);
        $pld_doc->fk_ecm_files = $ecmfile_id;
        $pld_doc->fk_societe = $fk_societe > 0 ? $fk_societe : null;
        $pld_doc->fk_socpeople = $fk_socpeople > 0 ? $fk_socpeople : null;
        $pld_doc->tipo_documento_pld = $tipo_documento_pld;
        $pld_doc->numero_documento = $numero_documento;
        $pld_doc->fecha_emision = $fecha_emision;
        $pld_doc->fecha_vencimiento = $fecha_vencimiento;
        $pld_doc->autoridad_emite = $autoridad_emite;
        
        if (!empty($fecha_emision)) {
            $pld_doc->calcularFechaRetencion();
        }
        
        $pld_doc_id = $pld_doc->create($user);
        
        if ($pld_doc_id <= 0) {
            $this->errors[] = "Error al crear metadatos PLD: ".implode(', ', $pld_doc->errors);
            $this->db->rollback();
            return -6;
        }
        
        $this->db->commit();
        
        return $pld_doc_id;
    }
    
    public function verificarDocumento($user, int $pld_doc_id): bool
    {
        $pld_doc = new PLDDocumento($this->db);
        $result = $pld_doc->fetch($pld_doc_id);
        
        if ($result <= 0) {
            $this->errors[] = "Documento PLD no encontrado: {$pld_doc_id}";
            return false;
        }
        
        $pld_doc->verificado = 1;
        $pld_doc->fecha_verificacion = date('Y-m-d');
        $pld_doc->fk_user_verificador = $user->id;
        
        $result = $pld_doc->update($user);
        
        return $result > 0;
    }
    
    public function obtenerDocumentosEntidad(string $tipo_entidad, int $id_entidad): array
    {
        $documentos = array();
        
        $sql = "SELECT d.rowid, d.tipo_documento_pld, d.numero_documento,";
        $sql .= " d.fecha_emision, d.fecha_vencimiento, d.verificado,";
        $sql .= " e.filename, e.filepath, e.label as content_hash";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_documento as d";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."ecm_files as e ON e.rowid = d.fk_ecm_files";
        $sql .= " WHERE ";
        
        if ($tipo_entidad === 'societe') {
            $sql .= "d.fk_societe = ".(int)$id_entidad;
        } elseif ($tipo_entidad === 'socpeople') {
            $sql .= "d.fk_socpeople = ".(int)$id_entidad;
        } else {
            return $documentos;
        }
        
        $sql .= " ORDER BY d.fecha_emision DESC";
        
        $resql = $this->db->query($sql);
        
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $documentos[] = array(
                    'rowid' => $obj->rowid,
                    'tipo_documento_pld' => $obj->tipo_documento_pld,
                    'numero_documento' => $obj->numero_documento,
                    'fecha_emision' => $obj->fecha_emision,
                    'fecha_vencimiento' => $obj->fecha_vencimiento,
                    'verificado' => $obj->verificado,
                    'filename' => $obj->filename,
                    'filepath' => $obj->filepath,
                    'content_hash' => $obj->content_hash
                );
            }
            $this->db->free($resql);
        }
        
        return $documentos;
    }
}
