<?php
/* Copyright (C) 2026 Ouroboros
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * @file        modModulecompliancepld.class.php
 * @module      CompliancePLD
 * @description Descriptor del módulo de Prevención de Lavado de Dinero (PLD)
 * @author      Ouroboros
 * @version     1.0.0
 * @date        2026-02-22
 * @compliance  LFPIORPI Art. 17 Fracc. VIII — PLD México
 *
 * @license     GNU/GPL
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Descriptor y activación del módulo Compliance PLD México
 *
 * Registra 143 extrafields PLD en 6 tablas de Dolibarr,
 * permisos, menús, pestañas y hooks al activar el módulo.
 */
class modModulecompliancepld extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		$this->numero = 500200;
		$this->rights_class = 'modulecompliancepld';
		$this->family = 'financial';
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = 'ModuleModulecompliancepldDesc';
		$this->descriptionlong = 'ModuleModulecompliancepldDesc';
		$this->editor_name = 'Ouroboros';
		$this->editor_url = '';
		$this->editor_squarred_logo = '';
		$this->version = '1.0.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		$this->picto = 'fa-shield';

		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(
				'/modulecompliancepld/css/modulecompliancepld.css.php',
			),
			'js' => array(
				'/modulecompliancepld/js/modulecompliancepld.js.php',
			),
			'hooks' => array(
				'data' => array(
					'thirdpartycard',
					'contactcard',
					'productcard',
					'invoicecard',
					'ordercard',
					'paymentcard',
				),
				'entity' => '0',
			),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/modulecompliancepld/temp","/modulecompliancepld/subdir");
		$this->dirs = array("/modulecompliancepld/temp");

		// Config pages. Put here list of php page, stored into modulecompliancepld/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@modulecompliancepld");

		// Dependencies
		// A condition to hide module
		$this->hidden = getDolGlobalInt('MODULE_MODULECOMPLIANCEPLD_DISABLED'); // A condition to disable module;
		// List of module class names that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR')...)
		$this->depends = array();
		// List of module class names to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->requiredby = array();
		// List of module class names this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array();

		// The language file dedicated to your module
		$this->langfiles = array("modulecompliancepld@modulecompliancepld");

		$this->phpmin = array(8, 1);
		$this->need_dolibarr_version = array(20, 0);
		$this->need_javascript_ajax = 0;

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
		//$this->automatic_activation = array('FR'=>'ModulecompliancepldWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('MODULECOMPLIANCEPLD_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('MODULECOMPLIANCEPLD_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isModEnabled("modulecompliancepld")) {
			$conf->modulecompliancepld = new stdClass();
			$conf->modulecompliancepld->enabled = 0;
		}

		$pldPerm = '$user->hasRight(\'modulecompliancepld\', \'read\')';
		$pldLang = 'modulecompliancepld@modulecompliancepld';
		$this->tabs = array();
		$this->tabs[] = array('data' => 'thirdparty:+plddata:PLDTabThirdparty:'.$pldLang.':'.$pldPerm.':/modulecompliancepld/pld_thirdparty.php?id=__ID__');
		$this->tabs[] = array('data' => 'contact:+plddata:PLDTabContact:'.$pldLang.':'.$pldPerm.':/modulecompliancepld/pld_contact.php?id=__ID__');
		$this->tabs[] = array('data' => 'product:+plddata:PLDTabProduct:'.$pldLang.':'.$pldPerm.':/modulecompliancepld/pld_product.php?id=__ID__');
		$this->tabs[] = array('data' => 'invoice:+plddata:PLDTabInvoice:'.$pldLang.':'.$pldPerm.':/modulecompliancepld/pld_invoice.php?id=__ID__');
		$this->tabs[] = array('data' => 'order:+plddata:PLDTabOrder:'.$pldLang.':'.$pldPerm.':/modulecompliancepld/pld_order.php?id=__ID__');
		$this->tabs[] = array('data' => 'payment:+plddata:PLDTabPayment:'.$pldLang.':'.$pldPerm.':/modulecompliancepld/pld_payment.php?id=__ID__');


		// Dictionaries
		/* Example:
		 $this->dictionaries=array(
		 'langs'=>'modulecompliancepld@modulecompliancepld',
		 // List of tables we want to see into dictonnary editor
		 'tabname'=>array("table1", "table2", "table3"),
		 // Label of tables
		 'tablib'=>array("Table1", "Table2", "Table3"),
		 // Request to select fields
		 'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
		 // Sort order
		 'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
		 // List of fields (result of select to show dictionary)
		 'tabfield'=>array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields to edit a record)
		 'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
		 // List of fields (list of fields for insert)
		 'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
		 // Name of columns with primary key (try to always name it 'rowid')
		 'tabrowid'=>array("rowid", "rowid", "rowid"),
		 // Condition to show each dictionary
		 'tabcond'=>array(isModEnabled('modulecompliancepld'), isModEnabled('modulecompliancepld'), isModEnabled('modulecompliancepld')),
		 // Tooltip for every fields of dictionaries: DO NOT PUT AN EMPTY ARRAY
		 'tabhelp'=>array(array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), array('code'=>$langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip'), ...),
		 );
		 */
		/* BEGIN MODULEBUILDER DICTIONARIES */
		$this->dictionaries = array();
		/* END MODULEBUILDER DICTIONARIES */

		// Boxes/Widgets
		// Add here list of php file(s) stored in modulecompliancepld/core/boxes that contains a class to show a widget.
		/* BEGIN MODULEBUILDER WIDGETS */
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'modulecompliancepldwidget1.php@modulecompliancepld',
			//      'note' => 'Widget provided by Modulecompliancepld',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);
		/* END MODULEBUILDER WIDGETS */

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		/* BEGIN MODULEBUILDER CRON */
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/modulecompliancepld/class/myobject.class.php',
			//      'objectname' => 'MyObject',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => 'isModEnabled("modulecompliancepld")',
			//      'priority' => 50,
			//  ),
		);
		/* END MODULEBUILDER CRON */
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'isModEnabled("modulecompliancepld")', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'isModEnabled("modulecompliancepld")', 'priority'=>50)
		// );

		$this->rights = array();
		$r = 0;

		$this->rights[$r][0] = 50020001;
		$this->rights[$r][1] = 'Permission500001';
		$this->rights[$r][4] = 'read';
		$r++;

		$this->rights[$r][0] = 50020002;
		$this->rights[$r][1] = 'Permission500002';
		$this->rights[$r][4] = 'write';
		$r++;

		$this->rights[$r][0] = 50020003;
		$this->rights[$r][1] = 'Permission500003';
		$this->rights[$r][4] = 'delete';
		$r++;

		$this->rights[$r][0] = 50020004;
		$this->rights[$r][1] = 'Permission500004';
		$this->rights[$r][4] = 'generate';
		$r++;

		$this->rights[$r][0] = 50020005;
		$this->rights[$r][1] = 'Permission500005';
		$this->rights[$r][4] = 'send';
		$r++;


		$this->menu = array();
		$r = 0;
		$enabledCond = 'isModEnabled("modulecompliancepld")';
		$readPerm = '$user->hasRight("modulecompliancepld", "read")';
		$menuLang = 'modulecompliancepld@modulecompliancepld';

		$this->menu[$r++] = array(
			'fk_menu' => '',
			'type' => 'top',
			'titre' => 'PLDMenu',
			'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth valignmiddle"'),
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => '',
			'url' => '/modulecompliancepld/index.php',
			'langs' => $menuLang,
			'position' => 1000 + $r,
			'enabled' => $enabledCond,
			'perms' => $readPerm,
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=modulecompliancepld',
			'type' => 'left',
			'titre' => 'PLDDashboard',
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => 'pld_dashboard',
			'url' => '/modulecompliancepld/index.php',
			'langs' => $menuLang,
			'position' => 1000 + $r,
			'enabled' => $enabledCond,
			'perms' => $readPerm,
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=modulecompliancepld',
			'type' => 'left',
			'titre' => 'PLDOperaciones',
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => 'pld_operaciones',
			'url' => '/modulecompliancepld/operaciones_list.php',
			'langs' => $menuLang,
			'position' => 1000 + $r,
			'enabled' => $enabledCond,
			'perms' => $readPerm,
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=modulecompliancepld',
			'type' => 'left',
			'titre' => 'PLDAvisos',
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => 'pld_avisos',
			'url' => '/modulecompliancepld/avisos_list.php',
			'langs' => $menuLang,
			'position' => 1000 + $r,
			'enabled' => $enabledCond,
			'perms' => $readPerm,
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=modulecompliancepld',
			'type' => 'left',
			'titre' => 'PLDAlertas',
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => 'pld_alertas',
			'url' => '/modulecompliancepld/alertas_list.php',
			'langs' => $menuLang,
			'position' => 1000 + $r,
			'enabled' => $enabledCond,
			'perms' => $readPerm,
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=modulecompliancepld',
			'type' => 'left',
			'titre' => 'ListaBeneficiarios',
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => 'pld_beneficiarios',
			'url' => '/modulecompliancepld/beneficiarios_list.php',
			'langs' => $menuLang,
			'position' => 1000 + $r,
			'enabled' => $enabledCond,
			'perms' => $readPerm,
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=modulecompliancepld',
			'type' => 'left',
			'titre' => 'ListaDocumentos',
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => 'pld_documentos',
			'url' => '/modulecompliancepld/documentos_list.php',
			'langs' => $menuLang,
			'position' => 1000 + $r,
			'enabled' => $enabledCond,
			'perms' => $readPerm,
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=modulecompliancepld',
			'type' => 'left',
			'titre' => 'PLDReportes',
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => 'pld_reportes',
			'url' => '/modulecompliancepld/reportes.php',
			'langs' => $menuLang,
			'position' => 1000 + $r,
			'enabled' => $enabledCond,
			'perms' => $readPerm,
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu'  => 'fk_mainmenu=modulecompliancepld',
			'type'     => 'left',
			'titre'    => 'PLDGenerarXML',
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => 'pld_xml_generator',
			'url'      => '/modulecompliancepld/xml_generator.php',
			'langs'    => $menuLang,
			'position' => 1000 + $r,
			'enabled'  => $enabledCond,
			'perms'    => '$user->hasRight("modulecompliancepld", "write")',
			'target'   => '',
			'user'     => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=modulecompliancepld',
			'type' => 'left',
			'titre' => 'PLDConfiguracion',
			'mainmenu' => 'modulecompliancepld',
			'leftmenu' => 'pld_configuracion',
			'url' => '/modulecompliancepld/admin/setup.php',
			'langs' => $menuLang,
			'position' => 1000 + $r,
			'enabled' => $enabledCond,
			'perms' => '$user->admin',
			'target' => '',
			'user' => 0,
		);


	}

	public function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/modulecompliancepld/sql/');
		if ($result < 0) {
			return -1;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		// Limpiar fieldcomputed residual de init() previos con args desalineados (fix commit 49c6cb3)
		$this->db->query("UPDATE ".MAIN_DB_PREFIX."extrafields SET fieldcomputed = NULL WHERE name LIKE 'pld_%' AND fieldcomputed LIKE 'PLD%'");

		// Ocultar extrafields PLD del formulario nativo de facturas (list=0).
		// Los campos se muestran exclusivamente en el tab pld_invoice.php.
		// fix: tab PLD en factura 2026-03-16
		$this->db->query("UPDATE ".MAIN_DB_PREFIX."extrafields SET list = '0' WHERE elementtype = 'facture' AND name LIKE 'pld_%'");

		$e = 'isModEnabled("modulecompliancepld")';
		$l = 'modulecompliancepld@modulecompliancepld';

		// --- Opciones para campos select ---
		$optTipoPersona = array('options' => array('PF' => 'PLDTipoPersonaPF', 'PM' => 'PLDTipoPersonaPM', 'FI' => 'PLDTipoPersonaFI'));
		$optEstados = array('options' => array(
			'AGS' => 'PLDEstadoAGS', 'BC' => 'PLDEstadoBC', 'BCS' => 'PLDEstadoBCS', 'CAM' => 'PLDEstadoCAM',
			'COA' => 'PLDEstadoCOA', 'COL' => 'PLDEstadoCOL', 'CHIS' => 'PLDEstadoCHIS', 'CHIH' => 'PLDEstadoCHIH',
			'CDMX' => 'PLDEstadoCDMX', 'DGO' => 'PLDEstadoDGO', 'GTO' => 'PLDEstadoGTO', 'GRO' => 'PLDEstadoGRO',
			'HGO' => 'PLDEstadoHGO', 'JAL' => 'PLDEstadoJAL', 'MEX' => 'PLDEstadoMEX', 'MICH' => 'PLDEstadoMICH',
			'MOR' => 'PLDEstadoMOR', 'NAY' => 'PLDEstadoNAY', 'NL' => 'PLDEstadoNL', 'OAX' => 'PLDEstadoOAX',
			'PUE' => 'PLDEstadoPUE', 'QRO' => 'PLDEstadoQRO', 'QROO' => 'PLDEstadoQROO', 'SLP' => 'PLDEstadoSLP',
			'SIN' => 'PLDEstadoSIN', 'SON' => 'PLDEstadoSON', 'TAB' => 'PLDEstadoTAB', 'TAM' => 'PLDEstadoTAM',
			'TLAX' => 'PLDEstadoTLAX', 'VER' => 'PLDEstadoVER', 'YUC' => 'PLDEstadoYUC', 'ZAC' => 'PLDEstadoZAC',
		));
		$optTipoId = array('options' => array('INE' => 'PLDTipoIdINE', 'PAS' => 'PLDTipoIdPasaporte', 'FM3' => 'PLDTipoIdFM3', 'CED' => 'PLDTipoIdCedula', 'OTR' => 'PLDTipoIdOtra'));
		$optRepresentacion = array('options' => array('GEN' => 'PLDRepresentacionGeneral', 'ESP' => 'PLDRepresentacionEspecial', 'AMB' => 'PLDRepresentacionAmbos'));
		$optTipoVehiculo = array('options' => array('T' => 'PLDTipoVehiculoT', 'M' => 'PLDTipoVehiculoM', 'A' => 'PLDTipoVehiculoA'));
		$optOrigen = array('options' => array('NAC' => 'PLDOrigenNacional', 'IMP' => 'PLDOrigenImportado'));
		$optEstadoVeh = array('options' => array('NVO' => 'PLDEstadoNuevo', 'USA' => 'PLDEstadoUsado', 'SEM' => 'PLDEstadoSeminuevo'));
		$optUsoDestino = array('options' => array('PAR' => 'PLDUsoParticular', 'COM' => 'PLDUsoComercial', 'TRA' => 'PLDUsoTransporte'));
		$optTipoAviso = array('options' => array('MEN' => 'PLDTipoAvisoMensual', '24H' => 'PLDTipoAviso24hrs', 'ACU' => 'PLDTipoAvisoAcumulado'));
		$optTipoTarjeta = array('options' => array('DEB' => 'PLDTarjetaDebito', 'CRE' => 'PLDTarjetaCredito'));
		$optPagoPlaneado = array('options' => array('EFE' => 'PLDPagoPlaneadoEfectivo', 'FIN' => 'PLDPagoPlaneadoFinanciamiento', 'TRA' => 'PLDPagoPlaneadoTransferencia', 'MIX' => 'PLDPagoPlaneadoMixto'));

		// =====================================================================
		// THIRDPARTY (llx_societe_extrafields) — 33 campos
		// LFPIORPI Art. 17 Fracc. VIII: Identificación del cliente
		// =====================================================================

		// 1.1 Identificación Básica
		$extrafields->addExtraField('pld_tipo_persona', 'Tipo de Persona (PLD)', 'select', 100, '', 'thirdparty', 0, 1, '', $optTipoPersona, 1, '',1, 'PLDTipoPersonaHelp', '', '', $l, $e);
		$extrafields->addExtraField('pld_curp', 'CURP (PLD)', 'varchar', 101, '18', 'thirdparty', 0, 0, '', '', 1, '',1, 'PLDCURPHelp', '', '', $l, $e);
		$extrafields->addExtraField('pld_rfc_validado', 'RFC Validado (PLD)', 'varchar', 102, '13', 'thirdparty', 0, 1, '', '', 1, '',1, 'PLDRFCHelp', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_nacimiento', 'Fecha de Nacimiento (PLD)', 'date', 103, '', 'thirdparty', 0, 0, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_constitucion', 'Fecha de Constitución (PLD)', 'date', 104, '', 'thirdparty', 0, 0, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_nacionalidad', 'Nacionalidad (PLD)', 'varchar', 105, '2', 'thirdparty', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_pais_nacimiento', 'País de Nacimiento (PLD)', 'varchar', 106, '2', 'thirdparty', 0, 0, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_estado_nacimiento', 'Estado de Nacimiento (PLD)', 'varchar', 107, '50', 'thirdparty', 0, 0, '', '', 1, '',1, '', '', '', $l, $e);

		// 1.2 Domicilio Fiscal
		$extrafields->addExtraField('pld_calle', 'Calle (PLD)', 'varchar', 108, '100', 'thirdparty', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_exterior', 'Número Exterior (PLD)', 'varchar', 109, '56', 'thirdparty', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_interior', 'Número Interior (PLD)', 'varchar', 110, '40', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_colonia', 'Colonia (PLD)', 'varchar', 111, '50', 'thirdparty', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_codigo_postal', 'Código Postal (PLD)', 'varchar', 112, '5', 'thirdparty', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_municipio', 'Municipio / Alcaldía (PLD)', 'varchar', 113, '100', 'thirdparty', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_estado', 'Entidad Federativa (PLD)', 'select', 114, '', 'thirdparty', 0, 1, '', $optEstados, 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_pais', 'País (PLD)', 'varchar', 115, '2', 'thirdparty', 0, 1, 'MX', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_es_domicilio_extranjero', '¿Domicilio Extranjero? (PLD)', 'boolean', 116, '', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_estado_provincia_ext', 'Estado/Provincia Extranjero (PLD)', 'varchar', 117, '100', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_ciudad_poblacion_ext', 'Ciudad/Población Extranjero (PLD)', 'varchar', 118, '100', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 1.3 Actividad Económica
		$extrafields->addExtraField('pld_actividad_economica', 'Actividad Económica SCIAN (PLD)', 'varchar', 119, '7', 'thirdparty', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_giro_mercantil', 'Giro Mercantil (PLD)', 'varchar', 120, '7', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_ocupacion', 'Ocupación / Profesión (PLD)', 'varchar', 121, '100', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 1.4 Datos Constitutivos PM
		$extrafields->addExtraField('pld_denominacion_razon', 'Denominación o Razón Social (PLD)', 'varchar', 122, '254', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_escritura', 'Número de Escritura (PLD)', 'varchar', 123, '20', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_escritura', 'Fecha de Escritura (PLD)', 'date', 124, '', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_notario_numero', 'Número de Notario (PLD)', 'varchar', 125, '8', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_notario_nombre', 'Nombre del Notario (PLD)', 'varchar', 126, '150', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_notario_estado', 'Estado del Notario (PLD)', 'varchar', 127, '50', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_folio_mercantil', 'Folio Mercantil (PLD)', 'varchar', 128, '200', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 1.5 Fideicomiso
		$extrafields->addExtraField('pld_identificador_fideicomiso', 'Identificador del Fideicomiso (PLD)', 'varchar', 129, '40', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 1.6 Control PLD
		$extrafields->addExtraField('pld_cliente_identificado', '¿Cliente Identificado? (PLD)', 'boolean', 130, '', 'thirdparty', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_identificacion', 'Fecha de Identificación (PLD)', 'date', 131, '', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_expediente_completo', '¿Expediente Completo? (PLD)', 'boolean', 132, '', 'thirdparty', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_es_pep', '¿Persona Expuesta Políticamente? (PLD)', 'boolean', 133, '', 'thirdparty', 0, 0, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_relacion_pep', 'Relación con PEP (PLD)', 'varchar', 134, '200', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_tiene_beneficiario', '¿Tiene Beneficiario Controlador? (PLD)', 'boolean', 135, '', 'thirdparty', 0, 0, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_observaciones', 'Observaciones PLD', 'text', 136, '', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// Arts. 45 Bis–Quinquies DOF 27/03/2026: verificación PEP ante UIF
		$optNivelRiesgo = array('options' => array('bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto', 'muy_alto' => 'Muy alto'));
		$extrafields->addExtraField('pld_fecha_verificacion_pep', 'Última Verificación PEP (PLD)', 'date', 150, '', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_resultado_pep', 'Resultado UIF-PEP (PLD)', 'varchar', 151, '20', 'thirdparty', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_nivel_riesgo', 'Nivel de Riesgo (PLD)', 'select', 152, '', 'thirdparty', 0, 1, 'medio', $optNivelRiesgo, 1, '',1, '', '', '', $l, $e);

		// =====================================================================
		// SOCPEOPLE (llx_socpeople_extrafields) — 22 campos
		// LFPIORPI Art. 17 Fracc. VIII: Representantes y contactos
		// =====================================================================

		// 2.1 Datos Personales
		$extrafields->addExtraField('pld_apellido_paterno', 'Apellido Paterno (PLD)', 'varchar', 100, '200', 'socpeople', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_apellido_materno', 'Apellido Materno (PLD)', 'varchar', 101, '200', 'socpeople', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_nombre_completo', 'Nombre(s) Completo(s) (PLD)', 'varchar', 102, '200', 'socpeople', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_curp', 'CURP (PLD)', 'varchar', 103, '18', 'socpeople', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_rfc', 'RFC (PLD)', 'varchar', 104, '13', 'socpeople', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_nacimiento', 'Fecha de Nacimiento (PLD)', 'date', 105, '', 'socpeople', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_nacionalidad', 'Nacionalidad (PLD)', 'varchar', 106, '2', 'socpeople', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_actividad_economica', 'Actividad Económica SCIAN (PLD)', 'varchar', 107, '7', 'socpeople', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);

		// 2.2 Identificación Oficial
		$extrafields->addExtraField('pld_tipo_identificacion', 'Tipo de Identificación (PLD)', 'select', 108, '', 'socpeople', 0, 1, '', $optTipoId, 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_identificacion', 'Número de Identificación (PLD)', 'varchar', 109, '20', 'socpeople', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_vigencia_identificacion', 'Vigencia de Identificación (PLD)', 'date', 110, '', 'socpeople', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_autoridad_emite', 'Autoridad Emisora (PLD)', 'varchar', 111, '100', 'socpeople', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_clave_elector', 'Clave de Elector (PLD)', 'varchar', 112, '18', 'socpeople', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 2.3 Representación Legal
		$extrafields->addExtraField('pld_es_representante_legal', '¿Es Representante Legal? (PLD)', 'boolean', 113, '', 'socpeople', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_tipo_representacion', 'Tipo de Representación (PLD)', 'select', 114, '', 'socpeople', 0, 0, '', $optRepresentacion, 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_escritura_poder', 'Escritura del Poder (PLD)', 'varchar', 115, '20', 'socpeople', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_poder', 'Fecha del Poder (PLD)', 'date', 116, '', 'socpeople', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_notario_poder', 'Notario del Poder (PLD)', 'varchar', 117, '150', 'socpeople', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 2.4 Teléfono y Contacto
		$extrafields->addExtraField('pld_clave_pais_telefono', 'Clave País Teléfono (PLD)', 'varchar', 118, '2', 'socpeople', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_telefono', 'Número de Teléfono (PLD)', 'varchar', 119, '12', 'socpeople', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_correo_electronico', 'Correo Electrónico (PLD)', 'varchar', 120, '60', 'socpeople', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// =====================================================================
		// PRODUCT (llx_product_extrafields) — 24 campos
		// LFPIORPI Art. 17 Fracc. VIII: Datos del vehículo
		// =====================================================================

		// 3.1 Identificación del Vehículo
		$extrafields->addExtraField('pld_tipo_vehiculo', 'Tipo de Vehículo (PLD)', 'select', 100, '', 'product', 0, 1, '', $optTipoVehiculo, 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_marca', 'Marca del Fabricante (PLD)', 'varchar', 101, '40', 'product', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_modelo', 'Modelo (PLD)', 'varchar', 102, '40', 'product', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_anio_modelo', 'Año Modelo (PLD)', 'varchar', 103, '4', 'product', 0, 1, '', '', 1, '',1, '', '', '', $l, $e);

		// 3.2 Números de Serie
		$extrafields->addExtraField('pld_vin', 'VIN (PLD)', 'varchar', 104, '17', 'product', 0, 0, '', '', 1, '',1, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_repuve', 'Clave REPUVE (PLD)', 'varchar', 105, '8', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_placas', 'Placas (PLD)', 'varchar', 106, '12', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_nivel_blindaje', 'Nivel de Blindaje (PLD)', 'varchar', 107, '1', 'product', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_serie', 'Número de Serie (PLD)', 'varchar', 108, '20', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_bandera', 'País de Bandera (PLD)', 'varchar', 109, '2', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_matricula', 'Matrícula (PLD)', 'varchar', 110, '12', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 3.3 Origen y Estado
		$extrafields->addExtraField('pld_origen', 'Origen del Vehículo (PLD)', 'select', 111, '', 'product', 0, 1, '', $optOrigen, 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_pais_origen', 'País de Fabricación (PLD)', 'varchar', 112, '2', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_estado_vehiculo', 'Estado del Vehículo (PLD)', 'select', 113, '', 'product', 0, 1, '', $optEstadoVeh, 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_kilometraje', 'Kilometraje (PLD)', 'int', 114, '', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_uso_destino', 'Uso / Destino (PLD)', 'select', 115, '', 'product', 0, 1, '', $optUsoDestino, 1, '',0, '', '', '', $l, $e);

		// 3.4 Documentación Legal
		$extrafields->addExtraField('pld_numero_factura_original', 'Número Factura Original (PLD)', 'varchar', 116, '30', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_factura_original', 'Fecha Factura Original (PLD)', 'date', 117, '', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_propietario_anterior', 'Propietario Anterior (PLD)', 'varchar', 118, '200', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_tarjeta_circulacion', 'Tarjeta de Circulación (PLD)', 'varchar', 119, '20', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_pedimento', 'Número de Pedimento (PLD)', 'varchar', 120, '20', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 3.5 Valores
		$extrafields->addExtraField('pld_valor_factura', 'Valor de Factura (PLD)', 'price', 121, '15,2', 'product', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_valor_comercial', 'Valor Comercial (PLD)', 'price', 122, '15,2', 'product', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_valor_libro_azul', 'Valor Libro Azul (PLD)', 'price', 123, '15,2', 'product', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// =====================================================================
		// FACTURE (llx_facture_extrafields) — 31 campos
		// LFPIORPI Art. 17 Fracc. VIII: Operación vulnerable
		// =====================================================================

		// 4.1 Control PLD
		$extrafields->addExtraField('pld_es_actividad_vulnerable', '¿Es Actividad Vulnerable? (PLD)', 'boolean', 100, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_clave_actividad', 'Clave de Actividad Vulnerable (PLD)', 'varchar', 101, '3', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_tipo_operacion', 'Tipo de Operación (PLD)', 'varchar', 102, '4', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_supera_umbral_id', '¿Supera Umbral Identificación? (PLD)', 'boolean', 103, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_supera_umbral_aviso', '¿Supera Umbral Aviso? (PLD)', 'boolean', 104, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_requiere_aviso', '¿Requiere Aviso SAT? (PLD)', 'boolean', 105, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_tipo_aviso', 'Tipo de Aviso (PLD)', 'select', 106, '', 'facture', 0, 0, '', $optTipoAviso, 1, '',0, '', '', '', $l, $e);

		// 4.2 Datos de la Operación
		$extrafields->addExtraField('pld_fecha_operacion', 'Fecha de Operación (PLD)', 'date', 107, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_codigo_postal_operacion', 'Código Postal de la Operación (PLD)', 'varchar', 108, '5', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_descripcion_operacion', 'Descripción de la Operación (PLD)', 'text', 109, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_razon_operacion', 'Razón / Justificación (PLD)', 'text', 110, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_monto_moneda_nacional', 'Monto en Moneda Nacional (PLD)', 'price', 111, '15,2', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_tipo_cambio_aplicado', 'Tipo de Cambio Aplicado (PLD)', 'price', 112, '10,4', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 4.3 Referencia del Aviso
		$extrafields->addExtraField('pld_referencia_aviso', 'Referencia del Aviso (PLD)', 'varchar', 113, '14', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_prioridad', 'Prioridad del Aviso (PLD)', 'varchar', 114, '1', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 4.4 Alerta
		$extrafields->addExtraField('pld_tipo_alerta', 'Tipo de Alerta (PLD)', 'varchar', 115, '4', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_descripcion_alerta', 'Descripción de la Alerta (PLD)', 'text', 116, '', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 4.5 Acumulación
		$extrafields->addExtraField('pld_es_operacion_acumulada', '¿Operación Acumulada? (PLD)', 'boolean', 117, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_inicio_acumulacion', 'Fecha Inicio Acumulación (PLD)', 'date', 118, '', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_fin_acumulacion', 'Fecha Fin Acumulación (PLD)', 'date', 119, '', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_monto_acumulado_total', 'Monto Acumulado Total (PLD)', 'price', 120, '15,2', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 4.6 Control de Avisos
		$extrafields->addExtraField('pld_aviso_presentado', '¿Aviso Presentado? (PLD)', 'boolean', 121, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_presentacion', 'Fecha de Presentación (PLD)', 'date', 122, '', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_folio_aviso', 'Folio del Aviso SAT (PLD)', 'varchar', 123, '14', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_mes_reportado', 'Mes Reportado (PLD)', 'varchar', 124, '6', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_acuse_sat', 'Acuse SAT (PLD)', 'text', 125, '', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 4.7 Modificatorio
		$extrafields->addExtraField('pld_es_modificatorio', '¿Es Aviso Modificatorio? (PLD)', 'boolean', 126, '', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_folio_modificacion', 'Folio del Aviso a Modificar (PLD)', 'varchar', 127, '14', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_descripcion_modificacion', 'Descripción de la Modificación (PLD)', 'text', 128, '', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 4.8 Alertas y Aviso 24h
		$extrafields->addExtraField('pld_genera_alerta', '¿Genera Alerta Interna? (PLD)', 'boolean', 129, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_requiere_aviso_24hrs', '¿Requiere Aviso 24 Horas? (PLD)', 'boolean', 130, '', 'facture', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_razon_24hrs', 'Razón del Aviso 24 Horas (PLD)', 'text', 131, '', 'facture', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// =====================================================================
		// PAYMENT (llx_paiement_extrafields) — 29 campos
		// LFPIORPI Art. 17 Fracc. VIII: Datos de liquidación
		// =====================================================================

		// 5.1 Datos de Liquidación XSD
		$extrafields->addExtraField('pld_fecha_pago', 'Fecha de Pago (PLD)', 'date', 100, '', 'payment', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_forma_pago', 'Forma de Pago SAT (PLD)', 'varchar', 101, '1', 'payment', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_instrumento_monetario', 'Instrumento Monetario (PLD)', 'varchar', 102, '2', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_moneda', 'Moneda (PLD)', 'varchar', 103, '3', 'payment', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_monto_operacion', 'Monto de la Operación (PLD)', 'varchar', 104, '17', 'payment', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);

		// 5.2 Desglose por Forma de Pago
		$extrafields->addExtraField('pld_monto_efectivo', 'Monto en Efectivo (PLD)', 'price', 105, '15,2', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_monto_transferencia', 'Monto por Transferencia (PLD)', 'price', 106, '15,2', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_monto_cheque', 'Monto por Cheque (PLD)', 'price', 107, '15,2', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_monto_tarjeta', 'Monto por Tarjeta (PLD)', 'price', 108, '15,2', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_monto_otros', 'Monto por Otros Medios (PLD)', 'price', 109, '15,2', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 5.3 Datos Bancarios (Transferencia)
		$extrafields->addExtraField('pld_banco_origen', 'Banco Origen (PLD)', 'varchar', 110, '100', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_cuenta_origen', 'Cuenta Origen (últ. 4 dígitos) (PLD)', 'varchar', 111, '4', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_clabe_origen', 'CLABE Origen (PLD)', 'varchar', 112, '18', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_banco_destino', 'Banco Destino (PLD)', 'varchar', 113, '100', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_cuenta_destino', 'Cuenta Destino (últ. 4 dígitos) (PLD)', 'varchar', 114, '4', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_autorizacion', 'Número de Autorización (PLD)', 'varchar', 115, '20', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_transferencia', 'Fecha de Transferencia (PLD)', 'date', 116, '', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 5.4 Datos del Cheque
		$extrafields->addExtraField('pld_banco_cheque', 'Banco Emisor del Cheque (PLD)', 'varchar', 117, '100', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_cheque', 'Número de Cheque (PLD)', 'varchar', 118, '20', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_cuenta_cheque', 'Cuenta del Cheque (últ. 4 dígitos) (PLD)', 'varchar', 119, '4', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_fecha_cheque', 'Fecha del Cheque (PLD)', 'date', 120, '', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_librador_cheque', 'Librador del Cheque (PLD)', 'varchar', 121, '200', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 5.5 Datos de Tarjeta
		$extrafields->addExtraField('pld_tipo_tarjeta', 'Tipo de Tarjeta (PLD)', 'select', 122, '', 'payment', 0, 0, '', $optTipoTarjeta, 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_emisor_tarjeta', 'Emisor de la Tarjeta (PLD)', 'varchar', 123, '100', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_ultimos_digitos', 'Últimos 4 Dígitos Tarjeta (PLD)', 'varchar', 124, '4', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_numero_autorizacion_tarjeta', 'Número Autorización Tarjeta (PLD)', 'varchar', 125, '20', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// 5.6 Control de Efectivo
		$extrafields->addExtraField('pld_supera_limite_efectivo', '¿Supera Límite de Efectivo? (PLD)', 'boolean', 126, '', 'payment', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_alerta_efectivo', 'Alerta por Efectivo (PLD)', 'boolean', 127, '', 'payment', 0, 1, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_justificacion_efectivo', 'Justificación Uso de Efectivo (PLD)', 'text', 128, '', 'payment', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		// =====================================================================
		// COMMANDE (llx_commande_extrafields) — 4 campos
		// Pre-validación PLD en pedidos
		// =====================================================================

		$extrafields->addExtraField('pld_preventa_identificada', '¿Preventa Identificada? (PLD)', 'boolean', 100, '', 'commande', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_anticipo_estimado', 'Anticipo Estimado (PLD)', 'price', 101, '15,2', 'commande', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_forma_pago_planeada', 'Forma de Pago Planeada (PLD)', 'select', 102, '', 'commande', 0, 0, '', $optPagoPlaneado, 1, '',0, '', '', '', $l, $e);
		$extrafields->addExtraField('pld_alerta_previa', '¿Alerta Previa? (PLD)', 'boolean', 103, '', 'commande', 0, 0, '', '', 1, '',0, '', '', '', $l, $e);

		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}

	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
