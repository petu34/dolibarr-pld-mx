<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Clase para gestión de verificaciones PEP (Personas Políticamente Expuestas)
 * Base legal: Arts. 45 Bis – 45 Quinquies LFPIORPI (DOF 27/03/2026)
 *
 * Tabla: llx_pld_pep_verificacion
 * Extrafields en llx_societe: pld_resultado_pep, pld_fecha_verificacion_pep,
 *                              pld_nivel_diligencia, pld_is_pep
 */
class PLDPepVerificacion extends CommonObject
{
    public $element      = 'pld_pep_verificacion';
    public $table_element = 'pld_pep_verificacion';
    public $module       = 'modulecompliancepld';

    public $id;
    public $rowid;
    public $entity;

    public $fk_societe;
    public $fecha_consulta;
    public $resultado;           // negativo|positivo|sin_respuesta|error_uif
    public $referencia_uif;
    public $nivel_diligencia;    // simplificada|normal|reforzada
    public $observaciones;

    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $import_key;

    // Resultados posibles
    const RESULTADO_NEGATIVO     = 'negativo';
    const RESULTADO_POSITIVO     = 'positivo';
    const RESULTADO_SIN_RESPUESTA = 'sin_respuesta';
    const RESULTADO_ERROR_UIF    = 'error_uif';
    const RESULTADO_NO_CONSULTADO = 'no_consultado';

    // Niveles de diligencia debida
    const DILIGENCIA_SIMPLIFICADA = 'simplificada';
    const DILIGENCIA_NORMAL       = 'normal';
    const DILIGENCIA_REFORZADA    = 'reforzada';

    public function __construct($db)
    {
        $this->db     = $db;
        $this->entity = 1;
    }

    // -----------------------------------------------------------------------
    // CRUD
    // -----------------------------------------------------------------------

    public function create($user, $notrigger = 0): int
    {
        $error = 0;
        $now   = dol_now();

        $this->db->begin();

        if (empty($this->fecha_consulta)) {
            $this->fecha_consulta = $this->db->idate($now);
        }

        $sql  = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql .= " entity, fk_societe, fecha_consulta, resultado,";
        $sql .= " referencia_uif, nivel_diligencia, observaciones,";
        $sql .= " date_creation, fk_user_creat, import_key";
        $sql .= ") VALUES (";
        $sql .= " ".(int)$this->entity.",";
        $sql .= " ".(int)$this->fk_societe.",";
        $sql .= " '".$this->db->escape($this->fecha_consulta)."',";
        $sql .= " '".$this->db->escape($this->resultado)."',";
        $sql .= " ".($this->referencia_uif ? "'".$this->db->escape($this->referencia_uif)."'" : 'NULL').",";
        $sql .= " ".($this->nivel_diligencia ? "'".$this->db->escape($this->nivel_diligencia)."'" : 'NULL').",";
        $sql .= " ".($this->observaciones ? "'".$this->db->escape($this->observaciones)."'" : 'NULL').",";
        $sql .= " '".$this->db->idate($now)."',";
        $sql .= " ".(int)$user->id.",";
        $sql .= " ".($this->import_key ? "'".$this->db->escape($this->import_key)."'" : 'NULL');
        $sql .= ")";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        } else {
            $this->id    = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            $this->rowid = $this->id;
        }

        if (!$error) {
            // Actualizar extrafields de la empresa con el resultado más reciente
            $this->actualizarExtrafieldsSociete($this->fk_societe, $this->resultado, $this->nivel_diligencia);
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    public function fetch($id): int
    {
        $sql  = "SELECT rowid, entity, fk_societe, fecha_consulta, resultado,";
        $sql .= " referencia_uif, nivel_diligencia, observaciones,";
        $sql .= " date_creation, tms, fk_user_creat, fk_user_modif, import_key";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE rowid = ".(int)$id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id              = $obj->rowid;
                $this->rowid           = $obj->rowid;
                $this->entity          = $obj->entity;
                $this->fk_societe      = $obj->fk_societe;
                $this->fecha_consulta  = $obj->fecha_consulta;
                $this->resultado       = $obj->resultado;
                $this->referencia_uif  = $obj->referencia_uif;
                $this->nivel_diligencia = $obj->nivel_diligencia;
                $this->observaciones   = $obj->observaciones;
                $this->date_creation   = $this->db->jdate($obj->date_creation);
                $this->tms             = $this->db->jdate($obj->tms);
                $this->fk_user_creat   = $obj->fk_user_creat;
                $this->fk_user_modif   = $obj->fk_user_modif;
                $this->import_key      = $obj->import_key;
                $this->db->free($resql);
                return 1;
            }
            $this->db->free($resql);
            return 0;
        }
        $this->errors[] = "Error ".$this->db->lasterror();
        return -1;
    }

    public function delete($user, $notrigger = 0): int
    {
        $this->db->begin();
        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".(int)$this->id;
        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = "Error ".$this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
        $this->db->commit();
        return 1;
    }

    // -----------------------------------------------------------------------
    // Métodos de negocio PEP
    // -----------------------------------------------------------------------

    /**
     * Registra una consulta PEP manual y actualiza los extrafields de la empresa.
     *
     * @param int    $fk_societe       ID empresa
     * @param string $resultado        negativo|positivo|sin_respuesta|error_uif
     * @param string $referencia_uif   Folio UIF (opcional)
     * @param string $observaciones    Notas del oficial
     * @param object $user
     * @return int  rowid del registro creado, -1 si error
     */
    public function verificarPEP(int $fk_societe, string $resultado, $user, string $referencia_uif = '', string $observaciones = ''): int
    {
        $this->fk_societe     = $fk_societe;
        $this->resultado      = $resultado;
        $this->referencia_uif = $referencia_uif;
        $this->observaciones  = $observaciones;
        $this->nivel_diligencia = $this->calcularNivelDiligencia($fk_societe, $resultado);
        $this->fecha_consulta = $this->db->idate(dol_now());

        return $this->create($user);
    }

    /**
     * Devuelve el resultado PEP actual del cliente (desde extrafield de societe).
     * no_consultado si nunca se ha verificado.
     *
     * @param int $fk_societe
     * @return string  negativo|positivo|sin_respuesta|error_uif|no_consultado
     */
    public function getResultadoActual(int $fk_societe): string
    {
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $soc = new Societe($this->db);
        if ($soc->fetch($fk_societe) <= 0) {
            return self::RESULTADO_NO_CONSULTADO;
        }
        $soc->fetch_optionals();
        return $soc->array_options['options_pld_resultado_pep'] ?? self::RESULTADO_NO_CONSULTADO;
    }

    /**
     * Comprueba rápidamente si un cliente es PEP positivo.
     *
     * @param int $fk_societe
     * @return bool
     */
    public function esClientePEP(int $fk_societe): bool
    {
        return $this->getResultadoActual($fk_societe) === self::RESULTADO_POSITIVO;
    }

    /**
     * Obtiene el historial de verificaciones PEP para una empresa.
     *
     * @param int $fk_societe
     * @param int $limit  Máximo de registros (0 = sin límite)
     * @return array  Array de stdClass ordenado DESC por fecha
     */
    public function getHistorialVerificaciones(int $fk_societe, int $limit = 10): array
    {
        $sql  = "SELECT rowid, fecha_consulta, resultado, referencia_uif, nivel_diligencia, observaciones, fk_user_creat";
        $sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql .= " WHERE fk_societe = ".(int)$fk_societe;
        $sql .= " AND entity = ".(int)$this->entity;
        $sql .= " ORDER BY fecha_consulta DESC";
        if ($limit > 0) {
            $sql .= $this->db->plimit($limit);
        }

        $resql = $this->db->query($sql);
        $historial = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $historial[] = $obj;
            }
            $this->db->free($resql);
        }
        return $historial;
    }

    /**
     * Clasifica el nivel de riesgo / diligencia debida de un cliente.
     * Reglas básicas (deben documentarse como decisión del sujeto obligado):
     *   - Resultado PEP positivo → reforzada
     *   - Domicilio extranjero   → normal (o reforzada si PEP)
     *   - Resto                  → simplificada
     *
     * @param int    $fk_societe
     * @param string $resultado_pep  negativo|positivo|sin_respuesta|no_consultado
     * @return string  simplificada|normal|reforzada
     */
    public function calcularNivelDiligencia(int $fk_societe, string $resultado_pep): string
    {
        if ($resultado_pep === self::RESULTADO_POSITIVO) {
            return self::DILIGENCIA_REFORZADA;
        }

        // Verificar si tiene domicilio extranjero
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $soc = new Societe($this->db);
        if ($soc->fetch($fk_societe) > 0) {
            $soc->fetch_optionals();
            if (!empty($soc->array_options['options_pld_es_domicilio_extranjero'])) {
                return self::DILIGENCIA_NORMAL;
            }
        }

        return self::DILIGENCIA_SIMPLIFICADA;
    }

    // -----------------------------------------------------------------------
    // Helpers internos
    // -----------------------------------------------------------------------

    /**
     * Actualiza los extrafields pld_resultado_pep, pld_fecha_verificacion_pep,
     * pld_nivel_diligencia y pld_is_pep en llx_societe_extrafields.
     */
    private function actualizarExtrafieldsSociete(int $fk_societe, string $resultado, ?string $nivel): void
    {
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $soc = new Societe($this->db);
        if ($soc->fetch($fk_societe) <= 0) {
            return;
        }
        $soc->fetch_optionals();

        $soc->array_options['options_pld_resultado_pep']        = $resultado;
        $soc->array_options['options_pld_fecha_verificacion_pep'] = dol_now();
        $soc->array_options['options_pld_is_pep']               = ($resultado === self::RESULTADO_POSITIVO) ? 1 : 0;
        if ($nivel) {
            $soc->array_options['options_pld_nivel_diligencia'] = $nivel;
        }

        $soc->insertExtraFields();
    }
}
