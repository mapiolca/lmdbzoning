<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
dol_include_once('/lmdbzoning/class/lmdbzoningcalculationlog.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$object = new LmdbZoningCalculationLog($db);
llxHeader('', $langs->trans('CalculationLog'));
lmdbzoning_print_object_list($object, $langs->trans('CalculationLog'), 'calculation_log_card.php', array(), array(
	'truncate_tooltip_fields' => array('context_data' => 50),
));
llxFooter();
$db->close();
