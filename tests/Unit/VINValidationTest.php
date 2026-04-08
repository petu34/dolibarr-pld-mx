<?php
declare(strict_types=1);

/**
 * @file        VINValidationTest.php
 * @module      CompliancePLD
 * @description Tests de validación de VIN y todos los validadores auxiliares de PLDValidator
 * @author      Agente Generador (Sisyphus/Claude Code)
 * @version     1.0.0
 * @date        2026-02-20
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 *
 * @license     GNU/GPL
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../htdocs/custom/modulecompliancepld/class/pldvalidator.class.php';

/**
 * Tests de validación de VIN y demás validadores auxiliares
 *
 * Cubre: VIN, CP, País, Monto, Fecha, MesReportado, ActividadEconomica,
 * Nombre, Denominacion, Telefono, Correo, REPUVE, Placas, CLABE,
 * TipoPersona, TipoVehiculo, AnioModelo, FormatearMonto, FormatearFecha.
 */
class VINValidationTest extends TestCase
{
	private PLDValidator $validator;

	protected function setUp(): void
	{
		$this->validator = new PLDValidator();
	}

	// ====================================
	// VIN (Vehicle Identification Number)
	// ====================================

	public function testVINValido17Caracteres(): void
	{
		$this->assertTrue(
			$this->validator->validarVIN('1HGBH41JXMN109186'),
			'VIN de 17 caracteres alfanuméricos debe ser aceptado'
		);
	}

	public function testVINValidoConGuionYGuionBajo(): void
	{
		$this->assertTrue(
			$this->validator->validarVIN('1HGBH41JX-N_09186'),
			'VIN con guión y guión bajo debe ser aceptado'
		);
	}

	public function testVINValidoMinusculasNormalizado(): void
	{
		$this->assertTrue(
			$this->validator->validarVIN('1hgbh41jxmn109186'),
			'VIN en minúsculas debe ser aceptado tras normalización'
		);
	}

	public function testVINInvalidoCorto(): void
	{
		$this->assertFalse(
			$this->validator->validarVIN('1HGBH41JX'),
			'VIN con menos de 17 caracteres debe ser rechazado'
		);
	}

	public function testVINInvalidoLargo(): void
	{
		$this->assertFalse(
			$this->validator->validarVIN('1HGBH41JXMN1091861'),
			'VIN con más de 17 caracteres debe ser rechazado'
		);
	}

	public function testVINInvalidoVacio(): void
	{
		$this->assertFalse(
			$this->validator->validarVIN(''),
			'VIN vacío debe ser rechazado'
		);
	}

	// ====================================
	// Código Postal (cp_type: 5 dígitos)
	// ====================================

	public function testCodigoPostalValido(): void
	{
		$this->assertTrue(
			$this->validator->validarCodigoPostal('06600'),
			'CP de 5 dígitos debe ser aceptado'
		);
	}

	public function testCodigoPostalValidoCeros(): void
	{
		$this->assertTrue(
			$this->validator->validarCodigoPostal('00100'),
			'CP con ceros al inicio debe ser aceptado'
		);
	}

	public function testCodigoPostalInvalido4Digitos(): void
	{
		$this->assertFalse(
			$this->validator->validarCodigoPostal('0660'),
			'CP con 4 dígitos debe ser rechazado'
		);
	}

	public function testCodigoPostalInvalidoLetras(): void
	{
		$this->assertFalse(
			$this->validator->validarCodigoPostal('066AB'),
			'CP con letras debe ser rechazado'
		);
	}

	// ====================================
	// País (pais_type: ISO alpha-2)
	// ====================================

	public function testPaisValido(): void
	{
		$this->assertTrue(
			$this->validator->validarPais('MX'),
			'Código de país MX debe ser aceptado'
		);
	}

	public function testPaisValidoMinusculas(): void
	{
		$this->assertTrue(
			$this->validator->validarPais('us'),
			'Código de país en minúsculas debe ser aceptado tras normalización'
		);
	}

	public function testPaisInvalidoTresLetras(): void
	{
		$this->assertFalse(
			$this->validator->validarPais('MEX'),
			'Código de país de 3 letras (alpha-3) debe ser rechazado'
		);
	}

	public function testPaisInvalidoNumeros(): void
	{
		$this->assertFalse(
			$this->validator->validarPais('12'),
			'Código de país con números debe ser rechazado'
		);
	}

	// ====================================
	// Monto (monto_type: \d{1,14}\.\d{2})
	// ====================================

	public function testMontoValido(): void
	{
		$this->assertTrue(
			$this->validator->validarMonto('250000.00'),
			'Monto con formato correcto debe ser aceptado'
		);
	}

	public function testMontoValidoMinimo(): void
	{
		$this->assertTrue(
			$this->validator->validarMonto('0.01'),
			'Monto mínimo 0.01 debe ser aceptado'
		);
	}

	public function testMontoValidoGrande(): void
	{
		$this->assertTrue(
			$this->validator->validarMonto('99999999999999.99'),
			'Monto máximo (14 enteros) debe ser aceptado'
		);
	}

	public function testMontoInvalidoSinDecimales(): void
	{
		$this->assertFalse(
			$this->validator->validarMonto('250000'),
			'Monto sin decimales debe ser rechazado'
		);
	}

	public function testMontoInvalidoUnDecimal(): void
	{
		$this->assertFalse(
			$this->validator->validarMonto('250000.0'),
			'Monto con un solo decimal debe ser rechazado'
		);
	}

	public function testMontoInvalidoTresDecimales(): void
	{
		$this->assertFalse(
			$this->validator->validarMonto('250000.001'),
			'Monto con tres decimales debe ser rechazado'
		);
	}

	// ====================================
	// Fecha (fecha_type: YYYYMMDD)
	// ====================================

	public function testFechaValida(): void
	{
		$this->assertTrue(
			$this->validator->validarFecha('20260115'),
			'Fecha válida YYYYMMDD debe ser aceptada'
		);
	}

	public function testFechaValidaBisiesto(): void
	{
		$this->assertTrue(
			$this->validator->validarFecha('20240229'),
			'29 de febrero en año bisiesto debe ser aceptada'
		);
	}

	public function testFechaInvalidaBisiestoEnAnioNoBisiesto(): void
	{
		$this->assertFalse(
			$this->validator->validarFecha('20250229'),
			'29 de febrero en año no bisiesto debe ser rechazada'
		);
	}

	public function testFechaInvalidaMes13(): void
	{
		$this->assertFalse(
			$this->validator->validarFecha('20261301'),
			'Mes 13 debe ser rechazado'
		);
	}

	public function testFechaInvalidaDia00(): void
	{
		$this->assertFalse(
			$this->validator->validarFecha('20260100'),
			'Día 00 debe ser rechazado'
		);
	}

	public function testFechaInvalidaFormato(): void
	{
		$this->assertFalse(
			$this->validator->validarFecha('2026-01-15'),
			'Fecha con guiones no es formato YYYYMMDD'
		);
	}

	// ====================================
	// Mes Reportado (YYYYMM)
	// ====================================

	public function testMesReportadoValido(): void
	{
		$this->assertTrue(
			$this->validator->validarMesReportado('202602'),
			'Mes reportado válido debe ser aceptado'
		);
	}

	public function testMesReportadoInvalidoMes13(): void
	{
		$this->assertFalse(
			$this->validator->validarMesReportado('202613'),
			'Mes reportado con mes 13 debe ser rechazado'
		);
	}

	public function testMesReportadoInvalidoMes00(): void
	{
		$this->assertFalse(
			$this->validator->validarMesReportado('202600'),
			'Mes reportado con mes 00 debe ser rechazado'
		);
	}

	// ====================================
	// Actividad Económica SCIAN (7 dígitos)
	// ====================================

	public function testActividadEconomicaValida(): void
	{
		$this->assertTrue(
			$this->validator->validarActividadEconomica('4681110'),
			'Actividad económica de 7 dígitos debe ser aceptada'
		);
	}

	public function testActividadEconomicaInvalida6Digitos(): void
	{
		$this->assertFalse(
			$this->validator->validarActividadEconomica('468111'),
			'Actividad económica de 6 dígitos debe ser rechazada'
		);
	}

	// ====================================
	// Nombre (nombre_type: A-ZÑ .,)
	// ====================================

	public function testNombreValido(): void
	{
		$this->assertTrue(
			$this->validator->validarNombre('JUAN CARLOS'),
			'Nombre en mayúsculas con espacio debe ser aceptado'
		);
	}

	public function testNombreValidoConEnie(): void
	{
		$this->assertTrue(
			$this->validator->validarNombre('MARIA MUÑOZ'),
			'Nombre con Ñ debe ser aceptado'
		);
	}

	public function testNombreValidoConPuntoYComa(): void
	{
		$this->assertTrue(
			$this->validator->validarNombre('SR. GARCIA, JOSE'),
			'Nombre con punto y coma debe ser aceptado'
		);
	}

	public function testNombreValidoMinusculasNormalizado(): void
	{
		$this->assertTrue(
			$this->validator->validarNombre('juan carlos'),
			'Nombre en minúsculas debe ser aceptado tras normalización'
		);
	}

	public function testNombreInvalidoVacio(): void
	{
		$this->assertFalse(
			$this->validator->validarNombre(''),
			'Nombre vacío debe ser rechazado'
		);
	}

	// ====================================
	// Denominación / Razón Social
	// ====================================

	public function testDenominacionValida(): void
	{
		$this->assertTrue(
			$this->validator->validarDenominacion('AUTOMOTRIZ DEL NORTE S.A. DE C.V.'),
			'Denominación social válida debe ser aceptada'
		);
	}

	public function testDenominacionValidaConAmpersand(): void
	{
		$this->assertTrue(
			$this->validator->validarDenominacion('GARCIA & ASOCIADOS'),
			'Denominación con & debe ser aceptada'
		);
	}

	public function testDenominacionInvalidaVacia(): void
	{
		$this->assertFalse(
			$this->validator->validarDenominacion(''),
			'Denominación vacía debe ser rechazada'
		);
	}

	// ====================================
	// Teléfono (10-12 dígitos)
	// ====================================

	public function testTelefonoValido10Digitos(): void
	{
		$this->assertTrue(
			$this->validator->validarTelefono('5512345678'),
			'Teléfono de 10 dígitos debe ser aceptado'
		);
	}

	public function testTelefonoValidoConFormato(): void
	{
		$this->assertTrue(
			$this->validator->validarTelefono('(55) 1234-5678'),
			'Teléfono con formato debe ser aceptado tras limpieza'
		);
	}

	public function testTelefonoInvalido9Digitos(): void
	{
		$this->assertFalse(
			$this->validator->validarTelefono('551234567'),
			'Teléfono de 9 dígitos debe ser rechazado'
		);
	}

	// ====================================
	// Correo Electrónico
	// ====================================

	public function testCorreoValido(): void
	{
		$this->assertTrue(
			$this->validator->validarCorreo('contacto@empresa.com.mx'),
			'Correo válido debe ser aceptado'
		);
	}

	public function testCorreoInvalidoSinArroba(): void
	{
		$this->assertFalse(
			$this->validator->validarCorreo('contactoempresa.com'),
			'Correo sin @ debe ser rechazado'
		);
	}

	public function testCorreoInvalidoMuyLargo(): void
	{
		$largo = str_repeat('a', 50) . '@ejemplo.com.mx';
		$this->assertFalse(
			$this->validator->validarCorreo($largo),
			'Correo de más de 60 caracteres debe ser rechazado'
		);
	}

	// ====================================
	// REPUVE (8 caracteres alfanuméricos)
	// ====================================

	public function testREPUVEValido(): void
	{
		$this->assertTrue(
			$this->validator->validarREPUVE('AB123456'),
			'REPUVE de 8 caracteres alfanuméricos debe ser aceptado'
		);
	}

	public function testREPUVEInvalidoCorto(): void
	{
		$this->assertFalse(
			$this->validator->validarREPUVE('AB1234'),
			'REPUVE con menos de 8 caracteres debe ser rechazado'
		);
	}

	// ====================================
	// Placas (1-12 caracteres)
	// ====================================

	public function testPlacasValidas(): void
	{
		$this->assertTrue(
			$this->validator->validarPlacas('ABC-123-D'),
			'Placas alfanuméricas con guión deben ser aceptadas'
		);
	}

	public function testPlacasInvalidasVacias(): void
	{
		$this->assertFalse(
			$this->validator->validarPlacas(''),
			'Placas vacías deben ser rechazadas'
		);
	}

	// ====================================
	// CLABE Interbancaria (18 dígitos)
	// ====================================

	public function testCLABEValida(): void
	{
		$this->assertTrue(
			$this->validator->validarCLABE('012345678901234567'),
			'CLABE de 18 dígitos debe ser aceptada'
		);
	}

	public function testCLABEInvalidaCorta(): void
	{
		$this->assertFalse(
			$this->validator->validarCLABE('01234567890'),
			'CLABE con menos de 18 dígitos debe ser rechazada'
		);
	}

	public function testCLABEInvalidaConLetras(): void
	{
		$this->assertFalse(
			$this->validator->validarCLABE('01234567890123456A'),
			'CLABE con letras debe ser rechazada'
		);
	}

	// ====================================
	// Tipo de Persona (PF, PM, FI)
	// ====================================

	public function testTipoPersonaFisicaValida(): void
	{
		$this->assertTrue(
			$this->validator->validarTipoPersona('PF'),
			'Tipo PF (Persona Física) debe ser aceptado'
		);
	}

	public function testTipoPersonaMoralValida(): void
	{
		$this->assertTrue(
			$this->validator->validarTipoPersona('PM'),
			'Tipo PM (Persona Moral) debe ser aceptado'
		);
	}

	public function testTipoPersonaFideicomisoValida(): void
	{
		$this->assertTrue(
			$this->validator->validarTipoPersona('FI'),
			'Tipo FI (Fideicomiso) debe ser aceptado'
		);
	}

	public function testTipoPersonaInvalido(): void
	{
		$this->assertFalse(
			$this->validator->validarTipoPersona('XX'),
			'Tipo de persona inválido debe ser rechazado'
		);
	}

	// ====================================
	// Tipo de Vehículo (T, M, A)
	// ====================================

	public function testTipoVehiculoTerrestreValido(): void
	{
		$this->assertTrue(
			$this->validator->validarTipoVehiculo('T'),
			'Tipo T (Terrestre) debe ser aceptado'
		);
	}

	public function testTipoVehiculoMaritimoValido(): void
	{
		$this->assertTrue(
			$this->validator->validarTipoVehiculo('M'),
			'Tipo M (Marítimo) debe ser aceptado'
		);
	}

	public function testTipoVehiculoAereoValido(): void
	{
		$this->assertTrue(
			$this->validator->validarTipoVehiculo('A'),
			'Tipo A (Aéreo) debe ser aceptado'
		);
	}

	public function testTipoVehiculoInvalido(): void
	{
		$this->assertFalse(
			$this->validator->validarTipoVehiculo('X'),
			'Tipo de vehículo inválido debe ser rechazado'
		);
	}

	// ====================================
	// Año Modelo (4 dígitos, 1900-actual+2)
	// ====================================

	public function testAnioModeloValido2026(): void
	{
		$this->assertTrue(
			$this->validator->validarAnioModelo('2026'),
			'Año 2026 debe ser aceptado'
		);
	}

	public function testAnioModeloValidoFuturo(): void
	{
		$anioFuturo = (string) ((int) date('Y') + 2);
		$this->assertTrue(
			$this->validator->validarAnioModelo($anioFuturo),
			'Año actual + 2 debe ser aceptado'
		);
	}

	public function testAnioModeloInvalido1899(): void
	{
		$this->assertFalse(
			$this->validator->validarAnioModelo('1899'),
			'Año 1899 (antes de 1900) debe ser rechazado'
		);
	}

	public function testAnioModeloInvalidoMuyFuturo(): void
	{
		$anioMuyFuturo = (string) ((int) date('Y') + 3);
		$this->assertFalse(
			$this->validator->validarAnioModelo($anioMuyFuturo),
			'Año actual + 3 debe ser rechazado'
		);
	}

	public function testAnioModeloInvalidoNoNumerico(): void
	{
		$this->assertFalse(
			$this->validator->validarAnioModelo('ABCD'),
			'Año no numérico debe ser rechazado'
		);
	}

	// ====================================
	// Referencia de Aviso (1-14 alfanuméricos)
	// ====================================

	public function testReferenciaAvisoValida(): void
	{
		$this->assertTrue(
			(bool) preg_match(PLDValidator::REGEX_REFERENCIA_AVISO, 'PLD26021500001'),
			'Referencia de aviso de 14 caracteres alfanuméricos debe ser aceptada'
		);
	}

	// ====================================
	// Folio Modificación (14 alfanuméricos exactos)
	// ====================================

	public function testFolioModificacionValido(): void
	{
		$this->assertTrue(
			(bool) preg_match(PLDValidator::REGEX_FOLIO_MODIFICACION, 'AB123456789012'),
			'Folio de 14 caracteres debe ser aceptado'
		);
	}

	public function testFolioModificacionInvalidoCorto(): void
	{
		$this->assertFalse(
			(bool) preg_match(PLDValidator::REGEX_FOLIO_MODIFICACION, 'AB12345'),
			'Folio de menos de 14 caracteres debe ser rechazado'
		);
	}

	// ====================================
	// FormatearMonto (utilidad)
	// ====================================

	public function testFormatearMontoEntero(): void
	{
		$this->assertSame(
			'250000.00',
			$this->validator->formatearMonto(250000),
			'Monto entero debe formatearse con 2 decimales'
		);
	}

	public function testFormatearMontoDecimal(): void
	{
		$this->assertSame(
			'377770.40',
			$this->validator->formatearMonto(377770.40),
			'Monto decimal debe mantener 2 decimales'
		);
	}

	public function testFormatearMontoInvalidoNoNumerico(): void
	{
		$this->assertSame(
			'',
			$this->validator->formatearMonto('abc'),
			'Monto no numérico debe retornar cadena vacía'
		);
	}

	public function testFormatearMontoNegativo(): void
	{
		$this->assertSame(
			'',
			$this->validator->formatearMonto(-100),
			'Monto negativo debe retornar cadena vacía'
		);
	}

	// ====================================
	// FormatearFecha (utilidad)
	// ====================================

	public function testFormatearFechaISO(): void
	{
		$resultado = $this->validator->formatearFecha('2026-02-20');
		$this->assertSame(
			'20260220',
			$resultado,
			'Fecha ISO debe convertirse a YYYYMMDD'
		);
	}

	public function testFormatearFechaInvalida(): void
	{
		$resultado = $this->validator->formatearFecha('no-es-fecha');
		$this->assertSame(
			'',
			$resultado,
			'Fecha inválida debe retornar cadena vacía'
		);
	}
}
