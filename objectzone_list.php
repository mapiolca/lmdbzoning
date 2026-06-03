<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningobjectzone.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$object = new LmdbZoningObjectZone($db);
$filters = array();
if (GETPOST('element_type', 'alphanohtml') !== '') {
	$filters['t.element_type'] = "='".GETPOST('element_type', 'alphanohtml')."'";
}
if (GETPOST('zone_code', 'alphanohtml') !== '') {
	$filters['t.zone_code'] = "='".GETPOST('zone_code', 'alphanohtml')."'";
}
if (GETPOST('calculation_status', 'alphanohtml') !== '') {
	$filters['t.calculation_status'] = "='".GETPOST('calculation_status', 'alphanohtml')."'";
}

llxHeader('', $langs->trans('ObjectZoneResults'));
lmdbzoning_print_object_list($object, $langs->trans('ObjectZoneResults'), 'objectzone_card.php', $filters);
llxFooter();
$db->close();
