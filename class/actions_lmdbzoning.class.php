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

		if (!in_array($action, array('settags', 'updatelmdbzoningcontractcategories'), true)) {
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
		$selectedCategories = GETPOST('categories', 'array');
		if (!is_array($selectedCategories)) {
			$selectedCategories = GETPOST('lmdbzoning_contract_categories', 'array');
		}
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
		global $langs, $user;

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

		$langs->load('lmdbzoning@lmdbzoning');
		dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
		$service = new LmdbZoningService($this->db);
		$this->resprints = $this->renderContractCategoriesInlineRow($service, $object, $this->getObjectEntity($object), $this->canWriteContractCategories($user), $action);

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
		if (!empty($result) && array_key_exists('distance_km', $result) && $result['distance_km'] !== null && $result['distance_km'] !== '') {
			$tooltipAttributes = $this->getDistanceComputedAjaxTooltipAttributes($object, $elementType, $result);
			$out .= '<tr class="lmdbzoning-object-block"><td class="titlefield">'.$langs->trans('LmdbZoningDistanceComputed').'</td><td>';
			$out .= '<span'.$tooltipAttributes.'>'.price((float) $result['distance_km']).' '.$langs->trans('km').'</span>';
			$out .= '</td></tr>';
		}
		if ($out === '') {
			return 0;
		}
		$this->resprints = $out;

		return 0;
	}

	/**
	 * Complete native Dolibarr AJAX tooltips with lmdbzoning distance details.
	 *
	 * @param array<string,mixed> $parameters  Hook parameters
	 * @param object              $object      Dolibarr object
	 * @param string              $action      Current action
	 * @param HookManager         $hookmanager Hook manager
	 * @return int
	 */
	public function getTooltipContent($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user;

		if (empty($parameters['params']) || !isset($parameters['tooltipcontentarray']) || !is_array($parameters['params'])) {
			return 0;
		}
		if (empty($parameters['params']['fromajaxtooltip']) || empty($parameters['params']['option']) || $parameters['params']['option'] !== 'lmdbzoningdistance') {
			return 0;
		}
		if (!$this->isLmdbZoningEnabled() || !$this->canReadLmdbZoning($user)) {
			return 0;
		}
		if (empty($object->id)) {
			return 0;
		}

		dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');
		$service = new LmdbZoningService($this->db);
		$elementType = $this->getObjectElementType($object);
		$entity = $this->getObjectEntity($object);
		$result = $service->getObjectZone($elementType, (int) $object->id, null, $entity);
		$payload = is_array($result) ? $this->getDistanceComputedTooltipPayload($result) : array();
		$langs->load('lmdbzoning@lmdbzoning');

		if (empty($payload)) {
			$parameters['tooltipcontentarray'] = array($langs->trans('LmdbZoningNoDistanceDetail'));
			return 0;
		}

		$parameters['tooltipcontentarray'] = array($this->renderDistanceComputedTooltipHtml($payload));

		return 0;
	}

	/**
	 * Return AJAX tooltip attributes for computed distance details.
	 *
	 * @param object              $object      Dolibarr object
	 * @param string              $elementType lmdbzoning element type
	 * @param array<string,mixed> $result      Stored zoning result
	 * @return string
	 */
	private function getDistanceComputedAjaxTooltipAttributes($object, $elementType, array $result)
	{
		$payload = $this->getDistanceComputedTooltipPayload($result);
		if (empty($payload)) {
			return '';
		}
		$objectType = $this->getAjaxTooltipObjectType($object, $elementType);
		if ($objectType === '') {
			return '';
		}

		$params = array(
			'id' => $this->getObjectId($object),
			'objecttype' => $objectType,
			'option' => 'lmdbzoningdistance',
		);
		$json = json_encode($params);
		if (!is_string($json) || $json === '') {
			return '';
		}

		return ' class="classforajaxtooltip" title="tocomplete" data-params="'.dol_escape_htmltag($json).'"';
	}

	/**
	 * Extract computed distance tooltip payload from stored calculation details.
	 *
	 * @param array<string,mixed> $result Stored zoning result
	 * @return array<string,mixed>
	 */
	private function getDistanceComputedTooltipPayload(array $result)
	{
		$message = '';
		if (!empty($result['calculation_message'])) {
			$message = (string) $result['calculation_message'];
		} elseif (!empty($result['message'])) {
			$message = (string) $result['message'];
		}
		$prefix = 'LinkedPowerPlantDistances|';
		if ($message === '' || strpos($message, $prefix) !== 0) {
			return array();
		}

		$payload = json_decode(substr($message, strlen($prefix)), true);
		if (!is_array($payload)) {
			return array();
		}

		return $payload;
	}

	/**
	 * Render computed distance tooltip as HTML.
	 *
	 * @param array<string,mixed> $payload Tooltip payload
	 * @return string
	 */
	private function renderDistanceComputedTooltipHtml(array $payload)
	{
		global $langs;

		$html = '<div class="lmdbzoning-distance-tooltip">';
		$html .= '<strong>'.$langs->trans('LmdbZoningLinkedPowerPlantDistances').'</strong>';
		$html .= '<table class="nobordernopadding centpercent small">';
		if (!empty($payload['items']) && is_array($payload['items'])) {
			foreach ($payload['items'] as $item) {
				if (!is_array($item) || !isset($item['distance_km'])) {
					continue;
				}
				$label = !empty($item['label']) ? (string) $item['label'] : (!empty($item['ref']) ? (string) $item['ref'] : '#'.(!empty($item['id']) ? (int) $item['id'] : ''));
				$html .= '<tr><td>'.dol_escape_htmltag($label).'</td><td class="right nowrap">'.price((float) $item['distance_km']).' '.$langs->trans('km').'</td></tr>';
			}
		}
		if (isset($payload['total_km'])) {
			$html .= '<tr><td><strong>'.$langs->trans('Total').'</strong></td><td class="right nowrap"><strong>'.price((float) $payload['total_km']).' '.$langs->trans('km').'</strong></td></tr>';
		}
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render contract categories row with a JavaScript move into the contract card table.
	 *
	 * @param LmdbZoningService $service  Zoning service
	 * @param object            $object   Contract object
	 * @param int               $entity   Entity id
	 * @param bool              $canWrite Can write categories
	 * @param string            $action   Current action
	 * @return string
	 */
	private function renderContractCategoriesInlineRow($service, $object, $entity, $canWrite, $action)
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

		$formId = 'lmdbzoning-contract-categories-form-'.((int) $id);
		$stagingId = 'lmdbzoning-contract-categories-staging-'.((int) $id);
		$rowId = 'lmdbzoning-contract-categories-row-'.((int) $id);
		$selectId = 'lmdbzoning_contract_categories_'.((int) $id);

		$out = '';
		$isEditMode = ($action === 'edittags' && $canWrite);
		if ($isEditMode && !empty($options)) {
			$out .= '<form id="'.dol_escape_htmltag($formId).'" method="POST" action="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'" style="display:none">';
			$out .= '<input type="hidden" name="token" value="'.dol_escape_htmltag($this->getNewToken()).'">';
			$out .= '<input type="hidden" name="action" value="settags">';
			$out .= '<input type="hidden" name="id" value="'.((int) $id).'">';
			$out .= '</form>';
		}
		$out .= '<div id="'.dol_escape_htmltag($stagingId).'" class="lmdbzoning-contract-categories-fallback" style="position:absolute;left:-10000px;top:-10000px;visibility:hidden">';
		$out .= '<table class="border tableforfield centpercent">';
		$out .= $this->renderContractCategoriesRow($rowId, $formId, $selectId, $options, $selectedCategories, $canWrite, $isEditMode, $object);
		$out .= '</table></div>';
		$out .= $this->renderContractCategoriesPlacementScript($stagingId, $rowId, $selectId);

		return $out;
	}

	/**
	 * Render contract categories table row.
	 *
	 * @param string            $rowId              Row HTML id
	 * @param string            $formId             Form HTML id
	 * @param string            $selectId           Select HTML id
	 * @param array<int,string> $options            Category options
	 * @param array<int,int>    $selectedCategories Selected category ids
	 * @param bool              $canWrite           Can write categories
	 * @param bool              $isEditMode         Edit mode
	 * @param object            $object             Contract object
	 * @return string
	 */
	private function renderContractCategoriesRow($rowId, $formId, $selectId, array $options, array $selectedCategories, $canWrite, $isEditMode, $object)
	{
		global $langs;

		$out = '<tr id="'.dol_escape_htmltag($rowId).'" class="lmdbzoning-contract-categories"><td>';
		$out .= '<table class="nobordernopadding centpercent"><tr><td>';
		$out .= $langs->trans('Categories');
		$out .= '</td><td class="right">';
		if ($canWrite && !$isEditMode) {
			$editUrl = $_SERVER['PHP_SELF'].'?id='.$this->getObjectId($object).'&action=edittags&token='.$this->getNewToken();
			$out .= '<a class="editfielda" href="'.dol_escape_htmltag($editUrl).'">'.img_edit().'</a>';
		} else {
			$out .= '&nbsp;';
		}
		$out .= '</td></tr></table>';
		$out .= '</td><td>';

		if ($isEditMode) {
			if (empty($options) && empty($selectedCategories)) {
				$out .= '<span class="opacitymedium">'.$langs->trans('NoContractCategoryAvailable').'</span>';
			} elseif (!empty($options)) {
				$out .= $this->renderMultiSelect('categories', $options, $selectedCategories, $selectId, $formId);
				$out .= ' <input type="submit" class="button valignmiddle smallpaddingimp" form="'.dol_escape_htmltag($formId).'" value="'.dol_escape_htmltag($langs->trans('Modify')).'">';
			}
		} else {
			$form = $this->getFormHelper();
			$out .= is_object($form) ? $form->showCategories($this->getObjectId($object), 'contract', 1) : '';
		}

		$out .= '</td></tr>';

		return $out;
	}

	/**
	 * Render JavaScript that moves the staged row after the contract date line.
	 *
	 * @param string $stagingId Staging block id
	 * @param string $rowId     Row id
	 * @param string $selectId  Select id
	 * @return string
	 */
	private function renderContractCategoriesPlacementScript($stagingId, $rowId, $selectId)
	{
		global $langs;

		$stagingIdJs = json_encode($stagingId);
		$rowIdJs = json_encode($rowId);
		$selectIdJs = json_encode($selectId);
		$dateLabelJs = json_encode($langs->trans('Date'));

		$script = "(function() {\n";
		$script .= "\tfunction placeContractCategories() {\n";
		$script .= "\t\tvar staging = document.getElementById(".$stagingIdJs.");\n";
		$script .= "\t\tvar row = document.getElementById(".$rowIdJs.");\n";
		$script .= "\t\tif (!staging || !row) { return; }\n";
		$script .= "\t\tvar dateInput = document.getElementById('date_contrat');\n";
		$script .= "\t\tvar dateRow = dateInput ? dateInput.closest('tr') : null;\n";
		$script .= "\t\tif (!dateRow) {\n";
		$script .= "\t\t\tvar tables = document.querySelectorAll('table.tableforfield');\n";
		$script .= "\t\t\tfor (var i = 0; i < tables.length && !dateRow; i++) {\n";
		$script .= "\t\t\t\tvar rows = tables[i].querySelectorAll('tr');\n";
		$script .= "\t\t\t\tfor (var j = 0; j < rows.length; j++) {\n";
		$script .= "\t\t\t\t\tif (rows[j].querySelector('[name=\"date_contrat\"], [id=\"date_contrat\"]')) { dateRow = rows[j]; break; }\n";
		$script .= "\t\t\t\t\tvar firstCell = rows[j].querySelector('td');\n";
		$script .= "\t\t\t\t\tvar firstCellText = firstCell ? firstCell.textContent.replace(/\\s+/g, ' ').trim() : '';\n";
		$script .= "\t\t\t\t\tif (firstCellText === ".$dateLabelJs.") { dateRow = rows[j]; break; }\n";
		$script .= "\t\t\t\t}\n";
		$script .= "\t\t\t}\n";
		$script .= "\t\t}\n";
		$script .= "\t\tif (dateRow && dateRow.parentNode) {\n";
		$script .= "\t\t\tdateRow.parentNode.insertBefore(row, dateRow.nextSibling);\n";
		$script .= "\t\t\tstaging.parentNode.removeChild(staging);\n";
		$script .= "\t\t\tvar select = document.getElementById(".$selectIdJs.");\n";
		$script .= "\t\t\tif (select && window.jQuery) { window.jQuery(select).trigger('change'); }\n";
		$script .= "\t\t} else {\n";
		$script .= "\t\t\tstaging.style.position = '';\n";
		$script .= "\t\t\tstaging.style.left = '';\n";
		$script .= "\t\t\tstaging.style.top = '';\n";
		$script .= "\t\t\tstaging.style.visibility = '';\n";
		$script .= "\t\t}\n";
		$script .= "\t}\n";
		$script .= "\tif (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', placeContractCategories); } else { placeContractCategories(); }\n";
		$script .= "})();";

		return '<script>'.$script.'</script>';
	}

	/**
	 * Render a Dolibarr multiselect2-compatible control.
	 *
	 * @param string            $htmlName HTML field name
	 * @param array<int,string> $options  Options
	 * @param array<int,int>    $selected Selected values
	 * @param string            $htmlId   HTML id
	 * @param string            $formId   Form HTML id
	 * @return string
	 */
	private function renderMultiSelect($htmlName, array $options, array $selected, $htmlId = '', $formId = '')
	{
		$selected = array_map('intval', $selected);
		if ($htmlId === '') {
			$htmlId = preg_replace('/[^a-zA-Z0-9_]/', '_', $htmlName);
		}

		$out = '<select id="'.dol_escape_htmltag($htmlId).'" name="'.dol_escape_htmltag($htmlName).'[]" class="flat minwidth300" multiple="multiple"';
		if ($formId !== '') {
			$out .= ' form="'.dol_escape_htmltag($formId).'"';
		}
		$out .= '>';
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
	 * Return canonical lmdbzoning element type for an object.
	 *
	 * @param object $object Object
	 * @return string
	 */
	private function getObjectElementType($object)
	{
		if (!is_object($object) || empty($object->element)) {
			return '';
		}
		if (class_exists('LmdbZoningService')) {
			return LmdbZoningService::normalizeZonableElementType((string) $object->element);
		}
		$aliases = array(
			'contrat' => 'contract',
			'order' => 'commande',
			'invoice' => 'facture',
			'powerplant' => 'powerplantpv',
		);

		return !empty($aliases[$object->element]) ? $aliases[$object->element] : (string) $object->element;
	}

	/**
	 * Return objecttype expected by Dolibarr native AJAX tooltip endpoint.
	 *
	 * @param object $object      Object
	 * @param string $elementType Canonical lmdbzoning element type
	 * @return string
	 */
	private function getAjaxTooltipObjectType($object, $elementType)
	{
		if (!is_object($object) || empty($object->element)) {
			return '';
		}
		if ($elementType === 'powerplantpv') {
			$module = !empty($object->module) ? (string) $object->module : 'powerplantpv';
			return (string) $object->element.'@'.$module;
		}

		return (string) $object->element;
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
	 * Return a Dolibarr Form helper.
	 *
	 * @return Form|null
	 */
	private function getFormHelper()
	{
		global $form;

		if (is_object($form)) {
			return $form;
		}
		if (!class_exists('Form') && defined('DOL_DOCUMENT_ROOT')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		}
		if (class_exists('Form')) {
			return new Form($this->db);
		}

		return null;
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
