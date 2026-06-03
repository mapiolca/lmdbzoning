<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Module descriptor for lmdbzoning.
 */
class modLmdbZoning extends DolibarrModules
{
	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->numero = 450022;
		$this->rights_class = 'lmdbzoning';
		$this->family = 'technic';
		$this->module_position = 500;
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = 'LmdbZoningDesc';
		$this->descriptionlong = 'LmdbZoningDescLong';
		$this->version = '1.0.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'map-marker-alt';
		$this->editor_name = 'Les Métiers du Bâtiment';
		$this->editor_url = 'https://lesmetiersdubatiment.fr';
		$this->phpmin = array(8, 0);
		$this->need_dolibarr_version = array(20, 0);
		$this->langfiles = array('lmdbzoning@lmdbzoning');
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->config_page_url = array('setup.php@lmdbzoning', 'compatibility.php@lmdbzoning');
		$this->hidden = false;
		$this->dirs = array('/lmdbzoning/temp');

		$this->module_parts = array(
			'api' => 1,
			'triggers' => 1,
			'substitutions' => 1,
			'hooks' => array(
				'data' => array(
					'multicompanyexternalmodulesharing',
					'multicompanyexternalmodules',
					'multicompanysharingoptions',
					'propalcard',
					'ordercard',
					'contractcard',
					'projectcard',
					'fichintercard',
					'powerplantpvcard',
					'timesheetweekcard',
				),
				'entity' => '0',
			),
		);

		$this->const = array(
			array('LMDBZONING_GEOCODER_ENABLED', 'chaine', '0', 'Enable geocoder', 0, 'current', 1),
			array('LMDBZONING_GEOCODER_PROVIDER', 'chaine', 'geoplateforme', 'Geocoder provider', 0, 'current', 1),
			array('LMDBZONING_GEOCODER_API_URL', 'chaine', 'https://data.geopf.fr/geocodage/search', 'Geocoder API URL', 0, 'current', 1),
			array('LMDBZONING_GEOCODER_TIMEOUT', 'chaine', '5', 'Geocoder timeout', 0, 'current', 1),
			array('LMDBZONING_CACHE_DURATION_DAYS', 'chaine', '365', 'Cache duration', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY', 'chaine', '0', 'Auto apply category', 0, 'current', 1),
			array('LMDBZONING_ALLOW_MANUAL_OVERRIDE', 'chaine', '1', 'Allow manual override', 0, 'current', 1),
			array('LMDBZONING_DEFAULT_PROFILE', 'chaine', 'MAINT_PV_RES_1_9KWC', 'Default profile', 0, 'current', 1),
			array('LMDBZONING_CRON_ENABLED', 'chaine', '0', 'Enable cron', 0, 'current', 1),
			array('LMDBZONING_CRON_MAX_ITEMS', 'chaine', '50', 'Cron max items', 0, 'current', 1),
			array('LMDBZONING_CRON_RETRY_FAILED', 'chaine', '0', 'Retry failed rows', 0, 'current', 1),
			array('LMDBZONING_CRON_RECALCULATE_AFTER_DAYS', 'chaine', '0', 'Periodic recalculation', 0, 'current', 1),
		);

		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();

		$r = 0;
		$this->rights[$r][0] = $this->numero + 1;
		$this->rights[$r][1] = 'Read lmdbzoning data';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero + 2;
		$this->rights[$r][1] = 'Create/modify lmdbzoning data';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero + 3;
		$this->rights[$r][1] = 'Delete lmdbzoning data';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'delete';
		$r++;
		$this->rights[$r][0] = $this->numero + 4;
		$this->rights[$r][1] = 'Run geocoding';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'geocode';
		$r++;
		$this->rights[$r][0] = $this->numero + 5;
		$this->rights[$r][1] = 'Override object zones';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'override';
		$r++;
		$this->rights[$r][0] = $this->numero + 6;
		$this->rights[$r][1] = 'Administer lmdbzoning';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'admin';
		$r++;
		$this->rights[$r][0] = $this->numero + 7;
		$this->rights[$r][1] = 'Use lmdbzoning API';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'api';
		$r++;

		$r = 0;
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools', 'type' => 'left', 'titre' => 'Zoning', 'mainmenu' => 'tools', 'leftmenu' => 'lmdbzoning', 'url' => '/lmdbzoning/referencepoint_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1000, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'ReferencePoints', 'mainmenu' => 'tools', 'leftmenu' => 'referencepoints', 'url' => '/lmdbzoning/referencepoint_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1010, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'ZoningProfiles', 'mainmenu' => 'tools', 'leftmenu' => 'profiles', 'url' => '/lmdbzoning/profile_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1020, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'ObjectZoneResults', 'mainmenu' => 'tools', 'leftmenu' => 'objectzones', 'url' => '/lmdbzoning/objectzone_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1030, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'GeocodeCache', 'mainmenu' => 'tools', 'leftmenu' => 'cache', 'url' => '/lmdbzoning/geocode_cache_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1040, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'CalculationLog', 'mainmenu' => 'tools', 'leftmenu' => 'logs', 'url' => '/lmdbzoning/calculation_log_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1050, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'Settings', 'mainmenu' => 'tools', 'leftmenu' => 'settings', 'url' => '/lmdbzoning/admin/setup.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1060, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "admin")', 'target' => '', 'user' => 2);

		$this->cronjobs = array(
			0 => array(
				'label' => 'LmdbZoningCronRecalculate',
				'jobtype' => 'method',
				'class' => '/lmdbzoning/class/lmdbzoningservice.class.php',
				'objectname' => 'LmdbZoningService',
				'method' => 'cronRecalculatePending',
				'parameters' => '',
				'comment' => 'Recalculate pending lmdbzoning rows',
				'frequency' => 3600,
				'unitfrequency' => 3600,
				'status' => 0,
				'test' => '$conf->lmdbzoning->enabled && !empty($conf->global->LMDBZONING_CRON_ENABLED)',
			),
		);
	}

	/**
	 * Module initialization.
	 *
	 * @param string $options Options
	 * @return int
	 */
	public function init($options = '')
	{
		global $conf;

		$sql = array();
		$result = $this->_load_tables('/lmdbzoning/sql/');
		if ($result < 0) {
			return -1;
		}

		$this->syncMulticompanySharing(1);

		return $this->_init($sql, $options);
	}

	/**
	 * Module removal.
	 *
	 * @param string $options Options
	 * @return int
	 */
	public function remove($options = '')
	{
		$sql = array();
		$this->syncMulticompanySharing(0);

		return $this->_remove($sql, $options);
	}

	/**
	 * Synchronize Multicompany external module sharing payload.
	 *
	 * @param int $enable 1=merge, 0=remove module payload
	 * @return void
	 */
	private function syncMulticompanySharing($enable)
	{
		global $conf;

		dol_include_once('/lmdbzoning/class/actions_lmdbzoning.class.php');
		if (!class_exists('ActionsLmdbZoning') || !function_exists('dolibarr_set_const')) {
			return;
		}

		$current = array();
		if (!empty($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING)) {
			$decoded = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);
			if (is_array($decoded)) {
				$current = $decoded;
			}
		}
		if ($enable) {
			$current = array_replace_recursive($current, ActionsLmdbZoning::getMulticompanySharingDefinition());
		} else {
			unset($current['lmdbzoning']);
		}
		dolibarr_set_const($this->db, 'MULTICOMPANY_EXTERNAL_MODULES_SHARING', json_encode($current), 'chaine', 0, '', (int) $conf->entity);
	}
}
