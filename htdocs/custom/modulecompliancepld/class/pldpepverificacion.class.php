<?php
declare(strict_types=1);

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Historial de verificaciones PEP ante la UIF.
 * Arts. 45 Bis–Quinquies Reglamento LFPIORPI DOF 27/03/2026.
 */
class PLDPEPVerificacion extends CommonObject
{
    public $element       = 'pld_pep_verificacion';
    public $table_element = 'pld_pep_verificacion';
    public $module        = 'modulecompliancepld';

    public $id;
    public $rowid;
    public $entity;

    public $fk_societe;

    public $fecha_consulta;

    /** negativo | positivo | sin_respuesta | error_uif */
    public $resultado;

    public $referencia_uif;
    public $observaciones;

    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $import_key;

    const RESULTADOS_VALIDOS = array('negativo', 'positivo', 'sin_respuesta', 'error_uif');

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Crea un nuevo registro de verificación PEP.
     *
     * @param  User $user  Usuario que ejecuta la acción
     * @return int         rowid creado (>0) o -1 en error
     */
    public function create(User $user): int
    {
        if (!in_array($this->resultado, self::RESULTADOS_VALIDOS, true)) {
            $this->error = 'ErrorResultadoPEPInvalido';
            return -1;
        }

        $now = dol_now();
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'pld_pep_verificacion';
        $sql .= ' (entity, fk_societe, fecha_consulta, resultado, referencia_uif, observaciones,';
        $sql .= '  date_creation, fk_user_creat, import_key)';
        $sql .= ' VALUES (';
        $sql .= ' '.((int) $this->entity).',';
        $sql .= ' '.((int) $this->fk_societe).',';
        $sql .= " '".$this->db->escape($this->fecha_consulta)."',";
        $sql .= " '".$this->db->escape($this->resultado)."',";
        $sql .= ' '.($this->referencia_uif !== null ? "'".$this->db->escape($this->referencia_uif)."'" : 'NULL').',';
        $sql .= ' '.($this->observaciones !== null ? "'".$this->db->escape($this->observaciones)."'" : 'NULL').',';
        $sql .= " '".$this->db->ifsql('1=1', $this->db->escape(dol_print_date($now, 'dayrfc')), 'NULL')."'";
        // ifsql workaround replaced with direct value below
        $sql = str_replace(
            "'".$this->db->ifsql('1=1', $this->db->escape(dol_print_date($now, 'dayrfc')), 'NULL')."'",
            "'".$this->db->escape(dol_print_date($now, 'dayrfc'))."'",
            $sql
        );
        $sql .= ', '.((int) $user->id).',';
        $sql .= ' NULL)';

        $res = $this->db->query($sql);
        if (!$res) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'pld_pep_verificacion');
        return (int) $this->id;
    }

    /**
     * Carga un registro por rowid.
     *
     * @param  int $rowid  rowid a cargar
     * @return int         1 OK, -1 error, 0 no encontrado
     */
    public function fetch(int $rowid): int
    {
        $sql = 'SELECT rowid, entity, fk_societe, fecha_consulta, resultado,';
        $sql .= ' referencia_uif, observaciones, date_creation, tms,';
        $sql .= ' fk_user_creat, fk_user_modif, import_key';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'pld_pep_verificacion';
        $sql .= ' WHERE rowid = '.((int) $rowid);

        $res = $this->db->query($sql);
        if (!$res) {
            $this->error = $this->db->lasterror();
            return -1;
        }
        $obj = $this->db->fetch_object($res);
        if (!$obj) {
            return 0;
        }

        $this->id                = (int) $obj->rowid;
        $this->rowid             = $this->id;
        $this->entity            = (int) $obj->entity;
        $this->fk_societe        = (int) $obj->fk_societe;
        $this->fecha_consulta    = $obj->fecha_consulta;
        $this->resultado         = $obj->resultado;
        $this->referencia_uif    = $obj->referencia_uif;
        $this->observaciones     = $obj->observaciones;
        $this->date_creation     = $this->db->jdate($obj->date_creation);
        $this->tms               = $this->db->jdate($obj->tms);
        $this->fk_user_creat     = (int) $obj->fk_user_creat;
        $this->fk_user_modif     = $obj->fk_user_modif !== null ? (int) $obj->fk_user_modif : null;
        $this->import_key        = $obj->import_key;

        return 1;
    }

    /**
     * Devuelve el historial de verificaciones de un tercero, más reciente primero.
     *
     * @param  int   $fkSociete  rowid del tercero
     * @param  int   $limit      Máximo de registros (0 = sin límite)
     * @return array             Array de objetos stdClass con las columnas de la tabla
     */
    public function fetchHistorial(int $fkSociete, int $limit = 10): array
    {
        $sql = 'SELECT rowid, fecha_consulta, resultado, referencia_uif, observaciones, fk_user_creat';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'pld_pep_verificacion';
        $sql .= ' WHERE fk_societe = '.((int) $fkSociete);
        $sql .= ' AND entity = '.((int) ($this->entity ?: 1));
        $sql .= ' ORDER BY fecha_consulta DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT '.((int) $limit);
        }

        $rows  = array();
        $res   = $this->db->query($sql);
        if (!$res) {
            $this->error = $this->db->lasterror();
            return $rows;
        }
        while ($obj = $this->db->fetch_object($res)) {
            $rows[] = $obj;
        }
        return $rows;
    }

    /**
     * Devuelve el resultado más reciente de verificación para un tercero.
     *
     * @param  int         $fkSociete  rowid del tercero
     * @return string|null             Valor de `resultado` o null si no hay registros
     */
    public function getUltimoResultado(int $fkSociete): ?string
    {
        $historial = $this->fetchHistorial($fkSociete, 1);
        return !empty($historial) ? $historial[0]->resultado : null;
    }

    /**
     * Clasifica el nivel de riesgo del cliente basándose en el resultado PEP.
     *
     * Lógica:
     * - positivo                     → muy_alto  (Art. 45 Bis Reglamento)
     * - sin_respuesta / error_uif    → alto       (diligencia reforzada preventiva)
     * - negativo                     → nivel sin modificar (conserva el que ya tenga)
     * - sin historial                → medio      (valor por defecto Art. 15)
     *
     * @param  int    $fkSociete         rowid del tercero
     * @param  string $nivelActual       Nivel de riesgo actual del cliente
     * @return string                    Nuevo nivel de riesgo sugerido
     */
    public function clasificarRiesgo(int $fkSociete, string $nivelActual = 'medio'): string
    {
        $ultimo = $this->getUltimoResultado($fkSociete);

        if ($ultimo === null) {
            return 'medio';
        }
        if ($ultimo === 'positivo') {
            return 'muy_alto';
        }
        if (in_array($ultimo, array('sin_respuesta', 'error_uif'), true)) {
            return 'alto';
        }
        // resultado === 'negativo': no degradar, conservar nivel actual
        return $nivelActual;
    }
}
