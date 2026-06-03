<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

if (!function_exists('dol_include_once')) {
	function dol_include_once($path) {
		return 0;
	}
}
if (!function_exists('dol_strtolower')) {
	function dol_strtolower($value) {
		return strtolower($value);
	}
}

require_once __DIR__.'/../class/geocoder.class.php';
require_once __DIR__.'/../class/lmdbzoningservice.class.php';

$service = new LmdbZoningService(null, new stdClass());
$distance = $service->getAirDistanceKm(44.604, -0.936, 44.8378, -0.5792);
assert($distance > 37 && $distance < 40, 'Haversine distance should be around 38 km');

$geocoder = new LmdbZoningGeocoder(null);
$normalized = $geocoder->normalizeAddress(array(
	'address' => ' 4 rue Alfred Kastler ',
	'zip' => '33380',
	'town' => 'Mios',
	'country_code' => 'FR',
));
assert($normalized === '4 rue alfred kastler 33380 mios fr', 'Address normalization must be stable');
assert(strlen($geocoder->getAddressHash($normalized)) === 64, 'Address hash must be sha256 length');

echo "lmdbzoning service tests passed\n";
