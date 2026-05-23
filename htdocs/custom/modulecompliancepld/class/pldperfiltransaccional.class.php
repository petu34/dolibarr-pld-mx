<?php
declare(strict_types=1);

/**
 * @file        class/pldperfiltransaccional.class.php
 * @module      CompliancePLD
 * @description Value Object para perfil transaccional del cliente — métricas
 *              estadísticas que modelan el comportamiento habitual de un cliente
 *              para detectar operaciones fuera de perfil.
 * @author      Sisyphus
 * @version     1.0.0
 * @date        2026-05-09
 * @compliance  LFPIORPI Art. 17, 45 Bis–45 Quinquies — PLD México
 *
 * @license     GNU/GPL
 */

if (!defined('DOL_VERSION')) {
    exit('Restricted access');
}

class PLDPerfilTransaccional
{
    public int $fk_societe;

    /** Fechas del periodo analizado */
    public string $periodo_inicio;
    public string $periodo_fin;

    /** Cantidad de operaciones en el periodo */
    public int $num_operaciones_periodo = 0;

    /** Métricas de monto (MXN) */
    public float $promedio_monto          = 0.0;
    public float $desviacion_monto        = 0.0;
    public float $max_monto_historico     = 0.0;
    public float $min_monto_historico     = 0.0;
    public float $monto_acumulado_periodo = 0.0;

    /** Promedio de operaciones por mes */
    public float $frecuencia_mensual = 0.0;

    /** Clasificación de riesgo */
    public string $nivel_riesgo_perfil = 'bajo';
    public array  $factores_riesgo     = [];

    /** PEP y diligencia */
    public bool   $es_pep           = false;
    public string $nivel_diligencia = 'normal';

    /** Última evaluación */
    public string $fecha_ultima_evaluacion;

    /**
     * @param int    $fk_societe       ID del tercero
     * @param string $periodo_inicio   Fecha inicio (Y-m-d)
     * @param string $periodo_fin      Fecha fin (Y-m-d)
     */
    public function __construct(int $fk_societe, string $periodo_inicio, string $periodo_fin)
    {
        $this->fk_societe     = $fk_societe;
        $this->periodo_inicio = $periodo_inicio;
        $this->periodo_fin    = $periodo_fin;
        $this->fecha_ultima_evaluacion = date('Y-m-d H:i:s');
    }

    /**
     * Calcula el z-score de un monto respecto al perfil.
     * z = (x - μ) / σ
     *
     * Un z-score > 2 indica operación anómala (fuera de 2 desviaciones estándar).
     *
     * @param float $monto  Monto a evaluar (MXN)
     * @return float  z-score; 0.0 si no hay historial suficiente
     */
    public function calcularZScore(float $monto): float
    {
        if ($this->desviacion_monto <= 0 || $this->num_operaciones_periodo < 3) {
            return 0.0;
        }

        return round(($monto - $this->promedio_monto) / $this->desviacion_monto, 2);
    }

    /**
     * Calcula la variación porcentual de un monto respecto al máximo histórico.
     *
     * @param float $monto  Monto a evaluar (MXN)
     * @return float  Porcentaje de variación; 0.0 si no hay historial
     */
    public function calcularVariacionHistorica(float $monto): float
    {
        if ($this->max_monto_historico <= 0) {
            return 0.0;
        }

        return round((($monto - $this->max_monto_historico) / $this->max_monto_historico) * 100, 1);
    }

    /**
     * Determina si un monto está fuera del perfil transaccional.
     *
     * Criterios:
     *   - z-score > 2.0 (2+ desviaciones estándar)
     *   - Incremento > 50% sobre máximo histórico
     *   - Sin historial previo y monto > $250,000
     *
     * @param float $monto  Monto a evaluar (MXN)
     * @return array{fuera_perfil: bool, z_score: float, variacion_pct: float, motivos: string[]}
     */
    public function evaluarOperacion(float $monto): array
    {
        $motivos      = [];
        $zScore       = $this->calcularZScore($monto);
        $variacionPct = $this->calcularVariacionHistorica($monto);

        if ($zScore > 2.0) {
            $motivos[] = 'z_score_elevado';
        }

        if ($variacionPct > 50.0) {
            $motivos[] = 'incremento_significativo';
        }

        if ($this->num_operaciones_periodo === 0 && $monto > 250000) {
            $motivos[] = 'sin_historial_monto_alto';
        }

        if ($this->num_operaciones_periodo >= 3 && $this->frecuencia_mensual >= 3.0) {
            $motivos[] = 'frecuencia_elevada';
        }

        return [
            'fuera_perfil'  => !empty($motivos),
            'z_score'       => $zScore,
            'variacion_pct' => $variacionPct,
            'motivos'       => $motivos,
        ];
    }

    /**
     * Clasifica el nivel de riesgo del perfil basado en los factores detectados.
     *
     * @return string  bajo|medio|alto|critico
     */
    public function clasificarRiesgo(): string
    {
        $factores = $this->factores_riesgo;

        if (in_array('pep_positivo', $factores, true) || in_array('sancion_uif', $factores, true)) {
            return 'critico';
        }
        if (in_array('fuera_perfil', $factores, true) || in_array('frecuencia_elevada', $factores, true)) {
            return 'alto';
        }
        if (in_array('domicilio_extranjero', $factores, true) || in_array('beneficiario_pep', $factores, true)) {
            return 'medio';
        }

        return 'bajo';
    }

    /**
     * Serializa los factores de riesgo a JSON para almacenamiento en BD.
     */
    public function factoresToJson(): string
    {
        return json_encode($this->factores_riesgo, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Hidrata factores de riesgo desde JSON almacenado en BD.
     */
    public function factoresFromJson(string $json): void
    {
        $decoded = json_decode($json, true);
        $this->factores_riesgo = is_array($decoded) ? $decoded : [];
    }

    /**
     * Carga el perfil desde un registro de BD (stdClass).
     *
     * @param stdClass $row  Fila de llx_pld_perfil_cliente
     * @return self
     */
    public static function fromRow(stdClass $row): self
    {
        $perfil = new self((int)$row->fk_societe, $row->periodo_inicio, $row->periodo_fin);
        $perfil->num_operaciones_periodo = (int)$row->num_operaciones_periodo;
        $perfil->promedio_monto          = (float)$row->promedio_monto;
        $perfil->desviacion_monto        = (float)$row->desviacion_monto;
        $perfil->max_monto_historico     = (float)$row->max_monto_historico;
        $perfil->min_monto_historico     = (float)$row->min_monto_historico;
        $perfil->monto_acumulado_periodo = (float)$row->monto_acumulado_periodo;
        $perfil->frecuencia_mensual      = (float)$row->frecuencia_mensual;
        $perfil->nivel_riesgo_perfil     = $row->nivel_riesgo_perfil;
        $perfil->factoresFromJson($row->factores_riesgo ?? '[]');
        $perfil->es_pep                   = (bool)$row->es_pep;
        $perfil->nivel_diligencia         = $row->nivel_diligencia;
        $perfil->fecha_ultima_evaluacion  = $row->fecha_ultima_evaluacion;

        return $perfil;
    }
}
