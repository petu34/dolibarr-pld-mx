<?php
/**
 * @file        pldvalidator.class.php
 * @module      CompliancePLD
 * @description Validador de datos PLD conforme a esquemas XSD del SAT (veh, inmu, ssprof2)
 * @author      Agente Generador (Sisyphus/Claude Code)
 * @version     1.0.0
 * @date        2026-02-20
 * @compliance  LFPIORPI Art. 17 — PLD México
 *
 * Usa la variante más estricta de regex (SPR/ssprof2.xsd) por compatibilidad
 * futura con múltiples actividades vulnerables (ADR-002).
 *
 * @license     GNU/GPL
 */

if (!defined('DOL_VERSION')) {
	// Permitir ejecución desde PHPUnit sin Dolibarr
	if (!defined('PHPUNIT_RUN')) {
		exit('Restricted access');
	}
}

/**
 * Clase validadora de datos PLD
 *
 * Centraliza toda la lógica de validación de campos requeridos por LFPIORPI
 * y los esquemas XSD del SAT para el portal SPPLD.
 */
class PLDValidator
{
	/**
	 * CURP: 18 caracteres. Valida fecha real y entidad federativa.
	 * Fuente: ssprof2.xsd curp_type
	 */
	const REGEX_CURP = '/^([A-Z]{4})((\d{2})(((0[469]|1[1])(0[1-9]|[12]\d|3[0]))|((0[2])(0[1-9]|[12]\d))|((0[13578]|1[02])(0[1-9]|[12]\d|3[01]))))([MH])([A-Z]{5})([A-J\d][\d])$/';

	/**
	 * RFC persona física: 13 caracteres. Incluye Ñ y & como acepta el SAT.
	 * Fuente: ssprof2.xsd rfc_fisica_type
	 */
	const REGEX_RFC_FISICA = '/^[A-ZÑ&]{4}((\d{2})(((0[469]|1[1])(0[1-9]|[12]\d|3[0]))|((0[2])(0[1-9]|[12]\d))|((0[13578]|1[02])(0[1-9]|[12]\d|3[01]))))[A-Z\d]{3}$/u';

	/**
	 * RFC persona moral: 12 caracteres. Incluye Ñ y &.
	 * Fuente: ssprof2.xsd rfc_moral_type
	 */
	const REGEX_RFC_MORAL = '/^[A-ZÑ&]{3}((\d{2})(((0[469]|1[1])(0[1-9]|[12]\d|3[0]))|((0[2])(0[1-9]|[12]\d))|((0[13578]|1[02])(0[1-9]|[12]\d|3[01]))))[A-Z\d]{3}$/u';

	/**
	 * VIN: exactamente 17 caracteres alfanuméricos.
	 * Fuente: veh.xsd referencia_17_type
	 */
	const REGEX_VIN = '/^[A-Z\d\-_]{17}$/';

	/**
	 * Código postal: exactamente 5 dígitos.
	 * Fuente: veh.xsd / inmu.xsd / ssprof2.xsd cp_type
	 */
	const REGEX_CP = '/^\d{5}$/';

	/**
	 * País: ISO alpha-2, 2 letras mayúsculas.
	 * Fuente: los 3 XSD pais_type
	 */
	const REGEX_PAIS = '/^[A-Z]{2}$/';

	/**
	 * Monto: hasta 14 dígitos enteros + 2 decimales obligatorios.
	 * Fuente: los 3 XSD monto_type
	 */
	const REGEX_MONTO = '/^\d{1,14}\.\d{2}$/';

	/**
	 * Fecha: formato YYYYMMDD con validación de fecha real.
	 * Fuente: inmu.xsd / ssprof2.xsd fecha_type (más estricto que veh.xsd)
	 */
	const REGEX_FECHA = '/^([1-9]\d{3})(((0[469]|1[1])(0[1-9]|[12]\d|3[0]))|((0[2])(0[1-9]|[12]\d))|((0[13578]|1[02])(0[1-9]|[12]\d|3[01])))$/';

	/**
	 * Mes reportado: YYYYMM
	 * Fuente: los 3 XSD mes_reportado_type
	 */
	const REGEX_MES_REPORTADO = '/^([2-9]\d{3})((0[1-9])|(1[0-2]))$/';

	/**
	 * Actividad económica / giro mercantil: 7 dígitos SCIAN.
	 * Fuente: los 3 XSD digito_7_type
	 */
	const REGEX_ACTIVIDAD_ECONOMICA = '/^\d{7}$/';

	/**
	 * Nombre: letras mayúsculas, Ñ, espacios, puntos y comas. 1-200 caracteres.
	 * Fuente: inmu.xsd nombre_type (más permisivo, acepta .,)
	 */
	const REGEX_NOMBRE = '/^[A-ZÑ .,]{1,200}$/u';

	/**
	 * Denominación o razón social: 1-254 caracteres alfanuméricos expandidos.
	 * Fuente: ssprof2.xsd denominacion_razon_type
	 */
	const REGEX_DENOMINACION = '/^[A-ZÑ&0-9 .,\-\/()]{1,254}$/u';

	/**
	 * Referencia de aviso: 1-14 caracteres alfanuméricos.
	 * Fuente: los 3 XSD referencia_aviso_type
	 */
	const REGEX_REFERENCIA_AVISO = '/^[A-Z\d]{1,14}$/';

	/**
	 * Folio de modificación: exactamente 14 caracteres alfanuméricos.
	 * Fuente: los 3 XSD folio_modificacion_type
	 */
	const REGEX_FOLIO_MODIFICACION = '/^[A-Z\d]{14}$/';

	/**
	 * Teléfono: 10-12 dígitos.
	 * Fuente: los 3 XSD numero_telefono_type
	 */
	const REGEX_TELEFONO = '/^\d{10,12}$/';

	/**
	 * Correo electrónico: formato SAT (simplificado).
	 * Fuente: los 3 XSD correo_electronico_type
	 */
	const REGEX_CORREO = '/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,60}$/';

	/**
	 * REPUVE: exactamente 8 caracteres alfanuméricos.
	 * Fuente: veh.xsd repuve_8_type
	 */
	const REGEX_REPUVE = '/^[A-Z\d]{8}$/';

	/**
	 * Placas: 1-12 caracteres alfanuméricos con guión.
	 * Fuente: veh.xsd placas_1-12_type
	 */
	const REGEX_PLACAS = '/^[A-Z\d\-]{1,12}$/';

	/**
	 * CLABE interbancaria: exactamente 18 dígitos.
	 */
	const REGEX_CLABE = '/^\d{18}$/';


	/** Tipos de persona según LFPIORPI */
	const TIPO_PERSONA_FISICA = 'PF';
	const TIPO_PERSONA_MORAL = 'PM';
	const TIPO_FIDEICOMISO = 'FI';

	/** Tipos de vehículo según veh.xsd */
	const TIPO_VEHICULO_TERRESTRE = 'T';
	const TIPO_VEHICULO_MARITIMO = 'M';
	const TIPO_VEHICULO_AEREO = 'A';

	/** Formas de pago según catálogo SAT */
	const FORMA_PAGO_EFECTIVO = '1';
	const FORMA_PAGO_CHEQUE = '2';
	const FORMA_PAGO_TRANSFERENCIA = '3';
	const FORMA_PAGO_TARJETA_CREDITO = '4';
	const FORMA_PAGO_TARJETA_DEBITO = '5';
	const FORMA_PAGO_MONEDERO = '6';
	const FORMA_PAGO_OTROS = '7';

	/** Tipos de alerta según catálogo SAT */
	const ALERTA_INTERNA = 'INT';
	const ALERTA_LISTA_PERSONAS = 'LPB';
	const ALERTA_INUSUAL = 'INU';
	const ALERTA_PREOCUPANTE = 'PRE';

	/** Entidades federativas válidas para CURP */
	const ENTIDADES_FEDERATIVAS = [
		'AS', 'BC', 'BS', 'CC', 'CS', 'CH', 'DF', 'DG',
		'GT', 'GR', 'HG', 'JC', 'MC', 'MN', 'MS', 'NT',
		'NL', 'OC', 'PL', 'QT', 'QR', 'SP', 'SL', 'SR',
		'TC', 'TL', 'TS', 'VZ', 'YN', 'ZS', 'NE'
	];


	/**
	 * Valida una CURP mexicana
	 *
	 * @param string $curp CURP a validar
	 * @return bool true si la CURP es válida
	 */
	public function validarCURP(string $curp): bool
	{
		$curp = strtoupper(trim($curp));
		if (strlen($curp) !== 18) {
			return false;
		}
		return (bool) preg_match(self::REGEX_CURP, $curp);
	}

	/**
	 * Valida un RFC de persona física
	 *
	 * @param string $rfc RFC a validar
	 * @return bool true si el RFC es válido para persona física
	 */
	public function validarRFCFisica(string $rfc): bool
	{
		$rfc = mb_strtoupper(trim($rfc), 'UTF-8');
		if (mb_strlen($rfc, 'UTF-8') !== 13) {
			return false;
		}
		return (bool) preg_match(self::REGEX_RFC_FISICA, $rfc);
	}

	/**
	 * Valida un RFC de persona moral
	 *
	 * @param string $rfc RFC a validar
	 * @return bool true si el RFC es válido para persona moral
	 */
	public function validarRFCMoral(string $rfc): bool
	{
		$rfc = mb_strtoupper(trim($rfc), 'UTF-8');
		if (mb_strlen($rfc, 'UTF-8') !== 12) {
			return false;
		}
		return (bool) preg_match(self::REGEX_RFC_MORAL, $rfc);
	}

	/**
	 * Valida un RFC (detecta automáticamente si es física o moral)
	 *
	 * @param string $rfc RFC a validar
	 * @return bool true si el RFC es válido
	 */
	public function validarRFC(string $rfc): bool
	{
		$rfc = mb_strtoupper(trim($rfc), 'UTF-8');
		$len = mb_strlen($rfc, 'UTF-8');

		if ($len === 13) {
			return $this->validarRFCFisica($rfc);
		}
		if ($len === 12) {
			return $this->validarRFCMoral($rfc);
		}
		return false;
	}

	/**
	 * Valida un VIN (Vehicle Identification Number)
	 *
	 * @param string $vin VIN a validar
	 * @return bool true si el VIN es válido
	 */
	public function validarVIN(string $vin): bool
	{
		$vin = strtoupper(trim($vin));
		if (strlen($vin) !== 17) {
			return false;
		}
		return (bool) preg_match(self::REGEX_VIN, $vin);
	}

	/**
	 * Valida un código postal mexicano
	 *
	 * @param string $cp Código postal a validar
	 * @return bool true si el CP es válido
	 */
	public function validarCodigoPostal(string $cp): bool
	{
		$cp = trim($cp);
		return (bool) preg_match(self::REGEX_CP, $cp);
	}

	/**
	 * Valida un código de país ISO alpha-2
	 *
	 * @param string $pais Código de país a validar
	 * @return bool true si el código es válido
	 */
	public function validarPais(string $pais): bool
	{
		$pais = strtoupper(trim($pais));
		return (bool) preg_match(self::REGEX_PAIS, $pais);
	}

	/**
	 * Valida un monto según formato SAT (14 enteros + 2 decimales)
	 *
	 * @param string $monto Monto como cadena (ej: "250000.00")
	 * @return bool true si el formato es correcto
	 */
	public function validarMonto(string $monto): bool
	{
		$monto = trim($monto);
		return (bool) preg_match(self::REGEX_MONTO, $monto);
	}

	/**
	 * Valida una fecha en formato YYYYMMDD con días reales
	 *
	 * @param string $fecha Fecha en formato YYYYMMDD
	 * @return bool true si la fecha es válida
	 */
	public function validarFecha(string $fecha): bool
	{
		$fecha = trim($fecha);
		if (!preg_match(self::REGEX_FECHA, $fecha)) {
			return false;
		}
		// Validación adicional con checkdate para años bisiestos
		$anio = (int) substr($fecha, 0, 4);
		$mes = (int) substr($fecha, 4, 2);
		$dia = (int) substr($fecha, 6, 2);
		return checkdate($mes, $dia, $anio);
	}

	/**
	 * Valida un mes reportado en formato YYYYMM
	 *
	 * @param string $mes Mes en formato YYYYMM
	 * @return bool true si el formato es válido
	 */
	public function validarMesReportado(string $mes): bool
	{
		$mes = trim($mes);
		return (bool) preg_match(self::REGEX_MES_REPORTADO, $mes);
	}

	/**
	 * Valida una clave de actividad económica SCIAN (7 dígitos)
	 *
	 * @param string $clave Clave SCIAN a validar
	 * @return bool true si la clave es válida
	 */
	public function validarActividadEconomica(string $clave): bool
	{
		$clave = trim($clave);
		return (bool) preg_match(self::REGEX_ACTIVIDAD_ECONOMICA, $clave);
	}

	/**
	 * Valida un nombre según formato SAT (mayúsculas, Ñ, espacios, puntos, comas)
	 *
	 * @param string $nombre Nombre a validar
	 * @return bool true si el nombre cumple el formato
	 */
	public function validarNombre(string $nombre): bool
	{
		$nombre = mb_strtoupper(trim($nombre), 'UTF-8');
		if (mb_strlen($nombre, 'UTF-8') < 1 || mb_strlen($nombre, 'UTF-8') > 200) {
			return false;
		}
		return (bool) preg_match(self::REGEX_NOMBRE, $nombre);
	}

	/**
	 * Valida una denominación o razón social
	 *
	 * @param string $denominacion Razón social a validar
	 * @return bool true si cumple el formato
	 */
	public function validarDenominacion(string $denominacion): bool
	{
		$denominacion = mb_strtoupper(trim($denominacion), 'UTF-8');
		if (mb_strlen($denominacion, 'UTF-8') < 1 || mb_strlen($denominacion, 'UTF-8') > 254) {
			return false;
		}
		return (bool) preg_match(self::REGEX_DENOMINACION, $denominacion);
	}

	/**
	 * Valida un número de teléfono (10-12 dígitos)
	 *
	 * @param string $telefono Número de teléfono
	 * @return bool true si el teléfono es válido
	 */
	public function validarTelefono(string $telefono): bool
	{
		$telefono = preg_replace('/[\s\-\(\)]/', '', trim($telefono));
		return (bool) preg_match(self::REGEX_TELEFONO, $telefono);
	}

	/**
	 * Valida un correo electrónico según formato SAT
	 *
	 * @param string $correo Correo electrónico
	 * @return bool true si el correo es válido
	 */
	public function validarCorreo(string $correo): bool
	{
		$correo = trim($correo);
		if (strlen($correo) > 60) {
			return false;
		}
		return (bool) preg_match(self::REGEX_CORREO, $correo);
	}

	/**
	 * Valida una clave REPUVE (8 caracteres alfanuméricos)
	 *
	 * @param string $repuve Clave REPUVE
	 * @return bool true si la clave es válida
	 */
	public function validarREPUVE(string $repuve): bool
	{
		$repuve = strtoupper(trim($repuve));
		return (bool) preg_match(self::REGEX_REPUVE, $repuve);
	}

	/**
	 * Valida un número de placas (1-12 caracteres)
	 *
	 * @param string $placas Número de placas
	 * @return bool true si las placas son válidas
	 */
	public function validarPlacas(string $placas): bool
	{
		$placas = strtoupper(trim($placas));
		return (bool) preg_match(self::REGEX_PLACAS, $placas);
	}

	/**
	 * Valida una CLABE interbancaria (18 dígitos)
	 *
	 * @param string $clabe CLABE interbancaria
	 * @return bool true si la CLABE es válida
	 */
	public function validarCLABE(string $clabe): bool
	{
		$clabe = trim($clabe);
		return (bool) preg_match(self::REGEX_CLABE, $clabe);
	}

	/**
	 * Valida un tipo de persona (PF, PM, FI)
	 *
	 * @param string $tipo Tipo de persona
	 * @return bool true si el tipo es válido
	 */
	public function validarTipoPersona(string $tipo): bool
	{
		return in_array(strtoupper(trim($tipo)), [
			self::TIPO_PERSONA_FISICA,
			self::TIPO_PERSONA_MORAL,
			self::TIPO_FIDEICOMISO
		], true);
	}

	/**
	 * Valida un tipo de vehículo (T, M, A)
	 *
	 * @param string $tipo Tipo de vehículo
	 * @return bool true si el tipo es válido
	 */
	public function validarTipoVehiculo(string $tipo): bool
	{
		return in_array(strtoupper(trim($tipo)), [
			self::TIPO_VEHICULO_TERRESTRE,
			self::TIPO_VEHICULO_MARITIMO,
			self::TIPO_VEHICULO_AEREO
		], true);
	}

	/**
	 * Valida un año modelo de vehículo (4 dígitos, rango razonable)
	 *
	 * @param string $anio Año modelo
	 * @return bool true si el año es válido
	 */
	public function validarAnioModelo(string $anio): bool
	{
		$anio = trim($anio);
		if (!preg_match('/^\d{4}$/', $anio)) {
			return false;
		}
		$anioInt = (int) $anio;
		$anioActual = (int) date('Y');
		return $anioInt >= 1900 && $anioInt <= ($anioActual + 2);
	}

	/**
	 * Formatea un monto numérico al formato SAT (14.2)
	 *
	 * @param float|int|string $monto Monto a formatear
	 * @return string Monto formateado o cadena vacía si inválido
	 */
	public function formatearMonto($monto): string
	{
		if (!is_numeric($monto)) {
			return '';
		}
		$montoFloat = (float) $monto;
		if ($montoFloat < 0 || $montoFloat > 99999999999999.99) {
			return '';
		}
		return number_format($montoFloat, 2, '.', '');
	}

	/**
	 * Formatea una fecha al formato YYYYMMDD del SAT
	 *
	 * @param string $fecha Fecha en cualquier formato reconocible
	 * @return string Fecha en formato YYYYMMDD o cadena vacía si inválida
	 */
	public function formatearFecha(string $fecha): string
	{
		$timestamp = strtotime($fecha);
		if ($timestamp === false) {
			return '';
		}
		return date('Ymd', $timestamp);
	}
}
