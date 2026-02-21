<?php
declare(strict_types=1);

/**
 * @file        CURPValidationTest.php
 * @module      CompliancePLD
 * @description Tests de validación de CURP conforme a ssprof2.xsd (regex más estricto ADR-002)
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
 * Tests de validación de CURP
 *
 * Verifica que el validador acepta CURP válidas y rechaza inválidas
 * según el patrón curp_type de ssprof2.xsd (el más estricto de los 3 XSD).
 */
class CURPValidationTest extends TestCase
{
	private PLDValidator $validator;

	protected function setUp(): void
	{
		$this->validator = new PLDValidator();
	}

	/**
	 * CURP válida — sexo masculino, estado DF, fecha 15-mar-1985
	 */
	public function testCURPValidaMasculinoDF(): void
	{
		$this->assertTrue(
			$this->validator->validarCURP('GOGA850315HDFNZR07'),
			'CURP válida masculina DF debe ser aceptada'
		);
	}

	/**
	 * CURP válida — sexo femenino, estado MC (Michoacán), fecha 01-ene-1988
	 */
	public function testCURPValidaFemeninaMC(): void
	{
		$this->assertTrue(
			$this->validator->validarCURP('ROMD880101MMCSLR09'),
			'CURP válida femenina MC debe ser aceptada'
		);
	}

	/**
	 * CURP válida — fecha febrero (mes corto), estado DF
	 */
	public function testCURPValidaFebrero28(): void
	{
		$this->assertTrue(
			$this->validator->validarCURP('HEPE010228HDFRRR01'),
			'CURP válida con fecha 28 de febrero debe ser aceptada'
		);
	}

	/**
	 * CURP válida — estado NE (Nacido en el Extranjero)
	 */
	public function testCURPValidaNacidoExtranjero(): void
	{
		$this->assertTrue(
			$this->validator->validarCURP('LOMA950630HNEPRR05'),
			'CURP válida nacido en el extranjero debe ser aceptada'
		);
	}

	/**
	 * CURP válida — estado BS (Baja California Sur), década 2000
	 */
	public function testCURPValidaDecada2000(): void
	{
		$this->assertTrue(
			$this->validator->validarCURP('PEGA001215MBSRRR08'),
			'CURP válida de la década 2000 debe ser aceptada'
		);
	}

	/**
	 * CURP válida — entrada en minúsculas debe aceptarse (strtoupper interno)
	 */
	public function testCURPValidaMinusculasAceptada(): void
	{
		$this->assertTrue(
			$this->validator->validarCURP('goga850315hdfnzr07'),
			'CURP válida en minúsculas debe ser aceptada tras normalización'
		);
	}

	/**
	 * CURP inválida — cadena vacía
	 */
	public function testCURPInvalidaVacia(): void
	{
		$this->assertFalse(
			$this->validator->validarCURP(''),
			'CURP vacía debe ser rechazada'
		);
	}

	/**
	 * CURP inválida — demasiado corta (menos de 18 caracteres)
	 */
	public function testCURPInvalidaCorta(): void
	{
		$this->assertFalse(
			$this->validator->validarCURP('GOGA850315'),
			'CURP demasiado corta debe ser rechazada'
		);
	}

	/**
	 * CURP inválida — demasiado larga (más de 18 caracteres)
	 */
	public function testCURPInvalidaLarga(): void
	{
		$this->assertFalse(
			$this->validator->validarCURP('GOGA850315HDFNZR07X'),
			'CURP demasiado larga debe ser rechazada'
		);
	}

	/**
	 * CURP inválida — código de sexo incorrecto (ni M ni H)
	 */
	public function testCURPInvalidaSexoIncorrecto(): void
	{
		$this->assertFalse(
			$this->validator->validarCURP('GOGA850315XDFNZR07'),
			'CURP con sexo X (no M ni H) debe ser rechazada'
		);
	}

	/**
	 * CURP con código de entidad no estándar — el regex XSD acepta [A-Z]{5}
	 * sin enumerar entidades, por lo que 'ZZ' pasa la validación de formato.
	 * La validación semántica de entidad se hará en una capa superior.
	 */
	public function testCURPEstadoNoEstandarAceptadaPorRegex(): void
	{
		$this->assertTrue(
			$this->validator->validarCURP('GOGA850315HZZNZR07'),
			'CURP con entidad ZZ pasa validación de formato (regex XSD no enumera entidades)'
		);
	}

	/**
	 * CURP inválida — mes 13 (no existe)
	 */
	public function testCURPInvalidaMes13(): void
	{
		$this->assertFalse(
			$this->validator->validarCURP('GOGA851315HDFNZR07'),
			'CURP con mes 13 debe ser rechazada'
		);
	}

	/**
	 * CURP inválida — día 32 (no existe en ningún mes)
	 */
	public function testCURPInvalidaDia32(): void
	{
		$this->assertFalse(
			$this->validator->validarCURP('GOGA850332HDFNZR07'),
			'CURP con día 32 debe ser rechazada'
		);
	}

	/**
	 * CURP inválida — caracteres numéricos al inicio
	 */
	public function testCURPInvalidaNumerosAlInicio(): void
	{
		$this->assertFalse(
			$this->validator->validarCURP('1234850315HDFNZR07'),
			'CURP con dígitos al inicio debe ser rechazada'
		);
	}

	/**
	 * CURP inválida — día 31 en mes de 30 días (abril)
	 */
	public function testCURPInvalidaDia31EnAbril(): void
	{
		$this->assertFalse(
			$this->validator->validarCURP('GOGA850431HDFNZR07'),
			'CURP con día 31 en abril debe ser rechazada'
		);
	}

	/**
	 * CURP inválida — día 30 en febrero
	 */
	public function testCURPInvalidaDia30EnFebrero(): void
	{
		$this->assertFalse(
			$this->validator->validarCURP('GOGA850230HDFNZR07'),
			'CURP con día 30 en febrero debe ser rechazada'
		);
	}
}
