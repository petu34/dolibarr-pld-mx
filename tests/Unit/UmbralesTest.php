<?php
declare(strict_types=1);

/**
 * @file        UmbralesTest.php
 * @module      CompliancePLD
 * @description Tests de umbrales regulatorios, alertas y verificación de expedientes PLD
 * @author      Agente Generador (Sisyphus/Claude Code)
 * @version     1.0.0
 * @date        2026-02-20
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 *
 * @license     GNU/GPL
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../htdocs/custom/modulecompliancepld/class/compliancepld.class.php';

/**
 * Tests de umbrales regulatorios y lógica de negocio PLD
 *
 * Verifica cálculos de umbral basados en UMAs, detección de operaciones
 * vulnerables, prioridad de avisos y verificación de expedientes.
 */
class UmbralesTest extends TestCase
{
	private CompliancePLD $pld;

	protected function setUp(): void
	{
		$this->pld = new CompliancePLD();
	}

	// ====================================
	// Constantes regulatorias
	// ====================================

	public function testConstanteUMA2026(): void
	{
		$this->assertSame(
			117.32,
			CompliancePLD::UMA_2026,
			'UMA 2026 debe ser 117.32 (INEGI/DOF)'
		);
	}

	public function testConstanteUmbralUMAsVehiculo(): void
	{
		$this->assertSame(
			3220,
			CompliancePLD::UMBRAL_UMAS_VEHICULO,
			'Umbral vehículos debe ser 3,220 UMAs (Art. 17 Fracc. VIII)'
		);
	}

	public function testConstanteUmbralUMAsEfectivo(): void
	{
		$this->assertSame(
			3100,
			CompliancePLD::UMBRAL_UMAS_EFECTIVO,
			'Umbral efectivo debe ser 3,100 UMAs'
		);
	}

	public function testConstanteUmbralAcumulado6M(): void
	{
		$this->assertSame(
			500000.00,
			CompliancePLD::UMBRAL_ACUMULADO_6M,
			'Umbral acumulado 6 meses debe ser $500,000 MXN'
		);
	}

	public function testConstanteClaveActividad(): void
	{
		$this->assertSame(
			'808',
			CompliancePLD::CLAVE_ACTIVIDAD_VEHICULOS,
			'Clave actividad vehículos debe ser 808 (catálogo SAT)'
		);
	}

	public function testConstantePeriodoConservacion(): void
	{
		$this->assertSame(
			5,
			CompliancePLD::PERIODO_CONSERVACION_ANIOS,
			'Período de conservación debe ser 5 años (Art. 18 LFPIORPI)'
		);
	}

	// ====================================
	// Cálculo de Umbrales
	// ====================================

	public function testCalcularUmbralAvisoDefault(): void
	{
		$umbral = $this->pld->calcularUmbralAviso();
		$esperado = round(3220 * 117.32, 2);
		$this->assertSame(
			$esperado,
			$umbral,
			'Umbral aviso con UMA default debe ser 3220 * 117.32 = ' . $esperado
		);
	}

	public function testCalcularUmbralAvisoCustomUMA(): void
	{
		$umbral = $this->pld->calcularUmbralAviso(120.00);
		$esperado = round(3220 * 120.00, 2);
		$this->assertSame(
			$esperado,
			$umbral,
			'Umbral aviso con UMA 120.00 debe ser 3220 * 120 = ' . $esperado
		);
	}

	public function testCalcularUmbralEfectivoDefault(): void
	{
		$umbral = $this->pld->calcularUmbralEfectivo();
		$esperado = round(3100 * 117.32, 2);
		$this->assertSame(
			$esperado,
			$umbral,
			'Umbral efectivo con UMA default debe ser 3100 * 117.32 = ' . $esperado
		);
	}

	public function testCalcularUmbralEfectivoCustomUMA(): void
	{
		$umbral = $this->pld->calcularUmbralEfectivo(100.00);
		$this->assertSame(
			310000.00,
			$umbral,
			'Umbral efectivo con UMA 100.00 debe ser 3100 * 100 = 310,000'
		);
	}

	// ====================================
	// debeGenerarAviso — Vehículos
	// ====================================

	public function testDebeGenerarAvisoVehiculoNuevoEnUmbral(): void
	{
		$umbral = $this->pld->calcularUmbralAviso();
		$this->assertTrue(
			$this->pld->debeGenerarAviso($umbral, 'vehiculo_nuevo'),
			'Monto exactamente en el umbral debe generar aviso'
		);
	}

	public function testDebeGenerarAvisoVehiculoNuevoSobreUmbral(): void
	{
		$umbral = $this->pld->calcularUmbralAviso();
		$this->assertTrue(
			$this->pld->debeGenerarAviso($umbral + 1, 'vehiculo_nuevo'),
			'Monto sobre el umbral debe generar aviso'
		);
	}

	public function testNoDebeGenerarAvisoVehiculoNuevoBajoUmbral(): void
	{
		$umbral = $this->pld->calcularUmbralAviso();
		$this->assertFalse(
			$this->pld->debeGenerarAviso($umbral - 0.01, 'vehiculo_nuevo'),
			'Monto bajo el umbral NO debe generar aviso'
		);
	}

	public function testDebeGenerarAvisoVehiculoUsado(): void
	{
		$umbral = $this->pld->calcularUmbralAviso();
		$this->assertTrue(
			$this->pld->debeGenerarAviso($umbral, 'vehiculo_usado'),
			'Vehículo usado al umbral debe generar aviso (mismo umbral que nuevo per XSD)'
		);
	}

	public function testDebeGenerarAvisoVehiculoGenerico(): void
	{
		$umbral = $this->pld->calcularUmbralAviso();
		$this->assertTrue(
			$this->pld->debeGenerarAviso($umbral, 'vehiculo'),
			'Tipo "vehiculo" genérico al umbral debe generar aviso'
		);
	}

	public function testNoDebeGenerarAvisoMontoZero(): void
	{
		$this->assertFalse(
			$this->pld->debeGenerarAviso(0, 'vehiculo_nuevo'),
			'Monto cero NO debe generar aviso'
		);
	}

	public function testNoDebeGenerarAvisoMontoNegativo(): void
	{
		$this->assertFalse(
			$this->pld->debeGenerarAviso(-100000, 'vehiculo_nuevo'),
			'Monto negativo NO debe generar aviso'
		);
	}

	public function testNoDebeGenerarAvisoTipoDesconocido(): void
	{
		$this->assertFalse(
			$this->pld->debeGenerarAviso(999999999, 'tipo_inexistente'),
			'Tipo de operación desconocido NO debe generar aviso'
		);
	}

	// ====================================
	// debeGenerarAviso — Acumulado 6 meses
	// ====================================

	public function testDebeGenerarAvisoAcumuladoEnUmbral(): void
	{
		$this->assertTrue(
			$this->pld->debeGenerarAviso(500000.00, 'acumulado_6m'),
			'Acumulado exactamente en $500,000 debe generar aviso'
		);
	}

	public function testNoDebeGenerarAvisoAcumuladoBajoUmbral(): void
	{
		$this->assertFalse(
			$this->pld->debeGenerarAviso(499999.99, 'acumulado_6m'),
			'Acumulado bajo $500,000 NO debe generar aviso'
		);
	}

	// ====================================
	// superaLimiteEfectivo
	// ====================================

	public function testSuperaLimiteEfectivoEnUmbral(): void
	{
		$umbral = $this->pld->calcularUmbralEfectivo();
		$this->assertTrue(
			$this->pld->superaLimiteEfectivo($umbral),
			'Efectivo exactamente en el umbral debe superar límite'
		);
	}

	public function testSuperaLimiteEfectivoSobreUmbral(): void
	{
		$umbral = $this->pld->calcularUmbralEfectivo();
		$this->assertTrue(
			$this->pld->superaLimiteEfectivo($umbral + 1),
			'Efectivo sobre el umbral debe superar límite'
		);
	}

	public function testNoSuperaLimiteEfectivoBajoUmbral(): void
	{
		$umbral = $this->pld->calcularUmbralEfectivo();
		$this->assertFalse(
			$this->pld->superaLimiteEfectivo($umbral - 0.01),
			'Efectivo bajo el umbral NO debe superar límite'
		);
	}

	public function testNoSuperaLimiteEfectivoZero(): void
	{
		$this->assertFalse(
			$this->pld->superaLimiteEfectivo(0),
			'Efectivo cero NO debe superar límite'
		);
	}

	public function testNoSuperaLimiteEfectivoNegativo(): void
	{
		$this->assertFalse(
			$this->pld->superaLimiteEfectivo(-50000),
			'Efectivo negativo NO debe superar límite'
		);
	}

	// ====================================
	// Prioridad del aviso
	// ====================================

	public function testPrioridadNormal(): void
	{
		$this->assertSame(
			'1',
			$this->pld->determinarPrioridad(false, false),
			'Sin alertas la prioridad debe ser 1 (normal)'
		);
	}

	public function testPrioridadInusual(): void
	{
		$this->assertSame(
			'2',
			$this->pld->determinarPrioridad(true, false),
			'Operación inusual debe tener prioridad 2 (prioritario)'
		);
	}

	public function testPrioridadPreocupante(): void
	{
		$this->assertSame(
			'2',
			$this->pld->determinarPrioridad(false, true),
			'Operación preocupante debe tener prioridad 2 (prioritario)'
		);
	}

	public function testPrioridadAmbas(): void
	{
		$this->assertSame(
			'2',
			$this->pld->determinarPrioridad(true, true),
			'Inusual + preocupante debe tener prioridad 2'
		);
	}

	// ====================================
	// Referencia del aviso
	// ====================================

	public function testGenerarReferenciaAvisoFormato(): void
	{
		$ref = $this->pld->generarReferenciaAviso(1);
		$this->assertMatchesRegularExpression(
			'/^PLD\d{6}\d{5}$/',
			$ref,
			'Referencia debe tener formato PLD + 6 dígitos fecha + 5 dígitos secuencial'
		);
	}

	public function testGenerarReferenciaAvisoLongitud(): void
	{
		$ref = $this->pld->generarReferenciaAviso(1);
		$this->assertSame(
			14,
			strlen($ref),
			'Referencia del aviso debe tener exactamente 14 caracteres'
		);
	}

	public function testGenerarReferenciaAvisoSecuencialMaximo(): void
	{
		$ref = $this->pld->generarReferenciaAviso(99999);
		$this->assertStringEndsWith(
			'99999',
			$ref,
			'Secuencial máximo 99999 debe mantenerse'
		);
	}

	public function testGenerarReferenciaAvisoSecuencialExcedido(): void
	{
		$ref = $this->pld->generarReferenciaAviso(100000);
		$this->assertStringEndsWith(
			'99999',
			$ref,
			'Secuencial mayor a 99999 debe truncarse a 99999'
		);
	}

	// ====================================
	// Mes reportado
	// ====================================

	public function testCalcularMesReportadoFormato(): void
	{
		$mes = $this->pld->calcularMesReportado();
		$this->assertMatchesRegularExpression(
			'/^\d{6}$/',
			$mes,
			'Mes reportado debe tener formato YYYYMM (6 dígitos)'
		);
	}

	public function testCalcularMesReportadoValorActual(): void
	{
		$esperado = date('Ym');
		$this->assertSame(
			$esperado,
			$this->pld->calcularMesReportado(),
			'Mes reportado debe corresponder al mes actual'
		);
	}

	// ====================================
	// Verificar Expediente Persona Física
	// ====================================

	public function testExpedientePFCompleto(): void
	{
		$datos = [
			'curp' => 'GOGA850315HDFNZR07',
			'rfc' => 'GOGA850315ABC',
			'nombre' => 'GONZALO',
			'apellido_paterno' => 'GONZALEZ',
			'fecha_nacimiento' => '19850315',
			'nacionalidad' => 'MX',
			'identificacion_tipo' => 'INE',
			'identificacion_numero' => 'IDMEX0001234567'
		];
		$resultado = $this->pld->verificarExpedientePersonaFisica($datos);
		$this->assertTrue(
			$resultado['completo'],
			'Expediente PF con todos los campos debe estar completo'
		);
		$this->assertEmpty(
			$resultado['faltantes'],
			'No debe haber campos faltantes'
		);
	}

	public function testExpedientePFFaltaCURP(): void
	{
		$datos = [
			'rfc' => 'GOGA850315ABC',
			'nombre' => 'GONZALO',
			'apellido_paterno' => 'GONZALEZ',
			'fecha_nacimiento' => '19850315',
			'nacionalidad' => 'MX',
			'identificacion_tipo' => 'INE',
			'identificacion_numero' => 'IDMEX0001234567'
		];
		$resultado = $this->pld->verificarExpedientePersonaFisica($datos);
		$this->assertFalse(
			$resultado['completo'],
			'Expediente PF sin CURP NO debe estar completo'
		);
		$this->assertContains(
			'curp',
			$resultado['faltantes'],
			'Campo curp debe aparecer en faltantes'
		);
	}

	public function testExpedientePFMultiplesFaltantes(): void
	{
		$datos = [
			'nombre' => 'GONZALO'
		];
		$resultado = $this->pld->verificarExpedientePersonaFisica($datos);
		$this->assertFalse(
			$resultado['completo'],
			'Expediente PF con múltiples faltantes NO debe estar completo'
		);
		$this->assertCount(
			7,
			$resultado['faltantes'],
			'Deben faltar 7 campos (de 8 requeridos)'
		);
	}

	// ====================================
	// Verificar Expediente Persona Moral
	// ====================================

	public function testExpedientePMCompleto(): void
	{
		$datos = [
			'rfc' => 'ABC060101XY9',
			'denominacion_razon' => 'AUTOMOTRIZ DEL NORTE S.A. DE C.V.',
			'fecha_constitucion' => '20060101',
			'nacionalidad' => 'MX',
			'actividad_economica' => '4681110'
		];
		$resultado = $this->pld->verificarExpedientePersonaMoral($datos);
		$this->assertTrue(
			$resultado['completo'],
			'Expediente PM con todos los campos debe estar completo'
		);
	}

	public function testExpedientePMIncompleto(): void
	{
		$datos = [
			'rfc' => 'ABC060101XY9'
		];
		$resultado = $this->pld->verificarExpedientePersonaMoral($datos);
		$this->assertFalse(
			$resultado['completo'],
			'Expediente PM con solo RFC NO debe estar completo'
		);
		$this->assertCount(
			4,
			$resultado['faltantes'],
			'Deben faltar 4 campos (de 5 requeridos)'
		);
	}

	// ====================================
	// Verificar Datos Vehículo
	// ====================================

	public function testDatosVehiculoTerrestreCompleto(): void
	{
		$datos = [
			'tipo_vehiculo' => 'T',
			'marca' => 'NISSAN',
			'modelo' => 'VERSA',
			'anio' => '2026',
			'nivel_blindaje' => '0',
			'vin' => '1HGBH41JXMN109186'
		];
		$resultado = $this->pld->verificarDatosVehiculo($datos);
		$this->assertTrue(
			$resultado['completo'],
			'Vehículo terrestre con VIN debe estar completo'
		);
	}

	public function testDatosVehiculoTerrestreSinVIN(): void
	{
		$datos = [
			'tipo_vehiculo' => 'T',
			'marca' => 'NISSAN',
			'modelo' => 'VERSA',
			'anio' => '2026',
			'nivel_blindaje' => '0'
		];
		$resultado = $this->pld->verificarDatosVehiculo($datos);
		$this->assertFalse(
			$resultado['completo'],
			'Vehículo terrestre sin VIN NO debe estar completo'
		);
		$this->assertContains(
			'vin',
			$resultado['faltantes'],
			'VIN debe aparecer en faltantes para terrestres'
		);
	}

	public function testDatosVehiculoMaritimoCompleto(): void
	{
		$datos = [
			'tipo_vehiculo' => 'M',
			'marca' => 'BAYLINER',
			'modelo' => 'VR5',
			'anio' => '2025',
			'nivel_blindaje' => '0',
			'numero_serie' => 'BYNL12345678'
		];
		$resultado = $this->pld->verificarDatosVehiculo($datos);
		$this->assertTrue(
			$resultado['completo'],
			'Vehículo marítimo con número de serie debe estar completo'
		);
	}

	public function testDatosVehiculoAereoSinSerie(): void
	{
		$datos = [
			'tipo_vehiculo' => 'A',
			'marca' => 'CESSNA',
			'modelo' => '172',
			'anio' => '2020',
			'nivel_blindaje' => '0'
		];
		$resultado = $this->pld->verificarDatosVehiculo($datos);
		$this->assertFalse(
			$resultado['completo'],
			'Vehículo aéreo sin número de serie NO debe estar completo'
		);
		$this->assertContains(
			'numero_serie',
			$resultado['faltantes'],
			'numero_serie debe aparecer en faltantes para aéreos'
		);
	}
}
