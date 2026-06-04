<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');

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
			return 0;
		}
		if (!$this->isWatchedObjectAction($action)) {
			return 0;
		}
		$profileRef = empty($conf->global->LMDBZONING_DEFAULT_PROFILE) ? '' : (string) $conf->global->LMDBZONING_DEFAULT_PROFILE;
		if ($profileRef === '') {
			dol_syslog(__METHOD__.' '.$action.' skipped: missing default profile', LOG_WARNING);
			return 0;
		}
		$elementType = $this->resolveZonableElementType($object);
		if ($elementType === '') {
			return 0;
		}
		$fkElement = $this->getObjectId($object);
		if ($fkElement <= 0) {
			dol_syslog(__METHOD__.' '.$action.' skipped: missing object id for elementType='.$elementType, LOG_WARNING);
			return 0;
		}

		$entity = $this->getObjectEntity($object, (int) $conf->entity);
		$service = new LmdbZoningService($this->db);
		$result = $service->calculateZoneForObject($elementType, $fkElement, $profileRef, $entity, 0, 1);
		if (empty($result['status']) || $result['status'] !== 'ok') {
			$error = !empty($result['message']) ? $result['message'] : (!empty($service->error) ? $service->error : 'UnknownError');
			dol_syslog(__METHOD__.' '.$action.' failed to calculate elementType='.$elementType.' fkElement='.$fkElement.' error='.$error, LOG_WARNING);
			if (function_exists('setEventMessages')) {
				setEventMessages($langs->trans('LmdbZoningTriggerCalculationFailed', $elementType, $fkElement, $error), null, 'warnings');
			}
			return 0;
		}
		if (!empty($conf->global->LMDBZONING_AUTO_APPLY_CATEGORY) && function_exists('register_shutdown_function')) {
			$db = $this->db;
			register_shutdown_function(function () use ($db, $elementType, $fkElement, $profileRef, $entity) {
				$service = new LmdbZoningService($db);
				$result = $service->applyStoredZoneCategoryToObject($elementType, (int) $fkElement, $profileRef, (int) $entity);
				if ($result < 0) {
					$error = !empty($service->error) ? $service->error : 'UnknownError';
					dol_syslog('InterfaceLmdbZoningTriggers::shutdown failed to apply category elementType='.$elementType.' fkElement='.(int) $fkElement.' profile='.$profileRef.' entity='.(int) $entity.' error='.$error, LOG_WARNING);
				} else {
					dol_syslog('InterfaceLmdbZoningTriggers::shutdown applied category elementType='.$elementType.' fkElement='.(int) $fkElement.' profile='.$profileRef.' entity='.(int) $entity.' result='.$result, LOG_INFO);
				}
			});
		}
		dol_syslog(__METHOD__.' '.$action.' calculated elementType='.$elementType.' fkElement='.$fkElement.' profile='.$profileRef.' entity='.$entity.' status='.$result['status'], LOG_INFO);

		return 0;
	}

	/**
	 * Check if action should trigger a zoning recalculation.
	 *
	 * @param string $action Trigger action
	 * @return bool
	 */
	private function isWatchedObjectAction($action)
	{
		if ($this->isWatchedPowerPlantPvAction($action)) {
			return true;
		}
		if (strpos((string) $action, 'POWERPLANTPV_POWERPLANT_') === 0) {
			return false;
		}

		return (bool) preg_match('/_(CREATE|MODIFY|UPDATE)$/', (string) $action);
	}

	/**
	 * PowerPlantPV address changes are carried by the canonical object triggers only.
	 *
	 * @param string $action Trigger action
	 * @return bool
	 */
	private function isWatchedPowerPlantPvAction($action)
	{
		return in_array((string) $action, array(
			'POWERPLANTPV_POWERPLANT_CREATE',
			'POWERPLANTPV_POWERPLANT_MODIFY',
		), true);
	}

	/**
	 * Resolve a Dolibarr object to a supported lmdbzoning element type.
	 *
	 * @param object $object Dolibarr object
	 * @return string
	 */
	private function resolveZonableElementType($object)
	{
		if (!is_object($object)) {
			return '';
		}
		$objectClass = get_class($object);
		$objectElement = !empty($object->element) ? (string) $object->element : '';
		$objectTable = !empty($object->table_element) ? (string) $object->table_element : '';
		foreach (LmdbZoningService::getZonableObjectDefinitions(1) as $elementType => $definition) {
			if (!empty($definition['class']) && ($objectClass === $definition['class'] || is_a($object, $definition['class']))) {
				return LmdbZoningService::normalizeZonableElementType($elementType);
			}
			if ($objectElement !== '' && $objectElement === $elementType) {
				return LmdbZoningService::normalizeZonableElementType($elementType);
			}
			if ($objectTable !== '' && !empty($definition['table_element']) && $objectTable === $definition['table_element']) {
				return LmdbZoningService::normalizeZonableElementType($elementType);
			}
		}

		return '';
	}

	/**
	 * Return object id with Dolibarr-compatible fallbacks.
	 *
	 * @param object $object Dolibarr object
	 * @return int
	 */
	private function getObjectId($object)
	{
		if (!empty($object->id)) {
			return (int) $object->id;
		}
		if (!empty($object->rowid)) {
			return (int) $object->rowid;
		}

		return 0;
	}

	/**
	 * Return object entity with current entity fallback.
	 *
	 * @param object $object        Dolibarr object
	 * @param int    $defaultEntity Default entity
	 * @return int
	 */
	private function getObjectEntity($object, $defaultEntity)
	{
		if (isset($object->entity) && (int) $object->entity > 0) {
			return (int) $object->entity;
		}

		return (int) $defaultEntity;
	}
}
