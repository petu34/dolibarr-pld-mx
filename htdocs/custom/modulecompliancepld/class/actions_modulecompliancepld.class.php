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
 * @file        actions_modulecompliancepld.class.php
 * @module      CompliancePLD
 * @description Hooks del módulo PLD: validación de campos en formularios de contacto y empresa
 * @author      Agente Generador (Sisyphus/Claude Code)
 * @version     1.0.0
 * @date        2026-02-27
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 *
 * @license     GNU/GPL
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/modulecompliancepld/class/pldvalidator.class.php';

/**
 * Class ActionsModulecompliancepld
 */
class ActionsModulecompliancepld extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var ?string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var int		Priority of hook (50 is used if value is not defined)
	 */
	public $priority;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					Return integer <0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		if (!isModEnabled('modulecompliancepld')) {
			return 0;
		}

		if (!in_array($action, array('add', 'update'))) {
			return 0;
		}

		$langs->load('modulecompliancepld@modulecompliancepld');
		$context = $parameters['currentcontext'];
		$error = 0;

		if ($context === 'contactcard') {
			$error = $this->validarFormularioContacto();
		} elseif ($context === 'thirdpartycard') {
			$error = $this->validarFormularioEmpresa();
		}

		if ($error > 0) {
			$action = '';
			return -1;
		}

		return 0;
	}


	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		return 0;
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		return 0;
	}



	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$object		   	Object output on PDF
	 * @param   string	$action     	'add', 'update', 'view'
	 * @return  int 		        	Return integer <0 if KO,
	 *                          		=0 if OK but we want to process standard actions too,
	 *  	                            >0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		return 0;
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            Return integer <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		return 0;
	}



	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             Return integer < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $langs;

		$langs->load("modulecompliancepld@modulecompliancepld");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'modulecompliancepld') {
			$head[$h][0] = dol_buildpath('/modulecompliancepld/index.php', 1);
			$head[$h][1] = $langs->trans("PLDDashboard");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("PLDMenu");
			$this->results['picto'] = 'modulecompliancepld@modulecompliancepld';
		}

		$head[$h][0] = 'customreports.php?objecttype='.$parameters['objecttype'].(empty($parameters['tabfamily']) ? '' : '&tabfamily='.$parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		$this->results['arrayoftype'] = array();

		return 0;
	}



	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int 		      			  	Return integer <0 if KO,
	 *                          				=0 if OK but we want to process standard actions too,
	 *  	                            		>0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'modulecompliancepld') {
			if ($user->hasRight('modulecompliancepld', 'read')) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param   array           $parameters     Array of parameters
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         'add', 'update', 'view'
	 * @param   Hookmanager     $hookmanager    hookmanager
	 * @return  int                             Return integer <0 if KO,
	 *                                          =0 if OK but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(&$parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] == 'remove') {
			// used to make some tabs removed
			return 0;
		} elseif ($parameters['mode'] == 'add') {
			$langs->load('modulecompliancepld@modulecompliancepld');
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;

			if (in_array($element, ['societe', 'contact', 'product', 'facture', 'commande'])) {
				$parameters['head'][$counter][0] = dol_buildpath('/modulecompliancepld/pld_tab.php', 1) . '?id=' . $id . '&amp;element='.$element;
				$parameters['head'][$counter][1] = $langs->trans('PLDTab');
				$parameters['head'][$counter][2] = 'plddata';
				$counter++;
			}
			// Dolibarr 14+ modifica $parameters['head'] por referencia
			return 0;
		} else {
			// Bad value for $parameters['mode']
			return -1;
		}
	}

	/**
	 * Valida campos PLD del formulario de contacto (socpeople)
	 *
	 * Campos validados: CURP, RFC, teléfono, correo electrónico
	 *
	 * @return int Número de errores encontrados
	 */
	private function validarFormularioContacto(): int
	{
		global $langs;
		$validator = new PLDValidator();
		$error = 0;

		$curp = GETPOST('options_pld_curp', 'alpha');
		if ($curp !== '' && !$validator->validarCURP($curp)) {
			setEventMessages($langs->trans('PLDErrorCURPInvalida'), null, 'errors');
			$error++;
		}

		$rfc = GETPOST('options_pld_rfc', 'alpha');
		if ($rfc !== '' && !$validator->validarRFC($rfc)) {
			setEventMessages($langs->trans('PLDErrorRFCInvalido'), null, 'errors');
			$error++;
		}

		$telefono = GETPOST('options_pld_numero_telefono', 'alpha');
		if ($telefono !== '' && !$validator->validarTelefono($telefono)) {
			setEventMessages($langs->trans('PLDErrorTelefonoInvalido'), null, 'errors');
			$error++;
		}

		$correo = GETPOST('options_pld_correo_electronico', 'alpha');
		if ($correo !== '' && !$validator->validarCorreo($correo)) {
			setEventMessages($langs->trans('PLDErrorCorreoInvalido'), null, 'errors');
			$error++;
		}

		return $error;
	}

	/**
	 * Valida campos PLD del formulario de empresa (thirdparty/societe)
	 *
	 * Campos validados: CURP, RFC, código postal
	 *
	 * @return int Número de errores encontrados
	 */
	private function validarFormularioEmpresa(): int
	{
		global $langs;
		$validator = new PLDValidator();
		$error = 0;

		$curp = GETPOST('options_pld_curp', 'alpha');
		if ($curp !== '' && !$validator->validarCURP($curp)) {
			setEventMessages($langs->trans('PLDErrorCURPInvalida'), null, 'errors');
			$error++;
		}

		$rfc = GETPOST('options_pld_rfc_validado', 'alpha');
		if ($rfc !== '' && !$validator->validarRFC($rfc)) {
			setEventMessages($langs->trans('PLDErrorRFCInvalido'), null, 'errors');
			$error++;
		}

		$cp = GETPOST('options_pld_codigo_postal', 'alpha');
		if ($cp !== '' && !$validator->validarCodigoPostal($cp)) {
			setEventMessages($langs->trans('PLDErrorCPInvalido'), null, 'errors');
			$error++;
		}

		return $error;
	}
}
