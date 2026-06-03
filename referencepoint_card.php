<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/referencepoint.class.php');
dol_include_once('/lmdbzoning/class/geocoder.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$form = new Form($db);
$object = new LmdbZoningReferencePoint($db);
$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

lmdbzoning_handle_card_actions($object, 'referencepoint_card.php');

if ($action === 'geocode' && $id > 0) {
	lmdbzoning_check_access('geocode');
	lmdbzoning_check_post_token();
	if ($object->fetch($id) > 0) {
		$geocoder = new LmdbZoningGeocoder($db);
		$result = $geocoder->geocode($object->getAddressArray(), (int) $conf->entity, 1);
		$object->latitude = $result['latitude'];
		$object->longitude = $result['longitude'];
		$object->geocode_status = $result['status'];
		$object->geocode_source = $result['source'];
		$object->geocode_score = $result['confidence_score'];
		$object->update($user);
		setEventMessages($langs->trans('GeocodeDone'), null, 'mesgs');
		header('Location: referencepoint_card.php?id='.(int) $id);
		exit;
	}
}

if ($id > 0 && $object->fetch($id) <= 0) {
	accessforbidden();
}

llxHeader('', $langs->trans('ReferencePoint'));
$title = $id > 0 ? $langs->trans('ReferencePoint').' '.$object->ref : $langs->trans('NewReferencePoint');
print load_fiche_titre($title, '<a class="butAction" href="referencepoint_list.php">'.$langs->trans('BackToList').'</a>', 'object_lmdbzoning@lmdbzoning');
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="'.($id > 0 ? 'update' : 'add').'">';
if ($id > 0) {
	print '<input type="hidden" name="id" value="'.(int) $id.'">';
}
print '<table class="border centpercent">';
lmdbzoning_print_object_form_fields($object);
print '</table>';
print '<div class="center">';
print '<input class="button button-save" type="submit" value="'.$langs->trans('Save').'">';
print '</div></form>';
if ($id > 0) {
	print '<div class="tabsAction">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" class="inline-block">';
	print '<input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="geocode"><input type="hidden" name="id" value="'.(int) $id.'">';
	print '<input class="butAction" type="submit" value="'.$langs->trans('Geocode').'">';
	print '</form>';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" class="inline-block">';
	print '<input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'.(int) $id.'">';
	print '<input class="butActionDelete" type="submit" value="'.$langs->trans('Delete').'">';
	print '</form>';
	print '</div>';
}
llxFooter();
$db->close();
