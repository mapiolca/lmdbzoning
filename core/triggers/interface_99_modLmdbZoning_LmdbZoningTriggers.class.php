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
		$service = new LmdbZoningService($this->db);
		$targets = $this->resolveZoningTargets($action, $object, $service, $conf);
		if (empty($targets)) {
			return 0;
		}

		if ($action === 'OBJECT_LINK_DELETE' && function_exists('register_shutdown_function')) {
			$applyCategory = !empty($conf->global->LMDBZONING_AUTO_APPLY_CATEGORY);
			register_shutdown_function(function () use ($targets, $profileRef, $applyCategory) {
				$this->calculateZoningTargets($targets, $profileRef, $applyCategory, 1, null);
			});
			return 0;
		}

		$this->calculateZoningTargets($targets, $profileRef, !empty($conf->global->LMDBZONING_AUTO_APPLY_CATEGORY), 0, $langs);

		return 0;
	}

	/**
	 * Calculate zoning for a list of resolved targets.
	 *
	 * @param array<int,array<string,mixed>> $targets             Targets
	 * @param string                        $profileRef          Profile ref
	 * @param bool                          $applyCategory       Apply category
	 * @param int                           $isShutdownExecution 1=already running in shutdown
	 * @param Translate|null                $langs               Langs
	 * @return int
	 */
	private function calculateZoningTargets(array $targets, $profileRef, $applyCategory, $isShutdownExecution = 0, $langs = null)
	{
		$service = new LmdbZoningService($this->db);
		foreach ($this->deduplicateTargets($targets) as $target) {
			$elementType = !empty($target['element_type']) ? (string) $target['element_type'] : '';
			$fkElement = !empty($target['fk_element']) ? (int) $target['fk_element'] : 0;
			$entity = !empty($target['entity']) ? (int) $target['entity'] : 0;
			if ($elementType === '' || $fkElement <= 0 || $entity <= 0) {
				continue;
			}

			$result = $service->calculateZoneForObject($elementType, $fkElement, $profileRef, $entity, 0, 1);
			if (empty($result['status']) || $result['status'] !== 'ok') {
				$error = !empty($result['message']) ? $result['message'] : (!empty($service->error) ? $service->error : 'UnknownError');
				dol_syslog(__METHOD__.' failed to calculate elementType='.$elementType.' fkElement='.$fkElement.' error='.$error, LOG_WARNING);
				if (is_object($langs) && function_exists('setEventMessages')) {
					setEventMessages($langs->trans('LmdbZoningTriggerCalculationFailed', $elementType, $fkElement, $error), null, 'warnings');
				}
				continue;
			}

			if ($applyCategory) {
				$this->queueOrApplyStoredZoneCategory($elementType, $fkElement, $profileRef, $entity, (int) $isShutdownExecution);
			}
			dol_syslog(__METHOD__.' calculated elementType='.$elementType.' fkElement='.$fkElement.' profile='.$profileRef.' entity='.$entity.' status='.$result['status'], LOG_INFO);
		}

		return 0;
	}

	/**
	 * Apply a stored zone category immediately or queue it at shutdown.
	 *
	 * @param string $elementType         Object element type
	 * @param int    $fkElement           Object id
	 * @param string $profileRef          Profile ref
	 * @param int    $entity              Entity id
	 * @param int    $isShutdownExecution 1=already in shutdown
	 * @return void
	 */
	private function queueOrApplyStoredZoneCategory($elementType, $fkElement, $profileRef, $entity, $isShutdownExecution = 0)
	{
		if (!empty($isShutdownExecution) || !function_exists('register_shutdown_function')) {
			$this->applyStoredZoneCategory($elementType, (int) $fkElement, $profileRef, (int) $entity);
			return;
		}

		register_shutdown_function(function () use ($elementType, $fkElement, $profileRef, $entity) {
			$this->applyStoredZoneCategory($elementType, (int) $fkElement, $profileRef, (int) $entity);
		});
	}

	/**
	 * Apply the stored zone category to an object.
	 *
	 * @param string $elementType Object element type
	 * @param int    $fkElement   Object id
	 * @param string $profileRef  Profile ref
	 * @param int    $entity      Entity id
	 * @return void
	 */
	private function applyStoredZoneCategory($elementType, $fkElement, $profileRef, $entity)
	{
		$service = new LmdbZoningService($this->db);
		$result = $service->applyStoredZoneCategoryToObject($elementType, (int) $fkElement, $profileRef, (int) $entity);
		if ($result < 0) {
			$error = !empty($service->error) ? $service->error : 'UnknownError';
			dol_syslog('InterfaceLmdbZoningTriggers::shutdown failed to apply category elementType='.$elementType.' fkElement='.(int) $fkElement.' profile='.$profileRef.' entity='.(int) $entity.' error='.$error, LOG_WARNING);
		} else {
			dol_syslog('InterfaceLmdbZoningTriggers::shutdown applied category elementType='.$elementType.' fkElement='.(int) $fkElement.' profile='.$profileRef.' entity='.(int) $entity.' result='.$result, LOG_INFO);
		}
	}

	/**
	 * Resolve zoning targets from the current trigger payload.
	 *
	 * @param string             $action  Trigger action
	 * @param object             $object  Trigger object
	 * @param LmdbZoningService  $service Zoning service
	 * @param Conf               $conf    Conf
	 * @return array<int,array<string,mixed>>
	 */
	private function resolveZoningTargets($action, $object, LmdbZoningService $service, Conf $conf)
	{
		if ($this->isWatchedObjectLinkAction($action)) {
			return $this->resolveZoningTargetsFromObjectLink($action, $object, (int) $conf->entity);
		}

		$targets = array();
		$lineTarget = $this->resolveZoningTargetFromDocumentLine($action, $object, (int) $conf->entity);
		if (!empty($lineTarget)) {
			$this->addZoningTarget($targets, $lineTarget['element_type'], (int) $lineTarget['fk_element'], (int) $lineTarget['entity']);
			return array_values($targets);
		}

		$elementType = $this->resolveZonableElementType($object);
		if ($elementType === '') {
			return array();
		}
		$fkElement = $this->getObjectId($object);
		if ($fkElement <= 0) {
			dol_syslog(__METHOD__.' '.$action.' skipped: missing object id for elementType='.$elementType, LOG_WARNING);
			return array();
		}

		$entity = $this->getObjectEntity($object, (int) $conf->entity);
		$this->addZoningTarget($targets, $elementType, $fkElement, $entity);
		if ($elementType === 'powerplantpv') {
			foreach ($service->getLinkedZonableObjectsForPowerPlant($fkElement, $entity) as $linkedTarget) {
				$this->addZoningTarget($targets, (string) $linkedTarget['element_type'], (int) $linkedTarget['fk_element'], (int) $linkedTarget['entity']);
			}
		}

		return array_values($targets);
	}

	/**
	 * Resolve a parent document target from a line trigger.
	 *
	 * @param string $action        Trigger action
	 * @param object $object        Trigger object
	 * @param int    $defaultEntity Default entity
	 * @return array<string,mixed>
	 */
	private function resolveZoningTargetFromDocumentLine($action, $object, $defaultEntity)
	{
		$lineActions = $this->getDocumentLineActions();
		if (empty($lineActions[$action])) {
			return array();
		}

		$documentId = $this->getObjectIntProperty($object, $lineActions[$action]['parent_fields']);
		if ($documentId <= 0) {
			return array();
		}

		return array(
			'element_type' => $lineActions[$action]['element_type'],
			'fk_element' => $documentId,
			'entity' => $this->getObjectEntity($object, $defaultEntity),
		);
	}

	/**
	 * Resolve document targets from a native Dolibarr object-link trigger.
	 *
	 * @param string $action        Trigger action
	 * @param object $object        Trigger object
	 * @param int    $defaultEntity Default entity
	 * @return array<int,array<string,mixed>>
	 */
	private function resolveZoningTargetsFromObjectLink($action, $object, $defaultEntity)
	{
		if (!is_object($object)) {
			return array();
		}
		$context = (!empty($object->context) && is_array($object->context)) ? $object->context : array();
		$sourceType = '';
		$sourceId = 0;
		$targetType = '';
		$targetId = 0;

		if ($action === 'OBJECT_LINK_INSERT') {
			$sourceType = !empty($context['link_origin']) ? (string) $context['link_origin'] : '';
			$sourceId = !empty($context['link_origin_id']) ? (int) $context['link_origin_id'] : 0;
			$targetType = $this->getObjectLinkType($object);
			$targetId = $this->getObjectId($object);
		} else {
			if ($action === 'OBJECT_LINK_DELETE' && !empty($context['link_id'])) {
				$linkPair = $this->fetchObjectLinkPairById((int) $context['link_id']);
				if (!empty($linkPair)) {
					return $this->resolveZoningTargetsFromLinkPair($linkPair['source_type'], (int) $linkPair['source_id'], $linkPair['target_type'], (int) $linkPair['target_id'], $defaultEntity);
				}
			}
			$sourceType = !empty($context['link_source_type']) ? (string) $context['link_source_type'] : '';
			$sourceId = !empty($context['link_source_id']) ? (int) $context['link_source_id'] : 0;
			$targetType = !empty($context['link_target_type']) ? (string) $context['link_target_type'] : '';
			$targetId = !empty($context['link_target_id']) ? (int) $context['link_target_id'] : 0;
			if ($sourceType === '' || $sourceId <= 0 || $targetType === '' || $targetId <= 0) {
				$objectType = $this->getObjectLinkType($object);
				$objectId = $this->getObjectId($object);
				if ($sourceType === '' || $sourceId <= 0) {
					$sourceType = $objectType;
					$sourceId = $objectId;
				}
				if ($targetType === '' || $targetId <= 0) {
					$targetType = $objectType;
					$targetId = $objectId;
				}
			}
		}

		return $this->resolveZoningTargetsFromLinkPair($sourceType, $sourceId, $targetType, $targetId, $defaultEntity);
	}

	/**
	 * Fetch an object-link pair before Dolibarr deletes it.
	 *
	 * @param int $rowid Link rowid
	 * @return array<string,mixed>
	 */
	private function fetchObjectLinkPairById($rowid)
	{
		$rowid = (int) $rowid;
		if ($rowid <= 0) {
			return array();
		}
		$sql = 'SELECT fk_source, sourcetype, fk_target, targettype';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'element_element';
		$sql .= ' WHERE rowid = '.$rowid;
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' failed to fetch object link rowid='.$rowid.' error='.$this->db->lasterror(), LOG_WARNING);
			return array();
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			$this->db->free($resql);
			return array();
		}

		$linkPair = array(
			'source_id' => (int) $obj->fk_source,
			'source_type' => (string) $obj->sourcetype,
			'target_id' => (int) $obj->fk_target,
			'target_type' => (string) $obj->targettype,
		);
		$this->db->free($resql);

		return $linkPair;
	}

	/**
	 * Resolve zoning targets from a source/target object-link pair.
	 *
	 * @param string $sourceType Source type
	 * @param int    $sourceId   Source id
	 * @param string $targetType Target type
	 * @param int    $targetId   Target id
	 * @param int    $entity     Entity id
	 * @return array<int,array<string,mixed>>
	 */
	private function resolveZoningTargetsFromLinkPair($sourceType, $sourceId, $targetType, $targetId, $entity)
	{
		$targets = array();
		if ($this->isPowerPlantLinkedType($sourceType)) {
			$elementType = $this->normalizeLinkedDocumentType($targetType);
			if ($elementType !== '' && $targetId > 0) {
				$this->addZoningTarget($targets, $elementType, (int) $targetId, (int) $entity);
			}
		}
		if ($this->isPowerPlantLinkedType($targetType)) {
			$elementType = $this->normalizeLinkedDocumentType($sourceType);
			if ($elementType !== '' && $sourceId > 0) {
				$this->addZoningTarget($targets, $elementType, (int) $sourceId, (int) $entity);
			}
		}

		return array_values($targets);
	}

	/**
	 * Add a deduplicated target to a target map.
	 *
	 * @param array<string,array<string,mixed>> $targets     Targets
	 * @param string                           $elementType Element type
	 * @param int                              $fkElement   Object id
	 * @param int                              $entity      Entity id
	 * @return void
	 */
	private function addZoningTarget(array &$targets, $elementType, $fkElement, $entity)
	{
		$elementType = LmdbZoningService::normalizeZonableElementType((string) $elementType);
		$fkElement = (int) $fkElement;
		$entity = (int) $entity;
		if ($elementType === '' || $fkElement <= 0 || $entity <= 0) {
			return;
		}
		$key = $elementType.':'.$fkElement.':'.$entity;
		$targets[$key] = array(
			'element_type' => $elementType,
			'fk_element' => $fkElement,
			'entity' => $entity,
		);
	}

	/**
	 * Deduplicate target arrays.
	 *
	 * @param array<int,array<string,mixed>> $targets Targets
	 * @return array<int,array<string,mixed>>
	 */
	private function deduplicateTargets(array $targets)
	{
		$deduplicated = array();
		foreach ($targets as $target) {
			if (empty($target['element_type']) || empty($target['fk_element']) || empty($target['entity'])) {
				continue;
			}
			$this->addZoningTarget($deduplicated, (string) $target['element_type'], (int) $target['fk_element'], (int) $target['entity']);
		}

		return array_values($deduplicated);
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
		if ($this->isWatchedObjectLinkAction($action)) {
			return true;
		}
		if ($this->isWatchedDocumentLineAction($action)) {
			return true;
		}
		if ($this->isWatchedDocumentAction($action)) {
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
	 * Check native object-link actions that can change linked power plants.
	 *
	 * @param string $action Trigger action
	 * @return bool
	 */
	private function isWatchedObjectLinkAction($action)
	{
		return in_array((string) $action, array(
			'OBJECT_LINK_INSERT',
			'OBJECT_LINK_MODIFY',
			'OBJECT_LINK_DELETE',
		), true);
	}

	/**
	 * Check document line actions that can refresh a parent document.
	 *
	 * @param string $action Trigger action
	 * @return bool
	 */
	private function isWatchedDocumentLineAction($action)
	{
		$lineActions = $this->getDocumentLineActions();

		return !empty($lineActions[(string) $action]);
	}

	/**
	 * Check document actions that can refresh zoning directly.
	 *
	 * @param string $action Trigger action
	 * @return bool
	 */
	private function isWatchedDocumentAction($action)
	{
		return in_array((string) $action, array(
			'PROPAL_CREATE',
			'PROPAL_MODIFY',
			'PROPAL_VALIDATE',
			'PROPAL_REOPEN',
			'PROPAL_CLOSE_REFUSED',
			'PROPAL_CLOSE_SIGNED',
			'PROPAL_CLASSIFY_BILLED',
			'PROPAL_CANCEL',
			'ORDER_CREATE',
			'ORDER_MODIFY',
			'ORDER_VALIDATE',
			'ORDER_UNVALIDATE',
			'ORDER_REOPEN',
			'ORDER_CLOSE',
			'ORDER_CANCEL',
			'ORDER_CLASSIFY_BILLED',
			'ORDER_CLASSIFY_UNBILLED',
			'BILL_CREATE',
			'BILL_MODIFY',
			'BILL_VALIDATE',
			'BILL_UNVALIDATE',
			'BILL_PAYED',
			'BILL_UNPAYED',
			'BILL_CANCEL',
			'CONTRACT_CREATE',
			'CONTRACT_MODIFY',
			'CONTRACT_VALIDATE',
			'CONTRACT_REOPEN',
			'FICHINTER_CREATE',
			'FICHINTER_MODIFY',
			'FICHINTER_VALIDATE',
			'FICHINTER_UNVALIDATE',
			'FICHINTER_CLOSE',
		), true);
	}

	/**
	 * Return parent mappings for supported document line actions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function getDocumentLineActions()
	{
		return array(
			'LINEPROPAL_INSERT' => array('element_type' => 'propal', 'parent_fields' => array('fk_propal')),
			'LINEPROPAL_MODIFY' => array('element_type' => 'propal', 'parent_fields' => array('fk_propal')),
			'LINEPROPAL_UPDATE' => array('element_type' => 'propal', 'parent_fields' => array('fk_propal')),
			'LINEPROPAL_DELETE' => array('element_type' => 'propal', 'parent_fields' => array('fk_propal')),
			'LINEORDER_INSERT' => array('element_type' => 'commande', 'parent_fields' => array('fk_commande')),
			'LINEORDER_MODIFY' => array('element_type' => 'commande', 'parent_fields' => array('fk_commande')),
			'LINEORDER_UPDATE' => array('element_type' => 'commande', 'parent_fields' => array('fk_commande')),
			'LINEORDER_DELETE' => array('element_type' => 'commande', 'parent_fields' => array('fk_commande')),
			'LINEBILL_INSERT' => array('element_type' => 'facture', 'parent_fields' => array('fk_facture')),
			'LINEBILL_MODIFY' => array('element_type' => 'facture', 'parent_fields' => array('fk_facture')),
			'LINEBILL_UPDATE' => array('element_type' => 'facture', 'parent_fields' => array('fk_facture')),
			'LINEBILL_DELETE' => array('element_type' => 'facture', 'parent_fields' => array('fk_facture')),
			'LINECONTRACT_INSERT' => array('element_type' => 'contract', 'parent_fields' => array('fk_contrat', 'fk_contract')),
			'LINECONTRACT_MODIFY' => array('element_type' => 'contract', 'parent_fields' => array('fk_contrat', 'fk_contract')),
			'LINECONTRACT_DELETE' => array('element_type' => 'contract', 'parent_fields' => array('fk_contrat', 'fk_contract')),
			'LINECONTRACT_ACTIVATE' => array('element_type' => 'contract', 'parent_fields' => array('fk_contrat', 'fk_contract')),
			'LINECONTRACT_CLOSE' => array('element_type' => 'contract', 'parent_fields' => array('fk_contrat', 'fk_contract')),
			'LINEFICHINTER_CREATE' => array('element_type' => 'fichinter', 'parent_fields' => array('fk_fichinter')),
			'LINEFICHINTER_MODIFY' => array('element_type' => 'fichinter', 'parent_fields' => array('fk_fichinter')),
			'LINEFICHINTER_DELETE' => array('element_type' => 'fichinter', 'parent_fields' => array('fk_fichinter')),
		);
	}

	/**
	 * Check if a linked-object type is a PowerPlantPV type.
	 *
	 * @param string $type Linked-object type
	 * @return bool
	 */
	private function isPowerPlantLinkedType($type)
	{
		return in_array((string) $type, array('powerplantpv_powerplant', 'powerplant@powerplantpv', 'powerplant'), true);
	}

	/**
	 * Normalize a linked-object document type to a supported zoning type.
	 *
	 * @param string $type Linked-object type
	 * @return string
	 */
	private function normalizeLinkedDocumentType($type)
	{
		$type = (string) $type;
		$aliases = array(
			'propale' => 'propal',
			'order' => 'commande',
			'invoice' => 'facture',
			'contrat' => 'contract',
			'ficheinter' => 'fichinter',
			'timesheet_week' => 'timesheetweek',
		);
		if (!empty($aliases[$type])) {
			$type = $aliases[$type];
		}
		$type = LmdbZoningService::normalizeZonableElementType($type);
		$definition = LmdbZoningService::getZonableObjectDefinition($type);
		if (empty($definition['available']) || empty($definition['address_strategy']) || $definition['address_strategy'] !== 'thirdparty') {
			return '';
		}

		return $type;
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
	 * Return the native element_element type for an object.
	 *
	 * @param object $object Object
	 * @return string
	 */
	private function getObjectLinkType($object)
	{
		if (is_object($object) && method_exists($object, 'getElementType')) {
			return (string) $object->getElementType();
		}
		if (is_object($object) && !empty($object->element)) {
			return (string) $object->element;
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
	 * Return the first positive integer property from an object.
	 *
	 * @param object            $object     Object
	 * @param array<int,string> $properties Property names
	 * @return int
	 */
	private function getObjectIntProperty($object, array $properties)
	{
		if (!is_object($object)) {
			return 0;
		}
		foreach ($properties as $property) {
			if (isset($object->$property) && (int) $object->$property > 0) {
				return (int) $object->$property;
			}
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
