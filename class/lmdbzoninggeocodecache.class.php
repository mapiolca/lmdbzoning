<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningcommonobject.class.php');

/**
 * Geocoding cache entry.
 */
class LmdbZoningGeocodeCache extends LmdbZoningCommonObject
{
	public $element = 'lmdbzoning_geocodecache';
	public $table_element = 'lmdbzoning_geocode_cache';
	public $picto = 'lmdbzoning@lmdbzoning';
	public $ismultientitymanaged = 1;

	public $address_hash;
	public $address_raw;
	public $address_normalized;
	public $latitude;
	public $longitude;
	public $source;
	public $confidence_score;
	public $status;
	public $message;
	public $datec;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 5),
		'address_hash' => array('type' => 'varchar(64)', 'label' => 'AddressHash', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 10),
		'address_raw' => array('type' => 'text', 'label' => 'RawAddress', 'enabled' => 1, 'visible' => 1, 'position' => 20),
		'address_normalized' => array('type' => 'text', 'label' => 'NormalizedAddress', 'enabled' => 1, 'visible' => 1, 'position' => 30),
		'latitude' => array('type' => 'double', 'label' => 'Latitude', 'enabled' => 1, 'visible' => 1, 'position' => 40),
		'longitude' => array('type' => 'double', 'label' => 'Longitude', 'enabled' => 1, 'visible' => 1, 'position' => 50),
		'source' => array('type' => 'varchar(64)', 'label' => 'Source', 'enabled' => 1, 'visible' => 1, 'position' => 60),
		'confidence_score' => array('type' => 'double', 'label' => 'ConfidenceScore', 'enabled' => 1, 'visible' => 1, 'position' => 70),
		'status' => array('type' => 'varchar(32)', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 80),
		'message' => array('type' => 'text', 'label' => 'Message', 'enabled' => 1, 'visible' => 1, 'position' => 90),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 500),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 501),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -2, 'position' => 510),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'position' => 511),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 1000),
	);

	protected function getCardPage()
	{
		return 'geocode_cache_card.php';
	}
}
