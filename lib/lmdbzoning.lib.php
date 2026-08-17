<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Prepare admin head tabs.
 *
 * @return array<int,array<int,string>>
 */
function lmdbzoningAdminPrepareHead()
{
	global $langs;

	$langs->load('lmdbzoning@lmdbzoning');

	return array(
		array(dol_buildpath('/lmdbzoning/admin/setup.php', 1), $langs->trans('Settings'), 'settings'),
		array(dol_buildpath('/lmdbzoning/admin/compatibility.php', 1), $langs->trans('Compatibility'), 'compatibility'),
		array(dol_buildpath('/lmdbzoning/admin/about.php', 1), $langs->trans('About'), 'about'),
	);
}

/**
 * Prepare profile tabs.
 *
 * @param LmdbZoningProfile $object Profile
 * @return array<int,array<int,string>>
 */
function lmdbzoningProfilePrepareHead($object)
{
	global $langs;

	$langs->load('lmdbzoning@lmdbzoning');
	$id = (int) $object->id;

	return array(
		array(dol_buildpath('/lmdbzoning/profile_card.php', 1).'?id='.$id, $langs->trans('Card'), 'card'),
		array(dol_buildpath('/lmdbzoning/profile_zone_list.php', 1).'?fk_profile='.$id, $langs->trans('Zones'), 'zones'),
	);
}
