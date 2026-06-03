<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoninggeocodecache.class.php');
dol_include_once('/lmdbzoning/class/geocoder.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$action = GETPOST('action', 'aZ09');
if ($action === 'purge') {
	lmdbzoning_check_access('geocode');
	lmdbzoning_check_post_token();
	$geocoder = new LmdbZoningGeocoder($db);
	if ($geocoder->purgeCache((int) $conf->entity) > 0) {
		setEventMessages($langs->trans('CachePurged'), null, 'mesgs');
	}
	header('Location: geocode_cache_list.php');
	exit;
}

$object = new LmdbZoningGeocodeCache($db);
llxHeader('', $langs->trans('GeocodeCache'));
print '<div class="tabsAction"><form method="POST" action="'.$_SERVER['PHP_SELF'].'"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="purge"><input class="butActionDelete" type="submit" value="'.$langs->trans('PurgeCache').'"></form></div>';
lmdbzoning_print_object_list($object, $langs->trans('GeocodeCache'), 'geocode_cache_card.php');
llxFooter();
$db->close();
