<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningcommonobject.class.php');

/**
 * LmdbZoning profile.
 */
class LmdbZoningProfile extends LmdbZoningCommonObject
{
	public $element = 'lmdbzoning_profile';
	public $table_element = 'lmdbzoning_profile';
	public $picto = 'lmdbzoning@lmdbzoning';
	public $ismultientitymanaged = 1;

	public $ref;
	public $label;
	public $fk_referencepoint;
	public $distance_method = 'air_distance';
	public $unit = 'km';
	public $description;
	public $active = 1;
	public $datec;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 5),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 10, 'searchall' => 1),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 20, 'searchall' => 1),
		'fk_referencepoint' => array('type' => 'integer:LmdbZoningReferencePoint:lmdbzoning/class/referencepoint.class.php', 'label' => 'ReferencePoint', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 30),
		'distance_method' => array('type' => 'varchar(32)', 'label' => 'DistanceMethod', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => 'air_distance', 'position' => 40),
		'unit' => array('type' => 'varchar(16)', 'label' => 'Unit', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => 'km', 'position' => 50),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => 1, 'visible' => 1, 'position' => 60),
		'active' => array('type' => 'boolean', 'label' => 'Active', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 70),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 500),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 501),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -2, 'position' => 510),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'position' => 511),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 1000),
	);

	/**
	 * Fetch profile by id with a strict entity filter.
	 *
	 * @param int $id     Profile id
	 * @param int $entity Entity id
	 * @return int
	 */
	public function fetchInEntity($id, $entity)
	{
		$id = (int) $id;
		$entity = (int) $entity;
		if ($id <= 0 || $entity <= 0) {
			$this->error = 'Missing profile id or entity';
			$this->errors[] = $this->error;
			return -1;
		}

		$sql = 'SELECT t.* FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$sql .= ' AND t.entity = '.$entity;
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
		if (!$this->db->num_rows($resql)) {
			return 0;
		}

		$this->hydrateFromRow($this->db->fetch_object($resql));

		return 1;
	}
}
