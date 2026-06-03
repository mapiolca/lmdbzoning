<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * lmdbzoning triggers.
 */
class InterfaceLmdbZoningTriggers
{
	/** @var DoliDB */
	public $db;

	/** @var string */
	public $name = 'LmdbZoningTriggers';

	/** @var string */
	public $family = 'lmdbzoning';

	/** @var string */
	public $description = 'Triggers for lmdbzoning module';

	/** @var string */
	public $version = '1.0.0';

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
	 * Trigger entry point.
	 *
	 * @param string $action Event action
	 * @param object $object Object
	 * @param User   $user   User
	 * @param Translate $langs Langs
	 * @param Conf   $conf   Conf
	 * @return int
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (strpos($action, 'LMDBZONING_') === 0) {
			dol_syslog(__METHOD__.' '.$action, LOG_DEBUG);
		}

		return 0;
	}
}
