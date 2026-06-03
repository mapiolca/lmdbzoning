<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Common helpers for LmdbZoning business objects.
 */
abstract class LmdbZoningCommonObject extends CommonObject
{
	/**
	 * Entity id.
	 *
	 * @var int
	 */
	public $entity = 1;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var array<int,string> Error list
	 */
	public $errors = array();

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Create object.
	 *
	 * @param User $user      User that creates
	 * @param int  $notrigger 1=disable triggers
	 * @return int
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;

		if (empty($this->entity)) {
			$this->entity = (int) $conf->entity;
		}

		$this->db->begin();
		$this->datec = function_exists('dol_now') ? dol_now() : time();
		$this->fk_user_creat = !empty($user->id) ? (int) $user->id : null;

		$fields = $this->getWritableFields(false);
		$sql = 'INSERT INTO '.$this->getTableName().' ('.implode(', ', $fields).') VALUES (';
		$values = array();
		foreach ($fields as $field) {
			$values[] = $this->sqlValue($field, isset($this->$field) ? $this->$field : null);
		}
		$sql .= implode(', ', $values).')';

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->setDbError();
			$this->db->rollback();
			return -1;
		}

		$this->id = (int) $this->db->last_insert_id($this->getTableName());
		$this->rowid = $this->id;
		if (!$notrigger && $this->callLmdbZoningTrigger('CREATE', $user) < 0) {
			$this->db->rollback();
			return -1;
		}
		$this->db->commit();

		return $this->id;
	}

	/**
	 * Fetch object.
	 *
	 * @param int         $id  Object id
	 * @param string|null $ref Object ref
	 * @return int
	 */
	public function fetch($id, $ref = null)
	{
		$sql = 'SELECT t.* FROM '.$this->getTableName().' as t WHERE 1 = 1';
		if ($id > 0) {
			$sql .= ' AND t.rowid = '.((int) $id);
		} elseif ($ref !== null && $this->isValidField('ref')) {
			$sql .= " AND t.ref = '".$this->db->escape($ref)."'";
		} else {
			$this->error = 'Missing object id or ref';
			$this->errors[] = $this->error;
			return -1;
		}
		$sql .= $this->buildEntityWhere('t');

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->setDbError();
			return -1;
		}
		if (!$this->db->num_rows($resql)) {
			return 0;
		}

		$this->hydrateFromRow($this->db->fetch_object($resql));

		return 1;
	}

	/**
	 * Fetch all objects.
	 *
	 * @param string               $sortorder Sort order
	 * @param string               $sortfield Sort field
	 * @param int                  $limit     Limit
	 * @param int                  $offset    Offset
	 * @param array<string,string> $filter    Filters
	 * @param string               $filtermode Filter mode
	 * @return array<int,static>|int
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		$sql = 'SELECT t.* FROM '.$this->getTableName().' as t WHERE 1 = 1';
		$sql .= $this->buildEntityWhere('t');
		$sql .= $this->buildFilterWhere($filter, $filtermode);

		$field = $this->normalizeFieldName($sortfield ?: 'rowid');
		if (!$this->isValidField($field) && $field !== 'rowid') {
			$field = 'rowid';
		}
		$order = strtoupper($sortorder) === 'ASC' ? 'ASC' : 'DESC';
		$sql .= ' ORDER BY t.'.$field.' '.$order;
		if ($limit > 0) {
			$sql .= $this->db->plimit((int) $limit, (int) $offset);
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->setDbError();
			return -1;
		}

		$objects = array();
		while ($row = $this->db->fetch_object($resql)) {
			$class = get_class($this);
			$item = new $class($this->db);
			$item->hydrateFromRow($row);
			$objects[] = $item;
		}

		return $objects;
	}

	/**
	 * Update object.
	 *
	 * @param User $user      User that updates
	 * @param int  $notrigger 1=disable triggers
	 * @return int
	 */
	public function update($user, $notrigger = 0)
	{
		$id = !empty($this->id) ? (int) $this->id : (!empty($this->rowid) ? (int) $this->rowid : 0);
		if ($id <= 0) {
			$this->error = 'Missing object id';
			$this->errors[] = $this->error;
			return -1;
		}

		$this->db->begin();
		$this->fk_user_modif = !empty($user->id) ? (int) $user->id : null;

		$sets = array();
		foreach ($this->getWritableFields(true) as $field) {
			$sets[] = $field.' = '.$this->sqlValue($field, isset($this->$field) ? $this->$field : null);
		}
		$sql = 'UPDATE '.$this->getTableName().' SET '.implode(', ', $sets).' WHERE rowid = '.$id;
		$sql .= $this->buildEntityWhere('');

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->setDbError();
			$this->db->rollback();
			return -1;
		}
		if (!$notrigger && $this->callLmdbZoningTrigger('UPDATE', $user) < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();

		return 1;
	}

	/**
	 * Delete object.
	 *
	 * @param User $user      User that deletes
	 * @param int  $notrigger 1=disable triggers
	 * @return int
	 */
	public function delete($user, $notrigger = 0)
	{
		$id = !empty($this->id) ? (int) $this->id : (!empty($this->rowid) ? (int) $this->rowid : 0);
		if ($id <= 0) {
			$this->error = 'Missing object id';
			$this->errors[] = $this->error;
			return -1;
		}

		$this->db->begin();
		$sql = 'DELETE FROM '.$this->getTableName().' WHERE rowid = '.$id;
		$sql .= $this->buildEntityWhere('');
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->setDbError();
			$this->db->rollback();
			return -1;
		}
		if (!$notrigger && $this->callLmdbZoningTrigger('DELETE', $user) < 0) {
			$this->db->rollback();
			return -1;
		}
		$this->db->commit();

		return 1;
	}

	/**
	 * Return SQL table name.
	 *
	 * @return string
	 */
	protected function getTableName()
	{
		return MAIN_DB_PREFIX.$this->table_element;
	}

	/**
	 * Return writable field names.
	 *
	 * @param bool $forUpdate Update mode
	 * @return array<int,string>
	 */
	private function getWritableFields($forUpdate)
	{
		$excluded = array('rowid', 'tms', 'import_key');
		if ($forUpdate) {
			$excluded[] = 'entity';
			$excluded[] = 'datec';
			$excluded[] = 'fk_user_creat';
		} else {
			$excluded[] = 'fk_user_modif';
		}

		$fields = array();
		foreach ($this->fields as $field => $definition) {
			if (in_array($field, $excluded, true)) {
				continue;
			}
			if (!empty($definition['enabled']) && $definition['enabled'] != 1) {
				continue;
			}
			$fields[] = $field;
		}

		return $fields;
	}

	/**
	 * Convert value to SQL literal.
	 *
	 * @param string $field Field name
	 * @param mixed  $value Value
	 * @return string
	 */
	private function sqlValue($field, $value)
	{
		$definition = isset($this->fields[$field]) ? $this->fields[$field] : array();
		$type = isset($definition['type']) ? $definition['type'] : '';
		$nullable = empty($definition['notnull']);

		if ($field === 'entity' && ($value === null || $value === '')) {
			global $conf;
			return (string) ((int) $conf->entity);
		}
		if (($value === null || $value === '') && $nullable) {
			return 'NULL';
		}
		if (strpos($type, 'integer') === 0 || strpos($type, 'boolean') === 0) {
			return (string) ((int) $value);
		}
		if (strpos($type, 'double') === 0) {
			return ($value === null || $value === '') ? ($nullable ? 'NULL' : '0') : (string) price2num($value);
		}
		if (strpos($type, 'datetime') === 0 || strpos($type, 'timestamp') === 0) {
			if (is_numeric($value)) {
				return "'".$this->db->idate($value)."'";
			}
			return $value === '' ? 'NULL' : "'".$this->db->escape($value)."'";
		}

		return "'".$this->db->escape((string) $value)."'";
	}

	/**
	 * Fill object properties from SQL row.
	 *
	 * @param stdClass $row SQL row
	 * @return void
	 */
	protected function hydrateFromRow($row)
	{
		$this->id = (int) $row->rowid;
		$this->rowid = (int) $row->rowid;
		foreach ($this->fields as $field => $definition) {
			if (isset($row->$field)) {
				$this->$field = $row->$field;
			}
		}
	}

	/**
	 * Build entity filter.
	 *
	 * @param string $alias Table alias, without dot
	 * @return string
	 */
	private function buildEntityWhere($alias = 't')
	{
		global $conf;

		if (!$this->isValidField('entity')) {
			return '';
		}
		$prefix = $alias !== '' ? $alias.'.' : '';
		if (function_exists('getEntity')) {
			$entityElement = !empty($this->element) ? $this->element : $this->table_element;
			return ' AND '.$prefix.'entity IN ('.$this->db->sanitize(getEntity($entityElement)).')';
		}

		return ' AND '.$prefix.'entity = '.((int) $conf->entity);
	}

	/**
	 * Build filter SQL.
	 *
	 * @param array<string,string> $filter Filters
	 * @param string               $filtermode Filter mode
	 * @return string
	 */
	private function buildFilterWhere(array $filter, $filtermode = 'AND')
	{
		$parts = array();
		$mode = strtoupper($filtermode) === 'OR' ? ' OR ' : ' AND ';
		foreach ($filter as $field => $condition) {
			$field = $this->normalizeFieldName($field);
			if (!$this->isValidField($field)) {
				continue;
			}
			$condition = trim((string) $condition);
			if ($condition === '') {
				continue;
			}
			if ($condition[0] === '=') {
				$parts[] = 't.'.$field.' = '.$this->sqlValue($field, substr($condition, 1));
			} elseif (preg_match('/^(>=|<=|>|<)(.*)$/', $condition, $matches)) {
				$parts[] = 't.'.$field.' '.$matches[1].' '.$this->sqlValue($field, trim($matches[2]));
			} else {
				$parts[] = 't.'.$field.' = '.$this->sqlValue($field, $condition);
			}
		}

		return empty($parts) ? '' : ' AND ('.implode($mode, $parts).')';
	}

	/**
	 * Normalize a field name.
	 *
	 * @param string $field Field name
	 * @return string
	 */
	private function normalizeFieldName($field)
	{
		$field = (string) $field;
		if (strpos($field, '.') !== false) {
			$parts = explode('.', $field);
			$field = end($parts);
		}

		return preg_replace('/[^a-zA-Z0-9_]/', '', $field);
	}

	/**
	 * Check field exists.
	 *
	 * @param string $field Field name
	 * @return bool
	 */
	private function isValidField($field)
	{
		return $field === 'rowid' || array_key_exists($field, $this->fields);
	}

	/**
	 * Store database error.
	 *
	 * @return void
	 */
	private function setDbError()
	{
		$this->error = $this->db->lasterror();
		$this->errors[] = $this->error;
	}

	/**
	 * Call a lmdbzoning business trigger when CommonObject supports it.
	 *
	 * @param string $action Action suffix
	 * @param User   $user   User
	 * @return int
	 */
	private function callLmdbZoningTrigger($action, $user)
	{
		if (!method_exists($this, 'call_trigger')) {
			return 0;
		}
		$triggerCode = strtoupper($this->element.'_'.$action);
		$result = $this->call_trigger($triggerCode, $user);
		if ($result < 0) {
			$this->error = method_exists($this, 'errorsToString') ? $this->errorsToString() : 'Trigger '.$triggerCode.' failed';
			if ($this->error !== '') {
				$this->errors[] = $this->error;
			}
		}

		return $result;
	}

	/**
	 * Return object URL label.
	 *
	 * @param int    $withpicto Include picto
	 * @param string $option    Option
	 * @return string
	 */
	public function getNomUrl($withpicto = 0, $option = '')
	{
		global $langs;

		if (!empty($this->ref)) {
			$label = $this->ref;
		} elseif (!empty($this->zone_code)) {
			$label = $this->zone_code;
		} elseif (!empty($this->label)) {
			$label = $this->label;
		} else {
			$label = (string) $this->id;
		}
		$url = dol_buildpath('/lmdbzoning/'.$this->getCardPage(), 1).'?id='.(int) $this->id;
		$linkclose = '';
		$link = '<a href="'.$url.'">';
		if ($withpicto) {
			$link .= img_object($langs->trans('Show'), $this->picto).' ';
		}
		$linkclose .= '</a>';

		return $link.dol_escape_htmltag($label).$linkclose;
	}

	/**
	 * Return default card page name.
	 *
	 * @return string
	 */
	protected function getCardPage()
	{
		return str_replace('lmdbzoning_', '', $this->element).'_card.php';
	}

	/**
	 * Return status label.
	 *
	 * @param int $mode Display mode
	 * @return string
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->active, $mode);
	}

	/**
	 * Return status label.
	 *
	 * @param int $status Status
	 * @param int $mode   Display mode
	 * @return string
	 */
	public function LibStatut($status, $mode = 0)
	{
		global $langs;

		if ((int) $status === 1) {
			return dolGetStatus($langs->trans('Enabled'), '', '', 'status4', $mode);
		}

		return dolGetStatus($langs->trans('Disabled'), '', '', 'status5', $mode);
	}

	/**
	 * Initialize object as specimen.
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		$this->entity = 1;
		$this->ref = 'SPECIMEN';
		$this->label = 'Specimen';
		$this->active = 1;
	}
}
