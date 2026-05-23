<?php
declare(strict_types=1);

/**
 * @file        class/services/PLDMonitorService.php
 * @module      CompliancePLD
 * @description Motor central de monitoreo automatizado PLD. Evalúa perfil
 *              transaccional, detecta operaciones fuera de perfil, acumulación
 *              sospechosa, y ejecuta seguimiento intensificado de PEPs y
 *              clientes de alto riesgo. Implementa la Fracción X — mecanismos
 *              automatizados de monitoreo permanente.
 * @author      Sisyphus
 * @version     1.0.0
 * @date        2026-05-09
 * @compliance  LFPIORPI Arts. 17, 18, 32, 45 Bis–45 Quinquies — PLD México
 *
 * @license     GNU/GPL
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldperfiltransaccional.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldalerta.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldpepverificacion.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldoperacion.class.php';

class PLDMonitorService
{
    public string $error  = '';
    public array  $errors = [];

    /** Período de análisis en meses para construir el perfil. */
    private const PERIODO_ANALISIS_MESES = 6;

    /** Umbral z-score para considerar operación fuera de perfil. */
    private const ZSCORE_UMBRAL = 2.0;

    /** Incremento porcentual sobre máximo histórico que dispara alerta. */
    private const INCREMENTO_UMBRAL_PCT = 50.0;

    /** Monto que sin historial previo dispara revisión. */
    private const MONTO_SIN_HISTORIAL_ALERTA = 250000.00;

    public function __construct(private $db) {}

    /**
     * Evalúa el perfil transaccional de un cliente después de registrar
     * una operación. Reconstruye o actualiza el perfil en BD y detecta
     * si la nueva operación está fuera del perfil habitual.
     *
     * @param PLDOperacion $operacion  Operación recién creada
     * @param object       $user       Usuario autenticado
     * @return array{fuera_perfil: bool, alerta_id: int|null, log_id: int, perfil: PLDPerfilTransaccional|null}
     */
    public function evaluarPerfilPostOperacion(PLDOperacion $operacion, object $user): array
    {
        $fkSociete = (int)$operacion->fk_societe;
        if ($fkSociete <= 0) {
            return ['fuera_perfil' => false, 'alerta_id' => null, 'log_id' => 0, 'perfil' => null];
        }

        $perfil = $this->construirPerfil($fkSociete);
        if (!$perfil) {
            $this->error = 'No se pudo construir el perfil transaccional para el cliente '.$fkSociete;
            return ['fuera_perfil' => false, 'alerta_id' => null, 'log_id' => 0, 'perfil' => null];
        }

        $this->guardarPerfil($perfil);

        $montoBruto = $operacion->getMontoBruto();
        $evaluacion = $perfil->evaluarOperacion($montoBruto);
        $alertId    = null;

        if ($evaluacion['fuera_perfil']) {
            $perfil->factores_riesgo[] = 'fuera_perfil';
            $perfil->nivel_riesgo_perfil = $perfil->clasificarRiesgo();
            $this->guardarPerfil($perfil);

            $alertId = $this->generarAlertaPerfil($operacion, $perfil, $evaluacion, $user);
        }

        $detalleMotivos = $evaluacion['fuera_perfil']
            ? 'FUERA DE PERFIL — '.implode(', ', $evaluacion['motivos'])
            : 'Dentro del perfil transaccional';

        $logId = $this->registrarLogMonitoreo(
            tipo: 'perfil_transaccional',
            fkSociete: $fkSociete,
            fkOperacion: (int)$operacion->id,
            fkAlerta: $alertId,
            resultado: $evaluacion['fuera_perfil'] ? 'anomalia' : 'ok',
            nivelRiesgo: $perfil->nivel_riesgo_perfil,
            detalle: $detalleMotivos,
            zScore: $evaluacion['z_score'],
            variacionPct: $evaluacion['variacion_pct'],
            superaUmbralPerfil: $evaluacion['fuera_perfil'],
            generoAlerta: $evaluacion['fuera_perfil']
        );

        return [
            'fuera_perfil' => $evaluacion['fuera_perfil'],
            'alerta_id'    => $alertId,
            'log_id'       => $logId,
            'perfil'       => $perfil,
        ];
    }

    /**
     * Construye el perfil transaccional de un cliente a partir de su
     * historial de operaciones en los últimos N meses.
     *
     * @param int $fkSociete  ID del tercero
     * @return PLDPerfilTransaccional|null  null si no se puede construir
     */
    public function construirPerfil(int $fkSociete): ?PLDPerfilTransaccional
    {
        $fechaFin    = date('Y-m-d');
        $fechaInicio = date('Y-m-d', strtotime('-'.self::PERIODO_ANALISIS_MESES.' months'));

        $perfil = new PLDPerfilTransaccional($fkSociete, $fechaInicio, $fechaFin);

        $sql  = "SELECT COUNT(rowid) as num_ops,";
        $sql .= " AVG(COALESCE(monto_sin_impuestos, monto_mxn)) as prom_monto,";
        $sql .= " STDDEV(COALESCE(monto_sin_impuestos, monto_mxn)) as desv_monto,";
        $sql .= " MAX(COALESCE(monto_sin_impuestos, monto_mxn)) as max_monto,";
        $sql .= " MIN(COALESCE(monto_sin_impuestos, monto_mxn)) as min_monto,";
        $sql .= " SUM(COALESCE(monto_sin_impuestos, monto_mxn)) as sum_monto";
        $sql .= " FROM ".MAIN_DB_PREFIX."pld_operacion";
        $sql .= " WHERE fk_societe = ".(int)$fkSociete;
        $sql .= " AND fecha_operacion >= '".$this->db->escape($fechaInicio)."'";
        $sql .= " AND fecha_operacion <= '".$this->db->escape($fechaFin)."'";
        $sql .= " AND estado != 'cancelada'";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            dol_syslog(__METHOD__.' BD error: '.$this->error, LOG_ERR);
            return null;
        }

        $row = $this->db->fetch_object($resql);
        $this->db->free($resql);

        if ($row) {
            $perfil->num_operaciones_periodo = (int)$row->num_ops;
            $perfil->promedio_monto          = round((float)($row->prom_monto ?? 0), 2);
            $perfil->desviacion_monto        = round((float)($row->desv_monto ?? 0), 2);
            $perfil->max_monto_historico     = round((float)($row->max_monto ?? 0), 2);
            $perfil->min_monto_historico     = round((float)($row->min_monto ?? 0), 2);
            $perfil->monto_acumulado_periodo = round((float)($row->sum_monto ?? 0), 2);
        }

        $mesesEnPeriodo = max(1, (strtotime($fechaFin) - strtotime($fechaInicio)) / (30 * 86400));
        $perfil->frecuencia_mensual = $perfil->num_operaciones_periodo > 0
            ? round($perfil->num_operaciones_periodo / $mesesEnPeriodo, 1)
            : 0.0;

        $pepVerif = new PLDPepVerificacion($this->db);
        $perfil->es_pep = $pepVerif->esClientePEP($fkSociete);

        if ($perfil->es_pep) {
            $perfil->factores_riesgo[] = 'pep_positivo';
            $perfil->nivel_diligencia  = 'reforzada';
        } else {
            $perfil->nivel_diligencia = $pepVerif->calcularNivelDiligencia($fkSociete, 'no_consultado');
        }

        $perfil->nivel_riesgo_perfil = $perfil->clasificarRiesgo();
        $perfil->fecha_ultima_evaluacion = date('Y-m-d H:i:s');

        return $perfil;
    }

    /**
     * Ejecuta monitoreo batch periódico sobre TODOS los clientes activos.
     * Evalúa perfil, PEP, y acumulación. Ideal para cron diario/semanal.
     *
     * @param object $user  Usuario del sistema
     * @return array{total_evaluados: int, anomalias: int, alertas: int, ejecuciones: int}
     */
    public function ejecutarMonitoreoPeriodico(object $user): array
    {
        $totalEvaluados = 0;
        $totalAnomalias = 0;
        $totalAlertas   = 0;

        $clientes = $this->getClientesActivos();

        foreach ($clientes as $cliente) {
            $perfil = $this->construirPerfil((int)$cliente->rowid);
            if (!$perfil) {
                continue;
            }
            $this->guardarPerfil($perfil);
            $totalEvaluados++;

            if ($perfil->nivel_riesgo_perfil !== 'bajo') {
                $totalAnomalias++;
            }

            if ($perfil->nivel_riesgo_perfil === 'alto' || $perfil->nivel_riesgo_perfil === 'critico') {
                $this->registrarLogMonitoreo(
                    tipo: 'batch_periodico',
                    fkSociete: (int)$cliente->rowid,
                    resultado: 'alerta',
                    nivelRiesgo: $perfil->nivel_riesgo_perfil,
                    detalle: 'Monitoreo batch — cliente con perfil '.$perfil->nivel_riesgo_perfil,
                    generoAlerta: false
                );

                if ($perfil->es_pep) {
                    $this->generarAlertaPEP($perfil->fk_societe, $perfil, $user);
                    $totalAlertas++;
                }
            }
        }

        return [
            'total_evaluados' => $totalEvaluados,
            'anomalias'       => $totalAnomalias,
            'alertas'         => $totalAlertas,
            'ejecuciones'     => $totalEvaluados,
        ];
    }

    /**
     * Evaluación intensificada para clientes PEP: verifica historial,
     * beneficiarios, y operaciones recientes.
     *
     * @param int    $fkSociete  ID del tercero
     * @param object $user       Usuario autenticado
     * @return array{alertas_generadas: int, nivel_riesgo: string}
     */
    public function seguimientoIntensificadoPEP(int $fkSociete, object $user): array
    {
        $alertasGeneradas = 0;
        $pepVerif = new PLDPepVerificacion($this->db);

        if (!$pepVerif->esClientePEP($fkSociete)) {
            return ['alertas_generadas' => 0, 'nivel_riesgo' => 'bajo'];
        }

        $beneficiariosPEP = $this->contarBeneficiariosPEP($fkSociete);
        $opsRecientes     = $this->contarOperacionesRecientes($fkSociete, 30);

        $factores = [];
        if ($beneficiariosPEP > 0) {
            $factores[] = "{$beneficiariosPEP} beneficiario(s) PEP";
        }
        if ($opsRecientes >= 3) {
            $factores[] = "{$opsRecientes} operaciones en 30 días (frecuencia elevada para PEP)";
        }

        $nivelRiesgo = !empty($factores) ? 'alto' : 'medio';

        if (!empty($factores)) {
            $alerta = new PLDAlerta($this->db);
            $alerta->fk_societe   = $fkSociete;
            $alerta->tipo_alerta  = 'pep_seguimiento';
            $alerta->nivel_riesgo = $nivelRiesgo;
            $alerta->titulo       = 'Seguimiento intensificado PEP — Cliente '.$fkSociete;
            $alerta->descripcion  = 'Factores detectados: '.implode('; ', $factores);
            $alerta->involucra_pep = 1;
            $alerta->requiere_analisis = 1;
            $alerta->estado = 'abierta';

            if ($alerta->create($user) > 0) {
                $alertasGeneradas++;
                $this->registrarLogMonitoreo(
                    tipo: 'pep',
                    fkSociete: $fkSociete,
                    fkAlerta: $alerta->id,
                    resultado: 'alerta',
                    nivelRiesgo: $nivelRiesgo,
                    detalle: implode('; ', $factores),
                    generoAlerta: true
                );
            }
        }

        return [
            'alertas_generadas' => $alertasGeneradas,
            'nivel_riesgo'      => $nivelRiesgo,
        ];
    }

    /**
     * Evaluación para clientes de alto riesgo: verifica múltiples
     * factores de riesgo y genera alertas si corresponde.
     *
     * @param int    $fkSociete  ID del tercero
     * @param object $user       Usuario autenticado
     * @return array{alertas_generadas: int, factores: string[]}
     */
    public function seguimientoAltoRiesgo(int $fkSociete, object $user): array
    {
        $factores        = [];
        $alertasGeneradas = 0;

        $perfil = $this->construirPerfil($fkSociete);
        if ($perfil) {
            if ($perfil->frecuencia_mensual >= 3.0) {
                $factores[] = 'frecuencia_mensual_elevada';
            }
            if ($perfil->monto_acumulado_periodo >= 500000) {
                $factores[] = 'monto_acumulado_alto_6m';
            }
        }

        $beneficiariosPEP = $this->contarBeneficiariosPEP($fkSociete);
        if ($beneficiariosPEP > 0) {
            $factores[] = 'beneficiarios_pep';
        }

        if (!empty($factores)) {
            $alerta = new PLDAlerta($this->db);
            $alerta->fk_societe   = $fkSociete;
            $alerta->tipo_alerta  = 'alto_riesgo';
            $alerta->nivel_riesgo = 'alto';
            $alerta->titulo       = 'Cliente de alto riesgo — ID '.$fkSociete;
            $alerta->descripcion  = 'Factores: '.implode(', ', $factores);
            $alerta->requiere_analisis = 1;

            if ($alerta->create($user) > 0) {
                $alertasGeneradas++;
                $this->registrarLogMonitoreo(
                    tipo: 'alto_riesgo',
                    fkSociete: $fkSociete,
                    fkAlerta: $alerta->id,
                    resultado: 'alerta',
                    nivelRiesgo: 'alto',
                    detalle: implode(', ', $factores),
                    generoAlerta: true
                );
            }
        }

        return [
            'alertas_generadas' => $alertasGeneradas,
            'factores'          => $factores,
        ];
    }

    /**
     * Guarda o actualiza el perfil transaccional en BD.
     */
    public function guardarPerfil(PLDPerfilTransaccional $perfil): int
    {
        $perfil->nivel_riesgo_perfil = $perfil->clasificarRiesgo();

        $sql  = "INSERT INTO ".MAIN_DB_PREFIX."pld_perfil_cliente (";
        $sql .= " entity, fk_societe, periodo_inicio, periodo_fin, num_operaciones_periodo,";
        $sql .= " promedio_monto, desviacion_monto, max_monto_historico, min_monto_historico,";
        $sql .= " monto_acumulado_periodo, frecuencia_mensual, nivel_riesgo_perfil,";
        $sql .= " factores_riesgo, es_pep, nivel_diligencia, fecha_ultima_evaluacion";
        $sql .= ") VALUES (";
        $sql .= " 1,";
        $sql .= " ".(int)$perfil->fk_societe.",";
        $sql .= " '".$this->db->escape($perfil->periodo_inicio)."',";
        $sql .= " '".$this->db->escape($perfil->periodo_fin)."',";
        $sql .= " ".(int)$perfil->num_operaciones_periodo.",";
        $sql .= " ".$perfil->promedio_monto.",";
        $sql .= " ".$perfil->desviacion_monto.",";
        $sql .= " ".$perfil->max_monto_historico.",";
        $sql .= " ".$perfil->min_monto_historico.",";
        $sql .= " ".$perfil->monto_acumulado_periodo.",";
        $sql .= " ".$perfil->frecuencia_mensual.",";
        $sql .= " '".$this->db->escape($perfil->nivel_riesgo_perfil)."',";
        $sql .= " '".$this->db->escape($perfil->factoresToJson())."',";
        $sql .= " ".(int)$perfil->es_pep.",";
        $sql .= " '".$this->db->escape($perfil->nivel_diligencia)."',";
        $sql .= " '".$this->db->escape($perfil->fecha_ultima_evaluacion)."'";
        $sql .= ") ON DUPLICATE KEY UPDATE";
        $sql .= " periodo_inicio = VALUES(periodo_inicio),";
        $sql .= " periodo_fin = VALUES(periodo_fin),";
        $sql .= " num_operaciones_periodo = VALUES(num_operaciones_periodo),";
        $sql .= " promedio_monto = VALUES(promedio_monto),";
        $sql .= " desviacion_monto = VALUES(desviacion_monto),";
        $sql .= " max_monto_historico = VALUES(max_monto_historico),";
        $sql .= " min_monto_historico = VALUES(min_monto_historico),";
        $sql .= " monto_acumulado_periodo = VALUES(monto_acumulado_periodo),";
        $sql .= " frecuencia_mensual = VALUES(frecuencia_mensual),";
        $sql .= " nivel_riesgo_perfil = VALUES(nivel_riesgo_perfil),";
        $sql .= " factores_riesgo = VALUES(factores_riesgo),";
        $sql .= " es_pep = VALUES(es_pep),";
        $sql .= " nivel_diligencia = VALUES(nivel_diligencia),";
        $sql .= " fecha_ultima_evaluacion = VALUES(fecha_ultima_evaluacion)";

        // Usar savepoint (parametro 1) para evitar que un error de sintaxis
        // (ej. ON DUPLICATE KEY en PostgreSQL) aborte la transaccion externa.
        $resql = $this->db->query($sql, 1);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            dol_syslog(__METHOD__.' BD error: '.$this->error, LOG_ERR);
            return -1;
        }

        return 1;
    }

    /**
     * Carga el perfil transaccional desde BD para un cliente.
     *
     * @param int $fkSociete  ID del tercero
     * @return PLDPerfilTransaccional|null
     */
    public function cargarPerfil(int $fkSociete): ?PLDPerfilTransaccional
    {
        $sql  = "SELECT * FROM ".MAIN_DB_PREFIX."pld_perfil_cliente";
        $sql .= " WHERE fk_societe = ".(int)$fkSociete;
        $sql .= " AND entity = 1";

        $resql = $this->db->query($sql);
        if (!$resql) {
            return null;
        }

        $row = $this->db->fetch_object($resql);
        $this->db->free($resql);

        if (!$row) {
            return null;
        }

        return PLDPerfilTransaccional::fromRow($row);
    }

    /**
     * Genera una alerta por operación fuera de perfil transaccional.
     */
    private function generarAlertaPerfil(PLDOperacion $operacion, PLDPerfilTransaccional $perfil, array $evaluacion, object $user): ?int
    {
        $alerta = new PLDAlerta($this->db);
        $alerta->fk_pld_operacion = (int)$operacion->id;
        $alerta->fk_societe       = (int)$operacion->fk_societe;
        $alerta->tipo_alerta      = 'fuera_perfil';
        $alerta->nivel_riesgo     = $perfil->nivel_riesgo_perfil;
        $alerta->titulo           = 'Operación fuera de perfil transaccional';
        $alerta->descripcion      = sprintf(
            'Operación %s por $%s — z-score: %.2f, variación: %.1f%%. Motivos: %s. Perfil cliente: promedio $%s, máx histórico $%s.',
            $operacion->folio_interno,
            number_format($operacion->getMontoBruto(), 2),
            $evaluacion['z_score'],
            $evaluacion['variacion_pct'],
            implode(', ', $evaluacion['motivos']),
            number_format($perfil->promedio_monto, 2),
            number_format($perfil->max_monto_historico, 2)
        );
        $alerta->involucra_pep    = $perfil->es_pep ? 1 : 0;
        $alerta->requiere_analisis = 1;
        $alerta->estado = 'abierta';

        $result = $alerta->create($user);
        return $result > 0 ? $result : null;
    }

    /**
     * Genera una alerta específica para cliente PEP.
     */
    private function generarAlertaPEP(int $fkSociete, PLDPerfilTransaccional $perfil, object $user): ?int
    {
        $alerta = new PLDAlerta($this->db);
        $alerta->fk_societe       = $fkSociete;
        $alerta->tipo_alerta      = 'pep_monitoreo';
        $alerta->nivel_riesgo     = 'alto';
        $alerta->titulo           = 'PEP con perfil de riesgo '.$perfil->nivel_riesgo_perfil;
        $alerta->descripcion      = 'Cliente PEP requiere seguimiento intensificado. Nivel de diligencia: '.$perfil->nivel_diligencia.'. Factores: '.$perfil->factoresToJson();
        $alerta->involucra_pep    = 1;
        $alerta->requiere_analisis = 1;

        $result = $alerta->create($user);
        return $result > 0 ? $result : null;
    }

    /**
     * Registra una entrada en el log de monitoreo.
     */
    private function registrarLogMonitoreo(
        string $tipo,
        int $fkSociete = 0,
        int $fkOperacion = 0,
        ?int $fkAlerta = null,
        string $resultado = 'ok',
        string $nivelRiesgo = 'bajo',
        string $detalle = '',
        float $zScore = 0.0,
        float $variacionPct = 0.0,
        bool $superaUmbralPerfil = false,
        bool $generoAlerta = false
    ): int {
        $now = dol_now();

        $sql  = "INSERT INTO ".MAIN_DB_PREFIX."pld_monitoreo_log (";
        $sql .= " entity, tipo_evaluacion, fk_societe, fk_pld_operacion, fk_pld_alerta,";
        $sql .= " resultado, nivel_riesgo_detectado, detalle,";
        $sql .= " z_score, variacion_porcentual, supera_umbral_perfil,";
        $sql .= " genero_alerta, datec";
        $sql .= ") VALUES (";
        $sql .= " 1,";
        $sql .= " '".$this->db->escape($tipo)."',";
        $sql .= " ".($fkSociete > 0 ? (int)$fkSociete : 'NULL').",";
        $sql .= " ".($fkOperacion > 0 ? (int)$fkOperacion : 'NULL').",";
        $sql .= " ".($fkAlerta ? (int)$fkAlerta : 'NULL').",";
        $sql .= " '".$this->db->escape($resultado)."',";
        $sql .= " ".($nivelRiesgo ? "'".$this->db->escape($nivelRiesgo)."'" : 'NULL').",";
        $sql .= " ".($detalle ? "'".$this->db->escape($detalle)."'" : 'NULL').",";
        $sql .= " ".$zScore.",";
        $sql .= " ".$variacionPct.",";
        $sql .= " ".(int)$superaUmbralPerfil.",";
        $sql .= " ".(int)$generoAlerta.",";
        $sql .= " '".$this->db->idate($now)."'";
        $sql .= ")";

        // Usar savepoint (parametro 1) para evitar que un error de tabla inexistente
        // en PostgreSQL aborte la transaccion externa.
        $resql = $this->db->query($sql, 1);
        if (!$resql) {
            $this->error = $this->db->lasterror();
            dol_syslog(__METHOD__.' BD error: '.$this->error, LOG_ERR);
            return -1;
        }

        return $this->db->last_insert_id(MAIN_DB_PREFIX.'pld_monitoreo_log');
    }

    /**
     * Obtiene todos los clientes activos con operaciones PLD.
     */
    private function getClientesActivos(): array
    {
        $sql  = "SELECT DISTINCT s.rowid FROM ".MAIN_DB_PREFIX."societe as s";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."pld_operacion as o ON o.fk_societe = s.rowid";
        $sql .= " WHERE s.entity IN (".getEntity('societe').")";
        $sql .= " ORDER BY s.rowid";

        $resql = $this->db->query($sql);
        if (!$resql) {
            return [];
        }

        $clientes = [];
        while ($obj = $this->db->fetch_object($resql)) {
            $clientes[] = $obj;
        }
        $this->db->free($resql);

        return $clientes;
    }

    private function contarBeneficiariosPEP(int $fkSociete): int
    {
        $sql  = "SELECT COUNT(rowid) as cnt FROM ".MAIN_DB_PREFIX."pld_beneficiario";
        $sql .= " WHERE fk_societe = ".(int)$fkSociete;
        $sql .= " AND es_pep = 1 AND activo = 1";

        $resql = $this->db->query($sql);
        if (!$resql) {
            return 0;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        return (int)($obj->cnt ?? 0);
    }

    private function contarOperacionesRecientes(int $fkSociete, int $dias): int
    {
        $fechaInicio = date('Y-m-d', strtotime("-{$dias} days"));

        $sql  = "SELECT COUNT(rowid) as cnt FROM ".MAIN_DB_PREFIX."pld_operacion";
        $sql .= " WHERE fk_societe = ".(int)$fkSociete;
        $sql .= " AND fecha_operacion >= '".$this->db->escape($fechaInicio)."'";
        $sql .= " AND estado != 'cancelada'";

        $resql = $this->db->query($sql);
        if (!$resql) {
            return 0;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        return (int)($obj->cnt ?? 0);
    }
}
