<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
dol_include_once('/lmdbzoning/class/referencepoint.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningprofile.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningprofilezone.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningobjectzone.class.php');
dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
dol_include_once('/lmdbzoning/class/geocoder.class.php');

/**
 * lmdbzoning REST API.
 */
class LmdbZoningApi extends DolibarrApi
{
	/** @var DoliDB */
	public $db;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	/**
	 * List reference points.
	 *
	 * @url GET /referencepoints
	 *
	 * @param int $limit  Limit
	 * @param int $page   Page
	 * @return array<int,array<string,mixed>>
	 */
	public function getReferencePoints($limit = 100, $page = 0)
	{
		$this->checkPermission('read');
		return $this->listObjects(new LmdbZoningReferencePoint($this->db), $limit, $page);
	}

	/**
	 * Get reference point.
	 *
	 * @url GET /referencepoints/{id}
	 *
	 * @param int $id Object id
	 * @return array<string,mixed>
	 */
	public function getReferencePoint($id)
	{
		$this->checkPermission('read');
		return $this->fetchObject(new LmdbZoningReferencePoint($this->db), $id);
	}

	/**
	 * Create reference point.
	 *
	 * @url POST /referencepoints
	 *
	 * @param array<string,mixed> $request_data Request data
	 * @return int
	 */
	public function postReferencePoint($request_data = null)
	{
		$this->checkPermission('write');
		return $this->createObject(new LmdbZoningReferencePoint($this->db), (array) $request_data);
	}

	/**
	 * Update reference point.
	 *
	 * @url PUT /referencepoints/{id}
	 *
	 * @param int                 $id Request id
	 * @param array<string,mixed> $request_data Request data
	 * @return int
	 */
	public function putReferencePoint($id, $request_data = null)
	{
		$this->checkPermission('write');
		return $this->updateObject(new LmdbZoningReferencePoint($this->db), $id, (array) $request_data);
	}

	/**
	 * Delete reference point.
	 *
	 * @url DELETE /referencepoints/{id}
	 *
	 * @param int $id Object id
	 * @return array<string,string>
	 */
	public function deleteReferencePoint($id)
	{
		$this->checkPermission('delete');
		$this->deleteObject(new LmdbZoningReferencePoint($this->db), $id);
		return array('success' => 'ok');
	}

	/**
	 * List profiles.
	 *
	 * @url GET /profiles
	 *
	 * @param int $limit Limit
	 * @param int $page  Page
	 * @return array<int,array<string,mixed>>
	 */
	public function getProfiles($limit = 100, $page = 0)
	{
		$this->checkPermission('read');
		return $this->listObjects(new LmdbZoningProfile($this->db), $limit, $page);
	}

	/**
	 * Get profile.
	 *
	 * @url GET /profiles/{id}
	 *
	 * @param int $id Object id
	 * @return array<string,mixed>
	 */
	public function getProfile($id)
	{
		$this->checkPermission('read');
		return $this->fetchObject(new LmdbZoningProfile($this->db), $id);
	}

	/**
	 * Create profile.
	 *
	 * @url POST /profiles
	 *
	 * @param array<string,mixed> $request_data Request data
	 * @return int
	 */
	public function postProfile($request_data = null)
	{
		$this->checkPermission('write');
		return $this->createObject(new LmdbZoningProfile($this->db), (array) $request_data);
	}

	/**
	 * Update profile.
	 *
	 * @url PUT /profiles/{id}
	 *
	 * @param int                 $id Request id
	 * @param array<string,mixed> $request_data Request data
	 * @return int
	 */
	public function putProfile($id, $request_data = null)
	{
		$this->checkPermission('write');
		return $this->updateObject(new LmdbZoningProfile($this->db), $id, (array) $request_data);
	}

	/**
	 * List zones for all profiles or one profile.
	 *
	 * @url GET /zones
	 *
	 * @param int $profile Profile id
	 * @param int $limit   Limit
	 * @param int $page    Page
	 * @return array<int,array<string,mixed>>
	 */
	public function getZones($profile = 0, $limit = 100, $page = 0)
	{
		$this->checkPermission('read');
		$filters = array();
		if ($profile > 0) {
			$filters['t.fk_profile'] = '='.(int) $profile;
		}
		return $this->listObjects(new LmdbZoningProfileZone($this->db), $limit, $page, $filters);
	}

	/**
	 * Create zone.
	 *
	 * @url POST /zones
	 *
	 * @param array<string,mixed> $request_data Request data
	 * @return int
	 */
	public function postZone($request_data = null)
	{
		$this->checkPermission('write');
		return $this->createObject(new LmdbZoningProfileZone($this->db), (array) $request_data);
	}

	/**
	 * Calculate from address.
	 *
	 * @url POST /calculate/address
	 *
	 * @param array<string,mixed> $request_data Request data
	 * @return array<string,mixed>
	 */
	public function calculateAddress($request_data = null)
	{
		global $conf;
		$this->checkPermission('api');
		$data = (array) $request_data;
		$profileRef = empty($data['profile_ref']) ? $conf->global->LMDBZONING_DEFAULT_PROFILE : $data['profile_ref'];
		$service = new LmdbZoningService($this->db);

		return $service->calculateZoneForAddress($data, $profileRef, (int) $conf->entity);
	}

	/**
	 * Calculate from object.
	 *
	 * @url POST /calculate/object
	 *
	 * @param array<string,mixed> $request_data Request data
	 * @return array<string,mixed>
	 */
	public function calculateObject($request_data = null)
	{
		global $conf;
		$this->checkPermission('api');
		$data = (array) $request_data;
		if (empty($data['element_type']) || empty($data['fk_element'])) {
			throw new RestException(400, 'element_type and fk_element are required');
		}
		$profileRef = empty($data['profile_ref']) ? $conf->global->LMDBZONING_DEFAULT_PROFILE : $data['profile_ref'];
		$service = new LmdbZoningService($this->db);

		return $service->calculateZoneForObject($data['element_type'], (int) $data['fk_element'], $profileRef, (int) $conf->entity);
	}

	/**
	 * Override object zone.
	 *
	 * @url POST /objects/{elementType}/{id}/override
	 *
	 * @param string              $elementType Element type
	 * @param int                 $id Object id
	 * @param array<string,mixed> $request_data Request data
	 * @return array<string,string>
	 */
	public function overrideObjectZone($elementType, $id, $request_data = null)
	{
		$this->checkPermission('override');
		$data = (array) $request_data;
		if (empty($data['zone_code']) || empty($data['reason'])) {
			throw new RestException(400, 'zone_code and reason are required');
		}
		$service = new LmdbZoningService($this->db);
		$apiUser = $this->getApiUser();
		if ($service->overrideZoneForObject($elementType, (int) $id, $data['zone_code'], $data['reason'], (int) $apiUser->id) < 0) {
			throw new RestException(500, $service->error);
		}

		return array('success' => 'ok');
	}

	/**
	 * Clear object override.
	 *
	 * @url POST /objects/{elementType}/{id}/clearoverride
	 *
	 * @param string $elementType Element type
	 * @param int    $id Object id
	 * @return array<string,string>
	 */
	public function clearObjectZoneOverride($elementType, $id)
	{
		$this->checkPermission('override');
		$service = new LmdbZoningService($this->db);
		$apiUser = $this->getApiUser();
		if ($service->clearZoneOverride($elementType, (int) $id, (int) $apiUser->id) < 0) {
			throw new RestException(500, $service->error);
		}

		return array('success' => 'ok');
	}

	/**
	 * Purge geocode cache.
	 *
	 * @url POST /geocode/cache/purge
	 *
	 * @return array<string,string>
	 */
	public function purgeGeocodeCache()
	{
		global $conf;
		$this->checkPermission('geocode');
		$geocoder = new LmdbZoningGeocoder($this->db);
		if ($geocoder->purgeCache((int) $conf->entity) < 0) {
			throw new RestException(500, 'Cache purge failed');
		}

		return array('success' => 'ok');
	}

	/**
	 * Check permission.
	 *
	 * @param string $right Right
	 * @return void
	 */
	private function checkPermission($right)
	{
		global $conf;

		$apiUser = $this->getApiUser();
		if (function_exists('isModEnabled') && !isModEnabled('lmdbzoning')) {
			throw new RestException(403, 'Module disabled');
		}
		if (empty($conf->lmdbzoning->enabled)) {
			throw new RestException(403, 'Module disabled');
		}
		if (!method_exists($apiUser, 'hasRight') || !$apiUser->hasRight('lmdbzoning', 'lmdbzoning', $right)) {
			throw new RestException(403, 'Forbidden');
		}
	}

	/**
	 * Return current API user with v20-compatible fallback.
	 *
	 * @return User
	 */
	private function getApiUser()
	{
		global $user;

		return !empty($this->user) ? $this->user : $user;
	}

	/**
	 * List objects.
	 *
	 * @param CommonObject         $object  Object
	 * @param int                  $limit   Limit
	 * @param int                  $page    Page
	 * @param array<string,string> $filters Filters
	 * @return array<int,array<string,mixed>>
	 */
	private function listObjects($object, $limit, $page, array $filters = array())
	{
		$limit = min(max((int) $limit, 1), 500);
		$page = max((int) $page, 0);
		$result = $object->fetchAll('ASC', 't.rowid', $limit, $page * $limit, $filters);
		if (!is_array($result)) {
			throw new RestException(500, $object->error);
		}
		$out = array();
		foreach ($result as $item) {
			$out[] = $this->objectToArray($item);
		}

		return $out;
	}

	/**
	 * Fetch object.
	 *
	 * @param CommonObject $object Object
	 * @param int          $id     Object id
	 * @return array<string,mixed>
	 */
	private function fetchObject($object, $id)
	{
		if ($object->fetch((int) $id) <= 0) {
			throw new RestException(404, 'Object not found');
		}

		return $this->objectToArray($object);
	}

	/**
	 * Create object.
	 *
	 * @param CommonObject        $object Object
	 * @param array<string,mixed> $data   Data
	 * @return int
	 */
	private function createObject($object, array $data)
	{
		foreach ($data as $key => $value) {
			if (array_key_exists($key, $object->fields)) {
				$object->$key = $value;
			}
		}
		$result = $object->create($this->getApiUser());
		if ($result <= 0) {
			throw new RestException(500, $object->error);
		}

		return (int) $result;
	}

	/**
	 * Update object.
	 *
	 * @param CommonObject        $object Object
	 * @param int                 $id     Object id
	 * @param array<string,mixed> $data   Data
	 * @return int
	 */
	private function updateObject($object, $id, array $data)
	{
		if ($object->fetch((int) $id) <= 0) {
			throw new RestException(404, 'Object not found');
		}
		foreach ($data as $key => $value) {
			if (array_key_exists($key, $object->fields)) {
				$object->$key = $value;
			}
		}
		$result = $object->update($this->getApiUser());
		if ($result <= 0) {
			throw new RestException(500, $object->error);
		}

		return (int) $result;
	}

	/**
	 * Delete object.
	 *
	 * @param CommonObject $object Object
	 * @param int          $id     Object id
	 * @return void
	 */
	private function deleteObject($object, $id)
	{
		if ($object->fetch((int) $id) <= 0) {
			throw new RestException(404, 'Object not found');
		}
		if ($object->delete($this->getApiUser()) <= 0) {
			throw new RestException(500, $object->error);
		}
	}

	/**
	 * Convert object to array.
	 *
	 * @param object $object Object
	 * @return array<string,mixed>
	 */
	private function objectToArray($object)
	{
		$array = array();
		foreach ($object->fields as $field => $definition) {
			$array[$field === 'rowid' ? 'id' : $field] = isset($object->$field) ? $object->$field : null;
		}
		if (!empty($object->id)) {
			$array['id'] = (int) $object->id;
		}

		return $array;
	}
}
