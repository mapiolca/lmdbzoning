<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoninggeocodecache.class.php');
dol_include_once('/lmdbzoning/class/geocoder.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$form = new Form($db);
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
$limit = GETPOSTINT('limit') > 0 ? GETPOSTINT('limit') : (empty($conf->liste_limit) ? 50 : (int) $conf->liste_limit);
$page = GETPOSTINT('page');
if ($page < 0) {
	$page = 0;
}
$offset = $page * $limit;
$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 't.rowid';
$sortorder = strtoupper(GETPOST('sortorder', 'aZ09comma') ?: 'DESC');
$sortorder = $sortorder === 'ASC' ? 'ASC' : 'DESC';
$removefilter = GETPOST('button_removefilter', 'alpha');

$search = array(
	'rowid' => $removefilter ? 0 : GETPOST('search_rowid', 'int'),
	'address_hash' => $removefilter ? '' : GETPOST('search_address_hash', 'alphanohtml'),
	'address_raw' => $removefilter ? '' : GETPOST('search_address_raw', 'alphanohtml'),
	'address_normalized' => $removefilter ? '' : GETPOST('search_address_normalized', 'alphanohtml'),
	'source' => $removefilter ? '' : GETPOST('search_source', 'alphanohtml'),
	'status' => $removefilter ? '' : GETPOST('search_status', 'alphanohtml'),
	'message' => $removefilter ? '' : GETPOST('search_message', 'alphanohtml'),
);

$arrayfields = array(
	't.rowid' => array('label' => 'ID', 'checked' => 0, 'enabled' => 1, 'position' => 1),
	't.address_hash' => array('label' => 'AddressHash', 'checked' => 1, 'enabled' => 1, 'position' => 10),
	't.address_raw' => array('label' => 'RawAddress', 'checked' => 1, 'enabled' => 1, 'position' => 20),
	't.address_normalized' => array('label' => 'NormalizedAddress', 'checked' => 0, 'enabled' => 1, 'position' => 30),
	't.latitude' => array('label' => 'Latitude', 'checked' => 1, 'enabled' => 1, 'position' => 40),
	't.longitude' => array('label' => 'Longitude', 'checked' => 1, 'enabled' => 1, 'position' => 50),
	't.source' => array('label' => 'Source', 'checked' => 1, 'enabled' => 1, 'position' => 60),
	't.confidence_score' => array('label' => 'ConfidenceScore', 'checked' => 1, 'enabled' => 1, 'position' => 70),
	't.status' => array('label' => 'Status', 'checked' => 1, 'enabled' => 1, 'position' => 80),
	't.message' => array('label' => 'Message', 'checked' => 0, 'enabled' => 1, 'position' => 90),
	't.tms' => array('label' => 'DateModification', 'checked' => 1, 'enabled' => 1, 'position' => 100),
);
$sortablefields = array('t.rowid', 't.address_hash', 't.latitude', 't.longitude', 't.source', 't.confidence_score', 't.status', 't.tms');
if (!in_array($sortfield, $sortablefields, true)) {
	$sortfield = 't.rowid';
}

$param = lmdbzoning_cache_build_param($search);
$where = array('t.entity = '.((int) $conf->entity));
if ($search['rowid'] > 0) {
	$where[] = 't.rowid = '.((int) $search['rowid']);
}
if ($search['address_hash'] !== '') {
	$where[] = "t.address_hash LIKE '%".$db->escape($search['address_hash'])."%'";
}
if ($search['address_raw'] !== '') {
	$where[] = "t.address_raw LIKE '%".$db->escape($search['address_raw'])."%'";
}
if ($search['address_normalized'] !== '') {
	$where[] = "t.address_normalized LIKE '%".$db->escape($search['address_normalized'])."%'";
}
if ($search['source'] !== '') {
	$where[] = "t.source LIKE '%".$db->escape($search['source'])."%'";
}
if ($search['status'] !== '') {
	$where[] = "t.status LIKE '%".$db->escape($search['status'])."%'";
}
if ($search['message'] !== '') {
	$where[] = "t.message LIKE '%".$db->escape($search['message'])."%'";
}

$sql = 'SELECT COUNT(t.rowid) as nb';
$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_geocode_cache as t';
$sql .= ' WHERE '.implode(' AND ', $where);
$resql = $db->query($sql);
$nbtotalofrecords = 0;
if ($resql && ($obj = $db->fetch_object($resql))) {
	$nbtotalofrecords = (int) $obj->nb;
}

$sql = 'SELECT t.*';
$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_geocode_cache as t';
$sql .= ' WHERE '.implode(' AND ', $where);
$sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
$sql .= ' LIMIT '.((int) $limit).' OFFSET '.((int) $offset);
$resql = $db->query($sql);

$rows = array();
if ($resql) {
	while ($row = $db->fetch_object($resql)) {
		$rows[] = $row;
	}
}

$varpage = $_SERVER['PHP_SELF'];
$selectedfields = '';
if (method_exists($form, 'multiSelectArrayWithCheckbox')) {
	$checkboxleft = function_exists('getDolGlobalString') ? getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '') : (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN) ? '' : $conf->global->MAIN_CHECKBOX_LEFT_COLUMN);
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, $checkboxleft);
}

llxHeader('', $langs->trans('GeocodeCache'));
print '<div class="tabsAction"><form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="purge"><input class="butActionDelete" type="submit" value="'.$langs->trans('PurgeCache').'"></form></div>';
print '<form method="GET" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
if (function_exists('print_barre_liste')) {
	print_barre_liste($langs->trans('GeocodeCache'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', count($rows), $nbtotalofrecords, 'object_lmdbzoning@lmdbzoning', 0, '', '', $limit, 0, 0, 1);
} else {
	print load_fiche_titre($langs->trans('GeocodeCache'), '', 'object_lmdbzoning@lmdbzoning');
}
print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">';
print '<tr class="liste_titre">';
foreach ($arrayfields as $key => $field) {
	if (empty($field['checked'])) {
		continue;
	}
	if (function_exists('print_liste_field_titre')) {
		print_liste_field_titre($field['label'], $_SERVER['PHP_SELF'], $key, '', $param, '', $sortfield, $sortorder);
	} else {
		print '<th>'.$langs->trans($field['label']).'</th>';
	}
}
if (function_exists('getTitleFieldOfList')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
} else {
	print '<th class="center">'.$selectedfields.'</th>';
}
print '</tr>';
print '<tr class="liste_titre_filter">';
foreach ($arrayfields as $key => $field) {
	if (empty($field['checked'])) {
		continue;
	}
	print '<td>'.lmdbzoning_cache_filter_input($key, $search).'</td>';
}
print '<td class="liste_titre center">'.lmdbzoning_cache_show_filter_buttons($form).'</td>';
print '</tr>';

foreach ($rows as $row) {
	print '<tr class="oddeven">';
	foreach ($arrayfields as $key => $field) {
		if (empty($field['checked'])) {
			continue;
		}
		print '<td>'.lmdbzoning_cache_cell($object, $row, $key).'</td>';
	}
	print '<td class="right"><a class="button small" href="geocode_cache_card.php?id='.(int) $row->rowid.'">'.$langs->trans('Open').'</a></td>';
	print '</tr>';
}
if (empty($rows)) {
	print '<tr><td colspan="'.(count($arrayfields) + 1).'"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
}
print '</table></div>';
print '</form>';
llxFooter();
$db->close();

/**
 * Build persistent URL parameters.
 *
 * @param array<string,mixed> $search Search values
 * @return string
 */
function lmdbzoning_cache_build_param(array $search)
{
	$param = '';
	foreach ($search as $key => $value) {
		if ($value !== '' && $value !== 0) {
			$param .= '&search_'.$key.'='.urlencode((string) $value);
		}
	}
	$selectedfields = GETPOST('selectedfields', 'array');
	if (is_array($selectedfields)) {
		foreach ($selectedfields as $field) {
			$param .= '&selectedfields[]='.urlencode((string) $field);
		}
	}

	return $param;
}

/**
 * Render native filter buttons when available.
 *
 * @param Form $form Form helper
 * @return string
 */
function lmdbzoning_cache_show_filter_buttons($form)
{
	global $langs;

	if (method_exists($form, 'showFilterAndCheckAddButtons')) {
		return $form->showFilterAndCheckAddButtons(0);
	}
	if (method_exists($form, 'showFilterButtons')) {
		return $form->showFilterButtons();
	}

	return '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Search')).'"> <input type="submit" class="button" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans('RemoveFilter')).'">';
}

/**
 * Render filter input for cache lists.
 *
 * @param string              $field  Field key
 * @param array<string,mixed> $search Search values
 * @return string
 */
function lmdbzoning_cache_filter_input($field, array $search)
{
	if ($field === 't.rowid') {
		return '<input class="flat width50" type="text" name="search_rowid" value="'.dol_escape_htmltag($search['rowid'] ?: '').'">';
	}
	if ($field === 't.address_hash') {
		return '<input class="flat maxwidth100" type="text" name="search_address_hash" value="'.dol_escape_htmltag($search['address_hash']).'">';
	}
	if ($field === 't.address_raw') {
		return '<input class="flat maxwidth150" type="text" name="search_address_raw" value="'.dol_escape_htmltag($search['address_raw']).'">';
	}
	if ($field === 't.address_normalized') {
		return '<input class="flat maxwidth150" type="text" name="search_address_normalized" value="'.dol_escape_htmltag($search['address_normalized']).'">';
	}
	if ($field === 't.source') {
		return '<input class="flat maxwidth100" type="text" name="search_source" value="'.dol_escape_htmltag($search['source']).'">';
	}
	if ($field === 't.status') {
		return '<input class="flat maxwidth100" type="text" name="search_status" value="'.dol_escape_htmltag($search['status']).'">';
	}
	if ($field === 't.message') {
		return '<input class="flat maxwidth150" type="text" name="search_message" value="'.dol_escape_htmltag($search['message']).'">';
	}

	return '';
}

/**
 * Render one cache cell.
 *
 * @param LmdbZoningGeocodeCache $object Object definition
 * @param stdClass               $row    SQL row
 * @param string                 $field  Field key
 * @return string
 */
function lmdbzoning_cache_cell($object, $row, $field)
{
	$name = preg_replace('/^t\./', '', $field);
	if ($name === 'rowid') {
		return (string) (int) $row->rowid;
	}
	if (!isset($object->fields[$name])) {
		return '';
	}
	$value = isset($row->$name) ? $row->$name : '';

	return lmdbzoning_render_field_output($name, $object->fields[$name], $value);
}
