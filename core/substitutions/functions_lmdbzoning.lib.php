<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr> */

/**
 * Complete substitution array for lmdbzoning objects.
 *
 * @param array<string,string> $substitutionarray Substitution array
 * @param Translate           $langs             Langs
 * @param object              $object            Current object
 * @return void
 */
function lmdbzoning_completesubstitutionarray(&$substitutionarray, $langs, $object)
{
	if (empty($object) || empty($object->element)) {
		return;
	}
	$substitutionarray['__LMDBZONING_REF__'] = !empty($object->ref) ? $object->ref : '';
	$substitutionarray['__LMDBZONING_LABEL__'] = !empty($object->label) ? $object->label : '';
	$substitutionarray['__LMDBZONING_STATUS__'] = !empty($object->status) ? (string) $object->status : '';
	$substitutionarray['__LMDBZONING_URL__'] = method_exists($object, 'getNomUrl') ? $object->getNomUrl(0) : '';
}
