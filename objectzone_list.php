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
$form = new Form($db);

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
	'fk_profile' => $removefilter ? 0 : GETPOST('search_fk_profile', 'int'),
	'element_type' => $removefilter ? '' : GETPOST('search_element_type', 'alphanohtml'),
	'fk_element' => $removefilter ? 0 : GETPOST('search_fk_element', 'int'),
	'object_ref_name' => $removefilter ? '' : GETPOST('search_object_ref_name', 'alphanohtml'),
	'zone_code' => $removefilter ? '' : GETPOST('search_zone_code', 'alphanohtml'),
	'calculation_status' => $removefilter ? '' : GETPOST('search_calculation_status', 'alphanohtml'),
);

$arrayfields = array(
	't.rowid' => array('label' => 'ID', 'checked' => 0, 'enabled' => 1, 'position' => 1),
	't.fk_profile' => array('label' => 'LmdbZoningProfile', 'checked' => 1, 'enabled' => 1, 'position' => 10),
	'object_ref_name' => array('label' => 'ReferenceName', 'checked' => 1, 'enabled' => 1, 'position' => 20),
	't.element_type' => array('label' => 'ElementType', 'checked' => 1, 'enabled' => 1, 'position' => 30),
	't.fk_element' => array('label' => 'ElementId', 'checked' => 0, 'enabled' => 1, 'position' => 40),
	't.zone_code' => array('label' => 'ZoneCode', 'checked' => 1, 'enabled' => 1, 'position' => 50),
	't.calculation_status' => array('label' => 'CalculationStatus', 'checked' => 1, 'enabled' => 1, 'position' => 60),
	't.distance_km' => array('label' => 'DistanceKm', 'checked' => 1, 'enabled' => 1, 'position' => 70),
	't.date_calculation' => array('label' => 'CalculationDate', 'checked' => 1, 'enabled' => 1, 'position' => 80),
	't.tms' => array('label' => 'DateModification', 'checked' => 0, 'enabled' => 1, 'position' => 90),
);
$sortablefields = array('t.rowid', 't.fk_profile', 't.element_type', 't.fk_element', 't.zone_code', 't.calculation_status', 't.distance_km', 't.date_calculation', 't.tms');
if (!in_array($sortfield, $sortablefields, true)) {
	$sortfield = 't.rowid';
}

$param = lmdbzoning_objectzone_build_param($search);
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

$needsReferenceScan = ($search['object_ref_name'] !== '');
$nbtotalofrecords = 0;
if (!$needsReferenceScan) {
	$sql = 'SELECT COUNT(t.rowid) as nb';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as t';
	$sql .= ' WHERE '.implode(' AND ', $where);
	$resql = $db->query($sql);
	if ($resql && ($obj = $db->fetch_object($resql))) {
		$nbtotalofrecords = (int) $obj->nb;
	}
}

$sql = 'SELECT t.*';
$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as t';
$sql .= ' WHERE '.implode(' AND ', $where);
$sql .= ' ORDER BY '.$sortfield.' '.$sortorder;
if (!$needsReferenceScan) {
	$sql .= ' LIMIT '.((int) $limit).' OFFSET '.((int) $offset);
}
$resql = $db->query($sql);

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
	$nbtotalofrecords = count($rows);
	$rows = array_slice($rows, $offset, $limit);
}

$varpage = $_SERVER['PHP_SELF'];
$selectedfields = '';
if (method_exists($form, 'multiSelectArrayWithCheckbox')) {
	$checkboxleft = function_exists('getDolGlobalString') ? getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN', '') : (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN) ? '' : $conf->global->MAIN_CHECKBOX_LEFT_COLUMN);
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, $checkboxleft);
}

llxHeader('', $langs->trans('ObjectZoneResults'));
print '<form method="GET" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
if (function_exists('print_barre_liste')) {
	print_barre_liste($langs->trans('ObjectZoneResults'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', count($rows), $nbtotalofrecords, 'object_lmdbzoning@lmdbzoning', 0, '', '', $limit, 0, 0, 1);
} else {
	print load_fiche_titre($langs->trans('ObjectZoneResults'), '', 'object_lmdbzoning@lmdbzoning');
}
print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">';
print '<tr class="liste_titre">';
foreach ($arrayfields as $key => $field) {
	if (empty($field['checked'])) {
		continue;
	}
	if ($key === 'object_ref_name') {
		print '<th>'.$langs->trans($field['label']).'</th>';
	} elseif (function_exists('print_liste_field_titre')) {
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
	print '<td>'.lmdbzoning_objectzone_filter_input($key, $search).'</td>';
}
print '<td class="liste_titre center">'.lmdbzoning_show_filter_buttons($form).'</td>';
print '</tr>';

foreach ($rows as $resolvedRow) {
	$row = $resolvedRow['row'];
	$referenceName = $resolvedRow['reference_name'];
	print '<tr class="oddeven">';
	foreach ($arrayfields as $key => $field) {
		if (empty($field['checked'])) {
			continue;
		}
		print '<td>'.lmdbzoning_objectzone_cell($object, $row, $key, $referenceName).'</td>';
	}
	print '<td class="right"><a class="button small" href="objectzone_card.php?id='.(int) $row->rowid.'">'.$langs->trans('Open').'</a></td>';
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
function lmdbzoning_objectzone_build_param(array $search)
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
function lmdbzoning_show_filter_buttons($form)
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
