<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningcommonobject.class.php');

/**
 * LmdbZoning result attached to any Dolibarr object.
 */
class LmdbZoningObjectZone extends LmdbZoningCommonObject
{
	public $element = 'lmdbzoning_objectzone';
	public $table_element = 'lmdbzoning_object_zone';
	public $picto = 'lmdbzoning@lmdbzoning';
	public $ismultientitymanaged = 1;

	public $fk_profile;
	public $fk_referencepoint;
	public $element_type;
	public $fk_element;
	public $address_hash;
	public $address_raw;
	public $latitude;
	public $longitude;
	public $distance_km;
	public $calculated_zone_code;
	public $calculated_fk_zone;
	public $zone_code;
	public $fk_zone;
	public $fk_categorie;
	public $calculation_status;
	public $calculation_message;
	public $manual_override = 0;
	public $override_reason;
	public $date_calculation;
	public $fk_user_calculation;
	public $date_override;
	public $fk_user_override;
	public $datec;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 5),
		'fk_profile' => array('type' => 'integer:LmdbZoningProfile:lmdbzoning/class/lmdbzoningprofile.class.php', 'label' => 'LmdbZoningProfile', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 10),
		'fk_referencepoint' => array('type' => 'integer:LmdbZoningReferencePoint:lmdbzoning/class/referencepoint.class.php', 'label' => 'ReferencePoint', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 20),
		'element_type' => array('type' => 'varchar(64)', 'label' => 'ElementType', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 30),
		'fk_element' => array('type' => 'integer', 'label' => 'ElementId', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 40),
		'address_hash' => array('type' => 'varchar(64)', 'label' => 'AddressHash', 'enabled' => 1, 'visible' => 1, 'index' => 1, 'position' => 50),
		'address_raw' => array('type' => 'text', 'label' => 'RawAddress', 'enabled' => 1, 'visible' => 1, 'position' => 60),
		'latitude' => array('type' => 'double', 'label' => 'Latitude', 'enabled' => 1, 'visible' => 1, 'position' => 70),
		'longitude' => array('type' => 'double', 'label' => 'Longitude', 'enabled' => 1, 'visible' => 1, 'position' => 80),
		'distance_km' => array('type' => 'double', 'label' => 'DistanceKm', 'enabled' => 1, 'visible' => 1, 'position' => 90),
		'calculated_zone_code' => array('type' => 'varchar(64)', 'label' => 'CalculatedZoneCode', 'enabled' => 1, 'visible' => 1, 'position' => 100),
		'calculated_fk_zone' => array('type' => 'integer:LmdbZoningProfileZone:lmdbzoning/class/lmdbzoningprofilezone.class.php', 'label' => 'CalculatedZone', 'enabled' => 1, 'visible' => 1, 'position' => 105),
		'zone_code' => array('type' => 'varchar(64)', 'label' => 'ZoneCode', 'enabled' => 1, 'visible' => 1, 'index' => 1, 'position' => 110),
		'fk_zone' => array('type' => 'integer:LmdbZoningProfileZone:lmdbzoning/class/lmdbzoningprofilezone.class.php', 'label' => 'AppliedZone', 'enabled' => 1, 'visible' => 1, 'position' => 120),
		'fk_categorie' => array('type' => 'integer:Categorie:categories/class/categorie.class.php', 'label' => 'AppliedCategory', 'enabled' => 1, 'visible' => 1, 'position' => 130),
		'calculation_status' => array('type' => 'varchar(32)', 'label' => 'CalculationStatus', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 140),
		'calculation_message' => array('type' => 'text', 'label' => 'CalculationMessage', 'enabled' => 1, 'visible' => 1, 'position' => 150),
		'manual_override' => array('type' => 'boolean', 'label' => 'ManualOverride', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => '0', 'index' => 1, 'position' => 160),
		'override_reason' => array('type' => 'text', 'label' => 'OverrideReason', 'enabled' => 1, 'visible' => 1, 'position' => 170),
		'date_calculation' => array('type' => 'datetime', 'label' => 'CalculationDate', 'enabled' => 1, 'visible' => 1, 'index' => 1, 'position' => 180),
		'fk_user_calculation' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'CalculationUser', 'enabled' => 1, 'visible' => 1, 'position' => 190),
		'date_override' => array('type' => 'datetime', 'label' => 'OverrideDate', 'enabled' => 1, 'visible' => 1, 'position' => 200),
		'fk_user_override' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'OverrideUser', 'enabled' => 1, 'visible' => 1, 'position' => 210),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 500),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 501),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -2, 'position' => 510),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'position' => 511),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 1000),
	);

	protected function getCardPage()
	{
		return 'objectzone_card.php';
	}
}
