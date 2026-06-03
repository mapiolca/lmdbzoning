<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningcommonobject.class.php');

/**
 * Zone belonging to a LmdbZoning profile.
 */
class LmdbZoningProfileZone extends LmdbZoningCommonObject
{
	public $element = 'lmdbzoning_zone';
	public $table_element = 'lmdbzoning_profile_zone';
	public $picto = 'lmdbzoning@lmdbzoning';
	public $ismultientitymanaged = 1;

	public $fk_profile;
	public $zone_code;
	public $label;
	public $distance_min = 0;
	public $distance_max;
	public $priority = 0;
	public $fk_categorie_default;
	public $fk_categorie_powerplantpv;
	public $fk_categorie_propal;
	public $fk_categorie_commande;
	public $fk_categorie_contract;
	public $fk_categorie_project;
	public $fk_categorie_fichinter;
	public $fk_categorie_timesheetweek;
	public $active = 1;
	public $datec;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 5),
		'fk_profile' => array('type' => 'integer:LmdbZoningProfile:lmdbzoning/class/lmdbzoningprofile.class.php', 'label' => 'LmdbZoningProfile', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 10),
		'zone_code' => array('type' => 'varchar(64)', 'label' => 'ZoneCode', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 20, 'searchall' => 1),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 30, 'searchall' => 1),
		'distance_min' => array('type' => 'double', 'label' => 'DistanceMin', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 40),
		'distance_max' => array('type' => 'double', 'label' => 'DistanceMax', 'enabled' => 1, 'visible' => 1, 'position' => 50),
		'priority' => array('type' => 'integer', 'label' => 'Priority', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => '0', 'index' => 1, 'position' => 60),
		'fk_categorie_default' => array('type' => 'integer:Categorie:categories/class/categorie.class.php', 'label' => 'DefaultCategory', 'enabled' => 1, 'visible' => 1, 'position' => 70),
		'fk_categorie_powerplantpv' => array('type' => 'integer:Categorie:categories/class/categorie.class.php', 'label' => 'PowerplantPVCategory', 'enabled' => 1, 'visible' => 1, 'position' => 80),
		'fk_categorie_propal' => array('type' => 'integer:Categorie:categories/class/categorie.class.php', 'label' => 'PropalCategory', 'enabled' => 1, 'visible' => 1, 'position' => 90),
		'fk_categorie_commande' => array('type' => 'integer:Categorie:categories/class/categorie.class.php', 'label' => 'OrderCategory', 'enabled' => 1, 'visible' => 1, 'position' => 100),
		'fk_categorie_contract' => array('type' => 'integer:Categorie:categories/class/categorie.class.php', 'label' => 'ContractCategory', 'enabled' => 1, 'visible' => 1, 'position' => 110),
		'fk_categorie_project' => array('type' => 'integer:Categorie:categories/class/categorie.class.php', 'label' => 'ProjectCategory', 'enabled' => 1, 'visible' => 1, 'position' => 120),
		'fk_categorie_fichinter' => array('type' => 'integer:Categorie:categories/class/categorie.class.php', 'label' => 'InterventionCategory', 'enabled' => 1, 'visible' => 1, 'position' => 130),
		'fk_categorie_timesheetweek' => array('type' => 'integer:Categorie:categories/class/categorie.class.php', 'label' => 'TimesheetWeekCategory', 'enabled' => 1, 'visible' => 1, 'position' => 140),
		'active' => array('type' => 'boolean', 'label' => 'Active', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 150),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 500),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 501),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -2, 'position' => 510),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'position' => 511),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 1000),
	);

	protected function getCardPage()
	{
		return 'profile_zone_card.php';
	}
}
