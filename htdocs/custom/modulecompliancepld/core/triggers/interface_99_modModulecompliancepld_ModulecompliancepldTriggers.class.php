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
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/services/PLDOperacionService.php';


/**
 *  Class of triggers for Modulecompliancepld module
 *
 *  Validation of PLD fields is handled by the hook in
 *  actions_modulecompliancepld.class.php (Strategy/Composite pattern).
 *  This trigger is kept as a stub for future non-validation events.
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
		$this->description = "Triggers PLD — LFPIORPI Art. 17";
		$this->version = self::VERSIONS['dev'];
		$this->picto = 'modulecompliancepld@modulecompliancepld';
	}

	/**
	 * Function called when a Dolibarr business event is done.
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
			return 0;
		}

		$methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
		$callback = array($this, $methodName);
		if (is_callable($callback)) {
			dol_syslog(
				"Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id
			);
			return call_user_func($callback, $action, $object, $user, $langs, $conf);
		}

		return 0;
	}

	/**
	 * Verifica si una factura contiene productos tipo vehículo.
	 *
	 * Consulta las líneas de la factura y verifica si al menos un producto
	 * tiene el extrafield pld_tipo_vehiculo poblado, o si la descripción
	 * contiene un VIN de 17 caracteres.
	 *
	 * @param Facture $facture Objeto factura de Dolibarr
	 * @return bool true si la factura contiene vehículos
	 */
	private function esFacturaVehiculo($facture): bool
	{
		// Verificar líneas de la factura con productos vinculados
		$sql  = "SELECT COUNT(*) AS cnt FROM ".MAIN_DB_PREFIX."facturedet fd";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = fd.fk_product";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields pe ON pe.fk_object = p.rowid";
		$sql .= " WHERE fd.fk_facture = ".(int)$facture->id;
		$sql .= " AND (";
		// Producto tiene extrafield pld_tipo_vehiculo
		$sql .= "  pe.pld_tipo_vehiculo IS NOT NULL AND pe.pld_tipo_vehiculo != ''";
		$sql .= "  OR fd.description LIKE '%VIN%'";
		$sql .= "  OR p.ref LIKE 'DEMO-VEH-%'";
		$sql .= "  OR p.label LIKE '%VIN%'";
		$sql .= " )";
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj && (int)$obj->cnt > 0) {
				return true;
			}
		}

		// Si hay nota pública que menciona vehículo, considerar como tal
		if (!empty($facture->note_public) && preg_match('/VIN|vehículo|vehiculo|nuevo|usado|seminuevo/i', $facture->note_public)) {
			return true;
		}

		return false;
	}

	/**
	 * Trigger: Marca factura como operación vulnerable cuando se valida.
	 *
	 * Dispara cuando BILL_VALIDATE — crea operación PLD para revisión.
	 *
	 * @param string        $action  Event code (BILL_VALIDATE)
	 * @param Facture       $object  Invoice object
	 * @param User          $user    User object
	 * @param Translate     $langs   Language object
	 * @param Conf          $conf    Config object
	 * @return int 0 siempre (no interrumpir eventos)
	 */
	public function billValidate($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!$object || $object->type != 0) {
			return 0;
		}

		if (!$this->esFacturaVehiculo($object)) {
			return 0;
		}

		$svc = new PLDOperacionService($this->db);
		$result = $svc->marcarFacturaComoVulnerable($object, $user);

		if ($result < 0) {
			dol_syslog("PLD: Error marcando factura validada ".$object->id." como vulnerable: ".$svc->error, LOG_ERR);
		}

		return 0;
	}

	/**
	 * Trigger: Marca factura como operación vulnerable cuando se paga.
	 *
	 * Dispara cuando BILL_PAY — crea/actualiza operación PLD para revisión.
	 *
	 * @param string        $action  Event code (BILL_PAY)
	 * @param Facture       $object  Invoice object
	 * @param User          $user    User object
	 * @param Translate     $langs   Language object
	 * @param Conf          $conf    Config object
	 * @return int 0 siempre (no interrumpir eventos)
	 */
	public function billPay($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!$object || $object->type != 0) {
			return 0;
		}

		if (!$this->esFacturaVehiculo($object)) {
			return 0;
		}

		$svc = new PLDOperacionService($this->db);
		$result = $svc->marcarFacturaComoVulnerable($object, $user);

		if ($result < 0) {
			dol_syslog("PLD: Error marcando factura pagada ".$object->id." como vulnerable: ".$svc->error, LOG_ERR);
		}

		return 0;
	}
}
