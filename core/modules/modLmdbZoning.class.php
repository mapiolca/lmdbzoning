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
		$this->family = 'Les Métiers du Bâtiment';
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
					'category',
					'propalcard',
					'propaldao',
					'ordercard',
					'commandedao',
					'contractcard',
					'contratdao',
					'facturedao',
					'projectcard',
					'projectdao',
					'fichintercard',
					'fichinterdao',
					'powerplantpvcard',
					'powerplantdao',
					'timesheetweekcard',
					'timesheetweekdao',
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
			array('LMDBZONING_AUTO_APPLY_CATEGORY_SOCIETE', 'chaine', '0', 'Auto apply category to third parties', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_CONTACT', 'chaine', '0', 'Auto apply category to contacts', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_PROPAL', 'chaine', '0', 'Auto apply category to proposals', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_COMMANDE', 'chaine', '0', 'Auto apply category to orders', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_FACTURE', 'chaine', '0', 'Auto apply category to invoices', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_CONTRACT', 'chaine', '0', 'Auto apply category to contracts', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_PROJECT', 'chaine', '0', 'Auto apply category to projects', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_FICHINTER', 'chaine', '0', 'Auto apply category to interventions', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_TIMESHEETWEEK', 'chaine', '0', 'Auto apply category to weekly timesheets', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_POWERPLANTPV', 'chaine', '0', 'Auto apply category to photovoltaic power plants', 0, 'current', 1),
			array('LMDBZONING_AUTO_APPLY_CATEGORY_MIGRATED', 'chaine', '0', 'Automatic category settings migration marker', 0, 'current', 1),
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
		$r++;
		$this->rights[$r][0] = $this->numero * 100 + $r;
		$this->rights[$r][1] = 'Read lmdbzoning data';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero * 100 + $r;
		$this->rights[$r][1] = 'Create/modify lmdbzoning data';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero * 100 + $r;
		$this->rights[$r][1] = 'Delete lmdbzoning data';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'delete';
		$r++;
		$this->rights[$r][0] = $this->numero * 100 + $r;
		$this->rights[$r][1] = 'Run geocoding';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'geocode';
		$r++;
		$this->rights[$r][0] = $this->numero * 100 + $r;
		$this->rights[$r][1] = 'Override object zones';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'override';
		$r++;
		$this->rights[$r][0] = $this->numero * 100 + $r;
		$this->rights[$r][1] = 'Administer lmdbzoning';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'admin';
		$r++;
		$this->rights[$r][0] = $this->numero * 100 + $r;
		$this->rights[$r][1] = 'Use lmdbzoning API';
		$this->rights[$r][4] = 'lmdbzoning';
		$this->rights[$r][5] = 'api';

		$r = 0;
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools', 'type' => 'left', 'titre' => 'Zoning', 'mainmenu' => 'tools', 'leftmenu' => 'lmdbzoning', 'url' => '/lmdbzoning/referencepoint_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1000, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2, 'prefix' => img_picto('', 'fa-map-marker-alt', 'class="pictofixedwidth valignmiddle"'));
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'ReferencePoints', 'mainmenu' => 'tools', 'leftmenu' => 'referencepoints', 'url' => '/lmdbzoning/referencepoint_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1010, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'ZoningProfiles', 'mainmenu' => 'tools', 'leftmenu' => 'profiles', 'url' => '/lmdbzoning/profile_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1020, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'ObjectZoneResults', 'mainmenu' => 'tools', 'leftmenu' => 'objectzones', 'url' => '/lmdbzoning/objectzone_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1030, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'GeocodeCache', 'mainmenu' => 'tools', 'leftmenu' => 'cache', 'url' => '/lmdbzoning/geocode_cache_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1040, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);
		$this->menu[$r++] = array('fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=lmdbzoning', 'type' => 'left', 'titre' => 'CalculationLog', 'mainmenu' => 'tools', 'leftmenu' => 'logs', 'url' => '/lmdbzoning/calculation_log_list.php', 'langs' => 'lmdbzoning@lmdbzoning', 'position' => 1050, 'enabled' => '$conf->lmdbzoning->enabled', 'perms' => '$user->hasRight("lmdbzoning", "lmdbzoning", "read")', 'target' => '', 'user' => 2);

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

		$this->syncProfileRefUniqueIndex();
		$result = $this->_load_tables('/lmdbzoning/sql/');
		if ($result < 0) {
			return -1;
		}
		$this->syncProfileRefUniqueIndex();
		$result = $this->migrateAutomaticCategoryConstants();
		if ($result < 0) {
			return -1;
		}

		$this->syncMulticompanySharing(1);

		$sql = $this->getPermissionMigrationSql();
		$result = $this->_init($sql, $options);
		if ($result > 0) {
			$this->syncEntityCronJob();
		}

		return $result;
	}

	/**
	 * Migrate the legacy global automatic-category switch to per-object settings.
	 *
	 * Existing per-object values are preserved. Definitions are initialized even
	 * when their target module is currently disabled so a later activation keeps
	 * the historical behavior.
	 *
	 * @return int 1=done or already migrated, -1=error
	 */
	private function migrateAutomaticCategoryConstants()
	{
		global $conf;

		$markerName = 'LMDBZONING_AUTO_APPLY_CATEGORY_MIGRATED';
		$marker = $this->fetchCurrentEntityConstant($markerName);
		if ($marker === null) {
			return -1;
		}
		if (!empty($marker['found']) && (int) $marker['value'] === 1) {
			return 1;
		}

		dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
		if (!class_exists('LmdbZoningService') || !function_exists('dolibarr_set_const')) {
			$this->error = 'Unable to load automatic categorization migration dependencies';
			return -1;
		}

		$legacyValue = getDolGlobalInt('LMDBZONING_AUTO_APPLY_CATEGORY');
		$definitions = LmdbZoningService::getAutomaticCategorizationDefinitions(0);
		foreach ($definitions as $definition) {
			$constantName = !empty($definition['auto_category_constant']) ? (string) $definition['auto_category_constant'] : '';
			if ($constantName === '') {
				continue;
			}
			$current = $this->fetchCurrentEntityConstant($constantName);
			if ($current === null) {
				return -1;
			}
			if (!empty($current['found'])) {
				continue;
			}
			$result = dolibarr_set_const($this->db, $constantName, (string) $legacyValue, 'chaine', 0, '', (int) $conf->entity);
			if ($result < 0) {
				$this->error = 'Failed to migrate automatic categorization constant '.$constantName;
				return -1;
			}
		}

		$result = dolibarr_set_const($this->db, $markerName, '1', 'chaine', 0, '', (int) $conf->entity);
		if ($result < 0) {
			$this->error = 'Failed to store automatic categorization migration marker';
			return -1;
		}

		return 1;
	}

	/**
	 * Fetch a module constant from the active entity.
	 *
	 * @param string $name Constant name
	 * @return array{found:bool,value:string}|null Null on SQL error
	 */
	private function fetchCurrentEntityConstant($name)
	{
		global $conf;

		$sql = 'SELECT value FROM '.MAIN_DB_PREFIX.'const';
		$sql .= " WHERE name = '".$this->db->escape((string) $name)."'";
		$sql .= ' AND entity = '.((int) $conf->entity);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return null;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!is_object($obj)) {
			return array('found' => false, 'value' => '');
		}

		return array('found' => true, 'value' => (string) $obj->value);
	}

	/**
	 * Build the idempotent native permission assignment migration for this entity.
	 *
	 * @return array<int,string>
	 */
	private function getPermissionMigrationSql()
	{
		global $conf;

		$sql = array();
		$entity = (int) $conf->entity;
		$legacyIds = array(450023, 450024, 450025, 450026, 450027, 450028, 450029);
		foreach ($legacyIds as $offset => $legacyId) {
			$newId = $this->numero * 100 + $offset + 1;
			$sql[] = 'INSERT IGNORE INTO '.MAIN_DB_PREFIX.'user_rights (entity, fk_user, fk_id)'
				.' SELECT entity, fk_user, '.$newId.' FROM '.MAIN_DB_PREFIX.'user_rights'
				.' WHERE entity = '.$entity.' AND fk_id = '.$legacyId;
			$sql[] = 'INSERT IGNORE INTO '.MAIN_DB_PREFIX.'usergroup_rights (entity, fk_usergroup, fk_id)'
				.' SELECT entity, fk_usergroup, '.$newId.' FROM '.MAIN_DB_PREFIX.'usergroup_rights'
				.' WHERE entity = '.$entity.' AND fk_id = '.$legacyId;
		}

		$legacyIdList = implode(', ', $legacyIds);
		$sql[] = 'DELETE FROM '.MAIN_DB_PREFIX.'user_rights WHERE entity = '.$entity.' AND fk_id IN ('.$legacyIdList.')';
		$sql[] = 'DELETE FROM '.MAIN_DB_PREFIX.'usergroup_rights WHERE entity = '.$entity.' AND fk_id IN ('.$legacyIdList.')';
		$sql[] = 'DELETE FROM '.MAIN_DB_PREFIX.'rights_def WHERE entity = '.$entity.' AND id IN ('.$legacyIdList.')';

		return $sql;
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
	 * @param int $enable 1=merge module payload
	 * @return void
	 */
	private function syncMulticompanySharing($enable)
	{
		global $conf;

		dol_include_once('/lmdbzoning/class/actions_lmdbzoning.class.php');
		if (!class_exists('ActionsLmdbZoning') || !function_exists('dolibarr_set_const')) {
			return;
		}
		if (empty($enable)) {
			return;
		}

		$current = array();
		if (!empty($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING)) {
			$decoded = json_decode($conf->global->MULTICOMPANY_EXTERNAL_MODULES_SHARING, true);
			if (is_array($decoded)) {
				$current = $decoded;
			}
		}
		$current = array_replace_recursive(ActionsLmdbZoning::getMulticompanySharingDefinition(), $current);
		dolibarr_set_const($this->db, 'MULTICOMPANY_EXTERNAL_MODULES_SHARING', json_encode($current), 'chaine', 0, '', (int) $conf->entity);
	}

	/**
	 * Ensure profile references are unique per entity, not globally.
	 *
	 * @return void
	 */
	private function syncProfileRefUniqueIndex()
	{
		$indexes = $this->getTableIndexes('lmdbzoning_profile');
		if (empty($indexes)) {
			return;
		}

		$expectedName = 'uk_lmdbzoning_profile_ref';
		$expectedColumns = array('entity', 'ref');
		$hasExpectedIndex = false;
		$indexesToDrop = array();

		foreach ($indexes as $indexName => $index) {
			if (empty($index['unique'])) {
				continue;
			}
			$columns = $index['columns'];
			if ($indexName === $expectedName && $columns === $expectedColumns) {
				$hasExpectedIndex = true;
				continue;
			}
			if ($indexName === $expectedName || $columns === array('ref')) {
				$indexesToDrop[] = $indexName;
			}
		}

		foreach (array_unique($indexesToDrop) as $indexName) {
			$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'lmdbzoning_profile DROP INDEX '.$this->quoteSqlIdentifier($indexName);
			if (!$this->db->query($sql) && function_exists('dol_syslog')) {
				dol_syslog(__METHOD__.' failed to drop index '.$indexName.': '.$this->db->lasterror(), LOG_WARNING);
			}
		}

		if ($hasExpectedIndex && empty($indexesToDrop)) {
			return;
		}

		$indexes = $this->getTableIndexes('lmdbzoning_profile');
		if (!empty($indexes[$expectedName]) && !empty($indexes[$expectedName]['unique']) && $indexes[$expectedName]['columns'] === $expectedColumns) {
			return;
		}

		$sql = 'ALTER TABLE '.MAIN_DB_PREFIX.'lmdbzoning_profile ADD UNIQUE INDEX '.$expectedName.' (entity, ref)';
		if (!$this->db->query($sql) && function_exists('dol_syslog')) {
			dol_syslog(__METHOD__.' failed to create profile entity/ref unique index: '.$this->db->lasterror(), LOG_WARNING);
		}
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
	 * Return table indexes keyed by index name.
	 *
	 * @param string $tableElement Table name without MAIN_DB_PREFIX
	 * @return array<string,array{unique:int,columns:array<int,string>}>
	 */
	private function getTableIndexes($tableElement)
	{
		$indexes = array();
		$sql = 'SHOW INDEX FROM '.MAIN_DB_PREFIX.$tableElement;
		$resql = $this->db->query($sql);
		if (!$resql) {
			return $indexes;
		}
		while ($row = $this->db->fetch_object($resql)) {
			$keyName = (string) $row->Key_name;
			if (!isset($indexes[$keyName])) {
				$indexes[$keyName] = array('unique' => empty($row->Non_unique) ? 1 : 0, 'columns' => array());
			}
			$indexes[$keyName]['columns'][(int) $row->Seq_in_index] = (string) $row->Column_name;
		}
		foreach ($indexes as $keyName => $index) {
			ksort($indexes[$keyName]['columns']);
			$indexes[$keyName]['columns'] = array_values($indexes[$keyName]['columns']);
		}

		return $indexes;
	}

	/**
	 * Quote an SQL identifier.
	 *
	 * @param string $identifier Identifier
	 * @return string
	 */
	private function quoteSqlIdentifier($identifier)
	{
		return '`'.str_replace('`', '``', $identifier).'`';
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
