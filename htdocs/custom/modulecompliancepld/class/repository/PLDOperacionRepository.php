<?php
declare(strict_types=1);

/**
 * @file    class/repository/PLDOperacionRepository.php
 * @module  modulecompliancepld
 *
 * Repositorio de queries de negocio para PLDOperacion.
 *
 * Encapsula las consultas SQL complejas separadas del modelo CommonObject,
 * manteniendo PLDOperacion centrado exclusivamente en CRUD básico (create /
 * fetch / update / delete) conforme al patrón Repository.
 *
 * @see PLDOperacion
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

class PLDOperacionRepository
{
    public function __construct(private $db) {}

    /**
     * Calcula la suma de montos brutos de un cliente en los últimos 6 meses,
     * excluyendo opcionalmente la operación actual.
     *
     * Implementa la regla de acumulación periódica (Art. 7 Regl. LFPIORPI):
     * si la suma del período iguala o supera el umbral, la operación también
     * debe generar aviso aunque individualmente no lo supere.
     *
     * Usa COALESCE(monto_sin_impuestos, monto_mxn) para comparar contra el
     * umbral sin IVA, conforme al Art. 6 DOF 27/03/2026.
     *
     * @param int      $fkSociete  ID del tercero (cliente)
     * @param int|null $excludeId  ID de operación a excluir (la actual al editar)
     * @return float Suma de montos brutos en MXN
     */
    public function getAcumuladoSeisMeses(int $fkSociete, ?int $excludeId = null): float
    {
        $fechaInicio = date('Y-m-d', strtotime('-6 months'));

        $sql  = "SELECT SUM(COALESCE(monto_sin_impuestos, monto_mxn)) as total_acum";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion";
        $sql .= " WHERE fk_societe = ".(int)$fkSociete;
        $sql .= " AND fecha_operacion >= '".$this->db->escape($fechaInicio)."'";
        $sql .= " AND estado != 'cancelada'";
        if ($excludeId > 0) {
            $sql .= " AND rowid != ".(int)$excludeId;
        }

        $resql = $this->db->query($sql);
        if (!$resql) {
            return 0.0;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        return (float)($obj->total_acum ?? 0);
    }

    /**
     * Devuelve el último número secuencial de folio para un mes/año dados.
     *
     * Parsea el último segmento numérico de folios con formato PLD-YYYY-MM-NNNN.
     * Usado por PLDOperacion::generarFolioInterno() para calcular el siguiente
     * número correlativo sin race conditions visibles en uso normal.
     *
     * @param string $year  Año en 4 dígitos (ej: '2026')
     * @param string $month Mes con cero inicial (ej: '04')
     * @return int Último consecutivo encontrado; 0 si no hay registros en el mes
     */
    public function getUltimoFolioConsecutivo(string $year, string $month): int
    {
        $mes   = $this->db->escape($year.$month);
        $ifsql = $this->db->ifsql(
            "folio_interno LIKE 'PLD-{$year}-{$month}-%'",
            "SUBSTRING_INDEX(folio_interno, '-', -1)",
            "0"
        );

        $sql  = "SELECT MAX(CAST(".$ifsql." AS UNSIGNED)) as ultimo";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion";
        $sql .= " WHERE mes_reportado = '".$mes."'";

        $resql = $this->db->query($sql);
        if (!$resql) {
            return 0;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        return (int)($obj->ultimo ?? 0);
    }

    /**
     * Devuelve los rowids de beneficiarios activos de una empresa,
     * ordenados por porcentaje de participación descendente.
     *
     * @param int $fkSociete ID del tercero
     * @return int[] Array de rowids de llx_pld_beneficiario
     */
    public function fetchBeneficiarioIds(int $fkSociete): array
    {
        $sql  = "SELECT rowid FROM ".MAIN_DB_PREFIX."pld_beneficiario";
        $sql .= " WHERE fk_societe = ".(int)$fkSociete;
        $sql .= " AND activo = 1";
        $sql .= " ORDER BY porcentaje_participacion DESC";

        $resql = $this->db->query($sql);
        if (!$resql) {
            return [];
        }

        $ids = [];
        while ($obj = $this->db->fetch_object($resql)) {
            $ids[] = (int)$obj->rowid;
        }
        $this->db->free($resql);

        return $ids;
    }

    /**
     * Obtiene los pagos y sus extrafields PLD asociados a una factura.
     *
     * Realiza JOIN entre llx_paiement, llx_paiement_extrafields y
     * llx_paiement_facture para recuperar en una sola query todos los datos
     * de forma de pago que el módulo PLD necesita.
     *
     * @param int $fkFacture ID de la factura (llx_facture.rowid)
     * @return stdClass[] Array de objetos pago con propiedades PLD; vacío si no hay pagos
     */
    public function fetchFormasPago(int $fkFacture): array
    {
        $sql  = "SELECT p.rowid, p.datep, p.amount,";
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
        $sql .= " WHERE pf.fk_facture = ".(int)$fkFacture;
        $sql .= " ORDER BY p.datep ASC";

        $resql = $this->db->query($sql);
        if (!$resql) {
            return [];
        }

        $pagos = [];
        while ($obj = $this->db->fetch_object($resql)) {
            $pago                           = new stdClass();
            $pago->rowid                    = $obj->rowid;
            $pago->datep                    = $obj->datep;
            $pago->amount                   = $obj->amount;
            $pago->pld_forma_pago           = $obj->pld_forma_pago ?? '';
            $pago->pld_instrumento_monetario = $obj->pld_instrumento_monetario ?? '01';
            $pago->pld_moneda               = $obj->pld_moneda ?? 'MXN';
            $pago->pld_monto_efectivo       = (float)($obj->pld_monto_efectivo ?? 0);
            $pago->pld_monto_transferencia  = (float)($obj->pld_monto_transferencia ?? 0);
            $pago->pld_banco_destino        = $obj->pld_banco_destino ?? '';
            $pago->pld_cuenta_destino       = $obj->pld_cuenta_destino ?? '';
            $pago->pld_banco_cheque         = $obj->pld_banco_cheque ?? '';
            $pago->pld_numero_cheque        = $obj->pld_numero_cheque ?? '';
            $pagos[] = $pago;
        }
        $this->db->free($resql);

        return $pagos;
    }
}
