<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningprofile.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$form = new Form($db);
$object = new LmdbZoningProfile($db);
$id = GETPOSTINT('id');

lmdbzoning_handle_card_actions($object, 'profile_card.php');
if ($id > 0 && $object->fetch($id) <= 0) {
	accessforbidden();
}

llxHeader('', $langs->trans('ZoningProfile'));
$title = $id > 0 ? $langs->trans('ZoningProfile').' '.$object->ref : $langs->trans('NewZoningProfile');
print load_fiche_titre($title, '<a class="butAction" href="profile_list.php">'.$langs->trans('BackToList').'</a>', 'object_lmdbzoning@lmdbzoning');
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="'.($id > 0 ? 'update' : 'add').'">';
if ($id > 0) {
	print '<input type="hidden" name="id" value="'.(int) $id.'">';
}
print '<table class="border centpercent">';
lmdbzoning_print_object_form_fields($object);
print '</table><div class="center"><input class="button button-save" type="submit" value="'.$langs->trans('Save').'"></div></form>';
if ($id > 0) {
	print '<div class="tabsAction">';
	print '<a class="butAction" href="profile_zone_list.php?fk_profile='.(int) $id.'">'.$langs->trans('ManageZones').'</a>';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" class="inline-block"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.(int) $id.'"><input class="butActionDelete" type="submit" value="'.$langs->trans('Delete').'"></form>';
	print '</div>';
}
llxFooter();
$db->close();
