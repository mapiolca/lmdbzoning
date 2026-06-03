<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

dol_include_once('/lmdbzoning/class/lmdbzoninggeocodecache.class.php');

/**
 * Geocoder service with Dolibarr entity-aware cache.
 */
class LmdbZoningGeocoder
{
	const STATUS_OK = 'ok';
	const STATUS_AMBIGUOUS = 'ambiguous';
	const STATUS_FAILED = 'failed';
	const SOURCE_MANUAL = 'manual';
	const SOURCE_GEOPLATEFORME = 'geoplateforme';

	/** @var DoliDB */
	private $db;

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
	 * Normalize an address array.
	 *
	 * @param array<string,mixed> $address Address data
	 * @return string
	 */
	public function normalizeAddress(array $address)
	{
		$parts = array();
		foreach (array('address', 'zip', 'town', 'country_code') as $key) {
			if (!empty($address[$key])) {
				$parts[] = trim((string) $address[$key]);
			}
		}
		$normalized = preg_replace('/\s+/', ' ', implode(' ', $parts));

		return dol_strtolower(trim((string) $normalized));
	}

	/**
	 * Return address hash.
	 *
	 * @param array<string,mixed>|string $address Address array or normalized string
	 * @return string
	 */
	public function getAddressHash($address)
	{
		$normalized = is_array($address) ? $this->normalizeAddress($address) : (string) $address;

		return hash('sha256', $normalized);
	}

	/**
	 * Geocode address or return already provided valid coordinates.
	 *
	 * @param array<string,mixed> $address Address data
	 * @param int                $entity  Entity id
	 * @param int                $force   Force external geocoding
	 * @return array<string,mixed>
	 */
	public function geocode(array $address, $entity = 0, $force = 0)
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$normalized = $this->normalizeAddress($address);
		$hash = $this->getAddressHash($normalized);
		$raw = trim(implode(', ', array_filter(array(
			isset($address['address']) ? $address['address'] : '',
			isset($address['zip']) ? $address['zip'] : '',
			isset($address['town']) ? $address['town'] : '',
			isset($address['country_code']) ? $address['country_code'] : '',
		))));

		if ($this->hasValidCoordinates($address)) {
			return array(
				'status' => self::STATUS_OK,
				'latitude' => (float) $address['latitude'],
				'longitude' => (float) $address['longitude'],
				'source' => self::SOURCE_MANUAL,
				'confidence_score' => null,
				'address_hash' => $hash,
				'address_normalized' => $normalized,
				'message' => '',
			);
		}

		if ($normalized === '') {
			$result = $this->buildFailure($hash, $normalized, 'EmptyAddress');
			$this->saveCache($entity, $hash, $raw, $normalized, $result);
			return $result;
		}

		if (empty($force)) {
			$cached = $this->fetchUsableCache($entity, $hash);
			if (is_array($cached)) {
				return $cached;
			}
		}

		if (empty($conf->global->LMDBZONING_GEOCODER_ENABLED)) {
			$result = $this->buildFailure($hash, $normalized, 'GeocoderDisabled');
			$this->saveCache($entity, $hash, $raw, $normalized, $result);
			return $result;
		}

		$provider = empty($conf->global->LMDBZONING_GEOCODER_PROVIDER) ? self::SOURCE_GEOPLATEFORME : $conf->global->LMDBZONING_GEOCODER_PROVIDER;
		if ($provider !== self::SOURCE_GEOPLATEFORME) {
			$result = $this->buildFailure($hash, $normalized, 'UnsupportedGeocoderProvider');
			$this->saveCache($entity, $hash, $raw, $normalized, $result);
			return $result;
		}

		$result = $this->geocodeWithGeoplateforme($normalized, $hash);
		$this->saveCache($entity, $hash, $raw, $normalized, $result);

		return $result;
	}

	/**
	 * Check latitude/longitude.
	 *
	 * @param array<string,mixed> $address Address
	 * @return bool
	 */
	public function hasValidCoordinates(array $address)
	{
		if (!isset($address['latitude']) || !isset($address['longitude']) || $address['latitude'] === '' || $address['longitude'] === '') {
			return false;
		}

		$lat = (float) $address['latitude'];
		$lon = (float) $address['longitude'];

		return $lat >= -90 && $lat <= 90 && $lon >= -180 && $lon <= 180;
	}

	/**
	 * Purge cache.
	 *
	 * @param int $entity Entity id, 0=current entity
	 * @return int
	 */
	public function purgeCache($entity = 0)
	{
		global $conf;

		$entity = $entity > 0 ? (int) $entity : (int) $conf->entity;
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'lmdbzoning_geocode_cache WHERE entity = '.$entity;
		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		}

		return 1;
	}

	/**
	 * Fetch usable cache by address hash.
	 *
	 * @param int    $entity Entity id
	 * @param string $hash   Address hash
	 * @return array<string,mixed>|false
	 */
	private function fetchUsableCache($entity, $hash)
	{
		global $conf;

		$duration = isset($conf->global->LMDBZONING_CACHE_DURATION_DAYS) ? (int) $conf->global->LMDBZONING_CACHE_DURATION_DAYS : 365;
		$sql = 'SELECT rowid, address_hash, address_raw, address_normalized, latitude, longitude, source, confidence_score, status, message, tms';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'lmdbzoning_geocode_cache';
		$sql .= ' WHERE entity = '.((int) $entity);
		$sql .= " AND address_hash = '".$this->db->escape($hash)."'";
		$sql .= ' ORDER BY rowid DESC';
		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}
		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return false;
		}
		if ($duration > 0 && !empty($obj->tms)) {
			$age = dol_now() - $this->db->jdate($obj->tms);
			if ($age > ($duration * 86400)) {
				return false;
			}
		}

		return array(
			'status' => $obj->status,
			'latitude' => $obj->latitude !== null ? (float) $obj->latitude : null,
			'longitude' => $obj->longitude !== null ? (float) $obj->longitude : null,
			'source' => $obj->source,
			'confidence_score' => $obj->confidence_score !== null ? (float) $obj->confidence_score : null,
			'address_hash' => $obj->address_hash,
			'address_normalized' => $obj->address_normalized,
			'message' => $obj->message,
			'cached' => 1,
		);
	}

	/**
	 * Save geocode result in cache.
	 *
	 * @param int                 $entity     Entity id
	 * @param string              $hash       Hash
	 * @param string              $raw        Raw address
	 * @param string              $normalized Normalized address
	 * @param array<string,mixed> $result     Geocode result
	 * @return int
	 */
	private function saveCache($entity, $hash, $raw, $normalized, array $result)
	{
		global $user;

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'lmdbzoning_geocode_cache(';
		$sql .= 'entity, address_hash, address_raw, address_normalized, latitude, longitude, source, confidence_score, status, message, datec, fk_user_creat';
		$sql .= ') VALUES (';
		$sql .= ((int) $entity).", ";
		$sql .= "'".$this->db->escape($hash)."', ";
		$sql .= "'".$this->db->escape($raw)."', ";
		$sql .= "'".$this->db->escape($normalized)."', ";
		$sql .= (isset($result['latitude']) && $result['latitude'] !== null ? ((float) $result['latitude']) : 'null').', ';
		$sql .= (isset($result['longitude']) && $result['longitude'] !== null ? ((float) $result['longitude']) : 'null').', ';
		$sql .= "'".$this->db->escape(isset($result['source']) ? $result['source'] : '')."', ";
		$sql .= (isset($result['confidence_score']) && $result['confidence_score'] !== null ? ((float) $result['confidence_score']) : 'null').', ';
		$sql .= "'".$this->db->escape(isset($result['status']) ? $result['status'] : self::STATUS_FAILED)."', ";
		$sql .= "'".$this->db->escape(isset($result['message']) ? $result['message'] : '')."', ";
		$sql .= "'".$this->db->idate(dol_now())."', ";
		$sql .= (!empty($user->id) ? ((int) $user->id) : 'null');
		$sql .= ') ON DUPLICATE KEY UPDATE ';
		$sql .= "address_raw = VALUES(address_raw), address_normalized = VALUES(address_normalized), latitude = VALUES(latitude), longitude = VALUES(longitude), source = VALUES(source), confidence_score = VALUES(confidence_score), status = VALUES(status), message = VALUES(message), fk_user_modif = ".(!empty($user->id) ? ((int) $user->id) : 'null');

		return $this->db->query($sql) ? 1 : -1;
	}

	/**
	 * Geocode through Geoplateforme/BAN endpoint.
	 *
	 * @param string $query Address query
	 * @param string $hash  Address hash
	 * @return array<string,mixed>
	 */
	private function geocodeWithGeoplateforme($query, $hash)
	{
		global $conf;

		$baseUrl = empty($conf->global->LMDBZONING_GEOCODER_API_URL) ? 'https://data.geopf.fr/geocodage/search' : $conf->global->LMDBZONING_GEOCODER_API_URL;
		$timeout = empty($conf->global->LMDBZONING_GEOCODER_TIMEOUT) ? 5 : (int) $conf->global->LMDBZONING_GEOCODER_TIMEOUT;
		$url = $baseUrl.(strpos($baseUrl, '?') === false ? '?' : '&').'q='.urlencode($query).'&limit=1';
		$content = false;

		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Dolibarr LmdbZoning/1.0');
			curl_setopt($ch, CURLOPT_HEADER, false);
			$content = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$error = curl_error($ch);
			curl_close($ch);
			if ($content === false || $httpcode < 200 || $httpcode >= 300) {
				return $this->buildFailure($hash, $query, $error ? $error : 'GeocoderHttpError');
			}
		} else {
			if (!function_exists('getURLContent')) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
			}
			if (function_exists('getURLContent')) {
				$response = getURLContent($url, 'GET', '', 1, $timeout);
				$content = is_array($response) && isset($response['content']) ? $response['content'] : $response;
			}
		}

		if ($content === false || $content === '') {
			return $this->buildFailure($hash, $query, 'EmptyGeocoderResponse');
		}

		$data = json_decode($content, true);
		if (!is_array($data) || empty($data['features'][0])) {
			return $this->buildFailure($hash, $query, 'NoGeocoderResult');
		}
		$feature = $data['features'][0];
		$coordinates = isset($feature['geometry']['coordinates']) && is_array($feature['geometry']['coordinates']) ? $feature['geometry']['coordinates'] : array();
		if (count($coordinates) < 2) {
			return $this->buildFailure($hash, $query, 'MissingGeocoderCoordinates');
		}
		$score = isset($feature['properties']['score']) ? (float) $feature['properties']['score'] : null;
		$status = ($score !== null && $score < 0.4) ? self::STATUS_AMBIGUOUS : self::STATUS_OK;

		return array(
			'status' => $status,
			'latitude' => (float) $coordinates[1],
			'longitude' => (float) $coordinates[0],
			'source' => self::SOURCE_GEOPLATEFORME,
			'confidence_score' => $score,
			'address_hash' => $hash,
			'address_normalized' => $query,
			'message' => $status === self::STATUS_AMBIGUOUS ? 'AmbiguousGeocoderResult' : '',
		);
	}

	/**
	 * Build failure result.
	 *
	 * @param string $hash       Address hash
	 * @param string $normalized Normalized address
	 * @param string $message    Error message
	 * @return array<string,mixed>
	 */
	private function buildFailure($hash, $normalized, $message)
	{
		return array(
			'status' => self::STATUS_FAILED,
			'latitude' => null,
			'longitude' => null,
			'source' => '',
			'confidence_score' => null,
			'address_hash' => $hash,
			'address_normalized' => $normalized,
			'message' => $message,
		);
	}
}
