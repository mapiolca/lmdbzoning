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
		if (!empty($definition['enabled']) && $definition['enabled'] != 1) {
			continue;
		}
		$type = isset($definition['type']) ? $definition['type'] : '';
		if (strpos($type, 'integer') === 0 || strpos($type, 'boolean') === 0) {
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
		if (empty($definition['visible']) || $definition['visible'] < 0) {
			continue;
		}
		$type = isset($definition['type']) ? $definition['type'] : '';
		$label = $langs->trans(isset($definition['label']) ? $definition['label'] : $field);
		$value = isset($object->$field) ? $object->$field : (isset($definition['default']) ? $definition['default'] : '');
		print '<tr><td class="titlefieldcreate">'.$label.'</td><td>';
		if (strpos($type, 'text') === 0) {
			print '<textarea class="flat minwidth500" name="'.$field.'" rows="3">'.dol_escape_htmltag($value).'</textarea>';
		} elseif (strpos($type, 'boolean') === 0) {
			print $form->selectyesno($field, (string) $value, 1);
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
		print '<tr><td class="titlefield">'.$label.'</td><td>'.dol_escape_htmltag((string) $value).'</td></tr>';
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

	print load_fiche_titre($title, '<a class="butAction" href="'.$cardPage.'?action=create">'.$langs->trans('New').'</a>', 'object_'.$object->picto);
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
			print '<td>'.dol_escape_htmltag((string) $value).'</td>';
		}
		print '<td class="right"><a class="button small" href="'.$cardPage.'?id='.(int) $item->id.'">'.$langs->trans('Open').'</a></td>';
		print '</tr>';
	}
	if (empty($objects)) {
		print '<tr><td colspan="20"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
	}
	print '</table></div>';
}
