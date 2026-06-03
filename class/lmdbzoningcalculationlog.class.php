<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
dol_include_once('/lmdbzoning/class/lmdbzoningcommonobject.class.php');

/**
 * Calculation log entry.
 */
class LmdbZoningCalculationLog extends LmdbZoningCommonObject
{
	public $element = 'lmdbzoning_calculationlog';
	public $table_element = 'lmdbzoning_calculation_log';
	public $picto = 'lmdbzoning@lmdbzoning';
	public $ismultientitymanaged = 1;

	public $fk_object_zone;
	public $event_code;
	public $element_type;
	public $fk_element;
	public $message;
	public $context_data;
	public $datec;
	public $fk_user_creat;
	public $import_key;

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 5),
		'fk_object_zone' => array('type' => 'integer:LmdbZoningObjectZone:lmdbzoning/class/lmdbzoningobjectzone.class.php', 'label' => 'ObjectZone', 'enabled' => 1, 'visible' => 1, 'index' => 1, 'position' => 10),
		'event_code' => array('type' => 'varchar(64)', 'label' => 'EventCode', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'index' => 1, 'position' => 20),
		'element_type' => array('type' => 'varchar(64)', 'label' => 'ElementType', 'enabled' => 1, 'visible' => 1, 'index' => 1, 'position' => 30),
		'fk_element' => array('type' => 'integer', 'label' => 'ElementId', 'enabled' => 1, 'visible' => 1, 'index' => 1, 'position' => 40),
		'message' => array('type' => 'text', 'label' => 'Message', 'enabled' => 1, 'visible' => 1, 'position' => 50),
		'context_data' => array('type' => 'text', 'label' => 'ContextData', 'enabled' => 1, 'visible' => 1, 'position' => 60),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => 1, 'index' => 1, 'position' => 500),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => 1, 'position' => 510),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 1000),
	);

	protected function getCardPage()
	{
		return 'calculation_log_card.php';
	}
}
