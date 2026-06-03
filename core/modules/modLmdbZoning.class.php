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
		$this->config_page_url = array('setup.php@lmdbzoning');
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
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools', 'type' => 'left', 'titre' => 'Zoning', 'mainmenu' => 'tools', 'leftmenu' => 'lmdbzoning', 'url' => '/lmdbzoning/referencepoint_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1000, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2, 'picto' => '<span class="fas fa-map-marker-alt pictofixedwidth valignmiddle" style=""></span>');
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

		$result = $this->_init($sql, $options);
		if ($result > 0) {
			$this->syncEntityCronJob();
		}

		return $result;
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
		$savedConstants = $this->fetchLmdbZoningConstants();
		$this->syncMulticompanySharing(0);

		$result = $this->_remove($sql, $options);
		if ($result > 0 && !empty($savedConstants)) {
			$this->restoreLmdbZoningConstants($savedConstants);
		}

		return $result;
	}

	/**
	 * Fetch existing lmdbzoning constants before module deactivation.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function fetchLmdbZoningConstants()
	{
		$constants = array();
		$sql = 'SELECT name, value, type, visible, note, entity';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'const';
		$sql .= " WHERE name LIKE 'LMDBZONING\\_%'";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $constants;
		}
		while ($row = $this->db->fetch_object($resql)) {
			$constants[] = array(
				'name' => $row->name,
				'value' => $row->value,
				'type' => $row->type,
				'visible' => (int) $row->visible,
				'note' => $row->note,
				'entity' => (int) $row->entity,
			);
		}

		return $constants;
	}

	/**
	 * Restore lmdbzoning constants after module deactivation.
	 *
	 * @param array<int,array<string,mixed>> $constants Constants to restore
	 * @return void
	 */
	private function restoreLmdbZoningConstants(array $constants)
	{
		if (!function_exists('dolibarr_set_const')) {
			return;
		}
		foreach ($constants as $constant) {
			if (empty($constant['name'])) {
				continue;
			}
			dolibarr_set_const(
				$this->db,
				$constant['name'],
				isset($constant['value']) ? $constant['value'] : '',
				empty($constant['type']) ? 'chaine' : $constant['type'],
				isset($constant['visible']) ? (int) $constant['visible'] : 0,
				isset($constant['note']) ? $constant['note'] : '',
				isset($constant['entity']) ? (int) $constant['entity'] : 1
			);
		}
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

	/**
	 * Ensure the module cron job exists in the current entity.
	 *
	 * Dolibarr normally creates cron rows from $this->cronjobs during _init(),
	 * but this explicit sync keeps the job visible per entity on installations
	 * where the native registration did not materialize it.
	 *
	 * @return void
	 */
	private function syncEntityCronJob()
	{
		global $conf;

		$columns = $this->getCronJobColumns();
		if (empty($columns['rowid'])) {
			return;
		}

		$classField = !empty($columns['classesname']) ? 'classesname' : (!empty($columns['class']) ? 'class' : '');
		$methodField = !empty($columns['methodename']) ? 'methodename' : (!empty($columns['method']) ? 'method' : '');
		if ($classField === '' || $methodField === '') {
			return;
		}

		$values = array(
			'label' => 'LmdbZoningCronRecalculate',
			'jobtype' => 'method',
			$classField => '/lmdbzoning/class/lmdbzoningservice.class.php',
			'objectname' => 'LmdbZoningService',
			$methodField => 'cronRecalculatePending',
			'params' => '',
			'parameters' => '',
			'md5params' => md5(''),
			'module_name' => 'lmdbzoning',
			'comment' => 'Recalculate pending lmdbzoning rows',
			'command' => '',
			'frequency' => 3600,
			'unitfrequency' => 3600,
			'status' => 0,
			'processing' => 0,
			'priority' => 0,
			'test' => '$conf->lmdbzoning->enabled && !empty($conf->global->LMDBZONING_CRON_ENABLED)',
		);
		if (!empty($columns['entity'])) {
			$values['entity'] = (int) $conf->entity;
		}

		$where = array();
		if (!empty($columns['entity'])) {
			$where[] = 'entity = '.((int) $conf->entity);
		}
		$where[] = $classField." = '".$this->db->escape($values[$classField])."'";
		if (!empty($columns['objectname'])) {
			$where[] = "objectname = '".$this->db->escape($values['objectname'])."'";
		}
		$where[] = $methodField." = '".$this->db->escape($values[$methodField])."'";

		$rowid = 0;
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'cronjob WHERE '.implode(' AND ', $where);
		$resql = $this->db->query($sql);
		if ($resql && ($row = $this->db->fetch_object($resql))) {
			$rowid = (int) $row->rowid;
		}

		if ($rowid > 0) {
			$set = array();
			foreach ($values as $field => $value) {
				if (empty($columns[$field]) || $field === 'entity' || $field === 'status') {
					continue;
				}
				$set[] = $field.' = '.$this->formatCronJobSqlValue($value);
			}
			if (!empty($set)) {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'cronjob SET '.implode(', ', $set).' WHERE rowid = '.$rowid;
				$this->db->query($sql);
			}
			return;
		}

		if (!empty($columns['datec'])) {
			$values['datec'] = array('sql' => $this->db->idate(dol_now()));
		}

		$fields = array();
		$sqlValues = array();
		foreach ($values as $field => $value) {
			if (empty($columns[$field])) {
				continue;
			}
			$fields[] = $field;
			$sqlValues[] = $this->formatCronJobSqlValue($value);
		}
		if (empty($fields)) {
			return;
		}

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'cronjob ('.implode(', ', $fields).') VALUES ('.implode(', ', $sqlValues).')';
		$this->db->query($sql);
	}

	/**
	 * Return available columns of Dolibarr cronjob table.
	 *
	 * @return array<string,int>
	 */
	private function getCronJobColumns()
	{
		$columns = array();
		$sql = 'SHOW COLUMNS FROM '.MAIN_DB_PREFIX.'cronjob';
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $columns;
		}
		while ($row = $this->db->fetch_object($resql)) {
			$columns[strtolower($row->Field)] = 1;
		}

		return $columns;
	}

	/**
	 * Format a value for a cronjob SQL assignment.
	 *
	 * @param mixed $value Value to format
	 * @return string
	 */
	private function formatCronJobSqlValue($value)
	{
		if ($value === null) {
			return 'null';
		}
		if (is_array($value) && isset($value['sql'])) {
			return (string) $value['sql'];
		}
		if (is_int($value) || is_float($value)) {
			return (string) $value;
		}

		return "'".$this->db->escape((string) $value)."'";
	}
}
