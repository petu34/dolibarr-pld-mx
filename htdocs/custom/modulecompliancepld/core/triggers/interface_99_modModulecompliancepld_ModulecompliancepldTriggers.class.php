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
	 * Al registrar un pago de cliente, copia la fecha de pago al extrafield
	 * pld_fecha_operacion de cada factura vinculada al pago.
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

		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label('facture');

		$errors = 0;
		foreach ($object->amounts as $fk_facture => $amount) {
			$fk_facture = (int) $fk_facture;
			if ($fk_facture <= 0) {
				continue;
			}

			$facture = new Facture($this->db);
			if ($facture->fetch($fk_facture) <= 0) {
				dol_syslog("PLD Trigger paymentCustomerCreate: no se pudo cargar factura id=".$fk_facture, LOG_WARNING);
				$errors++;
				continue;
			}

			$facture->fetch_optionals();
			$facture->array_options['options_pld_fecha_operacion'] = $object->datepaye;

			$ret = $facture->insertExtraFields();
			if ($ret < 0) {
				dol_syslog("PLD Trigger paymentCustomerCreate: error al guardar pld_fecha_operacion en factura id=".$fk_facture, LOG_ERR);
				$errors++;
			} else {
				dol_syslog("PLD Trigger paymentCustomerCreate: pld_fecha_operacion actualizada en factura id=".$fk_facture." fecha=".dol_print_date($object->datepaye, 'day'), LOG_INFO);
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
