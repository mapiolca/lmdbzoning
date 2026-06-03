<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
dol_include_once('/lmdbzoning/class/lmdbzoninggeocodecache.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$object = new LmdbZoningGeocodeCache($db);
$id = GETPOSTINT('id');
if ($id <= 0 || $object->fetch($id) <= 0) {
	accessforbidden();
}
llxHeader('', $langs->trans('GeocodeCache'));
print load_fiche_titre($langs->trans('GeocodeCache'), '<a class="butAction" href="geocode_cache_list.php">'.$langs->trans('BackToList').'</a>', 'object_lmdbzoning@lmdbzoning');
print '<table class="border centpercent">';
lmdbzoning_print_object_view_fields($object);
print '</table>';
llxFooter();
$db->close();
