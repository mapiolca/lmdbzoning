<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Central compatibility registry for the LmdbZoning module.
 */
class LmdbZoningCompatibility
{
	/**
	 * Return true when current Dolibarr version is at least requested version.
	 *
	 * @param string $version Minimal version
	 * @return bool
	 */
	public static function isDolibarrVersionAtLeast($version)
	{
		return defined('DOL_VERSION') && version_compare(DOL_VERSION, $version, '>=');
	}

	/**
	 * Return true when current PHP version is at least requested version.
	 *
	 * @param string $version Minimal version
	 * @return bool
	 */
	public static function isPhpVersionAtLeast($version)
	{
		return version_compare(PHP_VERSION, $version, '>=');
	}

	/**
	 * Return feature definitions with computed availability.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function getFeatures()
	{
		global $conf;

		if (!class_exists('Categorie') && defined('DOL_DOCUMENT_ROOT') && file_exists(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php')) {
			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		}

		$features = array(
			'core_module' => array(
				'label' => 'LmdbZoningCoreFeature',
				'description' => 'LmdbZoningCoreFeatureDescription',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => self::isDolibarrVersionAtLeast('20.0.0') && self::isPhpVersionAtLeast('8.0.0'),
				'reason' => '',
			),
			'geoplateforme_geocoder' => array(
				'label' => 'GeoplateformeGeocoderFeature',
				'description' => 'GeoplateformeGeocoderFeatureDescription',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => self::isDolibarrVersionAtLeast('20.0.0') && self::isPhpVersionAtLeast('8.0.0') && (function_exists('curl_init') || function_exists('getURLContent')),
				'reason' => 'RequiresCurlOrGetUrlContent',
			),
			'categories' => array(
				'label' => 'CategoriesFeature',
				'description' => 'CategoriesFeatureDescription',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => self::isDolibarrVersionAtLeast('20.0.0') && class_exists('Categorie'),
				'reason' => 'RequiresCategoriesClass',
			),
			'powerplantpv_integration' => array(
				'label' => 'PowerplantPVIntegrationFeature',
				'description' => 'PowerplantPVIntegrationFeatureDescription',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => function_exists('isModEnabled') ? isModEnabled('powerplantpv') : !empty($conf->powerplantpv->enabled),
				'reason' => 'PowerplantPVNotEnabled',
			),
			'pricelist_integration' => array(
				'label' => 'PricelistIntegrationFeature',
				'description' => 'PricelistIntegrationFeatureDescription',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => function_exists('isModEnabled') ? isModEnabled('pricelist') : !empty($conf->pricelist->enabled),
				'reason' => 'PricelistNotEnabled',
			),
			'timesheetweek_integration' => array(
				'label' => 'TimesheetWeekIntegrationFeature',
				'description' => 'TimesheetWeekIntegrationFeatureDescription',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => function_exists('isModEnabled') ? isModEnabled('timesheetweek') : !empty($conf->timesheetweek->enabled),
				'reason' => 'TimesheetWeekNotEnabled',
			),
			'api' => array(
				'label' => 'RestApiFeature',
				'description' => 'RestApiFeatureDescription',
				'min_dolibarr' => '20.0.0',
				'min_php' => '8.0.0',
				'available' => class_exists('DolibarrApi'),
				'reason' => 'RequiresDolibarrApi',
			),
		);

		foreach ($features as $code => $feature) {
			if (!$feature['available']) {
				if (!self::isDolibarrVersionAtLeast($feature['min_dolibarr'])) {
					$features[$code]['reason'] = 'RequiresDolibarrVersion';
				} elseif (!self::isPhpVersionAtLeast($feature['min_php'])) {
					$features[$code]['reason'] = 'RequiresPhpVersion';
				}
			}
		}

		return $features;
	}

	/**
	 * Return true if a feature is available.
	 *
	 * @param string $code Feature code
	 * @return bool
	 */
	public static function isFeatureAvailable($code)
	{
		$features = self::getFeatures();

		return !empty($features[$code]['available']);
	}

	/**
	 * Return unavailable features.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function getUnavailableFeatures()
	{
		$unavailable = array();
		foreach (self::getFeatures() as $code => $feature) {
			if (empty($feature['available'])) {
				$unavailable[$code] = $feature;
			}
		}

		return $unavailable;
	}
}
