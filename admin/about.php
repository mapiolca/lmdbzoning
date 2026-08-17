<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * About page for the LmdbZoning module.
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/lmdbzoning/lib/lmdbzoning.lib.php');
dol_include_once('/lmdbzoning/core/modules/modLmdbZoning.class.php');

$langs->loadLangs(array('admin', 'lmdbzoning@lmdbzoning'));

if (!$user->admin && !$user->hasRight('lmdbzoning', 'lmdbzoning', 'admin')) {
	accessforbidden();
}
if (!isModEnabled('lmdbzoning')) {
	accessforbidden();
}

$moduleDescriptor = new modLmdbZoning($db);
$title = $langs->trans('LmdbZoningAbout');
$editorUrl = (string) $moduleDescriptor->editor_url;
if ($editorUrl !== '' && !preg_match('~^https?://~i', $editorUrl)) {
	$editorUrl = 'https://'.$editorUrl;
}

llxHeader('', $title);

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', array('restore_lastsearch_values' => 1)).'">'.img_picto($langs->trans('LmdbZoningBackToModuleList'), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans('LmdbZoningBackToModuleList').'</span></a>';
print load_fiche_titre($title, $linkback, 'info');
$head = lmdbzoningAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $title, -1, 'lmdbzoning@lmdbzoning');

print '<div class="underbanner opacitymedium">'.$langs->trans('LmdbZoningAboutPage').'</div>';
print '<br>';

print '<div class="fichecenter">';

print '<div class="fichehalfleft">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('LmdbZoningAboutGeneral').'</th></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutVersion').'</td><td>'.dol_escape_htmltag((string) $moduleDescriptor->version).'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutFamily').'</td><td>'.dol_escape_htmltag((string) $moduleDescriptor->family).'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutDescription').'</td><td>'.dol_escape_htmltag($langs->trans((string) $moduleDescriptor->description)).'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutPublisher').'</td><td>'.dol_escape_htmltag((string) $moduleDescriptor->editor_name).'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutCompatibility').'</td><td>'.$langs->trans('LmdbZoningAboutCompatibilityValue').'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutDependencies').'</td><td>'.$langs->trans('LmdbZoningAboutDependenciesValue').'</td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutLicense').'</td><td>'.$langs->trans('LmdbZoningAboutLicenseValue').'</td></tr>';
print '</table>';
print '</div>';
print '</div>';

print '<div class="fichehalfright">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans('LmdbZoningAboutResources').'</th></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutDocumentation').'</td><td><a href="'.dol_buildpath('/lmdbzoning/README.md', 1).'" target="_blank" rel="noopener">'.$langs->trans('LmdbZoningAboutDocumentationLink').'</a></td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutChangeLog').'</td><td><a href="'.dol_buildpath('/lmdbzoning/ChangeLog.md', 1).'" target="_blank" rel="noopener">'.$langs->trans('LmdbZoningAboutChangeLogLink').'</a></td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutLicenseDocument').'</td><td><a href="'.dol_buildpath('/lmdbzoning/LICENSE', 1).'" target="_blank" rel="noopener">'.$langs->trans('LmdbZoningAboutLicenseDocumentLink').'</a></td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutRepository').'</td><td><a href="https://github.com/mapiolca/lmdbzoning" target="_blank" rel="noopener">github.com/mapiolca/lmdbzoning</a></td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutSupport').'</td><td><a href="mailto:developpeur@lesmetiersdubatiment.fr">developpeur@lesmetiersdubatiment.fr</a></td></tr>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans('LmdbZoningAboutWebsite').'</td><td><a href="'.dol_escape_htmltag($editorUrl).'" target="_blank" rel="noopener">'.dol_escape_htmltag($editorUrl).'</a></td></tr>';
print '</table>';
print '</div>';
print '</div>';

print '</div>';
print '<div class="clearboth"></div>';
print '<br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th>'.$langs->trans('LmdbZoningAboutFeatures').'</th></tr>';
foreach (array(
	'LmdbZoningAboutFeatureZones',
	'LmdbZoningAboutFeatureAutomaticCategories',
	'LmdbZoningAboutFeatureMulticompany',
	'LmdbZoningAboutFeatureApi',
	'LmdbZoningAboutFeatureCron',
) as $featureKey) {
	print '<tr class="oddeven"><td>'.img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans($featureKey).'</td></tr>';
}
print '</table>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
