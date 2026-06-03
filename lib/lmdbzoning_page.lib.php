<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Check module and user permission.
 *
 * @param string $right Required right
 * @return void
 */
function lmdbzoning_check_access($right = 'read')
{
	global $conf, $user;

	if (function_exists('isModEnabled')) {
		if (!isModEnabled('lmdbzoning')) {
			accessforbidden();
		}
	} elseif (empty($conf->lmdbzoning->enabled)) {
		accessforbidden();
	}
	if (!method_exists($user, 'hasRight') || !$user->hasRight('lmdbzoning', 'lmdbzoning', $right)) {
		accessforbidden();
	}
}

/**
 * Check token for POST actions.
 *
 * @return void
 */
function lmdbzoning_check_post_token()
{
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		return;
	}
	if (function_exists('checkToken')) {
		checkToken();
		return;
	}
	if (empty($_SESSION['newtoken']) || GETPOST('token', 'alpha') !== $_SESSION['newtoken']) {
		accessforbidden('Bad token');
	}
}

/**
 * Read form fields into object.
 *
 * @param CommonObject $object Object
 * @return void
 */
function lmdbzoning_read_object_fields($object)
{
	foreach ($object->fields as $field => $definition) {
		if (in_array($field, array('rowid', 'entity', 'datec', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key'), true)) {
			continue;
		}
		if (lmdbzoning_is_system_status_field($field)) {
			continue;
		}
		if (!empty($definition['enabled']) && $definition['enabled'] != 1) {
			continue;
		}
		$type = isset($definition['type']) ? $definition['type'] : '';
		if (strpos($type, 'integer') === 0) {
			$rawvalue = GETPOST($field, 'alphanohtml');
			$object->$field = ($rawvalue === '' && empty($definition['notnull'])) ? null : GETPOST($field, 'int');
		} elseif (strpos($type, 'boolean') === 0) {
			$object->$field = GETPOST($field, 'int');
		} elseif (strpos($type, 'double') === 0) {
			$value = GETPOST($field, 'alphanohtml');
			$object->$field = ($value === '') ? null : price2num($value);
		} elseif (strpos($type, 'text') === 0) {
			$object->$field = GETPOST($field, 'restricthtml');
		} else {
			$object->$field = GETPOST($field, 'alphanohtml');
		}
	}
}

/**
 * Render editable object fields.
 *
 * @param CommonObject $object Object
 * @return void
 */
function lmdbzoning_print_object_form_fields($object)
{
	global $langs, $form;

	foreach ($object->fields as $field => $definition) {
		if (in_array($field, array('rowid', 'entity', 'datec', 'tms', 'fk_user_creat', 'fk_user_modif', 'import_key'), true)) {
			continue;
		}
		if (lmdbzoning_is_system_status_field($field)) {
			continue;
		}
		if (empty($definition['visible']) || $definition['visible'] < 0) {
			continue;
		}
		$type = isset($definition['type']) ? $definition['type'] : '';
		$label = $langs->trans(isset($definition['label']) ? $definition['label'] : $field);
		$value = isset($object->$field) ? $object->$field : (isset($definition['default']) ? $definition['default'] : '');
		print '<tr><td class="titlefieldcreate">'.$label.'</td><td>';
		if (lmdbzoning_is_closed_choice_field($field)) {
			print lmdbzoning_render_closed_choice_select($field, $value, empty($definition['notnull']));
		} elseif (strpos($type, 'text') === 0) {
			print '<textarea class="flat minwidth500" name="'.$field.'" rows="3">'.dol_escape_htmltag($value).'</textarea>';
		} elseif (strpos($type, 'boolean') === 0) {
			print $form->selectyesno($field, (string) $value, 1);
		} elseif (lmdbzoning_is_resolvable_fk_field($field, $definition)) {
			print lmdbzoning_render_fk_select($field, $definition, $value, $object);
		} else {
			print '<input class="flat minwidth300" type="text" name="'.$field.'" value="'.dol_escape_htmltag($value).'">';
		}
		print '</td></tr>';
	}
}

/**
 * Render read-only object fields.
 *
 * @param CommonObject $object Object
 * @return void
 */
function lmdbzoning_print_object_view_fields($object)
{
	global $langs;

	foreach ($object->fields as $field => $definition) {
		if (in_array($field, array('rowid', 'entity', 'fk_user_creat', 'fk_user_modif', 'import_key'), true)) {
			continue;
		}
		if (empty($definition['visible']) || $definition['visible'] < 0) {
			continue;
		}
		$label = $langs->trans(isset($definition['label']) ? $definition['label'] : $field);
		$value = isset($object->$field) ? $object->$field : '';
		print '<tr><td class="titlefield">'.$label.'</td><td>'.lmdbzoning_render_field_output($field, $definition, $value).'</td></tr>';
	}
}

/**
 * Handle simple CRUD actions.
 *
 * @param CommonObject $object Object
 * @param string       $cardPage Card page
 * @return void
 */
function lmdbzoning_handle_card_actions($object, $cardPage)
{
	global $user, $langs;

	$action = GETPOST('action', 'aZ09');
	$id = GETPOSTINT('id');
	if (in_array($action, array('add', 'update', 'delete'), true)) {
		lmdbzoning_check_post_token();
	}

	if ($action === 'add') {
		lmdbzoning_check_access('write');
		lmdbzoning_read_object_fields($object);
		$result = $object->create($user);
		if ($result > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: '.$cardPage.'?id='.(int) $result);
			exit;
		}
		setEventMessages($object->error, $object->errors, 'errors');
	} elseif ($action === 'update' && $id > 0) {
		lmdbzoning_check_access('write');
		if ($object->fetch($id) <= 0) {
			accessforbidden();
		}
		lmdbzoning_read_object_fields($object);
		$result = $object->update($user);
		if ($result > 0) {
			setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
			header('Location: '.$cardPage.'?id='.(int) $id);
			exit;
		}
		setEventMessages($object->error, $object->errors, 'errors');
	} elseif ($action === 'delete' && $id > 0) {
		lmdbzoning_check_access('delete');
		if ($object->fetch($id) <= 0) {
			accessforbidden();
		}
		$result = $object->delete($user);
		if ($result > 0) {
			setEventMessages($langs->trans('RecordDeleted'), null, 'mesgs');
			header('Location: '.str_replace('_card.php', '_list.php', $cardPage));
			exit;
		}
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

/**
 * Render a simple list for an object.
 *
 * @param CommonObject $object Object
 * @param string       $title  Page title
 * @param string       $cardPage Card page
 * @param array<string,string> $filters Filters
 * @return void
 */
function lmdbzoning_print_object_list($object, $title, $cardPage, array $filters = array())
{
	global $langs;

	$limit = GETPOSTINT('limit') > 0 ? GETPOSTINT('limit') : 50;
	$page = GETPOSTINT('page');
	$sortfield = GETPOST('sortfield', 'aZ09comma') ?: 't.rowid';
	$sortorder = GETPOST('sortorder', 'aZ09comma') ?: 'DESC';
	$objects = $object->fetchAll($sortorder, $sortfield, $limit, $page * $limit, $filters);
	if (!is_array($objects)) {
		setEventMessages($object->error, $object->errors, 'errors');
		$objects = array();
	}

	if ($title !== '') {
		print load_fiche_titre($title, '<a class="butAction" href="'.$cardPage.'?action=create">'.$langs->trans('New').'</a>', 'object_'.$object->picto);
	}
	print '<div class="div-table-responsive"><table class="liste centpercent">';
	print '<tr class="liste_titre">';
	foreach ($object->fields as $field => $definition) {
		if (empty($definition['visible']) || $definition['visible'] < 0) {
			continue;
		}
		print '<th>'.$langs->trans(isset($definition['label']) ? $definition['label'] : $field).'</th>';
	}
	print '<th></th></tr>';
	foreach ($objects as $item) {
		print '<tr class="oddeven">';
		foreach ($object->fields as $field => $definition) {
			if (empty($definition['visible']) || $definition['visible'] < 0) {
				continue;
			}
			$value = isset($item->$field) ? $item->$field : '';
			print '<td>'.lmdbzoning_render_field_output($field, $definition, $value).'</td>';
		}
		print '<td class="right"><a class="button small" href="'.$cardPage.'?id='.(int) $item->id.'">'.$langs->trans('Open').'</a></td>';
		print '</tr>';
	}
	if (empty($objects)) {
		print '<tr><td colspan="20"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
	}
	print '</table></div>';
}

/**
 * Check if a field can be rendered as a resolved FK select.
 *
 * @param string              $field Field name
 * @param array<string,mixed> $definition Field definition
 * @return bool
 */
function lmdbzoning_is_resolvable_fk_field($field, array $definition)
{
	if (!preg_match('/^integer:[^:]+:[^:]+$/', isset($definition['type']) ? $definition['type'] : '')) {
		return false;
	}
	if (in_array($field, array('fk_user_creat', 'fk_user_modif', 'fk_user_calculation', 'fk_user_override'), true)) {
		return false;
	}

	return true;
}

/**
 * Check if a field uses a closed list of values.
 *
 * @param string $field Field name
 * @return bool
 */
function lmdbzoning_is_closed_choice_field($field)
{
	if (lmdbzoning_is_system_status_field($field)) {
		return false;
	}
	$options = lmdbzoning_get_closed_choice_options($field);

	return is_array($options);
}

/**
 * Check if a field is a module-computed status.
 *
 * @param string $field Field name
 * @return bool
 */
function lmdbzoning_is_system_status_field($field)
{
	return in_array($field, array('geocode_status', 'status', 'calculation_status'), true);
}

/**
 * Render a closed choice select enhanced with Dolibarr ajax_combobox when available.
 *
 * @param string $field Field name
 * @param mixed  $value Current value
 * @param bool   $allowEmpty Allow empty option
 * @return string
 */
function lmdbzoning_render_closed_choice_select($field, $value, $allowEmpty = true)
{
	global $form;

	$options = lmdbzoning_get_closed_choice_options($field);
	if (!is_array($options)) {
		return '<input class="flat minwidth300" type="text" name="'.$field.'" value="'.dol_escape_htmltag($value).'">';
	}

	$selected = ($value === null) ? '' : (string) $value;
	$out = $form->selectarray($field, $options, $selected, $allowEmpty ? 1 : 0, 0, 0, '', 0, 0, 0, '', 'flat minwidth300', 0);
	if (function_exists('ajax_combobox')) {
		$out .= ajax_combobox($field);
	}

	return $out;
}

/**
 * Return closed choice options for a field.
 *
 * @param string $field Field name
 * @return array<string,string>|null
 */
function lmdbzoning_get_closed_choice_options($field)
{
	global $langs;

	$definitions = array(
		'geocode_source' => array(
			'manual' => 'LmdbZoningSourceManual',
			'geoplateforme' => 'LmdbZoningSourceGeoplateforme',
		),
		'source' => array(
			'manual' => 'LmdbZoningSourceManual',
			'geoplateforme' => 'LmdbZoningSourceGeoplateforme',
		),
		'distance_method' => array(
			'air_distance' => 'LmdbZoningDistanceMethodAirDistance',
		),
		'unit' => array(
			'km' => 'LmdbZoningUnitKm',
		),
		'event_code' => array(
			'LMDBZONING_OBJECT_CALCULATE' => 'LmdbZoningEventObjectCalculate',
			'LMDBZONING_CATEGORY_APPLY' => 'LmdbZoningEventCategoryApply',
			'LMDBZONING_OBJECT_OVERRIDE' => 'LmdbZoningEventObjectOverride',
			'LMDBZONING_OBJECT_CLEAR_OVERRIDE' => 'LmdbZoningEventObjectClearOverride',
		),
		'LMDBZONING_GEOCODER_PROVIDER' => array(
			'geoplateforme' => 'LmdbZoningSourceGeoplateforme',
		),
	);
	if (!isset($definitions[$field])) {
		return null;
	}

	$options = array();
	foreach ($definitions[$field] as $value => $translationKey) {
		$options[$value] = $langs->trans($translationKey);
	}

	return $options;
}

/**
 * Render a FK select enhanced with Dolibarr ajax_combobox when available.
 *
 * @param string              $field Field name
 * @param array<string,mixed> $definition Field definition
 * @param mixed               $value Current value
 * @param CommonObject|null   $sourceObject Source object
 * @return string
 */
function lmdbzoning_render_fk_select($field, array $definition, $value, $sourceObject = null)
{
	global $form;

	$options = lmdbzoning_get_fk_options($field, $definition, $sourceObject);
	if (!is_array($options)) {
		return '<input class="flat minwidth300" type="text" name="'.$field.'" value="'.dol_escape_htmltag($value).'">';
	}

	$showempty = empty($definition['notnull']) ? 1 : 0;
	$selected = ($value === null || $value === '') ? '' : (int) $value;
	$out = $form->selectarray($field, $options, $selected, $showempty, 0, 0, '', 0, 0, 0, '', 'flat minwidth300', 0);
	if (function_exists('ajax_combobox')) {
		$out .= ajax_combobox($field);
	}

	return $out;
}

/**
 * Build select options for a FK field.
 *
 * @param string              $field Field name
 * @param array<string,mixed> $definition Field definition
 * @param CommonObject|null   $sourceObject Source object
 * @return array<int,string>|null
 */
function lmdbzoning_get_fk_options($field, array $definition, $sourceObject = null)
{
	if (strpos(isset($definition['type']) ? $definition['type'] : '', ':Categorie:') !== false) {
		return lmdbzoning_get_category_options($field);
	}

	$target = lmdbzoning_parse_fk_target($definition);
	if (empty($target)) {
		return null;
	}
	dol_include_once('/'.$target['path']);
	if (!class_exists($target['class'])) {
		return null;
	}

	$className = $target['class'];
	$object = new $className($GLOBALS['db']);
	if (empty($object->table_element)) {
		return null;
	}

	$sql = 'SELECT t.* FROM '.MAIN_DB_PREFIX.$object->table_element.' as t WHERE 1 = 1';
	if (!empty($object->fields['entity'])) {
		if (function_exists('getEntity')) {
			$sql .= ' AND t.entity IN ('.$GLOBALS['db']->sanitize(getEntity($object->table_element)).')';
		} else {
			$sql .= ' AND t.entity = '.((int) $GLOBALS['conf']->entity);
		}
	}
	if (!empty($object->fields['active'])) {
		$sql .= ' AND t.active = 1';
	}
	if ($target['class'] === 'LmdbZoningProfileZone' && in_array($field, array('calculated_fk_zone', 'fk_zone'), true) && !empty($sourceObject->fk_profile)) {
		$sql .= ' AND t.fk_profile = '.((int) $sourceObject->fk_profile);
	}
	$sql .= ' ORDER BY '.lmdbzoning_get_fk_order_sql($object);

	return lmdbzoning_fetch_options_from_sql($sql);
}

/**
 * Parse a Dolibarr integer object FK type.
 *
 * @param array<string,mixed> $definition Field definition
 * @return array{class:string,path:string}|null
 */
function lmdbzoning_parse_fk_target(array $definition)
{
	$type = isset($definition['type']) ? $definition['type'] : '';
	if (!preg_match('/^integer:([^:]+):(.+)$/', $type, $matches)) {
		return null;
	}

	return array('class' => $matches[1], 'path' => $matches[2]);
}

/**
 * Return SQL order for FK options.
 *
 * @param CommonObject $object Object
 * @return string
 */
function lmdbzoning_get_fk_order_sql($object)
{
	if (!empty($object->fields['ref'])) {
		return 't.ref ASC';
	}
	if (!empty($object->fields['zone_code'])) {
		return 't.zone_code ASC';
	}
	if (!empty($object->fields['label'])) {
		return 't.label ASC';
	}

	return 't.rowid ASC';
}

/**
 * Fetch options from SQL rows.
 *
 * @param string $sql SQL query
 * @return array<int,string>
 */
function lmdbzoning_fetch_options_from_sql($sql)
{
	$options = array();
	$resql = $GLOBALS['db']->query($sql);
	if (!$resql) {
		return $options;
	}
	while ($row = $GLOBALS['db']->fetch_object($resql)) {
		$options[(int) $row->rowid] = lmdbzoning_build_option_label($row);
	}

	return $options;
}

/**
 * Build a readable option label.
 *
 * @param stdClass $row SQL row
 * @return string
 */
function lmdbzoning_build_option_label($row)
{
	$main = '';
	if (!empty($row->ref)) {
		$main = $row->ref;
	} elseif (!empty($row->zone_code)) {
		$main = $row->zone_code;
	}
	if ($main !== '' && !empty($row->label)) {
		return $main.' - '.$row->label;
	}
	if ($main !== '') {
		return $main;
	}
	if (!empty($row->label)) {
		return $row->label;
	}

	return '#'.((int) $row->rowid);
}

/**
 * Build category options with type filtering when the target type is known.
 *
 * @param string $field Field name
 * @return array<int,string>|null
 */
function lmdbzoning_get_category_options($field)
{
	$types = lmdbzoning_get_category_types_for_field($field);
	if (!class_exists('Categorie') && file_exists(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php')) {
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	}
	if (!class_exists('Categorie')) {
		return null;
	}

	$sql = 'SELECT t.rowid, t.label FROM '.MAIN_DB_PREFIX.'categorie as t WHERE 1 = 1';
	if (is_array($types) && count($types) > 0) {
		$escapedTypes = array();
		foreach ($types as $type) {
			$escapedTypes[] = "'".$GLOBALS['db']->escape($type)."'";
		}
		$sql .= ' AND t.type IN ('.implode(',', $escapedTypes).')';
	} else {
		$sql .= ' AND 1 = 0';
	}
	if (function_exists('getEntity')) {
		$sql .= ' AND t.entity IN ('.$GLOBALS['db']->sanitize(getEntity('category')).')';
	} else {
		$sql .= ' AND t.entity = '.((int) $GLOBALS['conf']->entity);
	}
	$sql .= ' ORDER BY t.label ASC';

	return lmdbzoning_fetch_options_from_sql($sql);
}

/**
 * Return category types for a lmdbzoning FK field.
 *
 * @param string $field Field name
 * @return array<int,string>
 */
function lmdbzoning_get_category_types_for_field($field)
{
	dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
	if (class_exists('LmdbZoningService')) {
		return LmdbZoningService::getCategoryTypesForZoneField($field);
	}

	return array();
}

/**
 * Render field output.
 *
 * @param string              $field Field name
 * @param array<string,mixed> $definition Field definition
 * @param mixed               $value Value
 * @return string
 */
function lmdbzoning_render_field_output($field, array $definition, $value)
{
	if ($value === null || $value === '') {
		return '';
	}
	if (lmdbzoning_is_system_status_field($field)) {
		return lmdbzoning_render_status_badge((string) $value);
	}
	if (lmdbzoning_is_closed_choice_field($field)) {
		return lmdbzoning_render_closed_choice_output($field, $value);
	}
	if (lmdbzoning_is_resolvable_fk_field($field, $definition)) {
		$html = lmdbzoning_render_fk_output($field, $definition, (int) $value);
		if ($html !== '') {
			return $html;
		}
	}

	return dol_escape_htmltag((string) $value);
}

/**
 * Render a closed choice value.
 *
 * @param string $field Field name
 * @param mixed  $value Value
 * @return string
 */
function lmdbzoning_render_closed_choice_output($field, $value)
{
	$options = lmdbzoning_get_closed_choice_options($field);
	if (is_array($options) && isset($options[(string) $value])) {
		return dol_escape_htmltag($options[(string) $value]);
	}

	return dol_escape_htmltag((string) $value);
}

/**
 * Render a module status with Dolibarr badge.
 *
 * @param string $status Status code
 * @return string
 */
function lmdbzoning_render_status_badge($status)
{
	global $langs;

	$mapping = array(
		'pending' => array('LmdbZoningStatusPending', 'status0'),
		'ok' => array('LmdbZoningStatusOk', 'status4'),
		'ambiguous' => array('LmdbZoningStatusAmbiguous', 'status3'),
		'out_of_range' => array('LmdbZoningStatusOutOfRange', 'status3'),
		'failed' => array('LmdbZoningStatusFailed', 'status8'),
	);
	if (!isset($mapping[$status])) {
		return dol_escape_htmltag($status);
	}

	return dolGetStatus($langs->trans($mapping[$status][0]), '', '', $mapping[$status][1], 3);
}

/**
 * Render a resolved FK value.
 *
 * @param string              $field Field name
 * @param array<string,mixed> $definition Field definition
 * @param int                 $value Row id
 * @return string
 */
function lmdbzoning_render_fk_output($field, array $definition, $value)
{
	if ($value <= 0) {
		return '';
	}
	$target = lmdbzoning_parse_fk_target($definition);
	if (empty($target)) {
		return '';
	}
	dol_include_once('/'.$target['path']);
	if (!class_exists($target['class'])) {
		return dol_escape_htmltag((string) $value);
	}
	$className = $target['class'];
	$object = new $className($GLOBALS['db']);
	if (method_exists($object, 'fetch') && $object->fetch($value) > 0) {
		if (method_exists($object, 'getNomUrl')) {
			return $object->getNomUrl(1);
		}
		if (!empty($object->label)) {
			return dol_escape_htmltag($object->label);
		}
	}
	$options = lmdbzoning_get_fk_options($field, $definition);
	if (is_array($options) && isset($options[$value])) {
		return dol_escape_htmltag($options[$value]);
	}

	return dol_escape_htmltag((string) $value);
}
