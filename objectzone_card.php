<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningobjectzone.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$form = new Form($db);
$object = new LmdbZoningObjectZone($db);
$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

lmdbzoning_handle_card_actions($object, 'objectzone_card.php');
if ($id > 0 && $object->fetch($id) <= 0) {
	accessforbidden();
}
if ($id > 0 && $action === 'clearoverride') {
	lmdbzoning_check_access('override');
	lmdbzoning_check_post_token();
	$service = new LmdbZoningService($db);
	if ($service->clearZoneOverride($object->element_type, (int) $object->fk_element, (int) $user->id) > 0) {
		setEventMessages($langs->trans('OverrideCleared'), null, 'mesgs');
	}
	header('Location: objectzone_card.php?id='.(int) $id);
	exit;
}

llxHeader('', $langs->trans('ObjectZoneResult'));
print load_fiche_titre($id > 0 ? $langs->trans('ObjectZoneResult') : $langs->trans('NewObjectZoneResult'), '<a class="butAction" href="objectzone_list.php">'.$langs->trans('BackToList').'</a>', 'object_lmdbzoning@lmdbzoning');
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="'.($id > 0 ? 'update' : 'add').'">';
if ($id > 0) {
	print '<input type="hidden" name="id" value="'.(int) $id.'">';
}
print '<table class="border centpercent">';
lmdbzoning_print_object_form_fields($object);
print '</table><div class="center"><input class="button button-save" type="submit" value="'.$langs->trans('Save').'"></div></form>';
if ($id > 0) {
	print '<div class="tabsAction"><form method="POST" action="'.$_SERVER['PHP_SELF'].'"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="clearoverride"><input type="hidden" name="id" value="'.(int) $id.'"><input class="butAction" type="submit" value="'.$langs->trans('ClearOverride').'"></form></div>';
}
llxFooter();
$db->close();
