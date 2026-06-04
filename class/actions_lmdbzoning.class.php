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

	/** @var array<int,string> */
	public $warnings = array();

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
	 * Process lmdbzoning contract card actions.
	 *
	 * @param array<string,mixed> $parameters Parameters
	 * @param object             $object     Object
	 * @param string             $action     Action
	 * @param HookManager        $hookmanager Hook manager
	 * @return int
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

		if ($action !== 'updatelmdbzoningcontractcategories') {
			return 0;
		}
		if (!$this->isLmdbZoningEnabled()) {
			return 0;
		}
		if (!$this->isHookContext($parameters, 'contractcard', $hookmanager)) {
			return 0;
		}

		$langs->load('lmdbzoning@lmdbzoning');
		$id = $this->getObjectId($object);
		if ($id <= 0) {
			setEventMessages($langs->trans('NoRecordFound'), null, 'errors');
			return -1;
		}
		if (!$this->canWriteContractCategories($user)) {
			accessforbidden();
		}

		$this->checkPostToken();
		$selectedCategories = GETPOST('lmdbzoning_contract_categories', 'array');
		if (!is_array($selectedCategories)) {
			$selectedCategories = array();
		}

		dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
		$service = new LmdbZoningService($this->db);
		$entity = $this->getObjectEntity($object);
		$result = $service->syncLinkedCategoriesForElement('contract', $id, $selectedCategories, $entity);
		if ($result < 0) {
			$error = !empty($service->error) ? $service->error : 'InvalidContractCategory';
			setEventMessages($langs->trans($error), $service->errors, 'errors');
		} else {
			setEventMessages($langs->trans('ContractCategoriesSaved'), null, 'mesgs');
		}

		$url = $_SERVER['PHP_SELF'].'?id='.$id;
		header('Location: '.$url);
		exit;
	}

	/**
	 * Add lmdbzoning contract categories block on existing contract cards.
	 *
	 * @param array<string,mixed> $parameters Parameters
	 * @param object             $object     Object
	 * @param string             $action     Action
	 * @param HookManager        $hookmanager Hook manager
	 * @return int
	 */
	public function formConfirm($parameters, &$object, &$action, $hookmanager)
	{
		global $user;

		if (!$this->isLmdbZoningEnabled()) {
			return 0;
		}
		if (!$this->isHookContext($parameters, 'contractcard', $hookmanager)) {
			return 0;
		}
		if (!$this->canReadContract($user)) {
			return 0;
		}

		$id = $this->getObjectId($object);
		if ($id <= 0) {
			return 0;
		}

		dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
		$service = new LmdbZoningService($this->db);
		$this->resprints = $this->renderContractCategoriesBlock($service, $object, $this->getObjectEntity($object), $this->canWriteContractCategories($user));

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

		if (!$this->canReadLmdbZoning($user)) {
			return 0;
		}
		if (!$this->isLmdbZoningEnabled()) {
			return 0;
		}
		$contexts = $this->getHookContexts($parameters, $hookmanager);
		$supported = array('propalcard', 'ordercard', 'contractcard', 'projectcard', 'fichintercard', 'powerplantpvcard', 'timesheetweekcard');
		if (!array_intersect($contexts, $supported)) {
			return 0;
		}
		if (empty($object->id) || empty($object->element)) {
			return 0;
		}

		dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
		$service = new LmdbZoningService($this->db);
		$entity = $this->getObjectEntity($object);
		$elementType = $this->getHookElementType($object, $contexts);
		$result = $service->getObjectZone($elementType, (int) $object->id, null, $entity);

		$langs->load('lmdbzoning@lmdbzoning');
		$out = '';
		if (!empty($result)) {
			$out .= '<tr class="lmdbzoning-object-block"><td class="titlefield">'.$langs->trans('LmdbZoning').'</td><td>';
			$out .= dol_escape_htmltag($result['zone_code']).' - '.price($result['distance_km']).' '.$langs->trans('km');
			if (!empty($result['manual_override'])) {
				$out .= ' '.img_picto($langs->trans('ManualOverride'), 'warning');
			}
			$out .= '</td></tr>';
		}
		if ($out === '') {
			return 0;
		}
		$this->resprints = $out;

		return 0;
	}

	/**
	 * Render contract categories block.
	 *
	 * @param LmdbZoningService $service  Zoning service
	 * @param object            $object   Contract object
	 * @param int               $entity   Entity id
	 * @param bool              $canWrite Can write categories
	 * @return string
	 */
	private function renderContractCategoriesBlock($service, $object, $entity, $canWrite)
	{
		global $langs;

		$id = $this->getObjectId($object);
		if ($id <= 0) {
			return '';
		}

		$options = $service->getCategoryOptionsForElementType('contract', $entity);
		$linkedCategories = $service->getLinkedCategoryIdsForElement('contract', $id, $entity);
		$protectedCategories = $service->getProtectedZoningCategoryIdsForElement('contract', $id, $entity);
		$selectedCategories = array_values(array_unique(array_merge($linkedCategories, $protectedCategories)));
		foreach ($protectedCategories as $categoryId) {
			if (isset($options[(int) $categoryId])) {
				$options[(int) $categoryId] .= ' - '.$langs->trans('LmdbZoningProtectedCategory');
			}
		}

		$out = '<div class="fichecenter lmdbzoning-contract-categories-block">';
		$out .= '<div class="underbanner clearboth"></div>';
		$out .= '<table class="border tableforfield centpercent">';
		$out .= '<tr class="lmdbzoning-contract-categories"><td class="titlefield">'.$langs->trans('ContractCategories').'</td><td>';
		if (empty($options) && empty($selectedCategories)) {
			$out .= '<span class="opacitymedium">'.$langs->trans('NoContractCategoryAvailable').'</span>';
		} elseif ($canWrite && !empty($options)) {
			$out .= '<form method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'">';
			$out .= '<input type="hidden" name="token" value="'.dol_escape_htmltag($this->getNewToken()).'">';
			$out .= '<input type="hidden" name="action" value="updatelmdbzoningcontractcategories">';
			$out .= '<input type="hidden" name="id" value="'.((int) $id).'">';
			$out .= $this->renderMultiSelect('lmdbzoning_contract_categories', $options, $selectedCategories);
			$out .= ' <input type="submit" class="button small" value="'.dol_escape_htmltag($langs->trans('Save')).'">';
			$out .= '</form>';
		} else {
			$labels = array();
			foreach ($selectedCategories as $categoryId) {
				if (isset($options[(int) $categoryId])) {
					$labels[] = dol_escape_htmltag($options[(int) $categoryId]);
				}
			}
			$out .= !empty($labels) ? implode(', ', $labels) : $langs->trans('NoRecordFound');
		}
		$out .= '</td></tr></table></div><br>';

		return $out;
	}

	/**
	 * Render a Dolibarr multiselect2-compatible control.
	 *
	 * @param string            $htmlName HTML field name
	 * @param array<int,string> $options  Options
	 * @param array<int,int>    $selected Selected values
	 * @return string
	 */
	private function renderMultiSelect($htmlName, array $options, array $selected)
	{
		global $form;

		$selected = array_map('intval', $selected);
		if (is_object($form) && method_exists($form, 'multiselectarray')) {
			return $form->multiselectarray($htmlName, $options, $selected, 0, 0, 'flat minwidth300', 0, 0, '', '');
		}

		$htmlId = preg_replace('/[^a-zA-Z0-9_]/', '_', $htmlName);
		$out = '<select id="'.dol_escape_htmltag($htmlId).'" name="'.dol_escape_htmltag($htmlName).'[]" class="flat minwidth300" multiple="multiple">';
		foreach ($options as $key => $label) {
			$out .= '<option value="'.((int) $key).'"'.(in_array((int) $key, $selected, true) ? ' selected' : '').'>'.dol_escape_htmltag($label).'</option>';
		}
		$out .= '</select>';
		if (function_exists('ajax_combobox')) {
			$out .= ajax_combobox($htmlId);
		}

		return $out;
	}

	/**
	 * Return hook contexts.
	 *
	 * @param array<string,mixed> $parameters Hook parameters
	 * @return array<int,string>
	 */
	private function getHookContexts($parameters, $hookmanager = null)
	{
		$contexts = array();
		foreach (array('context', 'currentcontext') as $key) {
			if (!empty($parameters[$key])) {
				$contexts = array_merge($contexts, explode(':', (string) $parameters[$key]));
			}
		}
		if (is_object($hookmanager)) {
			if (!empty($hookmanager->contextarray) && is_array($hookmanager->contextarray)) {
				$contexts = array_merge($contexts, $hookmanager->contextarray);
			}
			if (!empty($hookmanager->context)) {
				$contexts = array_merge($contexts, explode(':', (string) $hookmanager->context));
			}
		}

		return array_values(array_unique(array_filter($contexts)));
	}

	/**
	 * Check if hook context is active.
	 *
	 * @param array<string,mixed> $parameters Hook parameters
	 * @param string              $context    Context to check
	 * @return bool
	 */
	private function isHookContext($parameters, $context, $hookmanager = null)
	{
		return in_array($context, $this->getHookContexts($parameters, $hookmanager), true);
	}

	/**
	 * Return element type used by lmdbzoning for a hook object.
	 *
	 * @param object            $object   Object
	 * @param array<int,string> $contexts Hook contexts
	 * @return string
	 */
	private function getHookElementType($object, array $contexts)
	{
		if (in_array('contractcard', $contexts, true)) {
			return 'contract';
		}

		return !empty($object->element) ? (string) $object->element : '';
	}

	/**
	 * Return object id.
	 *
	 * @param object $object Object
	 * @return int
	 */
	private function getObjectId($object)
	{
		if (is_object($object) && !empty($object->id)) {
			return (int) $object->id;
		}
		if (is_object($object) && !empty($object->rowid)) {
			return (int) $object->rowid;
		}
		if (function_exists('GETPOSTINT')) {
			return GETPOSTINT('id');
		}
		if (function_exists('GETPOST')) {
			return (int) GETPOST('id', 'int');
		}

		return 0;
	}

	/**
	 * Return object entity.
	 *
	 * @param object $object Object
	 * @return int
	 */
	private function getObjectEntity($object)
	{
		if (is_object($object) && isset($object->entity) && (int) $object->entity > 0) {
			return (int) $object->entity;
		}

		return 0;
	}

	/**
	 * Check lmdbzoning activation.
	 *
	 * @return bool
	 */
	private function isLmdbZoningEnabled()
	{
		global $conf;

		if (function_exists('isModEnabled')) {
			return isModEnabled('lmdbzoning');
		}

		return (!empty($conf->lmdbzoning->enabled) || !empty($conf->global->MAIN_MODULE_LMDBZONING));
	}

	/**
	 * Check POST CSRF token.
	 *
	 * @return void
	 */
	private function checkPostToken()
	{
		if (function_exists('checkToken')) {
			if (!checkToken()) {
				accessforbidden('Bad token');
			}
			return;
		}
		$token = GETPOST('token', 'alpha');
		if (empty($_SESSION['newtoken']) || $token !== $_SESSION['newtoken']) {
			accessforbidden('Bad token');
		}
	}

	/**
	 * Return a fresh Dolibarr token.
	 *
	 * @return string
	 */
	private function getNewToken()
	{
		if (function_exists('newToken')) {
			return newToken();
		}

		return !empty($_SESSION['newtoken']) ? (string) $_SESSION['newtoken'] : '';
	}

	/**
	 * Check read permission on lmdbzoning.
	 *
	 * @param User $user User
	 * @return bool
	 */
	private function canReadLmdbZoning($user)
	{
		return $this->userHasRight($user, 'lmdbzoning', 'lmdbzoning', 'read');
	}

	/**
	 * Check read permission on contracts.
	 *
	 * @param User $user User
	 * @return bool
	 */
	private function canReadContract($user)
	{
		return $this->userHasRight($user, 'contrat', 'lire')
			|| $this->userHasRight($user, 'contrat', 'read')
			|| $this->canWriteContract($user);
	}

	/**
	 * Check write permission for contract categories.
	 *
	 * @param User $user User
	 * @return bool
	 */
	private function canWriteContractCategories($user)
	{
		$canWriteLmdbZoning = $this->userHasRight($user, 'lmdbzoning', 'lmdbzoning', 'write');

		return $canWriteLmdbZoning && $this->canWriteContract($user);
	}

	/**
	 * Check write permission on contracts.
	 *
	 * @param User $user User
	 * @return bool
	 */
	private function canWriteContract($user)
	{
		return $this->userHasRight($user, 'contrat', 'creer')
			|| $this->userHasRight($user, 'contrat', 'write')
			|| $this->userHasRight($user, 'contrat', 'contrat', 'write');
	}

	/**
	 * Check a Dolibarr right with hasRight and legacy rights tree fallback.
	 *
	 * @param User   $user   User
	 * @param string $module Module key
	 * @param string $level1 Right level 1
	 * @param string $level2 Right level 2
	 * @param string $level3 Right level 3
	 * @return bool
	 */
	private function userHasRight($user, $module, $level1, $level2 = '', $level3 = '')
	{
		if (method_exists($user, 'hasRight')) {
			if ($level3 !== '' && $user->hasRight($module, $level1, $level2, $level3)) {
				return true;
			}
			if ($level2 !== '' && $user->hasRight($module, $level1, $level2)) {
				return true;
			}
			if ($level2 === '' && $user->hasRight($module, $level1)) {
				return true;
			}
		}

		$path = array($module, $level1);
		if ($level2 !== '') {
			$path[] = $level2;
		}
		if ($level3 !== '') {
			$path[] = $level3;
		}
		if ($this->userHasLegacyRightPath($user, $path)) {
			return true;
		}
		if ($module === 'contrat' && $level2 !== '' && $this->userHasLegacyRightPath($user, array($module, $level2))) {
			return true;
		}

		return false;
	}

	/**
	 * Check a legacy $user->rights path.
	 *
	 * @param User              $user User
	 * @param array<int,string> $path Rights path
	 * @return bool
	 */
	private function userHasLegacyRightPath($user, array $path)
	{
		if (empty($user->rights)) {
			return false;
		}
		$current = $user->rights;
		foreach ($path as $segment) {
			if ($segment === '') {
				continue;
			}
			if (!isset($current->$segment)) {
				return false;
			}
			$current = $current->$segment;
		}

		return !empty($current);
	}
}
