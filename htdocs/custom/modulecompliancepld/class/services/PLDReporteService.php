<?php
declare(strict_types=1);

/**
 * @file    class/services/PLDReporteService.php
 * @module  modulecompliancepld
 *
 * Servicio de consultas para el dashboard, listas y reportes PLD.
 *
 * Centraliza todas las queries SELECT que anteriormente vivían como SQL
 * inline en index.php y en los archivos *_list.php del módulo.
 *
 * Mejora 1 — Service Layer: métodos de dashboard (getContadoresDashboard,
 *   getUltimasOperaciones, getAlertasAbiertas, getAvisosPendientes).
 * Mejora 4 — Eliminar SQL en vistas: métodos de lista/conteo para las
 *   cinco entidades PLD y helper getSocietesParaFiltro().
 *
 * @license GNU/GPL v3+
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

class PLDReporteService
{
    public function __construct(private $db) {}

    // ── Dashboard ─────────────────────────────────────────────────────────────

    /**
     * Contadores del dashboard para un mes dado.
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
     * @return stdClass[]
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
     * @return stdClass[]
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
     * @return stdClass[]
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

    // ── Helpers de filtro ─────────────────────────────────────────────────────

    /**
     * Array de societes para dropdown de filtro.
     *
     * @return array<int, string>  [rowid => nom]
     */
    public function getSocietesParaFiltro(): array
    {
        $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."societe"
            ." WHERE entity IN (".getEntity('societe').") ORDER BY nom";
        $res = $this->db->query($sql);
        $out = [];
        if ($res) {
            while ($obj = $this->db->fetch_object($res)) {
                $out[(int) $obj->rowid] = $obj->nom;
            }
            $this->db->free($res);
        }
        return $out;
    }

    // ── Operaciones ───────────────────────────────────────────────────────────

    /**
     * Lista paginada de operaciones con filtros.
     *
     * @param array  $filtros     Claves: empresa(int), tipo, estado, supera
     * @param int    $limit       Filas por página (se pide $limit+1 para detectar pág siguiente)
     * @param int    $offset      Desplazamiento
     * @param string $sortField   Campo de orden (ej: 'o.fecha_operacion')
     * @param string $sortOrder   'ASC'|'DESC'
     * @return stdClass[]
     */
    public function getListaOperaciones(array $filtros, int $limit, int $offset, string $sortField, string $sortOrder): array
    {
        $sql  = "SELECT o.rowid, o.folio_interno, o.fk_societe, o.tipo_operacion, o.fecha_operacion,";
        $sql .= " o.monto_mxn, o.supera_umbral, o.estado, s.nom as empresa_nom";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion as o";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = o.fk_societe";
        $sql .= " WHERE o.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondOperaciones($filtros);
        $sql .= $this->db->order($sortField, $sortOrder);
        $sql .= $this->db->plimit($limit + 1, $offset);
        return $this->fetchAll($sql);
    }

    /**
     * Conteo total de operaciones con filtros (para paginación).
     *
     * @param array $filtros  Mismas claves que getListaOperaciones()
     * @return int
     */
    public function countOperaciones(array $filtros): int
    {
        $sql  = "SELECT COUNT(o.rowid) as total FROM ".MAIN_DB_PREFIX."pld_operacion as o";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = o.fk_societe";
        $sql .= " WHERE o.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondOperaciones($filtros);
        return $this->countQuery($sql);
    }

    private function buildCondOperaciones(array $f): string
    {
        $c = '';
        if (!empty($f['empresa'])) {
            $c .= " AND o.fk_societe = ".((int) $f['empresa']);
        }
        if (!empty($f['tipo'])) {
            $c .= " AND o.tipo_operacion = '".$this->db->escape($f['tipo'])."'";
        }
        if (!empty($f['estado'])) {
            $c .= " AND o.estado = '".$this->db->escape($f['estado'])."'";
        }
        if (isset($f['supera']) && $f['supera'] !== '') {
            $c .= " AND o.supera_umbral = ".((int) $f['supera']);
        }
        return $c;
    }

    // ── Avisos ────────────────────────────────────────────────────────────────

    /**
     * Lista paginada de avisos SAT con filtros.
     *
     * @param array  $filtros  Claves: tipo, estado, mes (YYYYMM)
     * @return stdClass[]
     */
    public function getListaAvisos(array $filtros, int $limit, int $offset, string $sortField, string $sortOrder): array
    {
        $sql  = "SELECT a.rowid, a.referencia_aviso, a.tipo_aviso, a.mes_reportado, a.estado,";
        $sql .= " a.fecha_presentacion, a.folio_sat, a.numero_operaciones, a.monto_total_operaciones";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_aviso as a";
        $sql .= " WHERE a.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondAvisos($filtros);
        $sql .= $this->db->order($sortField, $sortOrder);
        $sql .= $this->db->plimit($limit + 1, $offset);
        return $this->fetchAll($sql);
    }

    /**
     * @param array $filtros  Mismas claves que getListaAvisos()
     */
    public function countAvisos(array $filtros): int
    {
        $sql  = "SELECT COUNT(a.rowid) as total FROM ".MAIN_DB_PREFIX."pld_aviso as a";
        $sql .= " WHERE a.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondAvisos($filtros);
        return $this->countQuery($sql);
    }

    private function buildCondAvisos(array $f): string
    {
        $c = '';
        if (!empty($f['tipo'])) {
            $c .= " AND a.tipo_aviso = '".$this->db->escape($f['tipo'])."'";
        }
        if (!empty($f['estado'])) {
            $c .= " AND a.estado = '".$this->db->escape($f['estado'])."'";
        }
        if (!empty($f['mes'])) {
            $c .= " AND a.mes_reportado = '".$this->db->escape($f['mes'])."'";
        }
        return $c;
    }

    // ── Alertas ───────────────────────────────────────────────────────────────

    /**
     * Lista paginada de alertas con filtros.
     *
     * @param array  $filtros  Claves: nivel, estado, tipo (LIKE)
     * @return stdClass[]
     */
    public function getListaAlertas(array $filtros, int $limit, int $offset, string $sortField, string $sortOrder): array
    {
        $sql  = "SELECT al.rowid, al.tipo_alerta, al.nivel_riesgo, al.fk_societe,";
        $sql .= " al.descripcion, al.estado, al.datec as fecha_alerta, s.nom as empresa_nom";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_alerta as al";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = al.fk_societe";
        $sql .= " WHERE al.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondAlertas($filtros);
        $sql .= $this->db->order($sortField, $sortOrder);
        $sql .= $this->db->plimit($limit + 1, $offset);
        return $this->fetchAll($sql);
    }

    /**
     * @param array $filtros  Mismas claves que getListaAlertas()
     */
    public function countAlertas(array $filtros): int
    {
        $sql  = "SELECT COUNT(al.rowid) as total FROM ".MAIN_DB_PREFIX."pld_alerta as al";
        $sql .= " WHERE al.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondAlertas($filtros);
        return $this->countQuery($sql);
    }

    private function buildCondAlertas(array $f): string
    {
        $c = '';
        if (!empty($f['nivel'])) {
            $c .= " AND al.nivel_riesgo = '".$this->db->escape($f['nivel'])."'";
        }
        if (!empty($f['estado'])) {
            $c .= " AND al.estado = '".$this->db->escape($f['estado'])."'";
        }
        if (!empty($f['tipo'])) {
            $c .= " AND al.tipo_alerta LIKE '%".$this->db->escape($f['tipo'])."%'";
        }
        return $c;
    }

    // ── Documentos ────────────────────────────────────────────────────────────

    /**
     * Lista paginada de documentos con filtros.
     *
     * @param array  $filtros  Claves: empresa(int), tipo (LIKE), verif ('0'|'1'|'')
     * @return stdClass[]
     */
    public function getListaDocumentos(array $filtros, int $limit, int $offset, string $sortField, string $sortOrder): array
    {
        $sql  = "SELECT d.rowid, d.fk_societe, d.fk_socpeople, d.tipo_documento_pld, d.numero_documento,";
        $sql .= " d.fecha_emision, d.fecha_vencimiento, d.verificado, s.nom as empresa_nom,";
        $sql .= " CONCAT(sp.firstname, ' ', sp.lastname) as contacto_nom";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_documento as d";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = d.fk_societe";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON sp.rowid = d.fk_socpeople";
        $sql .= " WHERE d.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondDocumentos($filtros);
        $sql .= $this->db->order($sortField, $sortOrder);
        $sql .= $this->db->plimit($limit + 1, $offset);
        return $this->fetchAll($sql);
    }

    /**
     * @param array $filtros  Mismas claves que getListaDocumentos()
     */
    public function countDocumentos(array $filtros): int
    {
        $sql  = "SELECT COUNT(d.rowid) as total FROM ".MAIN_DB_PREFIX."pld_documento as d";
        $sql .= " WHERE d.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondDocumentos($filtros);
        return $this->countQuery($sql);
    }

    private function buildCondDocumentos(array $f): string
    {
        $c = '';
        if (!empty($f['empresa'])) {
            $c .= " AND d.fk_societe = ".((int) $f['empresa']);
        }
        if (!empty($f['tipo'])) {
            $c .= " AND d.tipo_documento_pld LIKE '%".$this->db->escape($f['tipo'])."%'";
        }
        if (isset($f['verif']) && $f['verif'] !== '') {
            $c .= " AND d.verificado = ".((int) $f['verif']);
        }
        return $c;
    }

    // ── Beneficiarios ─────────────────────────────────────────────────────────

    /**
     * Lista paginada de beneficiarios controladores con filtros.
     *
     * @param array  $filtros  Claves: empresa(int), pep ('0'|'1'|'')
     * @return stdClass[]
     */
    public function getListaBeneficiarios(array $filtros, int $limit, int $offset, string $sortField, string $sortOrder): array
    {
        $sql  = "SELECT b.rowid, b.fk_societe,";
        $sql .= " CONCAT(b.nombre, ' ', b.apellido_paterno, CASE WHEN b.apellido_materno IS NOT NULL THEN CONCAT(' ', b.apellido_materno) ELSE '' END) as nombre_completo,";
        $sql .= " b.curp, b.rfc, b.tipo_beneficiario, b.porcentaje_participacion, b.es_pep, b.activo, s.nom as empresa_nom";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_beneficiario as b";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = b.fk_societe";
        $sql .= " WHERE b.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondBeneficiarios($filtros);
        $sql .= $this->db->order($sortField, $sortOrder);
        $sql .= $this->db->plimit($limit + 1, $offset);
        return $this->fetchAll($sql);
    }

    /**
     * @param array $filtros  Mismas claves que getListaBeneficiarios()
     */
    public function countBeneficiarios(array $filtros): int
    {
        $sql  = "SELECT COUNT(b.rowid) as total FROM ".MAIN_DB_PREFIX."pld_beneficiario as b";
        $sql .= " WHERE b.entity IN (".getEntity('modulecompliancepld').")";
        $sql .= $this->buildCondBeneficiarios($filtros);
        return $this->countQuery($sql);
    }

    private function buildCondBeneficiarios(array $f): string
    {
        $c = '';
        if (!empty($f['empresa'])) {
            $c .= " AND b.fk_societe = ".((int) $f['empresa']);
        }
        if (isset($f['pep']) && $f['pep'] !== '') {
            $c .= " AND b.es_pep = ".((int) $f['pep']);
        }
        return $c;
    }

    // ── Reportes (antes inline en reportes.php) ─────────────────────────────

    /**
     * Resumen mensual: contadores de operaciones + desglose de avisos por estado.
     *
     * @param string $periodo  YYYYMM
     * @return stdClass{total_ops, total_monto, ops_umbral, ops_aviso, avisos_por_estado}
     */
    public function getResumenMensual(string $periodo): stdClass
    {
        $result = new stdClass();
        $result->total_ops = 0;
        $result->total_monto = 0;
        $result->ops_umbral = 0;
        $result->ops_aviso = 0;
        $result->avisos_por_estado = [];

        $sql  = "SELECT COUNT(rowid) as total_ops, COALESCE(SUM(monto_mxn), 0) as total_monto,";
        $sql .= " SUM(CASE WHEN supera_umbral = 1 THEN 1 ELSE 0 END) as ops_umbral,";
        $sql .= " SUM(CASE WHEN requiere_aviso = 1 THEN 1 ELSE 0 END) as ops_aviso";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion";
        $sql .= " WHERE entity IN (".getEntity('modulecompliancepld').") AND mes_reportado = '".$this->db->escape($periodo)."'";
        $res  = $this->db->query($sql);
        if ($res && $obj = $this->db->fetch_object($res)) {
            $result->total_ops   = (int) $obj->total_ops;
            $result->total_monto = (float) $obj->total_monto;
            $result->ops_umbral  = (int) $obj->ops_umbral;
            $result->ops_aviso   = (int) $obj->ops_aviso;
            $this->db->free($res);
        }

        $sql2  = "SELECT COUNT(rowid) as total, estado FROM ".MAIN_DB_PREFIX."pld_aviso";
        $sql2 .= " WHERE entity IN (".getEntity('modulecompliancepld').") AND mes_reportado = '".$this->db->escape($periodo)."'";
        $sql2 .= " GROUP BY estado";
        $res2  = $this->db->query($sql2);
        if ($res2) {
            while ($obj2 = $this->db->fetch_object($res2)) {
                $result->avisos_por_estado[] = $obj2;
            }
            $this->db->free($res2);
        }

        return $result;
    }

    /**
     * Operaciones agrupadas por cliente para un período.
     *
     * @param string $periodo  YYYYMM
     * @return stdClass[]
     */
    public function getOperacionesPorCliente(string $periodo): array
    {
        $sql  = "SELECT s.rowid, s.nom, COUNT(o.rowid) as total_ops, SUM(o.monto_mxn) as total_monto";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion as o";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = o.fk_societe";
        $sql .= " WHERE o.entity IN (".getEntity('modulecompliancepld').") AND o.mes_reportado = '".$this->db->escape($periodo)."'";
        $sql .= " GROUP BY s.rowid, s.nom ORDER BY SUM(o.monto_mxn) DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Estado de avisos agrupado por mes y estado.
     *
     * @return stdClass[]
     */
    public function getEstadoAvisos(): array
    {
        $sql  = "SELECT mes_reportado, estado, COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_aviso";
        $sql .= " WHERE entity IN (".getEntity('modulecompliancepld').")";
        $sql .= " GROUP BY mes_reportado, estado ORDER BY mes_reportado DESC, estado";
        return $this->fetchAll($sql);
    }

    /**
     * Alertas agrupadas por tipo y nivel de riesgo.
     *
     * @return stdClass[]
     */
    public function getAlertasPorTipo(): array
    {
        $sql  = "SELECT tipo_alerta, nivel_riesgo, COUNT(rowid) as total,";
        $sql .= " SUM(CASE WHEN estado = 'abierta' THEN 1 ELSE 0 END) as abiertas,";
        $sql .= " SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_alerta";
        $sql .= " WHERE entity IN (".getEntity('modulecompliancepld').")";
        $sql .= " GROUP BY tipo_alerta, nivel_riesgo ORDER BY nivel_riesgo, COUNT(rowid) DESC";
        return $this->fetchAll($sql);
    }

    // ── Monitoreo (Fracción X) ──────────────────────────────────────────────────

    /**
     * Contadores del dashboard de monitoreo: PEPs, alto riesgo, fuera de perfil.
     *
     * @return array{tiene_pep: int, alto_riesgo: int, fuera_perfil: int, monitoreos_hoy: int}
     */
    public function getContadoresMonitoreo(): array
    {
        $sql_pep = "SELECT COUNT(DISTINCT fk_societe) as total FROM ".MAIN_DB_PREFIX."pld_perfil_cliente"
            ." WHERE entity = 1 AND es_pep = 1 AND nivel_riesgo_perfil IN ('alto', 'critico')";

        $sql_riesgo = "SELECT COUNT(DISTINCT fk_societe) as total FROM ".MAIN_DB_PREFIX."pld_perfil_cliente"
            ." WHERE entity = 1 AND nivel_riesgo_perfil IN ('alto', 'critico')";

        $sql_fuera = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_monitoreo_log"
            ." WHERE entity = 1 AND supera_umbral_perfil = 1"
            ." AND tipo_evaluacion = 'perfil_transaccional'";

        $sql_hoy = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."pld_monitoreo_log"
            ." WHERE entity = 1 AND datec >= '".$this->db->idate(dol_now())."'";

        return [
            'tiene_pep'       => $this->countQuery($sql_pep),
            'alto_riesgo'     => $this->countQuery($sql_riesgo),
            'fuera_perfil'    => $this->countQuery($sql_fuera),
            'monitoreos_hoy'  => $this->countQuery($sql_hoy),
        ];
    }

    /**
     * Clientes PEP activos con perfil de riesgo.
     *
     * @param int $limit  Máximo de filas
     * @return stdClass[]
     */
    public function getClientesPEP(int $limit = 20): array
    {
        $sql  = "SELECT pc.fk_societe, s.nom as empresa_nom,";
        $sql .= " pc.nivel_riesgo_perfil, pc.nivel_diligencia,";
        $sql .= " pc.num_operaciones_periodo, pc.monto_acumulado_periodo,";
        $sql .= " pc.fecha_ultima_evaluacion, pc.factores_riesgo";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_perfil_cliente as pc";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pc.fk_societe";
        $sql .= " WHERE pc.entity = 1 AND pc.es_pep = 1";
        $sql .= " ORDER BY CASE pc.nivel_riesgo_perfil WHEN 'critico' THEN 1 WHEN 'alto' THEN 2 ELSE 3 END, pc.fecha_ultima_evaluacion DESC";
        $sql .= $this->db->plimit($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Clientes clasificados como alto riesgo.
     *
     * @param int $limit  Máximo de filas
     * @return stdClass[]
     */
    public function getClientesAltoRiesgo(int $limit = 20): array
    {
        $sql  = "SELECT pc.fk_societe, s.nom as empresa_nom,";
        $sql .= " pc.nivel_riesgo_perfil, pc.frecuencia_mensual,";
        $sql .= " pc.promedio_monto, pc.max_monto_historico,";
        $sql .= " pc.monto_acumulado_periodo, pc.fecha_ultima_evaluacion";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_perfil_cliente as pc";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pc.fk_societe";
        $sql .= " WHERE pc.entity = 1 AND pc.nivel_riesgo_perfil IN ('alto', 'critico')";
        $sql .= " ORDER BY CASE pc.nivel_riesgo_perfil WHEN 'critico' THEN 1 ELSE 2 END, pc.monto_acumulado_periodo DESC";
        $sql .= $this->db->plimit($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Operaciones detectadas fuera del perfil transaccional.
     *
     * @param int $limit  Máximo de filas
     * @return stdClass[]
     */
    public function getOperacionesFueraPerfil(int $limit = 20): array
    {
        $sql  = "SELECT ml.rowid as log_id, ml.fk_societe, ml.fk_pld_operacion,";
        $sql .= " ml.z_score, ml.variacion_porcentual, ml.detalle,";
        $sql .= " ml.nivel_riesgo_detectado, ml.datec as fecha_deteccion,";
        $sql .= " s.nom as empresa_nom, o.folio_interno, o.monto_mxn";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_monitoreo_log as ml";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = ml.fk_societe";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."pld_operacion as o ON o.rowid = ml.fk_pld_operacion";
        $sql .= " WHERE ml.entity = 1 AND ml.supera_umbral_perfil = 1";
        $sql .= " ORDER BY ml.datec DESC";
        $sql .= $this->db->plimit($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Log de monitoreo reciente.
     *
     * @param int    $limit       Máximo de filas
     * @param string $tipoFiltro  Tipo de evaluación (opcional)
     * @return stdClass[]
     */
    public function getLogMonitoreo(int $limit = 50, string $tipoFiltro = ''): array
    {
        $sql  = "SELECT ml.rowid, ml.tipo_evaluacion, ml.fk_societe, ml.fk_pld_operacion,";
        $sql .= " ml.resultado, ml.nivel_riesgo_detectado, ml.detalle,";
        $sql .= " ml.z_score, ml.variacion_porcentual, ml.genero_alerta,";
        $sql .= " ml.datec, s.nom as empresa_nom";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_monitoreo_log as ml";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = ml.fk_societe";
        $sql .= " WHERE ml.entity = 1";
        if (!empty($tipoFiltro)) {
            $sql .= " AND ml.tipo_evaluacion = '".$this->db->escape($tipoFiltro)."'";
        }
        $sql .= " ORDER BY ml.datec DESC";
        $sql .= $this->db->plimit($limit, 0);

        return $this->fetchAll($sql);
    }

    /**
     * Resumen de perfiles de clientes agrupados por nivel de riesgo.
     *
     * @return stdClass[]
     */
    public function getResumenPerfilesRiesgo(): array
    {
        $sql  = "SELECT nivel_riesgo_perfil, COUNT(rowid) as total,";
        $sql .= " SUM(num_operaciones_periodo) as total_ops,";
        $sql .= " SUM(monto_acumulado_periodo) as monto_total";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_perfil_cliente";
        $sql .= " WHERE entity = 1";
        $sql .= " GROUP BY nivel_riesgo_perfil ORDER BY monto_total DESC";

        return $this->fetchAll($sql);
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function countQuery(string $sql): int
    {
        $res = $this->db->query($sql);
        if (!$res) {
            dol_syslog(__METHOD__.' BD error: '.$this->db->lasterror(), LOG_ERR);
            return 0;
        }
        $obj = $this->db->fetch_object($res);
        $this->db->free($res);
        return (int) ($obj->total ?? 0);
    }

    private function fetchAll(string $sql): array
    {
        $res = $this->db->query($sql);
        if (!$res) {
            dol_syslog(__METHOD__.' BD error: '.$this->db->lasterror(), LOG_ERR);
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
