<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningcommonobject.class.php');

/**
 * Reference point for LmdbZoning calculations.
 */
class LmdbZoningReferencePoint extends LmdbZoningCommonObject
{
	public $element = 'lmdbzoning_referencepoint';
	public $table_element = 'lmdbzoning_referencepoint';
	public $picto = 'lmdbzoning@lmdbzoning';
	public $ismultientitymanaged = 1;

	public $ref;
	public $label;
	public $address;
	public $zip;
	public $town;
	public $country_code;
	public $latitude;
	public $longitude;
	public $geocode_status;
	public $geocode_source;
	public $geocode_score;
	public $active = 1;
	public $datec;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 5),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 10, 'searchall' => 1),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 20, 'searchall' => 1),
		'address' => array('type' => 'varchar(255)', 'label' => 'Address', 'enabled' => 1, 'visible' => 1, 'position' => 30, 'searchall' => 1),
		'zip' => array('type' => 'varchar(25)', 'label' => 'Zip', 'enabled' => 1, 'visible' => 1, 'position' => 40),
		'town' => array('type' => 'varchar(128)', 'label' => 'Town', 'enabled' => 1, 'visible' => 1, 'position' => 50, 'searchall' => 1),
		'country_code' => array('type' => 'varchar(10)', 'label' => 'CountryCode', 'enabled' => 1, 'visible' => 1, 'position' => 60),
		'latitude' => array('type' => 'double', 'label' => 'Latitude', 'enabled' => 1, 'visible' => 1, 'position' => 70),
		'longitude' => array('type' => 'double', 'label' => 'Longitude', 'enabled' => 1, 'visible' => 1, 'position' => 80),
		'geocode_status' => array('type' => 'varchar(32)', 'label' => 'GeocodeStatus', 'enabled' => 1, 'visible' => 1, 'position' => 90),
		'geocode_source' => array('type' => 'varchar(64)', 'label' => 'GeocodeSource', 'enabled' => 1, 'visible' => 1, 'position' => 100),
		'geocode_score' => array('type' => 'double', 'label' => 'GeocodeScore', 'enabled' => 1, 'visible' => 1, 'position' => 110),
		'active' => array('type' => 'boolean', 'label' => 'Active', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 120),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 500),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 501),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -2, 'position' => 510),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'position' => 511),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 1000),
	);

	public function getAddressArray()
	{
		return array(
			'address' => $this->address,
			'zip' => $this->zip,
			'town' => $this->town,
			'country_code' => $this->country_code,
			'latitude' => $this->latitude,
			'longitude' => $this->longitude,
		);
	}
}
