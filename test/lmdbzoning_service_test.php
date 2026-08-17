<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

if (!function_exists('dol_include_once')) {
	function dol_include_once($path) {
		return 0;
	}
}
if (!function_exists('dol_strtolower')) {
	function dol_strtolower($value) {
		return strtolower($value);
	}
}
if (!function_exists('isModEnabled')) {
	function isModEnabled($module) {
		return !empty($GLOBALS['lmdbzoning_test_modules'][(string) $module]);
	}
}
if (!function_exists('getDolGlobalInt')) {
	function getDolGlobalInt($name, $default = 0) {
		return array_key_exists((string) $name, $GLOBALS['lmdbzoning_test_constants'])
			? (int) $GLOBALS['lmdbzoning_test_constants'][(string) $name]
			: (int) $default;
	}
}

$GLOBALS['lmdbzoning_test_modules'] = array();
$GLOBALS['lmdbzoning_test_constants'] = array();

require_once __DIR__.'/../class/geocoder.class.php';
require_once __DIR__.'/../class/lmdbzoningservice.class.php';

$service = new LmdbZoningService(null, new stdClass());
$distance = $service->getAirDistanceKm(44.604, -0.936, 44.8378, -0.5792);
assert($distance > 37 && $distance < 40, 'Haversine distance should be around 38 km');

$geocoder = new LmdbZoningGeocoder(null);
$normalized = $geocoder->normalizeAddress(array(
	'address' => ' 4 rue Alfred Kastler ',
	'zip' => '33380',
	'town' => 'Mios',
	'country_code' => 'FR',
));
assert($normalized === '4 rue alfred kastler 33380 mios fr', 'Address normalization must be stable');
assert(strlen($geocoder->getAddressHash($normalized)) === 64, 'Address hash must be sha256 length');

$expectedConstants = array(
	'societe' => 'LMDBZONING_AUTO_APPLY_CATEGORY_SOCIETE',
	'contact' => 'LMDBZONING_AUTO_APPLY_CATEGORY_CONTACT',
	'propal' => 'LMDBZONING_AUTO_APPLY_CATEGORY_PROPAL',
	'commande' => 'LMDBZONING_AUTO_APPLY_CATEGORY_COMMANDE',
	'facture' => 'LMDBZONING_AUTO_APPLY_CATEGORY_FACTURE',
	'contract' => 'LMDBZONING_AUTO_APPLY_CATEGORY_CONTRACT',
	'project' => 'LMDBZONING_AUTO_APPLY_CATEGORY_PROJECT',
	'fichinter' => 'LMDBZONING_AUTO_APPLY_CATEGORY_FICHINTER',
	'timesheetweek' => 'LMDBZONING_AUTO_APPLY_CATEGORY_TIMESHEETWEEK',
	'powerplantpv' => 'LMDBZONING_AUTO_APPLY_CATEGORY_POWERPLANTPV',
);
foreach ($expectedConstants as $elementType => $constantName) {
	assert(LmdbZoningService::getAutomaticCategoryConstantName($elementType) === $constantName, 'Unexpected constant for '.$elementType);
}
assert(LmdbZoningService::getAutomaticCategoryConstantName('order') === $expectedConstants['commande'], 'Order alias must use the order constant');
assert(LmdbZoningService::getAutomaticCategoryConstantName('invoice') === $expectedConstants['facture'], 'Invoice alias must use the invoice constant');
assert(LmdbZoningService::getAutomaticCategoryConstantName('contrat') === $expectedConstants['contract'], 'Contract alias must use the contract constant');
assert(LmdbZoningService::getAutomaticCategoryConstantName('projet') === $expectedConstants['project'], 'Project alias must use the project constant');
assert(LmdbZoningService::getAutomaticCategoryConstantName('powerplant') === $expectedConstants['powerplantpv'], 'Power plant alias must use the power plant constant');
assert(LmdbZoningService::getAutomaticCategoryConstantName('unsupported') === '', 'Unsupported objects must not expose a constant');

$GLOBALS['lmdbzoning_test_modules'] = array('societe' => 1, 'project' => 1);
$enabledDefinitions = LmdbZoningService::getAutomaticCategorizationDefinitions(1);
assert(array_keys($enabledDefinitions) === array('societe', 'contact', 'project'), 'Only canonical objects from enabled modules must be listed');
assert(count(LmdbZoningService::getAutomaticCategorizationDefinitions(0)) === 10, 'All canonical definitions must be available for migration');

$GLOBALS['lmdbzoning_test_constants'] = array('LMDBZONING_AUTO_APPLY_CATEGORY' => 1);
assert(LmdbZoningService::isAutomaticCategoryApplicationEnabled('societe') === true, 'Legacy enabled setting must remain the pre-migration fallback');
$GLOBALS['lmdbzoning_test_constants'] = array(
	'LMDBZONING_AUTO_APPLY_CATEGORY' => 1,
	'LMDBZONING_AUTO_APPLY_CATEGORY_MIGRATED' => 1,
	'LMDBZONING_AUTO_APPLY_CATEGORY_SOCIETE' => 0,
	'LMDBZONING_AUTO_APPLY_CATEGORY_PROJECT' => 1,
	'LMDBZONING_AUTO_APPLY_CATEGORY_COMMANDE' => 0,
);
assert(LmdbZoningService::isAutomaticCategoryApplicationEnabled('societe') === false, 'Third-party switch must be independent');
assert(LmdbZoningService::isAutomaticCategoryApplicationEnabled('project') === true, 'Project switch must be independent');
assert(LmdbZoningService::isAutomaticCategoryApplicationEnabled('order') === false, 'Aliases must read their canonical switch');

$shouldApplyCategory = new ReflectionMethod(LmdbZoningService::class, 'shouldApplyCategory');
if (PHP_VERSION_ID < 80100) {
	$shouldApplyCategory->setAccessible(true);
}
assert($shouldApplyCategory->invoke(null, 'societe', 0) === false, 'Disabled automatic switch must not apply a category');
assert($shouldApplyCategory->invoke(null, 'societe', 1) === true, 'Manual category application must override the automatic switch');

require_once __DIR__.'/../core/triggers/interface_99_modLmdbZoning_LmdbZoningTriggers.class.php';
$trigger = new InterfaceLmdbZoningTriggers(null);
$addTarget = new ReflectionMethod(InterfaceLmdbZoningTriggers::class, 'addZoningTarget');
$deduplicateTargets = new ReflectionMethod(InterfaceLmdbZoningTriggers::class, 'deduplicateTargets');
if (PHP_VERSION_ID < 80100) {
	$addTarget->setAccessible(true);
	$deduplicateTargets->setAccessible(true);
}
$GLOBALS['lmdbzoning_test_constants']['LMDBZONING_AUTO_APPLY_CATEGORY_SOCIETE'] = 1;
$GLOBALS['lmdbzoning_test_constants']['LMDBZONING_AUTO_APPLY_CATEGORY_PROJECT'] = 0;
$targets = array();
$arguments = array(&$targets, 'societe', 10, 1);
$addTarget->invokeArgs($trigger, $arguments);
$arguments = array(&$targets, 'project', 20, 1);
$addTarget->invokeArgs($trigger, $arguments);
assert($targets['societe:10:1']['apply_category'] === true, 'Enabled target must capture category application');
assert($targets['project:20:1']['apply_category'] === false, 'Disabled target must capture category refusal');
$GLOBALS['lmdbzoning_test_constants']['LMDBZONING_AUTO_APPLY_CATEGORY_SOCIETE'] = 0;
$GLOBALS['lmdbzoning_test_constants']['LMDBZONING_AUTO_APPLY_CATEGORY_PROJECT'] = 1;
$deferredTargets = $deduplicateTargets->invoke($trigger, array_values($targets));
assert($deferredTargets[0]['apply_category'] === true, 'Deferred targets must preserve the captured enabled decision');
assert($deferredTargets[1]['apply_category'] === false, 'Deferred targets must preserve the captured disabled decision');

echo "lmdbzoning service tests passed\n";
