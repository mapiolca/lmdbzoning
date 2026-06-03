<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningobjectzone.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningprofile.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$object = new LmdbZoningObjectZone($db);
$service = new LmdbZoningService($db);
$limit = GETPOSTINT('limit') > 0 ? GETPOSTINT('limit') : 50;
$page = GETPOSTINT('page');
$offset = $page * $limit;
$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 't.rowid';
$sortorder = strtoupper(GETPOST('sortorder', 'aZ09comma') ?: 'DESC');
$sortorder = $sortorder === 'ASC' ? 'ASC' : 'DESC';

$search = array(
	'rowid' => GETPOST('search_rowid', 'int'),
	'fk_profile' => GETPOST('search_fk_profile', 'int'),
	'element_type' => GETPOST('search_element_type', 'alphanohtml'),
	'fk_element' => GETPOST('search_fk_element', 'int'),
	'object_ref_name' => GETPOST('search_object_ref_name', 'alphanohtml'),
	'zone_code' => GETPOST('search_zone_code', 'alphanohtml'),
	'calculation_status' => GETPOST('search_calculation_status', 'alphanohtml'),
);

$arrayfields = array(
	't.rowid' => array('label' => 'ID', 'checked' => 0, 'position' => 1),
	't.fk_profile' => array('label' => 'LmdbZoningProfile', 'checked' => 1, 'position' => 10),
	'object_ref_name' => array('label' => 'ReferenceName', 'checked' => 1, 'position' => 20),
	't.element_type' => array('label' => 'ElementType', 'checked' => 1, 'position' => 30),
	't.fk_element' => array('label' => 'ElementId', 'checked' => 0, 'position' => 40),
	't.zone_code' => array('label' => 'ZoneCode', 'checked' => 1, 'position' => 50),
	't.calculation_status' => array('label' => 'CalculationStatus', 'checked' => 1, 'position' => 60),
	't.distance_km' => array('label' => 'DistanceKm', 'checked' => 1, 'position' => 70),
	't.date_calculation' => array('label' => 'CalculationDate', 'checked' => 1, 'position' => 80),
	't.tms' => array('label' => 'DateModification', 'checked' => 0, 'position' => 90),
);
$visiblefields = lmdbzoning_list_visible_fields($arrayfields);
$sortablefields = array('t.rowid', 't.fk_profile', 'object_ref_name', 't.element_type', 't.fk_element', 't.zone_code', 't.calculation_status', 't.distance_km', 't.date_calculation', 't.tms');
if (!in_array($sortfield, $sortablefields, true)) {
	$sortfield = 't.rowid';
}

$where = array('t.entity = '.((int) $conf->entity));
if ($search['rowid'] > 0) {
	$where[] = 't.rowid = '.((int) $search['rowid']);
}
if ($search['fk_profile'] > 0) {
	$where[] = 't.fk_profile = '.((int) $search['fk_profile']);
}
if ($search['element_type'] !== '') {
	$where[] = "t.element_type LIKE '%".$db->escape($search['element_type'])."%'";
}
if ($search['fk_element'] > 0) {
	$where[] = 't.fk_element = '.((int) $search['fk_element']);
}
if ($search['zone_code'] !== '') {
	$where[] = "t.zone_code LIKE '%".$db->escape($search['zone_code'])."%'";
}
if ($search['calculation_status'] !== '') {
	$where[] = "t.calculation_status LIKE '%".$db->escape($search['calculation_status'])."%'";
}

$num = 0;
$needsReferenceScan = ($search['object_ref_name'] !== '' || $sortfield === 'object_ref_name');
if (!$needsReferenceScan) {
	$sql = 'SELECT COUNT(t.rowid) as nb';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as t';
	$sql .= ' WHERE '.implode(' AND ', $where);
	$resql = $db->query($sql);
	if ($resql && ($obj = $db->fetch_object($resql))) {
		$num = (int) $obj->nb;
	}
}

$sql = 'SELECT t.*';
$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as t';
$sql .= ' WHERE '.implode(' AND ', $where);
if ($needsReferenceScan) {
	$sql .= ' ORDER BY t.element_type ASC, t.fk_element ASC';
} else {
	$sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
	$sql .= ' LIMIT '.((int) $limit).' OFFSET '.((int) $offset);
}
$resql = $db->query($sql);

llxHeader('', $langs->trans('ObjectZoneResults'));
print load_fiche_titre($langs->trans('ObjectZoneResults'), '', 'object_lmdbzoning@lmdbzoning');
print '<form method="GET" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
lmdbzoning_print_column_selector($arrayfields, $visiblefields);
print '<div class="div-table-responsive">';
print '<table class="liste centpercent">';
print '<tr class="liste_titre">';
foreach ($arrayfields as $key => $field) {
	if (empty($visiblefields[$key])) {
		continue;
	}
	print '<th>'.lmdbzoning_sort_link($field['label'], $key, $sortfield, $sortorder, $sortablefields).'</th>';
}
print '<th></th></tr>';
print '<tr class="liste_titre_filter">';
foreach ($arrayfields as $key => $field) {
	if (empty($visiblefields[$key])) {
		continue;
	}
	print '<td>'.lmdbzoning_objectzone_filter_input($key, $search).'</td>';
}
print '<td class="right"><input class="button" type="submit" value="'.$langs->trans('Search').'"></td></tr>';

$shown = 0;
$rows = array();
if ($resql) {
	while ($row = $db->fetch_object($resql)) {
		$referenceName = $service->renderLinkedObjectNomUrl($row->element_type, (int) $row->fk_element);
		if ($search['object_ref_name'] !== '' && stripos(strip_tags($referenceName), $search['object_ref_name']) === false) {
			continue;
		}
		$rows[] = array('row' => $row, 'reference_name' => $referenceName);
	}
}
if ($needsReferenceScan) {
	if ($sortfield === 'object_ref_name') {
		usort($rows, 'lmdbzoning_sort_objectzone_reference_name');
		if ($sortorder === 'DESC') {
			$rows = array_reverse($rows);
		}
	}
	$num = count($rows);
	$rows = array_slice($rows, $offset, $limit);
}
foreach ($rows as $resolvedRow) {
	$row = $resolvedRow['row'];
	$referenceName = $resolvedRow['reference_name'];
	$shown++;
	print '<tr class="oddeven">';
	foreach ($arrayfields as $key => $field) {
		if (empty($visiblefields[$key])) {
			continue;
		}
		print '<td>'.lmdbzoning_objectzone_cell($object, $row, $key, $referenceName).'</td>';
	}
	print '<td class="right"><a class="button small" href="objectzone_card.php?id='.(int) $row->rowid.'">'.$langs->trans('Open').'</a></td>';
	print '</tr>';
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
 * Return selected list fields.
 *
 * @param array<string,array<string,mixed>> $arrayfields Available fields
 * @return array<string,bool>
 */
function lmdbzoning_list_visible_fields(array $arrayfields)
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
function lmdbzoning_print_column_selector(array $arrayfields, array $visiblefields)
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
function lmdbzoning_sort_link($label, $field, $sortfield, $sortorder, array $sortablefields)
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
 * Render filter input for object zone lists.
 *
 * @param string              $field  Field key
 * @param array<string,mixed> $search Search values
 * @return string
 */
function lmdbzoning_objectzone_filter_input($field, array $search)
{
	if ($field === 't.rowid') {
		return '<input class="flat width50" type="text" name="search_rowid" value="'.dol_escape_htmltag($search['rowid'] ?: '').'">';
	}
	if ($field === 't.fk_profile') {
		return '<input class="flat width75" type="text" name="search_fk_profile" value="'.dol_escape_htmltag($search['fk_profile'] ?: '').'">';
	}
	if ($field === 'object_ref_name') {
		return '<input class="flat maxwidth100" type="text" name="search_object_ref_name" value="'.dol_escape_htmltag($search['object_ref_name']).'">';
	}
	if ($field === 't.element_type') {
		return '<input class="flat maxwidth100" type="text" name="search_element_type" value="'.dol_escape_htmltag($search['element_type']).'">';
	}
	if ($field === 't.fk_element') {
		return '<input class="flat width75" type="text" name="search_fk_element" value="'.dol_escape_htmltag($search['fk_element'] ?: '').'">';
	}
	if ($field === 't.zone_code') {
		return '<input class="flat maxwidth100" type="text" name="search_zone_code" value="'.dol_escape_htmltag($search['zone_code']).'">';
	}
	if ($field === 't.calculation_status') {
		return '<input class="flat maxwidth100" type="text" name="search_calculation_status" value="'.dol_escape_htmltag($search['calculation_status']).'">';
	}

	return '';
}

/**
 * Render one object zone cell.
 *
 * @param LmdbZoningObjectZone $object        Object definition
 * @param stdClass             $row           SQL row
 * @param string               $field         Field key
 * @param string               $referenceName Linked object reference/name
 * @return string
 */
function lmdbzoning_objectzone_cell($object, $row, $field, $referenceName)
{
	if ($field === 'object_ref_name') {
		return $referenceName;
	}
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

/**
 * Sort object zone rows by resolved reference/name.
 *
 * @param array<string,mixed> $a First row
 * @param array<string,mixed> $b Second row
 * @return int
 */
function lmdbzoning_sort_objectzone_reference_name(array $a, array $b)
{
	return strcasecmp(strip_tags($a['reference_name']), strip_tags($b['reference_name']));
}
