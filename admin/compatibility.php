<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/lmdbzoning/lib/lmdbzoning.lib.php');
dol_include_once('/lmdbzoning/class/lmdbzoningcompatibility.class.php');

$langs->loadLangs(array('admin', 'lmdbzoning@lmdbzoning'));

if (!$user->admin && (!method_exists($user, 'hasRight') || !$user->hasRight('lmdbzoning', 'lmdbzoning', 'admin'))) {
	accessforbidden();
}

llxHeader('', $langs->trans('Compatibility'));
$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', array('restore_lastsearch_values' => 1)).'">'.img_picto($langs->trans('LmdbZoningBackToModuleList'), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans('LmdbZoningBackToModuleList').'</span></a>';
print load_fiche_titre($langs->trans('LmdbZoning'), $linkback, 'title_setup');
$head = lmdbzoningAdminPrepareHead();
print dol_get_fiche_head($head, 'compatibility', $langs->trans('LmdbZoning'), -1, 'lmdbzoning@lmdbzoning');

print '<table class="border centpercent">';
print '<tr><td class="titlefield">'.$langs->trans('DetectedPhpVersion').'</td><td>'.dol_escape_htmltag(PHP_VERSION).'</td></tr>';
print '<tr><td>'.$langs->trans('DetectedDolibarrVersion').'</td><td>'.dol_escape_htmltag(defined('DOL_VERSION') ? DOL_VERSION : '').'</td></tr>';
print '<tr><td>'.$langs->trans('MinimalPhpVersion').'</td><td>8.0</td></tr>';
print '<tr><td>'.$langs->trans('MinimalDolibarrVersion').'</td><td>20.0</td></tr>';
print '</table>';

print '<br>';
print load_fiche_titre($langs->trans('Features'), '', 'generic');
print '<table class="liste centpercent">';
print '<tr class="liste_titre"><th>'.$langs->trans('Code').'</th><th>'.$langs->trans('Label').'</th><th>'.$langs->trans('Status').'</th><th>'.$langs->trans('Reason').'</th></tr>';
foreach (LmdbZoningCompatibility::getFeatures() as $code => $feature) {
	print '<tr class="oddeven">';
	print '<td>'.dol_escape_htmltag($code).'</td>';
	print '<td>'.$langs->trans($feature['label']).'</td>';
	print '<td>'.(!empty($feature['available']) ? img_picto($langs->trans('Available'), 'statut4').' '.$langs->trans('Available') : img_picto($langs->trans('Unavailable'), 'statut8').' '.$langs->trans('Unavailable')).'</td>';
	print '<td>'.(empty($feature['available']) ? $langs->trans($feature['reason']) : '').'</td>';
	print '</tr>';
}
print '</table>';

print dol_get_fiche_end();
llxFooter();
$db->close();
