<?php
declare(strict_types=1);

/**
 * @file        RFCValidationTest.php
 * @module      CompliancePLD
 * @description Tests de validación de RFC persona física y moral conforme a ssprof2.xsd
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
 * Tests de validación de RFC (Registro Federal de Contribuyentes)
 *
 * Persona física: 13 caracteres
 * Persona moral: 12 caracteres
 * Ambos con validación de fecha real en la porción de fecha (ssprof2.xsd).
 */
class RFCValidationTest extends TestCase
{
	private PLDValidator $validator;

	protected function setUp(): void
	{
		$this->validator = new PLDValidator();
	}

	// ====================================
	// RFC Persona Física (13 caracteres)
	// ====================================

	/**
	 * RFC persona física válido — fecha 15-mar-1985
	 */
	public function testRFCFisicaValidoFechaMarzo(): void
	{
		$this->assertTrue(
			$this->validator->validarRFCFisica('GOGA850315ABC'),
			'RFC persona física válido debe ser aceptado'
		);
	}

	/**
	 * RFC persona física válido — fecha 01-ene-1988
	 */
	public function testRFCFisicaValidoFechaEnero(): void
	{
		$this->assertTrue(
			$this->validator->validarRFCFisica('ROMD880101XY9'),
			'RFC persona física válido con dígitos en homoclave debe ser aceptado'
		);
	}

	/**
	 * RFC persona física válido — contiene Ñ (permitido por SAT)
	 */
	public function testRFCFisicaValidoConEnie(): void
	{
		$this->assertTrue(
			$this->validator->validarRFCFisica('GOÑE850315AB1'),
			'RFC persona física con Ñ debe ser aceptado'
		);
	}

	/**
	 * RFC persona física válido — contiene & (permitido por SAT)
	 */
	public function testRFCFisicaValidoConAmpersand(): void
	{
		$this->assertTrue(
			$this->validator->validarRFCFisica('GO&A850315AB2'),
			'RFC persona física con & debe ser aceptado'
		);
	}

	/**
	 * RFC persona física válido — homoclave con dígitos
	 */
	public function testRFCFisicaValidoHomoclaveDigitos(): void
	{
		$this->assertTrue(
			$this->validator->validarRFCFisica('LOMA950630123'),
			'RFC persona física con homoclave numérica debe ser aceptado'
		);
	}

	/**
	 * RFC persona física válido — entrada en minúsculas (normalización)
	 */
	public function testRFCFisicaMinusculasAceptado(): void
	{
		$this->assertTrue(
			$this->validator->validarRFCFisica('goga850315abc'),
			'RFC en minúsculas debe ser aceptado tras normalización'
		);
	}

	/**
	 * RFC persona física inválido — longitud 12 (es de persona moral)
	 */
	public function testRFCFisicaInvalidoLongitud12(): void
	{
		$this->assertFalse(
			$this->validator->validarRFCFisica('ABC060101XY9'),
			'RFC de 12 caracteres no es persona física (es persona moral)'
		);
	}

	/**
	 * RFC persona física inválido — cadena vacía
	 */
	public function testRFCFisicaInvalidoVacio(): void
	{
		$this->assertFalse(
			$this->validator->validarRFCFisica(''),
			'RFC vacío debe ser rechazado'
		);
	}

	/**
	 * RFC persona física inválido — mes 13
	 */
	public function testRFCFisicaInvalidoMes13(): void
	{
		$this->assertFalse(
			$this->validator->validarRFCFisica('GOGA851315ABC'),
			'RFC con mes 13 debe ser rechazado'
		);
	}

	/**
	 * RFC persona física inválido — día 32
	 */
	public function testRFCFisicaInvalidoDia32(): void
	{
		$this->assertFalse(
			$this->validator->validarRFCFisica('GOGA850332ABC'),
			'RFC con día 32 debe ser rechazado'
		);
	}

	/**
	 * RFC persona física inválido — demasiado largo
	 */
	public function testRFCFisicaInvalidoLargo(): void
	{
		$this->assertFalse(
			$this->validator->validarRFCFisica('GOGA850315ABCX'),
			'RFC con 14 caracteres debe ser rechazado'
		);
	}

	// ====================================
	// RFC Persona Moral (12 caracteres)
	// ====================================

	/**
	 * RFC persona moral válido — 12 caracteres, fecha 01-ene-2006
	 */
	public function testRFCMoralValido(): void
	{
		$this->assertTrue(
			$this->validator->validarRFCMoral('ABC060101XY9'),
			'RFC persona moral válido debe ser aceptado'
		);
	}

	/**
	 * RFC persona moral válido — con Ñ
	 */
	public function testRFCMoralValidoConEnie(): void
	{
		$this->assertTrue(
			$this->validator->validarRFCMoral('AÑC060101AB1'),
			'RFC persona moral con Ñ debe ser aceptado'
		);
	}

	/**
	 * RFC persona moral válido — con &
	 */
	public function testRFCMoralValidoConAmpersand(): void
	{
		$this->assertTrue(
			$this->validator->validarRFCMoral('A&C060101AB2'),
			'RFC persona moral con & debe ser aceptado'
		);
	}

	/**
	 * RFC persona moral inválido — 13 caracteres (es de persona física)
	 */
	public function testRFCMoralInvalidoLongitud13(): void
	{
		$this->assertFalse(
			$this->validator->validarRFCMoral('GOGA850315ABC'),
			'RFC de 13 caracteres no es persona moral'
		);
	}

	/**
	 * RFC persona moral inválido — cadena vacía
	 */
	public function testRFCMoralInvalidoVacio(): void
	{
		$this->assertFalse(
			$this->validator->validarRFCMoral(''),
			'RFC moral vacío debe ser rechazado'
		);
	}

	// ====================================
	// Auto-detección (validarRFC genérico)
	// ====================================

	/**
	 * validarRFC detecta persona física (13 caracteres)
	 */
	public function testRFCAutodeteccionFisica(): void
	{
		$this->assertTrue(
			$this->validator->validarRFC('GOGA850315ABC'),
			'validarRFC debe detectar automáticamente RFC persona física (13 chars)'
		);
	}

	/**
	 * validarRFC detecta persona moral (12 caracteres)
	 */
	public function testRFCAutodeteccionMoral(): void
	{
		$this->assertTrue(
			$this->validator->validarRFC('ABC060101XY9'),
			'validarRFC debe detectar automáticamente RFC persona moral (12 chars)'
		);
	}

	/**
	 * validarRFC rechaza longitudes inválidas
	 */
	public function testRFCAutodeteccionLongitudInvalida(): void
	{
		$this->assertFalse(
			$this->validator->validarRFC('GOGA85031'),
			'validarRFC debe rechazar longitudes que no sean 12 ni 13'
		);
	}

	/**
	 * validarRFC rechaza cadena vacía
	 */
	public function testRFCAutodeteccionVacio(): void
	{
		$this->assertFalse(
			$this->validator->validarRFC(''),
			'validarRFC debe rechazar cadena vacía'
		);
	}
}
