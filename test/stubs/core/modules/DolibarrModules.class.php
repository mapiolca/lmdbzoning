<?php

/**
 * Minimal Dolibarr module descriptor stub for isolated migration tests.
 */
#[\AllowDynamicProperties]
class DolibarrModules
{
	/** @var array<int,array<int,mixed>> */
	public $rights = array();

	/** @var array<int,array<string,mixed>> */
	public $menu = array();

	/** @var string */
	public $error = '';
}
