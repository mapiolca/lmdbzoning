<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningprofile.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningprofilezone.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$fk_profile = GETPOSTINT('fk_profile');
$action = GETPOST('action', 'aZ09');
$filters = array();
if ($fk_profile > 0) {
	$filters['t.fk_profile'] = '='.(int) $fk_profile;
}

if ($action === 'force_recalculate' && $fk_profile > 0) {
	lmdbzoning_check_access('write');
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		accessforbidden();
	}
	lmdbzoning_check_post_token();
	$profile = new LmdbZoningProfile($db);
	if ($profile->fetch((int) $fk_profile) <= 0) {
		accessforbidden();
	}
	$maxItems = max(1, empty($conf->global->LMDBZONING_CRON_MAX_ITEMS) ? 50 : (int) $conf->global->LMDBZONING_CRON_MAX_ITEMS);
	$service = new LmdbZoningService($db);
	$stats = $service->forceRecalculateProfileObjects($profile, $maxItems, (int) $conf->entity);
	if (!empty($service->error)) {
		setEventMessages($service->error, $service->errors, 'errors');
	}
	setEventMessages($langs->trans('LmdbZoningForcedRecalculationDone', $stats['queued'], $stats['processed'], $stats['ok'], $stats['failed'], $stats['remaining'], $stats['skipped_due_to_limit']), null, $stats['failed'] > 0 ? 'warnings' : 'mesgs');
	header('Location: '.$_SERVER['PHP_SELF'].'?fk_profile='.(int) $fk_profile);
	exit;
}

$object = new LmdbZoningProfileZone($db);
llxHeader('', $langs->trans('Zones'));
$newurl = 'profile_zone_card.php'.($fk_profile > 0 ? '?fk_profile='.(int) $fk_profile : '');
$buttons = '<a class="butAction" href="'.$newurl.'">'.$langs->trans('New').'</a>';
if ($fk_profile > 0 && method_exists($user, 'hasRight') && $user->hasRight('lmdbzoning', 'lmdbzoning', 'write')) {
	$buttons .= '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" style="display:inline-block; margin-left: 6px;">';
	$buttons .= '<input type="hidden" name="token" value="'.newToken().'">';
	$buttons .= '<input type="hidden" name="action" value="force_recalculate">';
	$buttons .= '<input type="hidden" name="fk_profile" value="'.(int) $fk_profile.'">';
	$buttons .= '<input class="butAction" type="submit" value="'.$langs->trans('ForceRecalculation').'">';
	$buttons .= '</form>';
}
print load_fiche_titre($langs->trans('Zones'), $buttons, 'object_lmdbzoning@lmdbzoning');
print '<style>
.lmdbzoning-profile-zone-list .lmdbzoning-category-link a,
.lmdbzoning-profile-zone-list .lmdbzoning-category-link a:link,
.lmdbzoning-profile-zone-list .lmdbzoning-category-link a:visited,
.lmdbzoning-profile-zone-list .lmdbzoning-category-link a:hover,
.lmdbzoning-profile-zone-list .lmdbzoning-category-link a:active,
.lmdbzoning-profile-zone-list .lmdbzoning-category-link a * {
	color: #000 !important;
}
</style>';
print '<div class="lmdbzoning-profile-zone-list">';
lmdbzoning_print_object_list($object, '', 'profile_zone_card.php', $filters);
print '</div>';
llxFooter();
$db->close();
