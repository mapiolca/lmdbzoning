<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Hooks for lmdbzoning.
 */
class ActionsLmdbZoning
{
	/** @var DoliDB */
	public $db;

	/** @var string */
	public $error = '';

	/** @var array<int,string> */
	public $errors = array();

	/** @var array<string,mixed> */
	public $results = array();

	/** @var string */
	public $resprints = '';

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
	 * Multicompany sharing definition.
	 *
	 * @return array<string,mixed>
	 */
	public static function getMulticompanySharingDefinition()
	{
		return array(
			'lmdbzoning' => array(
				'sharingelements' => array(
					'lmdbzoning_referencepoint' => array(
						'type' => 'element',
						'icon' => 'map-marker-alt',
						'lang' => 'lmdbzoning@lmdbzoning',
						'tooltip' => 'LmdbZoningReferencePointSharingInfo',
						'enable' => '! empty($conf->lmdbzoning->enabled)',
						'input' => array(
							'global' => array('showhide' => true, 'hide' => true, 'del' => true),
						),
					),
					'lmdbzoning_profile' => array(
						'type' => 'element',
						'icon' => 'layer-group',
						'lang' => 'lmdbzoning@lmdbzoning',
						'tooltip' => 'LmdbZoningProfileSharingInfo',
						'enable' => '! empty($conf->lmdbzoning->enabled)',
						'input' => array(
							'global' => array('showhide' => true, 'hide' => true, 'del' => true),
						),
					),
					'lmdbzoning_zone' => array(
						'type' => 'element',
						'icon' => 'draw-polygon',
						'lang' => 'lmdbzoning@lmdbzoning',
						'tooltip' => 'LmdbZoningZoneSharingInfo',
						'enable' => '! empty($conf->lmdbzoning->enabled)',
						'input' => array(
							'global' => array('showhide' => true, 'hide' => true, 'del' => true),
						),
					),
					'lmdbzoning_objectzone' => array(
						'type' => 'element',
						'icon' => 'bullseye',
						'lang' => 'lmdbzoning@lmdbzoning',
						'tooltip' => 'LmdbZoningObjectZoneSharingInfo',
						'enable' => '! empty($conf->lmdbzoning->enabled)',
						'input' => array(
							'global' => array('showhide' => true, 'hide' => true, 'del' => true),
						),
					),
					'lmdbzoning_geocodecache' => array(
						'type' => 'element',
						'icon' => 'database',
						'lang' => 'lmdbzoning@lmdbzoning',
						'tooltip' => 'LmdbZoningGeocodeCacheSharingInfo',
						'enable' => '! empty($conf->lmdbzoning->enabled)',
						'input' => array(
							'global' => array('showhide' => true, 'hide' => true, 'del' => true),
						),
					),
					'lmdbzoning_calculationlog' => array(
						'type' => 'element',
						'icon' => 'list',
						'lang' => 'lmdbzoning@lmdbzoning',
						'tooltip' => 'LmdbZoningCalculationLogSharingInfo',
						'enable' => '! empty($conf->lmdbzoning->enabled)',
						'input' => array(
							'global' => array('showhide' => true, 'hide' => true, 'del' => true),
						),
					),
				),
				'sharingmodulename' => array(
					'lmdbzoning_referencepoint' => 'lmdbzoning',
					'lmdbzoning_profile' => 'lmdbzoning',
					'lmdbzoning_zone' => 'lmdbzoning',
					'lmdbzoning_objectzone' => 'lmdbzoning',
					'lmdbzoning_geocodecache' => 'lmdbzoning',
					'lmdbzoning_calculationlog' => 'lmdbzoning',
				),
			),
		);
	}

	/**
	 * Multicompany hook.
	 *
	 * @param array<string,mixed> $parameters Parameters
	 * @param object             $object     Object
	 * @param string             $action     Action
	 * @param HookManager        $hookmanager Hook manager
	 * @return int
	 */
	public function multicompanyExternalModulesSharing($parameters, &$object, &$action, $hookmanager)
	{
		$this->results = array_replace_recursive($this->results, self::getMulticompanySharingDefinition());
		return 0;
	}

	/**
	 * Compatibility with singular hook naming.
	 *
	 * @param array<string,mixed> $parameters Parameters
	 * @param object             $object     Object
	 * @param string             $action     Action
	 * @param HookManager        $hookmanager Hook manager
	 * @return int
	 */
	public function multicompanyExternalModuleSharing($parameters, &$object, &$action, $hookmanager)
	{
		$this->results = array_replace_recursive($this->results, self::getMulticompanySharingDefinition());
		return 0;
	}

	/**
	 * Multicompany options hook.
	 *
	 * @param array<string,mixed> $parameters Parameters
	 * @param object             $object     Object
	 * @param string             $action     Action
	 * @param HookManager        $hookmanager Hook manager
	 * @return int
	 */
	public function multicompanySharingOptions($parameters, &$object, &$action, $hookmanager)
	{
		$this->results = array_replace_recursive($this->results, self::getMulticompanySharingDefinition());
		return 0;
	}

	/**
	 * Add the native category type used by contracts.
	 *
	 * @param array<string,mixed> $parameters Parameters
	 * @param object             $object     Category object
	 * @param string             $action     Action
	 * @param HookManager        $hookmanager Hook manager
	 * @return int
	 */
	public function constructCategory($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		if (function_exists('isModEnabled') && !isModEnabled('lmdbzoning')) {
			return 0;
		}
		$langs->load('lmdbzoning@lmdbzoning');

		$this->results = array(
			array(
				'id' => 450022,
				'code' => 'contract',
				'cat_fk' => 'contract',
				'cat_table' => 'contract',
				'obj_class' => 'Contrat',
				'obj_table' => 'contrat',
				'label' => 'Contract',
			),
		);
		$hookmanager->resArray = $this->results;

		return 0;
	}

	/**
	 * Add a read-only lmdbzoning block on supported object cards.
	 *
	 * @param array<string,mixed> $parameters Parameters
	 * @param object             $object     Object
	 * @param string             $action     Action
	 * @param HookManager        $hookmanager Hook manager
	 * @return int
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

		if (!method_exists($user, 'hasRight') || !$user->hasRight('lmdbzoning', 'lmdbzoning', 'read')) {
			return 0;
		}
		$contexts = explode(':', isset($parameters['context']) ? $parameters['context'] : '');
		$supported = array('propalcard', 'ordercard', 'contractcard', 'projectcard', 'fichintercard', 'powerplantpvcard', 'timesheetweekcard');
		if (!array_intersect($contexts, $supported)) {
			return 0;
		}
		if (empty($object->id) || empty($object->element)) {
			return 0;
		}

		dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
		$service = new LmdbZoningService($this->db);
		$entity = isset($object->entity) && (int) $object->entity > 0 ? (int) $object->entity : 0;
		$result = $service->getObjectZone($object->element, (int) $object->id, null, $entity);
		if (empty($result)) {
			return 0;
		}

		$langs->load('lmdbzoning@lmdbzoning');
		$out = '<tr class="lmdbzoning-object-block"><td class="titlefield">'.$langs->trans('LmdbZoning').'</td><td>';
		$out .= dol_escape_htmltag($result['zone_code']).' - '.price($result['distance_km']).' '.$langs->trans('km');
		if (!empty($result['manual_override'])) {
			$out .= ' '.img_picto($langs->trans('ManualOverride'), 'warning');
		}
		$out .= '</td></tr>';
		$this->resprints = $out;

		return 0;
	}
}
