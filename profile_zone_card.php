<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningprofilezone.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$form = new Form($db);
$object = new LmdbZoningProfileZone($db);
$id = GETPOSTINT('id');
$fk_profile = GETPOSTINT('fk_profile');
if ($fk_profile > 0 && $id <= 0) {
	$object->fk_profile = $fk_profile;
}

lmdbzoning_handle_card_actions($object, 'profile_zone_card.php');
if ($id > 0 && $object->fetch($id) <= 0) {
	accessforbidden();
}

llxHeader('', $langs->trans('Zone'));
print load_fiche_titre($id > 0 ? $langs->trans('Zone').' '.$object->zone_code : $langs->trans('NewZone'), '<a class="butAction" href="profile_zone_list.php'.($object->fk_profile ? '?fk_profile='.(int) $object->fk_profile : '').'">'.$langs->trans('BackToList').'</a>', 'object_lmdbzoning@lmdbzoning');
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="'.($id > 0 ? 'update' : 'add').'">';
if ($id > 0) {
	print '<input type="hidden" name="id" value="'.(int) $id.'">';
}
print '<table class="border centpercent">';
lmdbzoning_print_object_form_fields($object);
print '</table><div class="center"><input class="button button-save" type="submit" value="'.$langs->trans('Save').'"></div></form>';
if ($id > 0) {
	print '<div class="tabsAction"><form method="POST" action="'.$_SERVER['PHP_SELF'].'"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.(int) $id.'"><input class="butActionDelete" type="submit" value="'.$langs->trans('Delete').'"></form></div>';
}
llxFooter();
$db->close();
