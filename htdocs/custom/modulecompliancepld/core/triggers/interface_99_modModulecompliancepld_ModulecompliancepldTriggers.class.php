<?php
declare(strict_types=1);

/* Copyright (C) 2026 SuperAdmin
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modModulecompliancepld_ModulecompliancepldTriggers.class.php
 * \ingroup modulecompliancepld
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modModulecompliancepld_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldvalidator.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldoperacion.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldalerta.class.php';


/**
 *  Class of triggers for Modulecompliancepld module
 */
class InterfaceModulecompliancepldTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
		$this->family = "compliance";
		$this->description = "Validación PLD en contactos y empresas — LFPIORPI Art. 17";
		$this->version = self::VERSIONS['dev'];
		$this->picto = 'modulecompliancepld@modulecompliancepld';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('modulecompliancepld')) {
			return 0; // If module is not enabled, we do nothing
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		// You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
		// For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)
		$methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
		$callback = array($this, $methodName);
		if (is_callable($callback)) {
			dol_syslog(
				"Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id
			);

			return call_user_func($callback, $action, $object, $user, $langs, $conf);
		}

		switch ($action) {
			default:
				dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
				break;
		}

		return 0;
	}

	/**
	 * Validación PLD al crear un contacto
	 *
	 * @param string       $action  Acción (CONTACT_CREATE)
	 * @param CommonObject $object  Objeto Contact
	 * @param User         $user    Usuario que ejecuta
	 * @param Translate    $langs   Traducciones
	 * @param Conf         $conf    Configuración
	 * @return int -1 si validación falla (rollback), 1 si OK
	 */
	public function contactCreate($action, $object, User $user, Translate $langs, Conf $conf): int
	{
		return $this->validarCamposPLDContacto($object, $langs);
	}

	/**
	 * Validación PLD al modificar un contacto
	 *
	 * @param string       $action  Acción (CONTACT_MODIFY)
	 * @param CommonObject $object  Objeto Contact
	 * @param User         $user    Usuario que ejecuta
	 * @param Translate    $langs   Traducciones
	 * @param Conf         $conf    Configuración
	 * @return int -1 si validación falla (rollback), 1 si OK
	 */
	public function contactModify($action, $object, User $user, Translate $langs, Conf $conf): int
	{
		return $this->validarCamposPLDContacto($object, $langs);
	}

	/**
	 * Validación PLD al crear una empresa
	 *
	 * @param string       $action  Acción (COMPANY_CREATE)
	 * @param CommonObject $object  Objeto Societe
	 * @param User         $user    Usuario que ejecuta
	 * @param Translate    $langs   Traducciones
	 * @param Conf         $conf    Configuración
	 * @return int -1 si validación falla (rollback), 1 si OK
	 */
	public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf): int
	{
		return $this->validarCamposPLDEmpresa($object, $langs);
	}

	/**
	 * Validación PLD al modificar una empresa
	 *
	 * @param string       $action  Acción (COMPANY_MODIFY)
	 * @param CommonObject $object  Objeto Societe
	 * @param User         $user    Usuario que ejecuta
	 * @param Translate    $langs   Traducciones
	 * @param Conf         $conf    Configuración
	 * @return int -1 si validación falla (rollback), 1 si OK
	 */
	public function companyModify($action, $object, User $user, Translate $langs, Conf $conf): int
	{
		return $this->validarCamposPLDEmpresa($object, $langs);
	}

	/**
	 * Al registrar un pago de cliente:
	 * 1. Copia la fecha de pago al extrafield pld_fecha_operacion de la factura.
	 * 2. Crea PLDOperacion si no existe para esa factura.
	 * 3. Evalúa umbral y genera PLDAlerta si corresponde.
	 *
	 * @param string       $action  Acción (PAYMENT_CUSTOMER_CREATE)
	 * @param CommonObject $object  Objeto Paiement
	 * @param User         $user    Usuario que ejecuta
	 * @param Translate    $langs   Traducciones
	 * @param Conf         $conf    Configuración
	 * @return int 0 si no hay facturas, 1 si OK, -1 si error
	 */
	public function paymentCustomerCreate($action, $object, User $user, Translate $langs, Conf $conf): int
	{
		if (empty($object->amounts) || empty($object->datepaye)) {
			return 0;
		}

		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		$errors = 0;

		foreach ($object->amounts as $fk_facture => $amount) {
			$fk_facture = (int) $fk_facture;
			if ($fk_facture <= 0) {
				continue;
			}

			// --- Cargar factura ---
			$facture = new Facture($this->db);
			if ($facture->fetch($fk_facture) <= 0) {
				dol_syslog("PLD Trigger paymentCustomerCreate: no se pudo cargar factura id=".$fk_facture, LOG_WARNING);
				$errors++;
				continue;
			}

			// --- Actualizar extrafield pld_fecha_operacion en la factura ---
			$facture->fetch_optionals();
			$facture->array_options['options_pld_fecha_operacion'] = $object->datepaye;
			if ($facture->insertExtraFields() < 0) {
				dol_syslog("PLD Trigger paymentCustomerCreate: error al guardar pld_fecha_operacion en factura id=".$fk_facture, LOG_ERR);
				$errors++;
			} else {
				dol_syslog("PLD Trigger paymentCustomerCreate: pld_fecha_operacion actualizada en factura id=".$fk_facture, LOG_INFO);
			}

			// --- Anti-duplicado: verificar si ya existe PLDOperacion para esta factura ---
			$operacion = new PLDOperacion($this->db);
			$operacion->entity = (int) $facture->entity;
			if ($operacion->existeOperacionPorFactura($fk_facture)) {
				dol_syslog("PLD Trigger paymentCustomerCreate: PLDOperacion ya existe para factura id=".$fk_facture.", se omite creación", LOG_INFO);
				continue;
			}

			// --- Obtener fk_product y tipo_vehiculo desde líneas de la factura ---
			$fk_product = 0;
			$tipo_vehiculo = 'nuevo';

			$facture->fetch_lines();
			foreach ($facture->lines as $line) {
				if (!empty($line->fk_product) && $line->fk_product > 0) {
					$fk_product = (int) $line->fk_product;
					break;
				}
			}

			if ($fk_product > 0) {
				$product = new Product($this->db);
				if ($product->fetch($fk_product) > 0) {
					$product->fetch_optionals();
					$estado_vehiculo = $product->array_options['options_pld_estado_vehiculo'] ?? 'nuevo';
					$tipo_vehiculo = ($estado_vehiculo === 'usado') ? 'usado' : 'nuevo';
					dol_syslog("PLD Trigger paymentCustomerCreate: tipo_vehiculo=".$tipo_vehiculo." desde product id=".$fk_product, LOG_INFO);
				}
			}

			// --- Calcular monto sin impuestos (Art. 6 DOF 27/03/2026) ---
			$monto_total = (float) $amount;
			$monto_sin_impuestos = round($monto_total / 1.16, 2);

			// --- Poblar y crear PLDOperacion ---
			$operacion->fk_facture               = $fk_facture;
			$operacion->fk_societe               = (int) $facture->socid;
			$operacion->fk_product               = $fk_product > 0 ? $fk_product : null;
			$operacion->tipo_operacion           = 'venta_vehiculo';
			$operacion->tipo_actividad_vulnerable = '808';
			$operacion->fecha_operacion          = date('Y-m-d', $object->datepaye);
			$operacion->monto_mxn                = $monto_total;
			$operacion->monto_sin_impuestos      = $monto_sin_impuestos;
			$operacion->estado_operacion         = 'completada';
			$operacion->estado                   = 'confirmada';
			$operacion->fecha_inicio_custodia    = date('Y-m-d', $object->datepaye);

			$operacion->generarFolioInterno();

			$result = $operacion->create($user);
			if ($result <= 0) {
				dol_syslog("PLD Trigger paymentCustomerCreate: error creando PLDOperacion para factura id=".$fk_facture.": ".implode(', ', $operacion->errors), LOG_ERR);
				$errors++;
				continue;
			}

			dol_syslog("PLD Trigger paymentCustomerCreate: PLDOperacion id=".$operacion->id." creada para factura id=".$fk_facture, LOG_INFO);

			// --- Evaluar umbral (incluye acumulación 6 meses) ---
			$operacion->evaluarUmbral($tipo_vehiculo);

			if ($operacion->requiere_aviso) {
				// --- Crear PLDAlerta ---
				$alerta = new PLDAlerta($this->db);
				$alerta->entity            = $operacion->entity;
				$alerta->fk_pld_operacion  = $operacion->id;
				$alerta->fk_societe        = $operacion->fk_societe;
				$alerta->tipo_alerta       = 'umbral_superado';
				$alerta->nivel_riesgo      = 'alto';
				$alerta->titulo            = 'Umbral PLD superado — Art. 17 Fracc. VIII';
				$alerta->descripcion       = 'Operación '.$operacion->folio_interno.' supera umbral regulatorio. Monto sin impuestos: '.$monto_sin_impuestos.' MXN.';
				$alerta->requiere_analisis = 1;

				$alerta_result = $alerta->create($user);
				if ($alerta_result > 0) {
					$operacion->genera_alerta = 1;
					$operacion->fk_pld_alerta = $alerta->id;
					$operacion->update($user, 1);
					dol_syslog("PLD Trigger paymentCustomerCreate: PLDAlerta id=".$alerta->id." creada para operacion id=".$operacion->id, LOG_INFO);
				} else {
					dol_syslog("PLD Trigger paymentCustomerCreate: error creando PLDAlerta para operacion id=".$operacion->id.": ".implode(', ', $alerta->errors), LOG_ERR);
					$errors++;
				}
			}
		}

		return ($errors > 0) ? -1 : 1;
	}

	/**
	 * Valida campos PLD de un contacto (socpeople)
	 *
	 * @param CommonObject $object Contacto con array_options cargado
	 * @param Translate    $langs  Traducciones
	 * @return int 1 si válido, -1 si inválido (provoca rollback)
	 */
	private function validarCamposPLDContacto($object, Translate $langs): int
	{
		$langs->load('modulecompliancepld@modulecompliancepld');
		$validator = new PLDValidator();

		$curp = $object->array_options['options_pld_curp'] ?? '';
		if ($curp !== '' && !$validator->validarCURP($curp)) {
			$this->errors[] = $langs->trans('PLDErrorCURPInvalida');
			dol_syslog("PLD Trigger: CURP inválida para contacto id=".$object->id." curp=".$curp, LOG_WARNING);
			return -1;
		}

		$rfc = $object->array_options['options_pld_rfc'] ?? '';
		if ($rfc !== '' && !$validator->validarRFC($rfc)) {
			$this->errors[] = $langs->trans('PLDErrorRFCInvalido');
			dol_syslog("PLD Trigger: RFC inválido para contacto id=".$object->id." rfc=".$rfc, LOG_WARNING);
			return -1;
		}

		$telefono = $object->array_options['options_pld_numero_telefono'] ?? '';
		if ($telefono !== '' && !$validator->validarTelefono($telefono)) {
			$this->errors[] = $langs->trans('PLDErrorTelefonoInvalido');
			dol_syslog("PLD Trigger: Teléfono inválido para contacto id=".$object->id, LOG_WARNING);
			return -1;
		}

		$correo = $object->array_options['options_pld_correo_electronico'] ?? '';
		if ($correo !== '' && !$validator->validarCorreo($correo)) {
			$this->errors[] = $langs->trans('PLDErrorCorreoInvalido');
			dol_syslog("PLD Trigger: Correo inválido para contacto id=".$object->id, LOG_WARNING);
			return -1;
		}

		dol_syslog("PLD Trigger: Contacto id=".$object->id." validado correctamente", LOG_INFO);
		return 1;
	}

	/**
	 * Valida campos PLD de una empresa (societe/thirdparty)
	 *
	 * @param CommonObject $object Empresa con array_options cargado
	 * @param Translate    $langs  Traducciones
	 * @return int 1 si válido, -1 si inválido (provoca rollback)
	 */
	private function validarCamposPLDEmpresa($object, Translate $langs): int
	{
		$langs->load('modulecompliancepld@modulecompliancepld');
		$validator = new PLDValidator();

		$curp = $object->array_options['options_pld_curp'] ?? '';
		if ($curp !== '' && !$validator->validarCURP($curp)) {
			$this->errors[] = $langs->trans('PLDErrorCURPInvalida');
			dol_syslog("PLD Trigger: CURP inválida para empresa id=".$object->id." curp=".$curp, LOG_WARNING);
			return -1;
		}

		$rfc = $object->array_options['options_pld_rfc_validado'] ?? '';
		if ($rfc !== '' && !$validator->validarRFC($rfc)) {
			$this->errors[] = $langs->trans('PLDErrorRFCInvalido');
			dol_syslog("PLD Trigger: RFC inválido para empresa id=".$object->id." rfc=".$rfc, LOG_WARNING);
			return -1;
		}

		$cp = $object->array_options['options_pld_codigo_postal'] ?? '';
		if ($cp !== '' && !$validator->validarCodigoPostal($cp)) {
			$this->errors[] = $langs->trans('PLDErrorCPInvalido');
			dol_syslog("PLD Trigger: CP inválido para empresa id=".$object->id." cp=".$cp, LOG_WARNING);
			return -1;
		}

		dol_syslog("PLD Trigger: Empresa id=".$object->id." validada correctamente", LOG_INFO);
		return 1;
	}
}
