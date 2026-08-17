<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

define('DOL_DOCUMENT_ROOT', __DIR__.'/stubs');
define('MAIN_DB_PREFIX', 'llx_');

/**
 * Minimal SQL result used by migration tests.
 */
class LmdbZoningMigrationTestResult
{
	/** @var array<int,object> */
	public $rows = array();

	/** @var int */
	public $index = 0;
}

/**
 * In-memory constant store implementing the DoliDB calls used by migration.
 */
class LmdbZoningMigrationTestDb
{
	/** @var array<int,array<string,string>> */
	public $constants = array();

	/** @var string */
	private $lastError = '';

	/**
	 * @param string $sql SQL query
	 * @return LmdbZoningMigrationTestResult|false
	 */
	public function query($sql)
	{
		$result = new LmdbZoningMigrationTestResult();
		if (!preg_match("~SELECT value FROM llx_const WHERE name = '([^']+)' AND entity = ([0-9]+)~", (string) $sql, $matches)) {
			$this->lastError = 'Unexpected SQL: '.$sql;
			return false;
		}
		$name = (string) $matches[1];
		$entity = (int) $matches[2];
		if (isset($this->constants[$entity]) && array_key_exists($name, $this->constants[$entity])) {
			$result->rows[] = (object) array('value' => $this->constants[$entity][$name]);
		}

		return $result;
	}

	/**
	 * @param LmdbZoningMigrationTestResult $result SQL result
	 * @return object|false
	 */
	public function fetch_object($result)
	{
		if ($result->index >= count($result->rows)) {
			return false;
		}

		return $result->rows[$result->index++];
	}

	/** @param mixed $result SQL result */
	public function free($result)
	{
		return true;
	}

	/** @param string $value Value */
	public function escape($value)
	{
		return str_replace("'", "''", (string) $value);
	}

	/** @return string */
	public function lasterror()
	{
		return $this->lastError;
	}
}

if (!function_exists('dol_include_once')) {
	function dol_include_once($path) {
		return 0;
	}
}
if (!function_exists('isModEnabled')) {
	function isModEnabled($module) {
		return false;
	}
}
if (!function_exists('img_picto')) {
	function img_picto($label, $picto, $morecss = '') {
		return '';
	}
}
if (!function_exists('getDolGlobalInt')) {
	function getDolGlobalInt($name, $default = 0) {
		global $conf;

		$db = $GLOBALS['lmdbzoning_migration_test_db'];
		$entity = (int) $conf->entity;
		return isset($db->constants[$entity]) && array_key_exists((string) $name, $db->constants[$entity])
			? (int) $db->constants[$entity][(string) $name]
			: (int) $default;
	}
}
if (!function_exists('dolibarr_set_const')) {
	function dolibarr_set_const($db, $name, $value, $type = 'chaine', $visible = 0, $note = '', $entity = 1) {
		if (!isset($db->constants[(int) $entity])) {
			$db->constants[(int) $entity] = array();
		}
		$db->constants[(int) $entity][(string) $name] = (string) $value;

		return 1;
	}
}

$conf = new stdClass();
$conf->entity = 1;
$langs = null;
$db = new LmdbZoningMigrationTestDb();
$GLOBALS['lmdbzoning_migration_test_db'] = $db;

require_once __DIR__.'/../class/lmdbzoningservice.class.php';
require_once __DIR__.'/../core/modules/modLmdbZoning.class.php';

$migrationMethod = new ReflectionMethod(modLmdbZoning::class, 'migrateAutomaticCategoryConstants');
$permissionMethod = new ReflectionMethod(modLmdbZoning::class, 'getPermissionMigrationSql');
if (PHP_VERSION_ID < 80100) {
	$migrationMethod->setAccessible(true);
	$permissionMethod->setAccessible(true);
}

$db->constants[1] = array('LMDBZONING_AUTO_APPLY_CATEGORY' => '1');
$descriptor = new modLmdbZoning($db);
assert(array_keys($descriptor->rights) === range(1, 7), 'Permission offsets must run from 1 to 7');
foreach ($descriptor->rights as $offset => $right) {
	assert((int) $right[0] === 45002200 + $offset, 'Permission '.$offset.' must use the module native range');
}
assert($migrationMethod->invoke($descriptor) === 1, 'Entity 1 migration must succeed');
foreach (LmdbZoningService::getAutomaticCategorizationDefinitions(0) as $definition) {
	$constantName = (string) $definition['auto_category_constant'];
	assert($db->constants[1][$constantName] === '1', 'Legacy enabled state must initialize '.$constantName.' in entity 1');
}
assert($db->constants[1]['LMDBZONING_AUTO_APPLY_CATEGORY_MIGRATED'] === '1', 'Entity 1 migration marker must be stored');

$db->constants[1]['LMDBZONING_AUTO_APPLY_CATEGORY'] = '0';
assert($migrationMethod->invoke($descriptor) === 1, 'Entity 1 reactivation must be idempotent');
assert($db->constants[1]['LMDBZONING_AUTO_APPLY_CATEGORY_SOCIETE'] === '1', 'Reactivation must not overwrite migrated settings');

$conf->entity = 2;
$db->constants[2] = array(
	'LMDBZONING_AUTO_APPLY_CATEGORY' => '0',
	'LMDBZONING_AUTO_APPLY_CATEGORY_SOCIETE' => '1',
);
$descriptorEntity2 = new modLmdbZoning($db);
assert($migrationMethod->invoke($descriptorEntity2) === 1, 'Entity 2 migration must succeed');
assert($db->constants[2]['LMDBZONING_AUTO_APPLY_CATEGORY_SOCIETE'] === '1', 'Existing partial constant must be preserved');
assert($db->constants[2]['LMDBZONING_AUTO_APPLY_CATEGORY_PROJECT'] === '0', 'Missing constants must inherit the disabled legacy state');
assert(count(array_filter(array_keys($db->constants[2]), function ($name) {
	return strpos($name, 'LMDBZONING_AUTO_APPLY_CATEGORY_') === 0 && $name !== 'LMDBZONING_AUTO_APPLY_CATEGORY_MIGRATED';
})) === 10, 'All object constants must be initialized even when target modules are disabled');

$permissionSql = $permissionMethod->invoke($descriptorEntity2);
assert(count($permissionSql) === 17, 'Permission migration must contain fourteen copies and three cleanup statements');
assert(strpos($permissionSql[0], 'fk_id = 450023') !== false && strpos($permissionSql[0], '45002201') !== false, 'First user permission must migrate to the native range');
assert(strpos($permissionSql[13], 'fk_id = 450029') !== false && strpos($permissionSql[13], '45002207') !== false, 'Last group permission must migrate to the native range');
foreach ($permissionSql as $sql) {
	assert(strpos($sql, 'entity = 2') !== false, 'Every permission migration query must be scoped to the current entity');
}
assert(strpos($permissionSql[14], 'DELETE FROM llx_user_rights') === 0, 'Legacy user assignments must only be deleted after all copies');
assert(strpos($permissionSql[15], 'DELETE FROM llx_usergroup_rights') === 0, 'Legacy group assignments must only be deleted after all copies');
assert(strpos($permissionSql[16], 'DELETE FROM llx_rights_def') === 0, 'Legacy definitions must only be deleted after all copies');

echo "lmdbzoning migration tests passed\n";
