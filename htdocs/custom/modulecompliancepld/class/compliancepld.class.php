<?php
/**
 * @file        compliancepld.class.php
 * @module      CompliancePLD
 * @description Lógica de negocio PLD: umbrales, alertas y control de operaciones vulnerables
 * @author      Agente Generador (Sisyphus/Claude Code)
 * @version     1.0.0
 * @date        2026-02-20
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 *
 * @license     GNU/GPL
 */

if (!defined('DOL_VERSION')) {
	if (!defined('PHPUNIT_RUN')) {
		exit('Restricted access');
	}
}

/**
 * Clase principal de lógica PLD
 *
 * Gestiona umbrales regulatorios, detección de operaciones vulnerables
 * y generación de alertas conforme a LFPIORPI Art. 17 Fracc. VIII.
 */
class CompliancePLD
{
	/**
	 * Valor UMA 2026 en pesos mexicanos.
	 * Fuente: INEGI, Diario Oficial de la Federación.
	 * Se actualiza anualmente. Configurable vía llx_pld_configuracion en Fase 2.
	 */
	const UMA_2026 = 117.32;

	/** Umbral identificación/aviso vehículos: 3,220 UMAs (Art. 17 Fracc. VIII LFPIORPI) */
	const UMBRAL_UMAS_VEHICULO = 3220;

	/** Umbral restricción efectivo: 3,100 UMAs */
	const UMBRAL_UMAS_EFECTIVO = 3100;

	/** Umbral acumulado 6 meses mismo cliente */
	const UMBRAL_ACUMULADO_6M = 500000.00;

	/** Clave actividad vulnerable para vehículos (catálogo SAT) */
	const CLAVE_ACTIVIDAD_VEHICULOS = '808';

	/** Período de conservación de datos en años (Art. 18 LFPIORPI) */
	const PERIODO_CONSERVACION_ANIOS = 5;

	/**
	 * Calcula el umbral de aviso en pesos MXN para vehículos.
	 *
	 * @param float $valorUma Valor UMA vigente (default: UMA_2026)
	 * @return float Umbral en pesos MXN
	 */
	public function calcularUmbralAviso(float $valorUma = 0): float
	{
		if ($valorUma <= 0) {
			$valorUma = self::UMA_2026;
		}
		return round(self::UMBRAL_UMAS_VEHICULO * $valorUma, 2);
	}

	/**
	 * Calcula el umbral de restricción de efectivo en pesos MXN.
	 *
	 * @param float $valorUma Valor UMA vigente
	 * @return float Umbral de efectivo en pesos MXN
	 */
	public function calcularUmbralEfectivo(float $valorUma = 0): float
	{
		if ($valorUma <= 0) {
			$valorUma = self::UMA_2026;
		}
		return round(self::UMBRAL_UMAS_EFECTIVO * $valorUma, 2);
	}

	/**
	 * Determina si una operación debe generar aviso al SAT.
	 *
	 * Evalúa el monto contra los umbrales definidos en LFPIORPI.
	 * Para vehículos, el umbral es 3,220 UMAs independientemente
	 * de si es nuevo o usado (la distinción anterior se corrigió
	 * al alinear con los XSD del SAT).
	 *
	 * @param float  $monto          Monto de la operación en MXN
	 * @param string $tipoOperacion  Tipo: 'vehiculo_nuevo', 'vehiculo_usado', 'acumulado_6m'
	 * @param float  $valorUma       Valor UMA vigente
	 * @return bool true si debe generar aviso
	 */
	public function debeGenerarAviso(float $monto, string $tipoOperacion, float $valorUma = 0): bool
	{
		if ($monto <= 0) {
			return false;
		}

		switch ($tipoOperacion) {
			case 'vehiculo_nuevo':
			case 'vehiculo_usado':
			case 'vehiculo':
				return $monto >= $this->calcularUmbralAviso($valorUma);

			case 'acumulado_6m':
				return $monto >= self::UMBRAL_ACUMULADO_6M;

			default:
				return false;
		}
	}

	/**
	 * Determina si el pago en efectivo supera el límite regulatorio.
	 *
	 * @param float $montoEfectivo Monto pagado en efectivo
	 * @param float $valorUma      Valor UMA vigente
	 * @return bool true si supera el límite de efectivo
	 */
	public function superaLimiteEfectivo(float $montoEfectivo, float $valorUma = 0): bool
	{
		if ($montoEfectivo <= 0) {
			return false;
		}
		return $montoEfectivo >= $this->calcularUmbralEfectivo($valorUma);
	}

	/**
	 * Determina la prioridad del aviso según la naturaleza de la operación.
	 *
	 * @param bool $esInusual     La operación se considera inusual
	 * @param bool $esPreocupante La operación se considera preocupante
	 * @return string '1' = normal, '2' = prioritario (24 horas)
	 */
	public function determinarPrioridad(bool $esInusual, bool $esPreocupante): string
	{
		if ($esPreocupante || $esInusual) {
			return '2';
		}
		return '1';
	}

	/**
	 * Genera una referencia de aviso única para el SAT.
	 * Formato: AAVVMMDDNNNNN (año + mes + día + secuencial 5 dígitos)
	 *
	 * @param int $secuencial Número secuencial del aviso en el período
	 * @return string Referencia de aviso (máx 14 caracteres alfanuméricos)
	 */
	public function generarReferenciaAviso(int $secuencial): string
	{
		$fecha = date('ymd');
		$seq = str_pad((string) min($secuencial, 99999), 5, '0', STR_PAD_LEFT);
		return 'PLD' . $fecha . $seq;
	}

	/**
	 * Calcula el mes reportado actual en formato YYYYMM.
	 *
	 * @return string Mes reportado
	 */
	public function calcularMesReportado(): string
	{
		return date('Ym');
	}

	/**
	 * Verifica si los datos de identificación de un cliente están completos
	 * según los requisitos mínimos LFPIORPI para persona física.
	 *
	 * @param array $datos Array con claves: curp, rfc, nombre, apellido_paterno,
	 *                     fecha_nacimiento, nacionalidad, identificacion_tipo,
	 *                     identificacion_numero
	 * @return array ['completo' => bool, 'faltantes' => string[]]
	 */
	public function verificarExpedientePersonaFisica(array $datos): array
	{
		$campos_requeridos = [
			'curp', 'rfc', 'nombre', 'apellido_paterno',
			'fecha_nacimiento', 'nacionalidad',
			'identificacion_tipo', 'identificacion_numero'
		];

		$faltantes = [];
		foreach ($campos_requeridos as $campo) {
			if (empty($datos[$campo])) {
				$faltantes[] = $campo;
			}
		}

		return [
			'completo' => empty($faltantes),
			'faltantes' => $faltantes
		];
	}

	/**
	 * Verifica datos de identificación de persona moral.
	 *
	 * @param array $datos Array con claves: rfc, denominacion_razon,
	 *                     fecha_constitucion, nacionalidad, actividad_economica
	 * @return array ['completo' => bool, 'faltantes' => string[]]
	 */
	public function verificarExpedientePersonaMoral(array $datos): array
	{
		$campos_requeridos = [
			'rfc', 'denominacion_razon', 'fecha_constitucion',
			'nacionalidad', 'actividad_economica'
		];

		$faltantes = [];
		foreach ($campos_requeridos as $campo) {
			if (empty($datos[$campo])) {
				$faltantes[] = $campo;
			}
		}

		return [
			'completo' => empty($faltantes),
			'faltantes' => $faltantes
		];
	}

	/**
	 * Verifica datos mínimos de un vehículo para PLD.
	 *
	 * @param array $datos Array con claves: tipo_vehiculo, marca, modelo,
	 *                     anio, vin (si terrestre), nivel_blindaje
	 * @return array ['completo' => bool, 'faltantes' => string[]]
	 */
	public function verificarDatosVehiculo(array $datos): array
	{
		$campos_requeridos = [
			'tipo_vehiculo', 'marca', 'modelo', 'anio', 'nivel_blindaje'
		];

		$tipoVehiculo = $datos['tipo_vehiculo'] ?? '';
		if (strtoupper($tipoVehiculo) === 'T') {
			$campos_requeridos[] = 'vin';
		} else {
			$campos_requeridos[] = 'numero_serie';
		}

		$faltantes = [];
		foreach ($campos_requeridos as $campo) {
			if (!isset($datos[$campo]) || $datos[$campo] === '' || $datos[$campo] === null) {
				$faltantes[] = $campo;
			}
		}

		return [
			'completo' => empty($faltantes),
			'faltantes' => $faltantes
		];
	}
}
