<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningprofilezone.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$fk_profile = GETPOSTINT('fk_profile');
$filters = array();
if ($fk_profile > 0) {
	$filters['t.fk_profile'] = '='.(int) $fk_profile;
}
$object = new LmdbZoningProfileZone($db);
llxHeader('', $langs->trans('Zones'));
$newurl = 'profile_zone_card.php'.($fk_profile > 0 ? '?fk_profile='.(int) $fk_profile : '');
print load_fiche_titre($langs->trans('Zones'), '<a class="butAction" href="'.$newurl.'">'.$langs->trans('New').'</a>', 'object_lmdbzoning@lmdbzoning');
lmdbzoning_print_object_list($object, '', 'profile_zone_card.php', $filters);
llxFooter();
$db->close();
