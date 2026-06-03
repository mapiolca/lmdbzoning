<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
dol_include_once('/lmdbzoning/class/referencepoint.class.php');
dol_include_once('/lmdbzoning/lib/lmdbzoning_page.lib.php');

$langs->loadLangs(array('lmdbzoning@lmdbzoning'));
lmdbzoning_check_access('read');

$object = new LmdbZoningReferencePoint($db);
llxHeader('', $langs->trans('ReferencePoints'));
lmdbzoning_print_object_list($object, $langs->trans('ReferencePoints'), 'referencepoint_card.php');
llxFooter();
$db->close();
