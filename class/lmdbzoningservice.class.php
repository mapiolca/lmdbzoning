<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

dol_include_once('/lmdbzoning/class/geocoder.class.php');
dol_include_once('/lmdbzoning/class/referencepoint.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningprofile.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningprofilezone.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningobjectzone.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningcalculationlog.class.php');

/**
 * Main reusable LmdbZoning service.
 */
class LmdbZoningService
{
	/** @var DoliDB */
	private $db;

	/** @var LmdbZoningGeocoder */
	private $geocoder;

	/** @var string */
	public $error = '';

	/** @var array<int,string> */
	public $errors = array();

	/**
	 * Constructor.
	 *
	 * @param DoliDB              $db       Database handler
	 * @param LmdbZoningGeocoder|null $geocoder Optional geocoder
	 */
	public function __construct($db, $geocoder = null)
	{
		$this->db = $db;
		$this->geocoder = $geocoder ?: new LmdbZoningGeocoder($db);
	}

	/**
	 * Return zonable object definitions.
	 *
	 * @param int $onlyAvailable 1=only entries with enabled module and usable category type
	 * @return array<string,array<string,mixed>>
	 */
	public static function getZonableObjectDefinitions($onlyAvailable = 0)
	{
		$definitions = array(
			'societe' => array('file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'module' => 'societe', 'table_element' => 'societe', 'category_type_id' => 2, 'category_link_type' => 'soc', 'category_link_table' => 'categorie_societe', 'category_link_object_field' => 'fk_soc', 'category_link_category_field' => 'fk_categorie', 'category_field' => 'fk_categorie_societe', 'address_strategy' => 'self', 'category_priority' => 10),
			'contact' => array('file' => '/contact/class/contact.class.php', 'class' => 'Contact', 'module' => 'societe', 'table_element' => 'socpeople', 'category_type_id' => 4, 'category_link_type' => 'contact', 'category_field' => 'fk_categorie_contact', 'address_strategy' => 'self_then_thirdparty', 'category_priority' => 30),
			'propal' => array('file' => '/comm/propal/class/propal.class.php', 'class' => 'Propal', 'module' => 'propal', 'table_element' => 'propal', 'category_type_id' => 23, 'category_link_type' => 'propal', 'category_field' => 'fk_categorie_propal', 'address_strategy' => 'thirdparty', 'category_priority' => 30),
			'commande' => array('file' => '/commande/class/commande.class.php', 'class' => 'Commande', 'module' => 'commande', 'table_element' => 'commande', 'category_type_id' => 16, 'category_link_type' => 'commande', 'category_field' => 'fk_categorie_commande', 'address_strategy' => 'thirdparty', 'category_priority' => 30),
			'order' => array('alias' => 'commande'),
			'facture' => array('file' => '/compta/facture/class/facture.class.php', 'class' => 'Facture', 'module' => 'facture', 'table_element' => 'facture', 'category_type_id' => 17, 'category_link_type' => 'facture', 'category_field' => 'fk_categorie_facture', 'address_strategy' => 'thirdparty', 'category_priority' => 30),
			'invoice' => array('alias' => 'facture'),
			'contract' => array('file' => '/contrat/class/contrat.class.php', 'class' => 'Contrat', 'module' => 'contrat', 'table_element' => 'contrat', 'category_type_id' => 450022, 'category_link_type' => 'contract', 'category_link_table' => 'categorie_contract', 'category_link_object_field' => 'fk_contract', 'category_link_category_field' => 'fk_categorie', 'category_field' => 'fk_categorie_contract', 'address_strategy' => 'thirdparty', 'category_priority' => 30),
			'contrat' => array('alias' => 'contract'),
			'project' => array('file' => '/projet/class/project.class.php', 'class' => 'Project', 'module' => 'project', 'table_element' => 'projet', 'category_type_id' => 6, 'category_link_type' => 'project', 'category_field' => 'fk_categorie_project', 'address_strategy' => 'self_then_thirdparty', 'category_priority' => 20),
			'projet' => array('alias' => 'project'),
			'fichinter' => array('file' => '/fichinter/class/fichinter.class.php', 'class' => 'Fichinter', 'module' => 'ficheinter', 'table_element' => 'fichinter', 'category_type_id' => 14, 'category_link_type' => 'fichinter', 'category_field' => 'fk_categorie_fichinter', 'address_strategy' => 'thirdparty', 'category_priority' => 30),
			'timesheetweek' => array('file' => '/timesheetweek/class/timesheetweek.class.php', 'class' => 'TimesheetWeek', 'module' => 'timesheetweek', 'table_element' => 'timesheet_week', 'category_type_id' => 450003, 'category_link_type' => 'timesheetweek', 'category_field' => 'fk_categorie_timesheetweek', 'address_strategy' => 'thirdparty', 'category_priority' => 30),
			'powerplantpv' => array('file' => '/powerplantpv/class/powerplant.class.php', 'class' => 'PowerPlant', 'module' => 'powerplantpv', 'table_element' => 'powerplantpv_powerplant', 'category_type_id' => 450004, 'category_link_type' => 'powerplantpv', 'category_field' => 'fk_categorie_powerplantpv', 'address_strategy' => 'self_then_thirdparty', 'category_priority' => 30),
		);

		foreach ($definitions as $elementType => $definition) {
			if (!empty($definition['alias'])) {
				$definitions[$elementType] = $definitions[$definition['alias']];
			}
		}
		foreach ($definitions as $elementType => $definition) {
			$definitions[$elementType]['available'] = self::isZonableDefinitionAvailable($definitions[$elementType]);
		}
		if (empty($onlyAvailable)) {
			return $definitions;
		}

		$available = array();
		foreach ($definitions as $elementType => $definition) {
			if (!empty($definition['available'])) {
				$available[$elementType] = $definition;
			}
		}

		return $available;
	}

	/**
	 * Return zonable object definitions ordered by category assignment priority.
	 *
	 * @param int $onlyAvailable 1=only entries with enabled module and usable category type
	 * @return array<string,array<string,mixed>>
	 */
	public static function getOrderedZonableObjectDefinitions($onlyAvailable = 0)
	{
		$definitions = self::getZonableObjectDefinitions($onlyAvailable);
		$positions = array();
		$position = 0;
		foreach ($definitions as $elementType => $definition) {
			$positions[$elementType] = $position++;
		}
		uksort($definitions, function ($left, $right) use ($definitions, $positions) {
			$leftPriority = isset($definitions[$left]['category_priority']) ? (int) $definitions[$left]['category_priority'] : 30;
			$rightPriority = isset($definitions[$right]['category_priority']) ? (int) $definitions[$right]['category_priority'] : 30;
			if ($leftPriority === $rightPriority) {
				return $positions[$left] <=> $positions[$right];
			}

			return $leftPriority <=> $rightPriority;
		});

		return $definitions;
	}

	/**
	 * Return one zonable object definition.
	 *
	 * @param string $elementType Element type
	 * @return array<string,mixed>|null
	 */
	public static function getZonableObjectDefinition($elementType)
	{
		$definitions = self::getZonableObjectDefinitions(0);

		return isset($definitions[$elementType]) ? $definitions[$elementType] : null;
	}

	/**
	 * Return category types available for one category FK field.
	 *
	 * @param string $field Field name
	 * @return array<int,string>
	 */
	public static function getCategoryTypesForZoneField($field)
	{
		if ($field === 'fk_categorie') {
			$field = 'fk_categorie_default';
		}
		$types = array();
		foreach (self::getZonableObjectDefinitions(1) as $definition) {
			if (!isset($definition['category_type_id']) || $definition['category_type_id'] === null) {
				continue;
			}
			if ($field === 'fk_categorie_default' || (!empty($definition['category_field']) && $definition['category_field'] === $field)) {
				$types[] = (int) $definition['category_type_id'];
			}
		}

		return array_values(array_unique($types));
	}

	/**
	 * Return category fields that can be refreshed from setup.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function getRefreshableCategoryFields()
	{
		$fields = array(
			'fk_categorie_societe' => array('label' => 'ThirdpartyCategory', 'element_types' => array(), 'supported' => 0),
			'fk_categorie_contact' => array('label' => 'ContactCategory', 'element_types' => array(), 'supported' => 0),
			'fk_categorie_powerplantpv' => array('label' => 'PowerplantPVCategory', 'element_types' => array(), 'supported' => 0),
			'fk_categorie_propal' => array('label' => 'PropalCategory', 'element_types' => array(), 'supported' => 0),
			'fk_categorie_commande' => array('label' => 'OrderCategory', 'element_types' => array(), 'supported' => 0),
			'fk_categorie_facture' => array('label' => 'InvoiceCategory', 'element_types' => array(), 'supported' => 0),
			'fk_categorie_contract' => array('label' => 'ContractCategory', 'element_types' => array(), 'supported' => 0),
			'fk_categorie_project' => array('label' => 'ProjectCategory', 'element_types' => array(), 'supported' => 0),
			'fk_categorie_fichinter' => array('label' => 'InterventionCategory', 'element_types' => array(), 'supported' => 0),
			'fk_categorie_timesheetweek' => array('label' => 'TimesheetWeekCategory', 'element_types' => array(), 'supported' => 0),
		);
		foreach (self::getZonableObjectDefinitions(0) as $elementType => $definition) {
			if (empty($definition['category_field']) || !isset($fields[$definition['category_field']])) {
				continue;
			}
			$field = $definition['category_field'];
			if (!in_array($elementType, $fields[$field]['element_types'], true)) {
				$fields[$field]['element_types'][] = $elementType;
			}
			if (!empty($definition['available']) && isset($definition['category_type_id']) && $definition['category_type_id'] !== null) {
				$fields[$field]['supported'] = 1;
			}
		}

		return $fields;
	}

	/**
	 * Render the linked object name/reference.
	 *
	 * @param string $elementType Object element type
	 * @param int    $fkElement   Object id
	 * @return string
	 */
	public function renderLinkedObjectNomUrl($elementType, $fkElement)
	{
		global $conf;

		$object = $this->fetchSupportedObject($elementType, (int) $fkElement, (int) $conf->entity);
		if (!is_object($object)) {
			return (int) $fkElement > 0 ? '#'.((int) $fkElement) : '';
		}
		if (method_exists($object, 'getNomUrl')) {
			return $object->getNomUrl(1);
		}

		foreach (array('ref', 'name', 'nom', 'label') as $property) {
			if (!empty($object->$property)) {
				return dol_escape_htmltag($object->$property);
			}
		}

		return '#'.((int) $fkElement);
	}

	/**
	 * Calculate a zone for an address.
	 *
	 * @param array<string,mixed> $address    Address fields
	 * @param string              $profileRef Profile reference
	 * @param int                 $entity     Entity id
	 * @return array<string,mixed>
	 */
	public function calculateZoneForAddress(array $address, $profileRef, $entity = 0)
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$profile = $this->fetchProfileByRef($profileRef, $entity);
		if (!$profile) {
			return $this->failedResult('ProfileNotFound');
		}
		$referencePoint = new LmdbZoningReferencePoint($this->db);
		if ($referencePoint->fetch((int) $profile->fk_referencepoint) <= 0) {
			return $this->failedResult('ReferencePointNotFound');
		}
		if (!$this->geocoder->hasValidCoordinates($referencePoint->getAddressArray())) {
			return $this->failedResult('ReferencePointCoordinatesMissing');
		}

		$geocode = $this->geocoder->geocode($address, $entity, 0);
		if (empty($geocode['status']) || $geocode['status'] !== LmdbZoningGeocoder::STATUS_OK) {
			return array(
				'status' => isset($geocode['status']) ? $geocode['status'] : 'failed',
				'zone_code' => '',
				'zone_label' => '',
				'distance_km' => null,
				'fk_zone' => null,
				'fk_categorie' => null,
				'latitude' => isset($geocode['latitude']) ? $geocode['latitude'] : null,
				'longitude' => isset($geocode['longitude']) ? $geocode['longitude'] : null,
				'address_hash' => isset($geocode['address_hash']) ? $geocode['address_hash'] : '',
				'message' => isset($geocode['message']) ? $geocode['message'] : 'GeocodingFailed',
			);
		}

		$distance = $this->getAirDistanceKm((float) $referencePoint->latitude, (float) $referencePoint->longitude, (float) $geocode['latitude'], (float) $geocode['longitude']);
		$zone = $this->findZoneForDistance((int) $profile->id, $distance, $entity);
		if (!$zone) {
			return array(
				'status' => 'out_of_range',
				'zone_code' => '',
				'zone_label' => '',
				'distance_km' => round($distance, 2),
				'fk_zone' => null,
				'fk_categorie' => null,
				'latitude' => $geocode['latitude'],
				'longitude' => $geocode['longitude'],
				'address_hash' => $geocode['address_hash'],
				'message' => 'NoMatchingZone',
			);
		}

		return array(
			'status' => 'ok',
			'profile_ref' => $profile->ref,
			'fk_profile' => (int) $profile->id,
			'fk_referencepoint' => (int) $referencePoint->id,
			'zone_code' => $zone->zone_code,
			'zone_label' => $zone->label,
			'distance_km' => round($distance, 2),
			'distance_raw_km' => $distance,
			'fk_zone' => (int) $zone->id,
			'fk_categorie' => $this->getCategoryForElementType($zone, isset($address['element_type']) ? $address['element_type'] : ''),
			'latitude' => $geocode['latitude'],
			'longitude' => $geocode['longitude'],
			'address_hash' => $geocode['address_hash'],
			'address_raw' => $this->addressToString($address),
			'message' => '',
		);
	}

	/**
	 * Calculate and store a zone for a Dolibarr object.
	 *
	 * @param string $elementType Object element type
	 * @param int    $fkElement   Object id
	 * @param string $profileRef  Profile ref
	 * @param int    $entity      Entity id
	 * @param int    $forceApplyCategory Force category application
	 * @param int    $skipApplyCategory  1=do not apply category in this call
	 * @return array<string,mixed>
	 */
	public function calculateZoneForObject($elementType, $fkElement, $profileRef, $entity = 0, $forceApplyCategory = 0, $skipApplyCategory = 0)
	{
		global $conf, $user;

		$definition = self::getZonableObjectDefinition($elementType);
		if (empty($definition['available'])) {
			$result = $this->failedResult('ObjectTypeNotSupported');
			$result['element_type'] = $elementType;
			$result['fk_element'] = (int) $fkElement;
			return $result;
		}
		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$address = $this->fetchObjectAddress($elementType, (int) $fkElement, $entity);
		if (!$address) {
			$result = $this->failedResult('ObjectAddressNotFound');
			$result['element_type'] = $elementType;
			$result['fk_element'] = (int) $fkElement;
			$profile = $this->fetchProfileByRef($profileRef, $entity);
			if ($profile) {
				$result['entity'] = $entity;
				$result['profile_ref'] = $profile->ref;
				$result['fk_profile'] = (int) $profile->id;
				$result['fk_referencepoint'] = (int) $profile->fk_referencepoint;
				$this->storeObjectZoneResult($elementType, (int) $fkElement, $result, $entity);
			}
			return $result;
		}
		$address['element_type'] = $elementType;
		$result = $this->calculateZoneForAddress($address, $profileRef, $entity);
		$result['entity'] = $entity;
		$this->storeObjectZoneResult($elementType, (int) $fkElement, $result, $entity);
		if (empty($skipApplyCategory) && $result['status'] === 'ok' && (!empty($conf->global->LMDBZONING_AUTO_APPLY_CATEGORY) || !empty($forceApplyCategory))) {
			$this->applyZoneCategoryToObject($elementType, (int) $fkElement, $result);
		}
		$this->logEvent('LMDBZONING_OBJECT_CALCULATE', $elementType, (int) $fkElement, isset($result['message']) ? $result['message'] : '', $result);

		return $result;
	}

	/**
	 * Return stored zone for object.
	 *
	 * @param string      $elementType Object element type
	 * @param int         $fkElement   Object id
	 * @param string|null $profileRef  Profile ref
	 * @param int         $entity      Entity id
	 * @return array<string,mixed>|false
	 */
	public function getObjectZone($elementType, $fkElement, $profileRef = null, $entity = 0)
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$sql = 'SELECT oz.* FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as oz';
		if ($profileRef !== null && $profileRef !== '') {
			$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'lmdbzoning_profile as p ON p.rowid = oz.fk_profile';
		}
		$sql .= ' WHERE oz.entity = '.((int) $entity);
		$sql .= " AND oz.element_type = '".$this->db->escape($elementType)."'";
		$sql .= ' AND oz.fk_element = '.((int) $fkElement);
		if ($profileRef !== null && $profileRef !== '') {
			$sql .= " AND p.ref = '".$this->db->escape($profileRef)."'";
		}
		$sql .= ' ORDER BY oz.rowid DESC';
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return false;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return false;
		}

		return $this->objectToArray($obj);
	}

	/**
	 * Apply zone category to object when Dolibarr category API supports the target.
	 *
	 * @param string              $elementType Object element type
	 * @param int                 $fkElement   Object id
	 * @param array<string,mixed> $zoneResult  Zone result
	 * @return int
	 */
	public function applyZoneCategoryToObject($elementType, $fkElement, array $zoneResult)
	{
		if (empty($zoneResult['fk_categorie'])) {
			return 0;
		}
		if (!class_exists('Categorie')) {
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		}
		if (!class_exists('Categorie')) {
			return 0;
		}

		global $conf;

		$entity = !empty($zoneResult['entity']) ? (int) $zoneResult['entity'] : (int) $conf->entity;
		$object = $this->fetchSupportedObject($elementType, (int) $fkElement, $entity);
		if (!$object) {
			return -1;
		}
		$linkType = $this->getCategoryLinkTypeForElement($elementType);
		if ($linkType === '') {
			return 0;
		}
		if (!$this->categoryLinkTableExists($elementType, $linkType)) {
			dol_syslog(__METHOD__.' category link table missing linkType='.$linkType.' elementType='.$elementType, LOG_WARNING);
			return 0;
		}
		$category = new Categorie($this->db);
		if ($category->fetch((int) $zoneResult['fk_categorie']) <= 0) {
			return -1;
		}
		if (!$this->isCategoryInEntityScope($category, $entity)) {
			$this->error = 'CategoryEntityMismatch';
			return -1;
		}
		$this->removeKnownZoneCategories($object, $elementType, $linkType, (int) $fkElement, $zoneResult);
		$result = $this->addCategoryLinkToObject($object, $elementType, $linkType, (int) $fkElement, (int) $zoneResult['fk_categorie']);
		$this->logEvent('LMDBZONING_CATEGORY_APPLY', $elementType, (int) $fkElement, '', $zoneResult);

		return $result < 0 ? -1 : 1;
	}

	/**
	 * Apply the last stored zone category to an object.
	 *
	 * @param string $elementType Object element type
	 * @param int    $fkElement   Object id
	 * @param string $profileRef  Profile ref
	 * @param int    $entity      Entity id
	 * @return int
	 */
	public function applyStoredZoneCategoryToObject($elementType, $fkElement, $profileRef, $entity = 0)
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$storedZone = $this->getObjectZone($elementType, (int) $fkElement, $profileRef, $entity);
		if (empty($storedZone)) {
			$this->error = 'ObjectZoneNotFound';
			return -1;
		}
		if (empty($storedZone['calculation_status']) || $storedZone['calculation_status'] !== 'ok' || empty($storedZone['fk_categorie'])) {
			return 0;
		}

		$zoneResult = $storedZone;
		$zoneResult['status'] = $storedZone['calculation_status'];
		$zoneResult['message'] = isset($storedZone['calculation_message']) ? $storedZone['calculation_message'] : '';
		$zoneResult['entity'] = $entity;
		$zoneResult['profile_ref'] = $profileRef;

		return $this->applyZoneCategoryToObject($elementType, (int) $fkElement, $zoneResult);
	}

	/**
	 * Return the most unfavorable result by zone priority.
	 *
	 * @param array<int,array<string,mixed>> $zoneResults Zone results
	 * @return array<string,mixed>
	 */
	public function consolidateZones(array $zoneResults)
	{
		$selected = array();
		$selectedPriority = -PHP_INT_MAX;

		foreach ($zoneResults as $result) {
			if (empty($result['fk_zone'])) {
				continue;
			}
			$zone = new LmdbZoningProfileZone($this->db);
			if ($zone->fetch((int) $result['fk_zone']) <= 0) {
				continue;
			}
			if ((int) $zone->priority > $selectedPriority) {
				$selectedPriority = (int) $zone->priority;
				$selected = $result;
			}
		}

		return $selected;
	}

	/**
	 * Force object zone manually.
	 *
	 * @param string $elementType Object element type
	 * @param int    $fkElement   Object id
	 * @param string $zoneCode    Zone code
	 * @param string $reason      Override reason
	 * @param int    $userId      User id
	 * @return int
	 */
	public function overrideZoneForObject($elementType, $fkElement, $zoneCode, $reason, $userId)
	{
		global $conf;

		if (trim($reason) === '') {
			$this->error = 'OverrideReasonRequired';
			return -1;
		}
		if (empty($conf->global->LMDBZONING_ALLOW_MANUAL_OVERRIDE)) {
			$this->error = 'ManualOverrideDisabled';
			return -1;
		}
		$current = $this->getObjectZone($elementType, (int) $fkElement);
		if (!$current) {
			$this->error = 'ObjectZoneNotFound';
			return -1;
		}
		$zone = $this->fetchZoneByCode((int) $current['fk_profile'], $zoneCode, (int) $current['entity']);
		if (!$zone) {
			$this->error = 'ZoneNotFound';
			return -1;
		}
		$category = $this->getCategoryForElementType($zone, $elementType);
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'lmdbzoning_object_zone SET';
		$sql .= " zone_code = '".$this->db->escape($zone->zone_code)."',";
		$sql .= ' fk_zone = '.((int) $zone->id).',';
		$sql .= ' fk_categorie = '.($category ? ((int) $category) : 'null').',';
		$sql .= ' manual_override = 1,';
		$sql .= " override_reason = '".$this->db->escape($reason)."',";
		$sql .= " date_override = '".$this->db->idate(dol_now())."',";
		$sql .= ' fk_user_override = '.((int) $userId);
		$sql .= ' WHERE rowid = '.((int) $current['id']);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$this->logEvent('LMDBZONING_OBJECT_OVERRIDE', $elementType, (int) $fkElement, $reason, array('zone_code' => $zoneCode));
		return 1;
	}

	/**
	 * Clear manual override.
	 *
	 * @param string $elementType Object element type
	 * @param int    $fkElement   Object id
	 * @param int    $userId      User id
	 * @return int
	 */
	public function clearZoneOverride($elementType, $fkElement, $userId)
	{
		$current = $this->getObjectZone($elementType, (int) $fkElement);
		if (!$current) {
			$this->error = 'ObjectZoneNotFound';
			return -1;
		}
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'lmdbzoning_object_zone SET';
		$sql .= ' zone_code = calculated_zone_code,';
		$sql .= ' fk_zone = calculated_fk_zone,';
		$sql .= ' manual_override = 0,';
		$sql .= ' override_reason = null,';
		$sql .= ' date_override = null,';
		$sql .= ' fk_user_override = null';
		$sql .= ' WHERE rowid = '.((int) $current['id']);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$this->logEvent('LMDBZONING_OBJECT_CLEAR_OVERRIDE', $elementType, (int) $fkElement, '', array('user_id' => (int) $userId));
		return 1;
	}

	/**
	 * Recalculate pending or failed object zones.
	 *
	 * @param int $maxItems Max items
	 * @param int $retryFailed Retry failed rows
	 * @param int $entity Entity id
	 * @return array<string,int>
	 */
	public function recalculatePending($maxItems = 50, $retryFailed = 0, $entity = 0)
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$stats = array('processed' => 0, 'ok' => 0, 'failed' => 0);
		$sql = 'SELECT oz.rowid, oz.element_type, oz.fk_element, p.ref as profile_ref';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as oz';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'lmdbzoning_profile as p ON p.rowid = oz.fk_profile';
		$sql .= ' WHERE oz.entity = '.((int) $entity);
		$sql .= " AND (oz.calculation_status = 'pending'";
		if (!empty($retryFailed)) {
			$sql .= " OR oz.calculation_status = 'failed'";
		}
		$sql .= ')';
		$sql .= ' ORDER BY '.$this->getElementTypePrioritySql('oz.element_type').', oz.date_calculation ASC, oz.rowid ASC';
		$sql .= $this->db->plimit((int) $maxItems);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$stats['failed']++;
			return $stats;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$stats['processed']++;
			$result = $this->calculateZoneForObject($obj->element_type, (int) $obj->fk_element, $obj->profile_ref, $entity);
			if (!empty($result['status']) && $result['status'] === 'ok') {
				$stats['ok']++;
			} else {
				$stats['failed']++;
			}
		}

		return $stats;
	}

	/**
	 * Refresh categories on already known object zones.
	 *
	 * @param int    $maxItems      Max items
	 * @param int    $entity        Entity id
	 * @param string $categoryField Category field to refresh
	 * @return array<string,int>
	 */
	public function refreshObjectCategories($maxItems = 50, $entity = 0, $categoryField = '')
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$maxItems = max(1, (int) $maxItems);
		$stats = array('processed' => 0, 'ok' => 0, 'failed' => 0, 'remaining' => 0);
		$categoryField = trim((string) $categoryField);
		$categoryFields = self::getRefreshableCategoryFields();
		if ($categoryField !== '') {
			if (empty($categoryFields[$categoryField])) {
				$this->error = 'InvalidCategoryField';
				$stats['failed']++;
				return $stats;
			}
			if (empty($categoryFields[$categoryField]['supported'])) {
				$this->error = 'LmdbZoningCategoryRefreshUnsupported';
				return $stats;
			}
			return $this->refreshObjectCategoriesForField($maxItems, $entity, $categoryField, $categoryFields[$categoryField]);
		}
		$total = $this->countRefreshableObjectCategories($entity, $categoryField);
		$sql = 'SELECT oz.rowid, oz.element_type, oz.fk_element, p.ref as profile_ref';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as oz';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'lmdbzoning_profile as p ON p.rowid = oz.fk_profile';
		$sql .= ' WHERE oz.entity = '.((int) $entity);
		$sql .= " AND oz.element_type <> ''";
		$sql .= ' AND oz.fk_element > 0';
		$sql .= " AND oz.calculation_status IN ('ok', 'out_of_range', 'failed', 'pending')";
		if ($categoryField !== '') {
			$sql .= $this->buildRefreshCategoryFieldWhere($categoryField, 'oz.element_type');
		}
		$sql .= ' ORDER BY '.$this->getElementTypePrioritySql('oz.element_type').', CASE WHEN oz.fk_categorie IS NOT NULL THEN 0 ELSE 1 END, CASE WHEN oz.calculation_status IN (\'ok\', \'out_of_range\', \'failed\') THEN 0 ELSE 1 END, oz.tms ASC, oz.rowid ASC';
		$sql .= $this->db->plimit($maxItems);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$stats['failed']++;
			return $stats;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$stats['processed']++;
			$result = $this->calculateZoneForObject($obj->element_type, (int) $obj->fk_element, $obj->profile_ref, $entity, 1);
			if (!empty($result['status']) && $result['status'] === 'ok') {
				$stats['ok']++;
			} else {
				$stats['failed']++;
			}
		}
		$stats['remaining'] = max(0, $total - $stats['processed']);
		dol_syslog(__METHOD__.' entity='.$entity.' processed='.$stats['processed'].' ok='.$stats['ok'].' failed='.$stats['failed'].' remaining='.$stats['remaining'], $stats['failed'] > 0 ? LOG_WARNING : LOG_INFO);

		return $stats;
	}

	/**
	 * Refresh object categories by enumerating zonable objects for one category field.
	 *
	 * @param int                 $maxItems      Max items
	 * @param int                 $entity        Entity id
	 * @param string              $categoryField Category field
	 * @param array<string,mixed> $fieldInfo     Field definition
	 * @return array<string,int>
	 */
	private function refreshObjectCategoriesForField($maxItems, $entity, $categoryField, array $fieldInfo)
	{
		global $conf;

		$stats = array('processed' => 0, 'ok' => 0, 'failed' => 0, 'remaining' => 0);
		$profileRef = empty($conf->global->LMDBZONING_DEFAULT_PROFILE) ? '' : (string) $conf->global->LMDBZONING_DEFAULT_PROFILE;
		if ($profileRef === '') {
			$this->error = 'ProfileNotFound';
			$stats['failed']++;
			return $stats;
		}
		$profile = $this->fetchProfileByRef($profileRef, $entity);
		if (!$profile) {
			$this->error = 'ProfileNotFound';
			$stats['failed']++;
			return $stats;
		}

		$definitions = self::getOrderedZonableObjectDefinitions(1);
		$remainingSlots = max(1, (int) $maxItems);
		$deferred = 0;
		foreach ($definitions as $elementType => $definition) {
			if ($this->isAliasElementType($elementType) || empty($definition['category_field']) || $definition['category_field'] !== $categoryField) {
				continue;
			}
			if ($remainingSlots <= 0) {
				$deferred += $this->countZonableObjectIds($definition, $entity);
				continue;
			}
			$totalForElement = $this->countZonableObjectIds($definition, $entity);
			$objectIds = $this->fetchZonableObjectIds($definition, $entity, (int) $profile->id, $elementType, $remainingSlots + 1);
			if (!is_array($objectIds)) {
				$stats['failed']++;
				if (!empty($this->error)) {
					$this->errors[] = $elementType.': '.$this->error;
					dol_syslog(__METHOD__.' fetch failed categoryField='.$categoryField.' elementType='.$elementType.' error='.$this->error, LOG_WARNING);
				}
				continue;
			}
			if (count($objectIds) > $remainingSlots) {
				$deferred += max(0, $totalForElement - $remainingSlots);
				$objectIds = array_slice($objectIds, 0, $remainingSlots);
			}
			foreach ($objectIds as $fkElement) {
				$stats['processed']++;
				$result = $this->calculateZoneForObject($elementType, (int) $fkElement, $profileRef, $entity, 1);
				if (!empty($result['status']) && $result['status'] === 'ok') {
					$stats['ok']++;
				} else {
					$stats['failed']++;
				}
				$remainingSlots--;
				if ($remainingSlots <= 0) {
					break;
				}
			}
		}

		$stats['remaining'] = max(0, $deferred);
		dol_syslog(__METHOD__.' categoryField='.$categoryField.' entity='.$entity.' processed='.$stats['processed'].' ok='.$stats['ok'].' failed='.$stats['failed'].' remaining='.$stats['remaining'], $stats['failed'] > 0 ? LOG_WARNING : LOG_INFO);

		return $stats;
	}

	/**
	 * Force recalculation of eligible objects for a profile.
	 *
	 * @param LmdbZoningProfile $profile  Profile
	 * @param int               $maxItems Max rows to calculate immediately
	 * @param int               $entity   Entity id
	 * @return array<string,int>
	 */
	public function forceRecalculateProfileObjects($profile, $maxItems = 50, $entity = 0)
	{
		$stats = $this->queueProfileObjectsForRecalculation($profile, $maxItems, $entity);
		if (!empty($stats['failed'])) {
			return $stats;
		}

		$maxItems = max(1, (int) $maxItems);
		$todo = $this->fetchPendingProfileRows((int) $profile->id, $maxItems, $stats['entity']);
		if (!is_array($todo)) {
			$stats['failed']++;
			dol_syslog(__METHOD__.' fetch pending failed profile='.(int) $profile->id.' error='.$this->error, LOG_WARNING);
			return $stats;
		}
		foreach ($todo as $row) {
			$stats['processed']++;
			$result = $this->calculateZoneForObject($row->element_type, (int) $row->fk_element, $profile->ref, $stats['entity']);
			if (!empty($result['status']) && $result['status'] === 'ok') {
				$stats['ok']++;
			} else {
				$stats['failed']++;
			}
		}
		$stats['remaining'] = $this->countPendingProfileRows((int) $profile->id, $stats['entity']);
		dol_syslog(__METHOD__.' done profile='.(int) $profile->id.' queued='.$stats['queued'].' processed='.$stats['processed'].' ok='.$stats['ok'].' failed='.$stats['failed'].' remaining='.$stats['remaining'].' skipped_due_to_limit='.$stats['skipped_due_to_limit'], $stats['failed'] > 0 ? LOG_WARNING : LOG_INFO);

		return $stats;
	}

	/**
	 * Queue eligible objects for profile recalculation without calculating them.
	 *
	 * @param LmdbZoningProfile $profile  Profile
	 * @param int               $maxItems Max rows to queue
	 * @param int               $entity   Entity id
	 * @return array<string,int>
	 */
	public function queueProfileObjectsForRecalculation($profile, $maxItems = 50, $entity = 0)
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$maxItems = max(1, (int) $maxItems);
		$stats = array('entity' => $entity, 'queued' => 0, 'processed' => 0, 'ok' => 0, 'failed' => 0, 'remaining' => 0, 'already_pending' => 0, 'skipped' => 0, 'skipped_due_to_limit' => 0);
		if (empty($profile->id) || empty($profile->ref)) {
			$this->error = 'ProfileNotFound';
			$stats['failed']++;
			return $stats;
		}

		dol_syslog(__METHOD__.' start profile='.(int) $profile->id.' maxItems='.$maxItems.' entity='.$entity, LOG_INFO);
		$initialPending = $this->countPendingProfileRows((int) $profile->id, $entity);
		foreach (self::getOrderedZonableObjectDefinitions(1) as $elementType => $definition) {
			if ($this->isAliasElementType($elementType)) {
				continue;
			}
			$remainingQueueSlots = $maxItems - $stats['queued'];
			if ($remainingQueueSlots <= 0) {
				$stats['skipped_due_to_limit']++;
				break;
			}
			$objectIds = $this->fetchZonableObjectIds($definition, $entity, (int) $profile->id, $elementType, $remainingQueueSlots + 1);
			if (!is_array($objectIds)) {
				$stats['failed']++;
				if (!empty($this->error)) {
					$this->errors[] = $elementType.': '.$this->error;
					dol_syslog(__METHOD__.' fetch failed elementType='.$elementType.' error='.$this->error, LOG_WARNING);
				}
				continue;
			}
			if (count($objectIds) > $remainingQueueSlots) {
				$stats['skipped_due_to_limit'] += count($objectIds) - $remainingQueueSlots;
				$objectIds = array_slice($objectIds, 0, $remainingQueueSlots);
			}
			foreach ($objectIds as $fkElement) {
				$result = $this->markObjectZonePending($elementType, (int) $fkElement, $profile, $entity);
				if ($result > 0) {
					$stats['queued']++;
				} elseif ($result < 0) {
					$stats['failed']++;
				} else {
					$stats['skipped']++;
				}
				if ($stats['queued'] >= $maxItems) {
					break;
				}
			}
		}
		$stats['remaining'] = $this->countPendingProfileRows((int) $profile->id, $entity);
		$stats['already_pending'] = max(0, $initialPending);
		dol_syslog(__METHOD__.' done profile='.(int) $profile->id.' queued='.$stats['queued'].' already_pending='.$stats['already_pending'].' remaining='.$stats['remaining'].' failed='.$stats['failed'].' skipped_due_to_limit='.$stats['skipped_due_to_limit'], $stats['failed'] > 0 ? LOG_WARNING : LOG_INFO);

		return $stats;
	}

	/**
	 * Queue one object for profile recalculation without calculating it.
	 *
	 * @param string $elementType Object element type
	 * @param int    $fkElement   Object id
	 * @param string $profileRef  Profile reference
	 * @param int    $entity      Entity id
	 * @return int
	 */
	public function queueObjectForProfileRecalculation($elementType, $fkElement, $profileRef, $entity = 0)
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$elementType = (string) $elementType;
		$fkElement = (int) $fkElement;
		$profileRef = trim((string) $profileRef);
		if ($elementType === '' || $fkElement <= 0 || $profileRef === '') {
			$this->error = 'InvalidObjectForRecalculation';
			return -1;
		}
		$definition = self::getZonableObjectDefinition($elementType);
		if (empty($definition['available'])) {
			$this->error = 'ObjectTypeNotSupported';
			return -1;
		}
		$profile = $this->fetchProfileByRef($profileRef, $entity);
		if (!$profile) {
			$this->error = 'ProfileNotFound';
			return -1;
		}

		$result = $this->markObjectZonePending($elementType, $fkElement, $profile, $entity);
		dol_syslog(__METHOD__.' elementType='.$elementType.' fkElement='.$fkElement.' profile='.$profileRef.' entity='.$entity.' result='.$result, $result < 0 ? LOG_WARNING : LOG_INFO);

		return $result;
	}

	/**
	 * Cron wrapper for Dolibarr scheduler.
	 *
	 * @param User|null $user User
	 * @return int
	 */
	public function cronRecalculatePending($user = null)
	{
		global $conf;

		if (empty($conf->global->LMDBZONING_CRON_ENABLED)) {
			return 0;
		}
		$maxItems = empty($conf->global->LMDBZONING_CRON_MAX_ITEMS) ? 50 : (int) $conf->global->LMDBZONING_CRON_MAX_ITEMS;
		$retryFailed = empty($conf->global->LMDBZONING_CRON_RETRY_FAILED) ? 0 : 1;
		$stats = $this->recalculatePending($maxItems, $retryFailed, (int) $conf->entity);
		dol_syslog(__METHOD__.' processed='.$stats['processed'].' ok='.$stats['ok'].' failed='.$stats['failed'], LOG_INFO);

		return $stats['failed'] > 0 ? -1 : 0;
	}

	/**
	 * Check if a zonable definition can be used in the current instance.
	 *
	 * @param array<string,mixed> $definition Definition
	 * @return bool
	 */
	private static function isZonableDefinitionAvailable(array $definition)
	{
		if (!empty($definition['module']) && !self::isModuleEnabled((string) $definition['module'])) {
			return false;
		}
		if (!class_exists('Categorie') && defined('DOL_DOCUMENT_ROOT') && file_exists(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php')) {
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		}
		if (!class_exists('Categorie') || !isset($definition['category_type_id']) || $definition['category_type_id'] === null) {
			return false;
		}

		return true;
	}

	/**
	 * Check Dolibarr module activation with compatible fallbacks.
	 *
	 * @param string $moduleKey Module key
	 * @return bool
	 */
	private static function isModuleEnabled($moduleKey)
	{
		global $conf;

		if (function_exists('isModEnabled')) {
			return isModEnabled($moduleKey);
		}
		if (isset($conf->$moduleKey) && !empty($conf->$moduleKey->enabled)) {
			return true;
		}
		if (isset($conf->global->{'MAIN_MODULE_'.strtoupper($moduleKey)}) && !empty($conf->global->{'MAIN_MODULE_'.strtoupper($moduleKey)})) {
			return true;
		}

		return false;
	}

	/**
	 * Check alias element types that should not be enumerated twice.
	 *
	 * @param string $elementType Element type
	 * @return bool
	 */
	private function isAliasElementType($elementType)
	{
		return in_array($elementType, array('order', 'invoice', 'contrat', 'projet'), true);
	}

	/**
	 * Fetch ids of objects to recalculate.
	 *
	 * @param array<string,mixed> $definition Object definition
	 * @param int                 $entity Entity id
	 * @param int                 $profileId Profile id
	 * @param string              $elementType Element type
	 * @param int                 $limit Maximum ids to fetch
	 * @return array<int,int>|false
	 */
	private function fetchZonableObjectIds(array $definition, $entity, $profileId = 0, $elementType = '', $limit = 0)
	{
		$table = !empty($definition['table_element']) ? (string) $definition['table_element'] : '';
		if ($table === '') {
			return false;
		}

		$sql = 'SELECT t.rowid FROM '.MAIN_DB_PREFIX.$table.' as t WHERE 1 = 1';
		if ($profileId > 0 && $elementType !== '') {
			$sql = 'SELECT t.rowid, MIN(CASE WHEN oz.rowid IS NULL THEN 0 ELSE 1 END) as has_object_zone, MIN(oz.date_calculation) as oldest_date_calculation';
			$sql .= ' FROM '.MAIN_DB_PREFIX.$table.' as t';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as oz ON oz.entity = '.((int) $entity);
			$sql .= ' AND oz.fk_profile = '.((int) $profileId);
			$sql .= " AND oz.element_type = '".$this->db->escape($elementType)."'";
			$sql .= ' AND oz.fk_element = t.rowid';
			$sql .= ' WHERE 1 = 1';
			$sql .= ' AND NOT EXISTS (';
			$sql .= 'SELECT 1 FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as ozp';
			$sql .= ' WHERE ozp.entity = '.((int) $entity);
			$sql .= ' AND ozp.fk_profile = '.((int) $profileId);
			$sql .= " AND ozp.element_type = '".$this->db->escape($elementType)."'";
			$sql .= ' AND ozp.fk_element = t.rowid';
			$sql .= " AND ozp.calculation_status = 'pending'";
			$sql .= ')';
		}
		if ($this->tableHasColumn($table, 'entity')) {
			$sql .= ' AND t.entity IN ('.$this->getEntityFilter($table, $entity).')';
		}
		if ($this->tableHasColumn($table, 'statut')) {
			$sql .= ' AND t.statut >= 0';
		} elseif ($this->tableHasColumn($table, 'status')) {
			$sql .= ' AND t.status >= 0';
		}
		if ($profileId > 0 && $elementType !== '') {
			$sql .= ' GROUP BY t.rowid';
			$sql .= ' ORDER BY has_object_zone ASC, oldest_date_calculation ASC, t.rowid ASC';
		} else {
			$sql .= ' ORDER BY t.rowid ASC';
		}
		if ($limit > 0) {
			$sql .= $this->db->plimit((int) $limit);
		}
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return false;
		}
		$ids = array();
		while ($row = $this->db->fetch_object($resql)) {
			$ids[] = (int) $row->rowid;
		}

		return $ids;
	}

	/**
	 * Count zonable objects for one definition.
	 *
	 * @param array<string,mixed> $definition Object definition
	 * @param int                 $entity     Entity id
	 * @return int
	 */
	private function countZonableObjectIds(array $definition, $entity)
	{
		$table = !empty($definition['table_element']) ? (string) $definition['table_element'] : '';
		if ($table === '') {
			return 0;
		}

		$sql = 'SELECT COUNT(*) as nb FROM '.MAIN_DB_PREFIX.$table.' as t WHERE 1 = 1';
		if ($this->tableHasColumn($table, 'entity')) {
			$sql .= ' AND t.entity IN ('.$this->getEntityFilter($table, $entity).')';
		}
		if ($this->tableHasColumn($table, 'statut')) {
			$sql .= ' AND t.statut >= 0';
		} elseif ($this->tableHasColumn($table, 'status')) {
			$sql .= ' AND t.status >= 0';
		}
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return 0;
		}
		$row = $this->db->fetch_object($resql);

		return $row ? (int) $row->nb : 0;
	}

	/**
	 * Mark object/profile row as pending without changing existing manual override values.
	 *
	 * @param string             $elementType Element type
	 * @param int                $fkElement   Element id
	 * @param LmdbZoningProfile  $profile     Profile
	 * @param int                $entity      Entity id
	 * @return int
	 */
	private function markObjectZonePending($elementType, $fkElement, $profile, $entity)
	{
		global $user;

		$current = $this->getObjectZoneForProfile($elementType, $fkElement, (int) $profile->id, $entity);
		if (is_array($current) && !empty($current['id'])) {
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'lmdbzoning_object_zone SET';
			$sql .= " calculation_status = 'pending',";
			$sql .= " calculation_message = '".$this->db->escape('ForcedRecalculationPending')."',";
			$sql .= ' fk_user_modif = '.(!empty($user->id) ? ((int) $user->id) : 'null');
			$sql .= ' WHERE rowid = '.((int) $current['id']);
		} else {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'lmdbzoning_object_zone(';
			$sql .= 'entity, fk_profile, fk_referencepoint, element_type, fk_element, calculation_status, calculation_message, manual_override, datec, fk_user_creat';
			$sql .= ') VALUES (';
			$sql .= ((int) $entity).', ';
			$sql .= ((int) $profile->id).', ';
			$sql .= ((int) $profile->fk_referencepoint).', ';
			$sql .= "'".$this->db->escape($elementType)."', ";
			$sql .= ((int) $fkElement).', ';
			$sql .= "'pending', ";
			$sql .= "'".$this->db->escape('ForcedRecalculationPending')."', ";
			$sql .= '0, ';
			$sql .= "'".$this->db->idate(dol_now())."', ";
			$sql .= (!empty($user->id) ? ((int) $user->id) : 'null');
			$sql .= ')';
		}
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return 1;
	}

	/**
	 * Fetch pending rows for one profile.
	 *
	 * @param int $profileId Profile id
	 * @param int $maxItems  Max rows
	 * @param int $entity    Entity id
	 * @return array<int,object>|false
	 */
	private function fetchPendingProfileRows($profileId, $maxItems, $entity)
	{
		$sql = 'SELECT oz.rowid, oz.element_type, oz.fk_element';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as oz';
		$sql .= ' WHERE oz.entity = '.((int) $entity);
		$sql .= ' AND oz.fk_profile = '.((int) $profileId);
		$sql .= " AND oz.calculation_status = 'pending'";
		$sql .= ' ORDER BY oz.date_calculation ASC, oz.rowid ASC';
		if ($maxItems > 0) {
			$sql .= $this->db->plimit((int) $maxItems);
		}
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return false;
		}
		$rows = array();
		while ($row = $this->db->fetch_object($resql)) {
			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Count pending rows for one profile.
	 *
	 * @param int $profileId Profile id
	 * @param int $entity    Entity id
	 * @return int
	 */
	private function countPendingProfileRows($profileId, $entity)
	{
		$sql = 'SELECT COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as oz';
		$sql .= ' WHERE oz.entity = '.((int) $entity);
		$sql .= ' AND oz.fk_profile = '.((int) $profileId);
		$sql .= " AND oz.calculation_status = 'pending'";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return 0;
		}
		$row = $this->db->fetch_object($resql);

		return $row ? (int) $row->nb : 0;
	}

	/**
	 * Count refreshable object category rows.
	 *
	 * @param int $entity Entity id
	 * @return int
	 */
	private function countRefreshableObjectCategories($entity, $categoryField = '')
	{
		$sql = 'SELECT COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as oz';
		$sql .= ' WHERE oz.entity = '.((int) $entity);
		$sql .= " AND oz.element_type <> ''";
		$sql .= ' AND oz.fk_element > 0';
		$sql .= " AND oz.calculation_status IN ('ok', 'out_of_range', 'failed', 'pending')";
		if ($categoryField !== '') {
			$sql .= $this->buildRefreshCategoryFieldWhere($categoryField, 'oz.element_type');
		}
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return 0;
		}
		$row = $this->db->fetch_object($resql);

		return $row ? (int) $row->nb : 0;
	}

	/**
	 * Build SQL WHERE fragment for a category refresh field.
	 *
	 * @param string $categoryField Category field
	 * @param string $sqlField      SQL element type field
	 * @return string
	 */
	private function buildRefreshCategoryFieldWhere($categoryField, $sqlField)
	{
		$categoryFields = self::getRefreshableCategoryFields();
		if (empty($categoryFields[$categoryField]['element_types']) || !is_array($categoryFields[$categoryField]['element_types'])) {
			return ' AND 1 = 0';
		}
		$elementTypes = array();
		foreach ($categoryFields[$categoryField]['element_types'] as $elementType) {
			$elementTypes[] = "'".$this->db->escape($elementType)."'";
		}
		if (empty($elementTypes)) {
			return ' AND 1 = 0';
		}

		return ' AND '.$sqlField.' IN ('.implode(',', array_values(array_unique($elementTypes))).')';
	}

	/**
	 * Fetch one object zone row for a profile.
	 *
	 * @param string $elementType Element type
	 * @param int    $fkElement   Element id
	 * @param int    $profileId   Profile id
	 * @param int    $entity      Entity id
	 * @return array<string,mixed>|false
	 */
	private function getObjectZoneForProfile($elementType, $fkElement, $profileId, $entity)
	{
		$sql = 'SELECT oz.* FROM '.MAIN_DB_PREFIX.'lmdbzoning_object_zone as oz';
		$sql .= ' WHERE oz.entity = '.((int) $entity);
		$sql .= ' AND oz.fk_profile = '.((int) $profileId);
		$sql .= " AND oz.element_type = '".$this->db->escape($elementType)."'";
		$sql .= ' AND oz.fk_element = '.((int) $fkElement);
		$sql .= ' ORDER BY oz.rowid DESC';
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return false;
		}
		$row = $this->db->fetch_object($resql);
		if (!$row) {
			return false;
		}

		return $this->objectToArray($row);
	}

	/**
	 * Check a DB column exists.
	 *
	 * @param string $tableElement Table element without prefix
	 * @param string $column       Column name
	 * @return bool
	 */
	private function tableHasColumn($tableElement, $column)
	{
		$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX.$this->db->escape($tableElement)." LIKE '".$this->db->escape($column)."'";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}

		return (bool) $this->db->num_rows($resql);
	}

	/**
	 * Calculate air distance in kilometers using Haversine formula.
	 *
	 * @param float $lat1 Latitude 1
	 * @param float $lon1 Longitude 1
	 * @param float $lat2 Latitude 2
	 * @param float $lon2 Longitude 2
	 * @return float
	 */
	public function getAirDistanceKm($lat1, $lon1, $lat2, $lon2)
	{
		$earthRadius = 6371;
		$dLat = deg2rad($lat2 - $lat1);
		$dLon = deg2rad($lon2 - $lon1);
		$a = sin($dLat / 2) * sin($dLat / 2)
			+ cos(deg2rad($lat1)) * cos(deg2rad($lat2))
			* sin($dLon / 2) * sin($dLon / 2);
		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		return $earthRadius * $c;
	}

	/**
	 * Fetch profile by reference.
	 *
	 * @param string $profileRef Profile ref
	 * @param int    $entity     Entity id
	 * @return LmdbZoningProfile|false
	 */
	private function fetchProfileByRef($profileRef, $entity)
	{
		$profile = new LmdbZoningProfile($this->db);
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'lmdbzoning_profile';
		$sql .= ' WHERE entity = '.((int) $entity);
		$sql .= " AND ref = '".$this->db->escape($profileRef)."'";
		$sql .= ' AND active = 1';
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return false;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj || $profile->fetchInEntity((int) $obj->rowid, (int) $entity) <= 0) {
			return false;
		}

		return $profile;
	}

	/**
	 * Find zone matching distance.
	 *
	 * @param int   $profileId Profile id
	 * @param float $distance  Distance in km
	 * @param int   $entity    Entity id
	 * @return LmdbZoningProfileZone|false
	 */
	private function findZoneForDistance($profileId, $distance, $entity)
	{
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'lmdbzoning_profile_zone';
		$sql .= ' WHERE entity IN ('.$this->getEntityFilter('lmdbzoning_zone', $entity).')';
		$sql .= ' AND fk_profile = '.((int) $profileId);
		$sql .= ' AND active = 1';
		$sql .= ' AND ((distance_min = 0 AND '.((float) $distance).' >= distance_min) OR '.((float) $distance).' > distance_min)';
		$sql .= ' AND (distance_max IS NULL OR '.((float) $distance).' <= distance_max)';
		$sql .= ' ORDER BY priority ASC, rowid ASC';
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return false;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return false;
		}
		$zone = new LmdbZoningProfileZone($this->db);
		if ($zone->fetch((int) $obj->rowid) <= 0) {
			return false;
		}

		return $zone;
	}

	/**
	 * Fetch zone by code.
	 *
	 * @param int    $profileId Profile id
	 * @param string $zoneCode  Zone code
	 * @param int    $entity    Entity id
	 * @return LmdbZoningProfileZone|false
	 */
	private function fetchZoneByCode($profileId, $zoneCode, $entity)
	{
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'lmdbzoning_profile_zone';
		$sql .= ' WHERE entity IN ('.$this->getEntityFilter('lmdbzoning_zone', $entity).')';
		$sql .= ' AND fk_profile = '.((int) $profileId);
		$sql .= " AND zone_code = '".$this->db->escape($zoneCode)."'";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return false;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return false;
		}
		$zone = new LmdbZoningProfileZone($this->db);
		if ($zone->fetch((int) $obj->rowid) <= 0) {
			return false;
		}

		return $zone;
	}

	/**
	 * Store or update object zone result.
	 *
	 * @param string              $elementType Object element type
	 * @param int                 $fkElement   Object id
	 * @param array<string,mixed> $result      Result
	 * @param int                 $entity      Entity id
	 * @return int
	 */
	private function storeObjectZoneResult($elementType, $fkElement, array $result, $entity)
	{
		global $user;

		if (empty($result['fk_profile'])) {
			$profileRef = isset($result['profile_ref']) ? $result['profile_ref'] : '';
			$profile = $profileRef ? $this->fetchProfileByRef($profileRef, $entity) : false;
			if ($profile) {
				$result['fk_profile'] = (int) $profile->id;
				$result['fk_referencepoint'] = (int) $profile->fk_referencepoint;
			} else {
				return -1;
			}
		}
		$current = !empty($result['fk_profile']) ? $this->getObjectZoneForProfile($elementType, $fkElement, (int) $result['fk_profile'], $entity) : $this->getObjectZone($elementType, $fkElement, null, $entity);
		$manual = is_array($current) && !empty($current['manual_override']);
		$appliedZoneCode = $manual && !empty($current['zone_code']) ? $current['zone_code'] : (isset($result['zone_code']) ? $result['zone_code'] : '');
		$appliedFkZone = $manual && !empty($current['fk_zone']) ? (int) $current['fk_zone'] : (isset($result['fk_zone']) ? (int) $result['fk_zone'] : 'null');
		$appliedCategory = $manual && !empty($current['fk_categorie']) ? (int) $current['fk_categorie'] : (isset($result['fk_categorie']) && $result['fk_categorie'] ? (int) $result['fk_categorie'] : 'null');

		if (is_array($current) && !empty($current['id'])) {
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'lmdbzoning_object_zone SET';
			$sql .= ' fk_referencepoint = '.((int) $result['fk_referencepoint']).',';
			$sql .= " address_hash = '".$this->db->escape(isset($result['address_hash']) ? $result['address_hash'] : '')."',";
			$sql .= " address_raw = '".$this->db->escape(isset($result['address_raw']) ? $result['address_raw'] : '')."',";
			$sql .= ' latitude = '.(isset($result['latitude']) && $result['latitude'] !== null ? ((float) $result['latitude']) : 'null').',';
			$sql .= ' longitude = '.(isset($result['longitude']) && $result['longitude'] !== null ? ((float) $result['longitude']) : 'null').',';
			$sql .= ' distance_km = '.(isset($result['distance_km']) && $result['distance_km'] !== null ? ((float) $result['distance_km']) : 'null').',';
			$sql .= " calculated_zone_code = '".$this->db->escape(isset($result['zone_code']) ? $result['zone_code'] : '')."',";
			$sql .= ' calculated_fk_zone = '.(isset($result['fk_zone']) && $result['fk_zone'] ? ((int) $result['fk_zone']) : 'null').',';
			$sql .= " zone_code = '".$this->db->escape($appliedZoneCode)."',";
			$sql .= ' fk_zone = '.($appliedFkZone === 'null' ? 'null' : ((int) $appliedFkZone)).',';
			$sql .= ' fk_categorie = '.($appliedCategory === 'null' ? 'null' : ((int) $appliedCategory)).',';
			$sql .= " calculation_status = '".$this->db->escape(isset($result['status']) ? $result['status'] : 'failed')."',";
			$sql .= " calculation_message = '".$this->db->escape(isset($result['message']) ? $result['message'] : '')."',";
			$sql .= " date_calculation = '".$this->db->idate(dol_now())."',";
			$sql .= ' fk_user_calculation = '.(!empty($user->id) ? ((int) $user->id) : 'null').',';
			$sql .= ' fk_user_modif = '.(!empty($user->id) ? ((int) $user->id) : 'null');
			$sql .= ' WHERE rowid = '.((int) $current['id']);
		} else {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'lmdbzoning_object_zone(';
			$sql .= 'entity, fk_profile, fk_referencepoint, element_type, fk_element, address_hash, address_raw, latitude, longitude, distance_km, calculated_zone_code, calculated_fk_zone, zone_code, fk_zone, fk_categorie, calculation_status, calculation_message, manual_override, date_calculation, fk_user_calculation, datec, fk_user_creat';
			$sql .= ') VALUES (';
			$sql .= ((int) $entity).', ';
			$sql .= ((int) $result['fk_profile']).', ';
			$sql .= ((int) $result['fk_referencepoint']).', ';
			$sql .= "'".$this->db->escape($elementType)."', ";
			$sql .= ((int) $fkElement).', ';
			$sql .= "'".$this->db->escape(isset($result['address_hash']) ? $result['address_hash'] : '')."', ";
			$sql .= "'".$this->db->escape(isset($result['address_raw']) ? $result['address_raw'] : '')."', ";
			$sql .= (isset($result['latitude']) && $result['latitude'] !== null ? ((float) $result['latitude']) : 'null').', ';
			$sql .= (isset($result['longitude']) && $result['longitude'] !== null ? ((float) $result['longitude']) : 'null').', ';
			$sql .= (isset($result['distance_km']) && $result['distance_km'] !== null ? ((float) $result['distance_km']) : 'null').', ';
			$sql .= "'".$this->db->escape(isset($result['zone_code']) ? $result['zone_code'] : '')."', ";
			$sql .= (isset($result['fk_zone']) && $result['fk_zone'] ? ((int) $result['fk_zone']) : 'null').', ';
			$sql .= "'".$this->db->escape($appliedZoneCode)."', ";
			$sql .= ($appliedFkZone === 'null' ? 'null' : ((int) $appliedFkZone)).', ';
			$sql .= ($appliedCategory === 'null' ? 'null' : ((int) $appliedCategory)).', ';
			$sql .= "'".$this->db->escape(isset($result['status']) ? $result['status'] : 'failed')."', ";
			$sql .= "'".$this->db->escape(isset($result['message']) ? $result['message'] : '')."', ";
			$sql .= '0, ';
			$sql .= "'".$this->db->idate(dol_now())."', ";
			$sql .= (!empty($user->id) ? ((int) $user->id) : 'null').', ';
			$sql .= "'".$this->db->idate(dol_now())."', ";
			$sql .= (!empty($user->id) ? ((int) $user->id) : 'null');
			$sql .= ')';
		}
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return 1;
	}

	/**
	 * Fetch object address from supported Dolibarr objects.
	 *
	 * @param string $elementType Object element type
	 * @param int    $fkElement   Object id
	 * @return array<string,mixed>|false
	 */
	private function fetchObjectAddress($elementType, $fkElement, $entity)
	{
		$object = $this->fetchSupportedObject($elementType, $fkElement, $entity);
		if (!$object) {
			return false;
		}
		$definition = self::getZonableObjectDefinition($elementType);
		$strategy = !empty($definition['address_strategy']) ? (string) $definition['address_strategy'] : 'self_then_thirdparty';
		if ($strategy !== 'thirdparty') {
			$address = $this->extractAddressFromObject($object);
			if ($this->isUsableAddress($address)) {
				return $address;
			}
		}
		if ($strategy !== 'self') {
			foreach (array('fk_soc', 'socid') as $property) {
				if (empty($object->$property)) {
					continue;
				}
				$address = $this->fetchThirdpartyAddress((int) $object->$property, $entity);
				if ($this->isUsableAddress($address)) {
					return $address;
				}
			}
		}

		return false;
	}

	/**
	 * Fetch supported object without forcing optional module presence.
	 *
	 * @param string $elementType Object element type
	 * @param int    $fkElement   Object id
	 * @return CommonObject|false
	 */
	private function fetchSupportedObject($elementType, $fkElement, $entity = 0)
	{
		$definition = self::getZonableObjectDefinition($elementType);
		if (empty($definition) || empty($definition['file']) || empty($definition['class'])) {
			return false;
		}
		dol_include_once($definition['file']);
		if (!class_exists($definition['class'])) {
			return false;
		}
		$class = $definition['class'];
		$object = new $class($this->db);
		if (!method_exists($object, 'fetch') || $object->fetch((int) $fkElement) <= 0) {
			return false;
		}
		if (!$this->isObjectInEntityScope($object, !empty($definition['table_element']) ? (string) $definition['table_element'] : $elementType, (int) $entity)) {
			return false;
		}

		return $object;
	}

	/**
	 * Fetch thirdparty address.
	 *
	 * @param int $socid Thirdparty id
	 * @return array<string,mixed>|false
	 */
	private function fetchThirdpartyAddress($socid, $entity = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$soc = new Societe($this->db);
		if ($soc->fetch((int) $socid) > 0 && $this->isObjectInEntityScope($soc, 'societe', (int) $entity)) {
			return $this->extractAddressFromObject($soc);
		}

		return false;
	}

	/**
	 * Check object entity against the allowed Multicompany scope for an element.
	 *
	 * @param object $object  Dolibarr object
	 * @param string $element Element or table element
	 * @param int    $entity  Current entity
	 * @return bool
	 */
	private function isObjectInEntityScope($object, $element, $entity = 0)
	{
		global $conf;

		if (!isset($object->entity)) {
			return true;
		}
		$objectEntity = (int) $object->entity;
		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$allowed = array_map('intval', explode(',', $this->getEntityForScope($element, $entity)));

		return in_array($objectEntity, $allowed, true);
	}

	/**
	 * Check category entity against the allowed Multicompany category scope.
	 *
	 * @param object $category Category object
	 * @param int    $entity   Current entity
	 * @return bool
	 */
	private function isCategoryInEntityScope($category, $entity = 0)
	{
		global $conf;

		if (!isset($category->entity)) {
			return true;
		}
		$categoryEntity = (int) $category->entity;
		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$allowed = array_map('intval', explode(',', $this->getEntityForScope('category', $entity)));

		return in_array($categoryEntity, $allowed, true);
	}

	/**
	 * Check if an extracted address contains enough data for geocoding.
	 *
	 * @param array<string,mixed>|false $address Address
	 * @return bool
	 */
	private function isUsableAddress($address)
	{
		if (!is_array($address)) {
			return false;
		}
		if ($this->geocoder->hasValidCoordinates($address)) {
			return true;
		}

		return trim($this->addressToString($address)) !== '';
	}

	/**
	 * Extract address-like fields from an object.
	 *
	 * @param object $object Object
	 * @return array<string,mixed>
	 */
	private function extractAddressFromObject($object)
	{
		$address = array(
			'address' => $this->firstProperty($object, array('installation_address', 'address_installation', 'address', 'adresse')),
			'zip' => $this->firstProperty($object, array('installation_zip', 'zip_installation', 'zip', 'zipcode')),
			'town' => $this->firstProperty($object, array('installation_town', 'town_installation', 'town', 'city')),
			'country_code' => $this->firstProperty($object, array('installation_country_code', 'country_code', 'country')),
			'latitude' => $this->firstProperty($object, array('installation_latitude', 'latitude', 'lat')),
			'longitude' => $this->firstProperty($object, array('installation_longitude', 'longitude', 'lon', 'lng')),
		);

		return $address;
	}

	/**
	 * Return first existing property value.
	 *
	 * @param object            $object Object
	 * @param array<int,string> $names  Names
	 * @return mixed
	 */
	private function firstProperty($object, array $names)
	{
		foreach ($names as $name) {
			if (isset($object->$name) && $object->$name !== '') {
				return $object->$name;
			}
		}

		return '';
	}

	/**
	 * Convert address to string.
	 *
	 * @param array<string,mixed> $address Address
	 * @return string
	 */
	private function addressToString(array $address)
	{
		return trim(implode(', ', array_filter(array(
			isset($address['address']) ? $address['address'] : '',
			isset($address['zip']) ? $address['zip'] : '',
			isset($address['town']) ? $address['town'] : '',
			isset($address['country_code']) ? $address['country_code'] : '',
		))));
	}

	/**
	 * Return category field for an element type.
	 *
	 * @param LmdbZoningProfileZone $zone        Zone
	 * @param string            $elementType Element type
	 * @return int
	 */
	private function getCategoryForElementType($zone, $elementType)
	{
		$field = 'fk_categorie_default';
		$definition = self::getZonableObjectDefinition($elementType);
		if (!empty($definition['category_field'])) {
			$field = $definition['category_field'];
		}

		return !empty($zone->$field) ? (int) $zone->$field : (!empty($zone->fk_categorie_default) ? (int) $zone->fk_categorie_default : 0);
	}

	/**
	 * Return Dolibarr numeric category type.
	 *
	 * @param string $elementType Element type
	 * @return int|string
	 */
	private function getCategoryTypeForElement($elementType)
	{
		$definition = self::getZonableObjectDefinition($elementType);

		return isset($definition['category_type_id']) && $definition['category_type_id'] !== null ? (int) $definition['category_type_id'] : '';
	}

	/**
	 * Return Dolibarr category link type.
	 *
	 * @param string $elementType Element type
	 * @return string
	 */
	private function getCategoryLinkTypeForElement($elementType)
	{
		$definition = self::getZonableObjectDefinition($elementType);

		return !empty($definition['category_link_type']) ? (string) $definition['category_link_type'] : '';
	}

	/**
	 * Return Dolibarr category link definition.
	 *
	 * @param string $elementType Element type
	 * @return array<string,string>
	 */
	private function getCategoryLinkDefinitionForElement($elementType)
	{
		$definition = self::getZonableObjectDefinition($elementType);
		$linkType = !empty($definition['category_link_type']) ? (string) $definition['category_link_type'] : '';
		$linkType = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $linkType);
		$tableName = !empty($definition['category_link_table']) ? (string) $definition['category_link_table'] : ($linkType !== '' ? 'categorie_'.$linkType : '');
		$tableName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $tableName);
		$objectField = !empty($definition['category_link_object_field']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string) $definition['category_link_object_field']) : '';
		$categoryField = !empty($definition['category_link_category_field']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string) $definition['category_link_category_field']) : '';

		return array(
			'link_type' => $linkType,
			'table' => $tableName,
			'object_field' => $objectField,
			'category_field' => $categoryField,
		);
	}

	/**
	 * Check if a Dolibarr category link table exists.
	 *
	 * @param string $linkType Category link type
	 * @return bool
	 */
	private function categoryLinkTableExists($elementType, $linkType)
	{
		$linkDefinition = $this->getCategoryLinkDefinitionForElement($elementType);
		$tableName = $linkDefinition['table'];
		if ($tableName === '') {
			return false;
		}
		$sql = "SHOW TABLES LIKE '".$this->db->escape(MAIN_DB_PREFIX.$tableName)."'";
		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}

		return (bool) $this->db->num_rows($resql);
	}

	/**
	 * Add a category link to an object.
	 *
	 * @param object $object      Target object
	 * @param string $elementType Element type
	 * @param string $linkType    Category link type
	 * @param int    $fkElement   Element id
	 * @param int    $fkCategory  Category id
	 * @return int
	 */
	private function addCategoryLinkToObject($object, $elementType, $linkType, $fkElement, $fkCategory)
	{
		$linkDefinition = $this->getCategoryLinkDefinitionForElement($elementType);
		if ($linkDefinition['table'] !== '' && $linkDefinition['object_field'] !== '' && $linkDefinition['category_field'] !== '') {
			return $this->insertCategoryLink($elementType, (int) $fkElement, (int) $fkCategory);
		}

		if (!class_exists('Categorie') || !method_exists('Categorie', 'add_type')) {
			return 0;
		}
		$category = new Categorie($this->db);
		if ($category->fetch((int) $fkCategory) <= 0) {
			return -1;
		}
		$result = $category->add_type($object, $linkType);

		return $result < 0 ? -1 : 1;
	}

	/**
	 * Delete a category link from an object.
	 *
	 * @param object $object      Target object
	 * @param string $elementType Element type
	 * @param string $linkType    Category link type
	 * @param int    $fkElement   Element id
	 * @param int    $fkCategory  Category id
	 * @return int
	 */
	private function deleteCategoryLinkFromObject($object, $elementType, $linkType, $fkElement, $fkCategory)
	{
		$linkDefinition = $this->getCategoryLinkDefinitionForElement($elementType);
		if ($linkDefinition['table'] !== '' && $linkDefinition['object_field'] !== '' && $linkDefinition['category_field'] !== '') {
			return $this->deleteCategoryLink($elementType, (int) $fkElement, (int) $fkCategory);
		}

		if (!class_exists('Categorie') || !method_exists('Categorie', 'del_type')) {
			return 0;
		}
		$category = new Categorie($this->db);
		if ($category->fetch((int) $fkCategory) <= 0) {
			return -1;
		}
		$result = $category->del_type($object, $linkType);

		return $result < 0 ? -1 : 1;
	}

	/**
	 * Insert an explicit category link.
	 *
	 * @param string $elementType Element type
	 * @param int    $fkElement   Element id
	 * @param int    $fkCategory  Category id
	 * @return int
	 */
	private function insertCategoryLink($elementType, $fkElement, $fkCategory)
	{
		$linkDefinition = $this->getCategoryLinkDefinitionForElement($elementType);
		if (!$this->categoryLinkTableExists($elementType, $linkDefinition['link_type'])) {
			dol_syslog(__METHOD__.' category link table missing elementType='.$elementType.' table='.$linkDefinition['table'], LOG_WARNING);
			return 0;
		}
		$table = MAIN_DB_PREFIX.$linkDefinition['table'];
		$categoryField = $linkDefinition['category_field'];
		$objectField = $linkDefinition['object_field'];
		$sql = 'SELECT '.$categoryField.' FROM '.$table;
		$sql .= ' WHERE '.$categoryField.' = '.((int) $fkCategory);
		$sql .= ' AND '.$objectField.' = '.((int) $fkElement);
		$sql .= ' LIMIT 1';
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' category link select failed: '.$this->db->lasterror(), LOG_WARNING);
			return -1;
		}
		if ($this->db->num_rows($resql) > 0) {
			return 1;
		}

		$sql = 'INSERT INTO '.$table.' ('.$categoryField.', '.$objectField.') VALUES ('.((int) $fkCategory).', '.((int) $fkElement).')';
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' category link insert failed: '.$this->db->lasterror(), LOG_WARNING);
			return -1;
		}

		return 1;
	}

	/**
	 * Delete an explicit category link.
	 *
	 * @param string $elementType Element type
	 * @param int    $fkElement   Element id
	 * @param int    $fkCategory  Category id
	 * @return int
	 */
	private function deleteCategoryLink($elementType, $fkElement, $fkCategory)
	{
		$linkDefinition = $this->getCategoryLinkDefinitionForElement($elementType);
		if (!$this->categoryLinkTableExists($elementType, $linkDefinition['link_type'])) {
			dol_syslog(__METHOD__.' category link table missing elementType='.$elementType.' table='.$linkDefinition['table'], LOG_WARNING);
			return 0;
		}
		$table = MAIN_DB_PREFIX.$linkDefinition['table'];
		$sql = 'DELETE FROM '.$table;
		$sql .= ' WHERE '.$linkDefinition['category_field'].' = '.((int) $fkCategory);
		$sql .= ' AND '.$linkDefinition['object_field'].' = '.((int) $fkElement);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(__METHOD__.' category link delete failed: '.$this->db->lasterror(), LOG_WARNING);
			return -1;
		}

		return 1;
	}

	/**
	 * Build SQL CASE expression for element type category priority.
	 *
	 * @param string $field SQL field expression
	 * @return string
	 */
	private function getElementTypePrioritySql($field)
	{
		$cases = array();
		foreach (self::getOrderedZonableObjectDefinitions(0) as $elementType => $definition) {
			$priority = isset($definition['category_priority']) ? (int) $definition['category_priority'] : 30;
			$cases[] = "WHEN '".$this->db->escape($elementType)."' THEN ".$priority;
		}

		return 'CASE '.$field.' '.implode(' ', $cases).' ELSE 30 END';
	}

	/**
	 * Remove known zone categories for this profile from target before adding new one.
	 *
	 * @param object              $object     Target object
	 * @param string              $elementType Element type
	 * @param string              $linkType   Category link type
	 * @param int                 $fkElement  Element id
	 * @param array<string,mixed> $zoneResult Zone result
	 * @return void
	 */
	private function removeKnownZoneCategories($object, $elementType, $linkType, $fkElement, array $zoneResult)
	{
		if (empty($zoneResult['fk_profile']) || !class_exists('Categorie')) {
			return;
		}
		if (!$this->categoryLinkTableExists($elementType, $linkType)) {
			return;
		}
		$categoryFields = $this->getZoneCategoryFieldsForElement($elementType);
		if (empty($categoryFields)) {
			return;
		}
		$sql = 'SELECT '.implode(', ', $categoryFields);
		$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_profile_zone';
		$sql .= ' WHERE fk_profile = '.((int) $zoneResult['fk_profile']);
		$resql = $this->db->query($sql);
		if (!$resql) {
			return;
		}
		while ($row = $this->db->fetch_object($resql)) {
			foreach ($row as $fkcat) {
				if (empty($fkcat) || (int) $fkcat === (int) $zoneResult['fk_categorie']) {
					continue;
				}
				$this->deleteCategoryLinkFromObject($object, $elementType, $linkType, (int) $fkElement, (int) $fkcat);
			}
		}
	}

	/**
	 * Return profile zone category fields relevant for an element type.
	 *
	 * @param string $elementType Element type
	 * @return array<int,string>
	 */
	private function getZoneCategoryFieldsForElement($elementType)
	{
		$fields = array('fk_categorie_default');
		$definition = self::getZonableObjectDefinition($elementType);
		if (!empty($definition['category_field'])) {
			$fields[] = (string) $definition['category_field'];
		}

		$validFields = array();
		foreach (array_unique($fields) as $field) {
			$field = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $field);
			if ($field !== '') {
				$validFields[] = $field;
			}
		}

		return $validFields;
	}

	/**
	 * Log event.
	 *
	 * @param string              $eventCode   Event code
	 * @param string              $elementType Element type
	 * @param int                 $fkElement   Element id
	 * @param string              $message     Message
	 * @param array<string,mixed> $context     Context
	 * @return int
	 */
	private function logEvent($eventCode, $elementType, $fkElement, $message, array $context)
	{
		global $conf, $user;

		$entity = !empty($context['entity']) ? (int) $context['entity'] : (int) $conf->entity;
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'lmdbzoning_calculation_log(';
		$sql .= 'entity, event_code, element_type, fk_element, message, context_data, datec, fk_user_creat';
		$sql .= ') VALUES (';
		$sql .= ((int) $entity).', ';
		$sql .= "'".$this->db->escape($eventCode)."', ";
		$sql .= "'".$this->db->escape($elementType)."', ";
		$sql .= ((int) $fkElement).', ';
		$sql .= "'".$this->db->escape($message)."', ";
		$sql .= "'".$this->db->escape(json_encode($context))."', ";
		$sql .= "'".$this->db->idate(dol_now())."', ";
		$sql .= (!empty($user->id) ? ((int) $user->id) : 'null');
		$sql .= ')';
		$this->db->query($sql);

		return 1;
	}

	/**
	 * Return entity filter string.
	 *
	 * @param string $element Element name
	 * @param int    $entity  Entity id
	 * @return string
	 */
	private function getEntityFilter($element, $entity)
	{
		return $this->getEntityForScope($element, $entity);
	}

	/**
	 * Return an entity scope string for one element.
	 *
	 * @param string $element Element name
	 * @param int    $entity  Entity id
	 * @return string
	 */
	private function getEntityForScope($element, $entity)
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$entities = array($entity);
		if (function_exists('getEntity')) {
			$scope = explode(',', getEntity($element));
			foreach ($scope as $scopeEntity) {
				$scopeEntity = (int) trim($scopeEntity);
				if ($scopeEntity > 0) {
					$entities[] = $scopeEntity;
				}
			}
		}

		return implode(',', array_values(array_unique($entities)));
	}

	/**
	 * Convert object row to array.
	 *
	 * @param object $obj Row object
	 * @return array<string,mixed>
	 */
	private function objectToArray($obj)
	{
		$array = array();
		foreach ($obj as $key => $value) {
			$array[$key === 'rowid' ? 'id' : $key] = $value;
		}

		return $array;
	}

	/**
	 * Build failed result.
	 *
	 * @param string $message Message
	 * @return array<string,mixed>
	 */
	private function failedResult($message)
	{
		return array(
			'status' => 'failed',
			'zone_code' => '',
			'zone_label' => '',
			'distance_km' => null,
			'fk_zone' => null,
			'fk_categorie' => null,
			'latitude' => null,
			'longitude' => null,
			'message' => $message,
		);
	}
}
