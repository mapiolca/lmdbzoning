<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoninggeocodecache.class.php');
dol_include_once('/lmdbzoning/class/geocoder.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

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
$limit = GETPOSTINT('limit') > 0 ? GETPOSTINT('limit') : 50;
$page = GETPOSTINT('page');
$offset = $page * $limit;
$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 't.rowid';
$sortorder = strtoupper(GETPOST('sortorder', 'aZ09comma') ?: 'DESC');
$sortorder = $sortorder === 'ASC' ? 'ASC' : 'DESC';

$search = array(
	'rowid' => GETPOST('search_rowid', 'int'),
	'address_hash' => GETPOST('search_address_hash', 'alphanohtml'),
	'address_raw' => GETPOST('search_address_raw', 'alphanohtml'),
	'address_normalized' => GETPOST('search_address_normalized', 'alphanohtml'),
	'source' => GETPOST('search_source', 'alphanohtml'),
	'status' => GETPOST('search_status', 'alphanohtml'),
	'message' => GETPOST('search_message', 'alphanohtml'),
);

$arrayfields = array(
	't.rowid' => array('label' => 'ID', 'checked' => 0, 'position' => 1),
	't.address_hash' => array('label' => 'AddressHash', 'checked' => 1, 'position' => 10),
	't.address_raw' => array('label' => 'RawAddress', 'checked' => 1, 'position' => 20),
	't.address_normalized' => array('label' => 'NormalizedAddress', 'checked' => 0, 'position' => 30),
	't.latitude' => array('label' => 'Latitude', 'checked' => 1, 'position' => 40),
	't.longitude' => array('label' => 'Longitude', 'checked' => 1, 'position' => 50),
	't.source' => array('label' => 'Source', 'checked' => 1, 'position' => 60),
	't.confidence_score' => array('label' => 'ConfidenceScore', 'checked' => 1, 'position' => 70),
	't.status' => array('label' => 'Status', 'checked' => 1, 'position' => 80),
	't.message' => array('label' => 'Message', 'checked' => 0, 'position' => 90),
	't.tms' => array('label' => 'DateModification', 'checked' => 1, 'position' => 100),
);
$visiblefields = lmdbzoning_cache_visible_fields($arrayfields);
$sortablefields = array('t.rowid', 't.address_hash', 't.latitude', 't.longitude', 't.source', 't.confidence_score', 't.status', 't.tms');
if (!in_array($sortfield, $sortablefields, true)) {
	$sortfield = 't.rowid';
}

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
$num = 0;
if ($resql && ($obj = $db->fetch_object($resql))) {
	$num = (int) $obj->nb;
}

$sql = 'SELECT t.*';
$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_geocode_cache as t';
$sql .= ' WHERE '.implode(' AND ', $where);
$sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
$sql .= ' LIMIT '.((int) $limit).' OFFSET '.((int) $offset);
$resql = $db->query($sql);

llxHeader('', $langs->trans('GeocodeCache'));
print '<div class="tabsAction"><form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'"><input type="hidden" name="token" value="'.newToken().'"><input type="hidden" name="action" value="purge"><input class="butActionDelete" type="submit" value="'.$langs->trans('PurgeCache').'"></form></div>';
print load_fiche_titre($langs->trans('GeocodeCache'), '', 'object_lmdbzoning@lmdbzoning');
print '<form method="GET" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
lmdbzoning_print_cache_column_selector($arrayfields, $visiblefields);
print '<div class="div-table-responsive">';
print '<table class="liste centpercent">';
print '<tr class="liste_titre">';
foreach ($arrayfields as $key => $field) {
	if (empty($visiblefields[$key])) {
		continue;
	}
	print '<th>'.lmdbzoning_cache_sort_link($field['label'], $key, $sortfield, $sortorder, $sortablefields).'</th>';
}
print '<th></th></tr>';
print '<tr class="liste_titre_filter">';
foreach ($arrayfields as $key => $field) {
	if (empty($visiblefields[$key])) {
		continue;
	}
	print '<td>'.lmdbzoning_cache_filter_input($key, $search).'</td>';
}
print '<td class="right"><input class="button" type="submit" value="'.$langs->trans('Search').'"></td></tr>';

$shown = 0;
if ($resql) {
	while ($row = $db->fetch_object($resql)) {
		$shown++;
		print '<tr class="oddeven">';
		foreach ($arrayfields as $key => $field) {
			if (empty($visiblefields[$key])) {
				continue;
			}
			print '<td>'.lmdbzoning_cache_cell($object, $row, $key).'</td>';
		}
		print '<td class="right"><a class="button small" href="geocode_cache_card.php?id='.(int) $row->rowid.'">'.$langs->trans('Open').'</a></td>';
		print '</tr>';
	}
}
if (!$shown) {
	print '<tr><td colspan="'.(count($visiblefields) + 1).'"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
}
print '</table></div>';
print '</form>';
print '<div class="opacitymedium">'.((int) $num).' '.$langs->trans('Records').'</div>';
llxFooter();
$db->close();

/**
 * Return selected cache list fields.
 *
 * @param array<string,array<string,mixed>> $arrayfields Available fields
 * @return array<string,bool>
 */
function lmdbzoning_cache_visible_fields(array $arrayfields)
{
	$selected = GETPOST('visiblefields', 'array');
	$visible = array();
	foreach ($arrayfields as $key => $field) {
		$visible[$key] = is_array($selected) && count($selected) ? in_array($key, $selected, true) : !empty($field['checked']);
	}

	return $visible;
}

/**
 * Print column selector.
 *
 * @param array<string,array<string,mixed>> $arrayfields  Available fields
 * @param array<string,bool>                $visiblefields Visible fields
 * @return void
 */
function lmdbzoning_print_cache_column_selector(array $arrayfields, array $visiblefields)
{
	global $langs;

	print '<div class="liste_titre">';
	foreach ($arrayfields as $key => $field) {
		print '<label class="small" style="margin-right: 10px">';
		print '<input type="checkbox" name="visiblefields[]" value="'.dol_escape_htmltag($key).'"'.(!empty($visiblefields[$key]) ? ' checked' : '').'> ';
		print $langs->trans($field['label']).'</label>';
	}
	print '</div>';
}

/**
 * Build a sortable header link.
 *
 * @param string            $label          Translation key
 * @param string            $field          Field key
 * @param string            $sortfield      Current sort field
 * @param string            $sortorder      Current sort order
 * @param array<int,string> $sortablefields Sortable fields
 * @return string
 */
function lmdbzoning_cache_sort_link($label, $field, $sortfield, $sortorder, array $sortablefields)
{
	global $langs;

	if (!in_array($field, $sortablefields, true)) {
		return $langs->trans($label);
	}
	$neworder = ($sortfield === $field && $sortorder === 'ASC') ? 'DESC' : 'ASC';
	$params = $_GET;
	$params['sortfield'] = $field;
	$params['sortorder'] = $neworder;

	return '<a href="'.dol_escape_htmltag($_SERVER['PHP_SELF'].'?'.http_build_query($params)).'">'.$langs->trans($label).'</a>';
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
