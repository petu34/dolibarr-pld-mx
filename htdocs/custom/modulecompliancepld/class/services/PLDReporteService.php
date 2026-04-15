<?php
declare(strict_types=1);

/**
 * @file    class/services/PLDReporteService.php
 * @module  modulecompliancepld
 *
 * Servicio de consultas para el dashboard y reportes PLD.
 *
 * Centraliza las queries de contadores y listados que anteriormente
 * vivían como SQL inline en index.php (Mejora 1 — Service Layer).
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

class PLDReporteService
{
    public function __construct(private $db) {}

    /**
     * Contadores del dashboard para un mes dado.
     *
     * Devuelve en una sola llamada los cuatro contadores del panel
     * principal: operaciones del mes, avisos pendientes, alertas abiertas
     * y documentos con fecha de vencimiento vencida.
     *
     * @param string $mesActual  Formato YYYYMM (ej: '202604')
     * @return array{ops_mes: int, avisos_pendientes: int, alertas_abiertas: int, docs_vencidos: int}
     */
    public function getContadoresDashboard(string $mesActual): array
    {
        $today_sql = dol_print_date(dol_now(), 'dayrfc');

        $sql_ops = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_operacion"
            ." WHERE entity IN (".getEntity('modulecompliancepld').")"
            ." AND mes_reportado = '".$this->db->escape($mesActual)."'";

        $sql_avisos = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_aviso"
            ." WHERE entity IN (".getEntity('modulecompliancepld').")"
            ." AND estado IN ('borrador', 'pendiente')";

        $sql_alertas = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_alerta"
            ." WHERE entity IN (".getEntity('modulecompliancepld').")"
            ." AND estado = 'abierta'";

        $sql_docs = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_documento"
            ." WHERE entity IN (".getEntity('modulecompliancepld').")"
            ." AND fecha_vencimiento IS NOT NULL"
            ." AND fecha_vencimiento < '".$this->db->escape($today_sql)."'";

        return [
            'ops_mes'           => $this->countQuery($sql_ops),
            'avisos_pendientes' => $this->countQuery($sql_avisos),
            'alertas_abiertas'  => $this->countQuery($sql_alertas),
            'docs_vencidos'     => $this->countQuery($sql_docs),
        ];
    }

    /**
     * Últimas N operaciones vulnerables con nombre de cliente.
     *
     * @param int $limit Máximo de filas (default 10)
     * @return stdClass[]  rowid, folio_interno, tipo_operacion, monto_mxn, supera_umbral, estado, empresa_nom
     */
    public function getUltimasOperaciones(int $limit = 10): array
    {
        $sql  = "SELECT o.rowid, o.folio_interno, o.tipo_operacion,";
        $sql .= " o.monto_mxn, o.supera_umbral, o.estado, s.nom as empresa_nom";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion as o";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = o.fk_societe";
        $sql .= " WHERE o.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= " ORDER BY o.datec DESC";
        $sql .= $this->db->plimit($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Alertas abiertas ordenadas por nivel de riesgo descendente.
     *
     * @param int $limit Máximo de filas (default 10)
     * @return stdClass[]  rowid, tipo_alerta, nivel_riesgo, fecha_alerta, empresa_nom
     */
    public function getAlertasAbiertas(int $limit = 10): array
    {
        $sql  = "SELECT al.rowid, al.tipo_alerta, al.nivel_riesgo,";
        $sql .= " al.datec as fecha_alerta, s.nom as empresa_nom";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_alerta as al";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = al.fk_societe";
        $sql .= " WHERE al.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= " AND al.estado = 'abierta'";
        $sql .= " ORDER BY CASE al.nivel_riesgo WHEN 'alto' THEN 1 WHEN 'medio' THEN 2 ELSE 3 END, al.datec DESC";
        $sql .= $this->db->plimit($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Avisos SAT en estado borrador o pendiente.
     *
     * @param int $limit Máximo de filas (default 10)
     * @return stdClass[]  rowid, referencia_aviso, tipo_aviso, mes_reportado, numero_operaciones, monto_total_operaciones
     */
    public function getAvisosPendientes(int $limit = 10): array
    {
        $sql  = "SELECT a.rowid, a.referencia_aviso, a.tipo_aviso,";
        $sql .= " a.mes_reportado, a.numero_operaciones, a.monto_total_operaciones";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_aviso as a";
        $sql .= " WHERE a.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= " AND a.estado IN ('borrador', 'pendiente')";
        $sql .= " ORDER BY a.mes_reportado ASC, a.datec DESC";
        $sql .= $this->db->plimit($limit, 0);

        return $this->fetchAll($sql);
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function countQuery(string $sql): int
    {
        $res = $this->db->query($sql);
        if (!$res) {
            return 0;
        }
        $obj = $this->db->fetch_object($res);
        $this->db->free($res);
        return (int)($obj->total ?? 0);
    }

    private function fetchAll(string $sql): array
    {
        $res = $this->db->query($sql);
        if (!$res) {
            return [];
        }
        $rows = [];
        while ($obj = $this->db->fetch_object($res)) {
            $rows[] = $obj;
        }
        $this->db->free($res);
        return $rows;
    }
}
