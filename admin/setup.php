<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/lib/lmdbzoning.lib.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');
dol_include_once('/lmdbzoning/class/referencepoint.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningprofile.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningprofilezone.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');

$langs->loadLangs(array('admin', 'lmdbzoning@lmdbzoning'));

if (!$user->admin && (!method_exists($user, 'hasRight') || !$user->hasRight('lmdbzoning', 'lmdbzoning', 'admin'))) {
	accessforbidden();
}
if (function_exists('isModEnabled') && !isModEnabled('lmdbzoning')) {
	accessforbidden();
}

$form = new Form($db);
$action = GETPOST('action', 'aZ09');

if ($action === 'save') {
	lmdbzoning_check_post_token();
	$constants = array(
		'LMDBZONING_GEOCODER_ENABLED' => GETPOST('LMDBZONING_GEOCODER_ENABLED', 'int'),
		'LMDBZONING_GEOCODER_PROVIDER' => GETPOST('LMDBZONING_GEOCODER_PROVIDER', 'alphanohtml'),
		'LMDBZONING_GEOCODER_API_URL' => GETPOST('LMDBZONING_GEOCODER_API_URL', 'alphanohtml'),
		'LMDBZONING_GEOCODER_TIMEOUT' => GETPOST('LMDBZONING_GEOCODER_TIMEOUT', 'int'),
		'LMDBZONING_CACHE_DURATION_DAYS' => GETPOST('LMDBZONING_CACHE_DURATION_DAYS', 'int'),
		'LMDBZONING_AUTO_APPLY_CATEGORY' => GETPOST('LMDBZONING_AUTO_APPLY_CATEGORY', 'int'),
		'LMDBZONING_ALLOW_MANUAL_OVERRIDE' => GETPOST('LMDBZONING_ALLOW_MANUAL_OVERRIDE', 'int'),
		'LMDBZONING_DEFAULT_PROFILE' => GETPOST('LMDBZONING_DEFAULT_PROFILE', 'alphanohtml'),
		'LMDBZONING_CRON_ENABLED' => GETPOST('LMDBZONING_CRON_ENABLED', 'int'),
		'LMDBZONING_CRON_MAX_ITEMS' => GETPOST('LMDBZONING_CRON_MAX_ITEMS', 'int'),
		'LMDBZONING_CRON_RETRY_FAILED' => GETPOST('LMDBZONING_CRON_RETRY_FAILED', 'int'),
		'LMDBZONING_CRON_RECALCULATE_AFTER_DAYS' => GETPOST('LMDBZONING_CRON_RECALCULATE_AFTER_DAYS', 'int'),
	);
	foreach ($constants as $name => $value) {
		dolibarr_set_const($db, $name, $value, 'chaine', 0, '', (int) $conf->entity);
	}
	setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
	header('Location: setup.php');
	exit;
}

if ($action === 'createdefaults') {
	lmdbzoning_check_post_token();
	$result = lmdbzoning_create_initial_profile(GETPOST('create_categories', 'int'));
	if ($result > 0) {
		setEventMessages($langs->trans('InitialProfileCreated'), null, 'mesgs');
	} else {
		setEventMessages($langs->trans('InitialProfileAlreadyExists'), null, 'warnings');
	}
	header('Location: setup.php');
	exit;
}

llxHeader('', $langs->trans('LmdbZoningSetup'));
$head = lmdbzoningAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans('LmdbZoning'), -1, 'lmdbzoning@lmdbzoning');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';
print '<table class="border centpercent">';
lmdbzoning_print_const_yesno('LMDBZONING_GEOCODER_ENABLED', 'EnableGeocoder');
lmdbzoning_print_const_closed_choice('LMDBZONING_GEOCODER_PROVIDER', 'GeocoderProvider', 'geoplateforme');
lmdbzoning_print_const_text('LMDBZONING_GEOCODER_API_URL', 'GeocoderApiUrl', 'https://data.geopf.fr/geocodage/search');
lmdbzoning_print_const_text('LMDBZONING_GEOCODER_TIMEOUT', 'GeocoderTimeout', '5');
lmdbzoning_print_const_text('LMDBZONING_CACHE_DURATION_DAYS', 'CacheDurationDays', '365');
lmdbzoning_print_const_yesno('LMDBZONING_AUTO_APPLY_CATEGORY', 'AutoApplyCategory');
lmdbzoning_print_const_yesno('LMDBZONING_ALLOW_MANUAL_OVERRIDE', 'AllowManualOverride');
lmdbzoning_print_const_text('LMDBZONING_DEFAULT_PROFILE', 'DefaultProfile', 'MAINT_PV_RES_1_9KWC');
lmdbzoning_print_const_yesno('LMDBZONING_CRON_ENABLED', 'EnableCron');
lmdbzoning_print_const_text('LMDBZONING_CRON_MAX_ITEMS', 'CronMaxItems', '50');
lmdbzoning_print_const_yesno('LMDBZONING_CRON_RETRY_FAILED', 'CronRetryFailed');
lmdbzoning_print_const_text('LMDBZONING_CRON_RECALCULATE_AFTER_DAYS', 'CronRecalculateAfterDays', '0');
print '</table>';
print '<div class="center"><input class="button button-save" type="submit" value="'.$langs->trans('Save').'"></div>';
print '</form>';

print '<br>';
print load_fiche_titre($langs->trans('InitialSetupWizard'), '', 'setup');
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="createdefaults">';
print '<table class="border centpercent">';
print '<tr><td class="titlefield">'.$langs->trans('CreateInitialProfile').'</td><td>MAINT_PV_RES_1_9KWC</td></tr>';
print '<tr><td>'.$langs->trans('CreateCategoriesAfterConfirmation').'</td><td>'.$form->selectyesno('create_categories', 0, 1).'</td></tr>';
print '</table>';
print '<div class="center"><input class="button" type="submit" value="'.$langs->trans('CreateInitialData').'"></div>';
print '</form>';

print dol_get_fiche_end();
llxFooter();
$db->close();

/**
 * Print text constant row.
 *
 * @param string $name Constant name
 * @param string $label Translation key
 * @param string $default Default value
 * @return void
 */
function lmdbzoning_print_const_text($name, $label, $default = '')
{
	global $conf, $langs;
	$value = isset($conf->global->$name) ? $conf->global->$name : $default;
	print '<tr><td class="titlefield">'.$langs->trans($label).'</td><td><input class="flat minwidth500" type="text" name="'.$name.'" value="'.dol_escape_htmltag($value).'"></td></tr>';
}

/**
 * Print yes/no constant row.
 *
 * @param string $name Constant name
 * @param string $label Translation key
 * @return void
 */
function lmdbzoning_print_const_yesno($name, $label)
{
	global $conf, $langs, $form;
	$value = !empty($conf->global->$name) ? 1 : 0;
	print '<tr><td class="titlefield">'.$langs->trans($label).'</td><td>'.$form->selectyesno($name, $value, 1).'</td></tr>';
}

/**
 * Print closed choice constant row.
 *
 * @param string $name Constant name
 * @param string $label Translation key
 * @param string $default Default value
 * @return void
 */
function lmdbzoning_print_const_closed_choice($name, $label, $default = '')
{
	global $conf, $langs;
	$value = isset($conf->global->$name) ? $conf->global->$name : $default;
	print '<tr><td class="titlefield">'.$langs->trans($label).'</td><td>'.lmdbzoning_render_closed_choice_select($name, $value, false).'</td></tr>';
}

/**
 * Create initial Mios profile.
 *
 * @param int $createCategories Create categories
 * @return int
 */
function lmdbzoning_create_initial_profile($createCategories = 0)
{
	global $db, $conf, $user;

	$existing = new LmdbZoningProfile($db);
	$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'lmdbzoning_profile WHERE entity = '.((int) $conf->entity)." AND ref = 'MAINT_PV_RES_1_9KWC'";
	$resql = $db->query($sql);
	if ($resql && $db->fetch_object($resql)) {
		return 0;
	}

	$db->begin();
	$error = 0;
	$referenceId = 0;
	$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'lmdbzoning_referencepoint WHERE entity = '.((int) $conf->entity)." AND ref = 'MIOS'";
	$resql = $db->query($sql);
	if ($resql && ($obj = $db->fetch_object($resql))) {
		$referenceId = (int) $obj->rowid;
	} else {
		$reference = new LmdbZoningReferencePoint($db);
		$reference->ref = 'MIOS';
		$reference->label = 'Siège social Soleil Aquitain - Mios';
		$reference->address = '4 rue Alfred Kastler';
		$reference->zip = '33380';
		$reference->town = 'Mios';
		$reference->country_code = 'FR';
		$reference->geocode_status = 'pending';
		$reference->active = 1;
		$referenceId = $reference->create($user);
		if ($referenceId <= 0) {
			$error++;
		}
	}

	$profile = new LmdbZoningProfile($db);
	$profile->ref = 'MAINT_PV_RES_1_9KWC';
	$profile->label = 'Maintenance PV résidentielle 1 à 9 kWc';
	$profile->fk_referencepoint = $referenceId;
	$profile->distance_method = 'air_distance';
	$profile->unit = 'km';
	$profile->active = 1;
	$profileId = $error ? -1 : $profile->create($user);
	if ($profileId <= 0) {
		$error++;
	}

	$zones = array(
		array('ZONE_1', 'Zone 1 - 0 à 30 km', 0, 30, 10),
		array('ZONE_2', 'Zone 2 - 31 à 50 km', 30, 50, 20),
		array('ZONE_3', 'Zone 3 - 51 à 70 km', 50, 70, 30),
		array('ZONE_OUT', 'Hors zone - Sur devis', 70, null, 999),
	);
	foreach ($zones as $zoneData) {
		$zone = new LmdbZoningProfileZone($db);
		$zone->fk_profile = $profileId;
		$zone->zone_code = $zoneData[0];
		$zone->label = $zoneData[1];
		$zone->distance_min = $zoneData[2];
		$zone->distance_max = $zoneData[3];
		$zone->priority = $zoneData[4];
		if ($createCategories) {
			lmdbzoning_fill_recommended_categories($zone, $zoneData[1]);
		}
		$zone->active = 1;
		if ($zone->create($user) <= 0) {
			$error++;
		}
	}

	if ($error) {
		$db->rollback();
		return -1;
	}
	dolibarr_set_const($db, 'LMDBZONING_DEFAULT_PROFILE', 'MAINT_PV_RES_1_9KWC', 'chaine', 0, '', (int) $conf->entity);
	$db->commit();

	return 1;
}

/**
 * Fill recommended category fields for all active supported object types.
 *
 * @param LmdbZoningProfileZone $zone  Zone object
 * @param string                $label Category label
 * @return void
 */
function lmdbzoning_fill_recommended_categories($zone, $label)
{
	$processed = array();
	foreach (LmdbZoningService::getZonableObjectDefinitions(1) as $definition) {
		if (empty($definition['category_field']) || !isset($definition['category_type_id']) || $definition['category_type_id'] === null) {
			continue;
		}
		$field = $definition['category_field'];
		if ($field === 'fk_categorie_default' || !array_key_exists($field, $zone->fields)) {
			continue;
		}
		$key = $field.':'.((int) $definition['category_type_id']);
		if (isset($processed[$key])) {
			continue;
		}
		$processed[$key] = true;
		$zone->$field = lmdbzoning_create_category($label, (int) $definition['category_type_id']);
	}
}

/**
 * Create a native category when available.
 *
 * @param string $label Category label
 * @param int    $type  Numeric category type
 * @return int
 */
function lmdbzoning_create_category($label, $type)
{
	global $db, $conf, $user;

	if (!class_exists('Categorie') && file_exists(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php')) {
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	}
	if (!class_exists('Categorie')) {
		return 0;
	}
	$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'categorie WHERE entity = '.((int) $conf->entity)." AND type = ".((int) $type)." AND label = '".$db->escape($label)."'";
	$resql = $db->query($sql);
	if ($resql && ($obj = $db->fetch_object($resql))) {
		return (int) $obj->rowid;
	}
	$category = new Categorie($db);
	$category->label = $label;
	$category->type = $type;
	$category->entity = (int) $conf->entity;
	$result = $category->create($user);

	return $result > 0 ? (int) $result : 0;
}
