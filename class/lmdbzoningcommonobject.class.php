<?php
/* Copyright (C) 2026  Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Common helpers for LmdbZoning business objects.
 */
abstract class LmdbZoningCommonObject extends CommonObject
{
	/**
	 * Entity id.
	 *
	 * @var int
	 */
	public $entity = 1;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var array<int,string> Error list
	 */
	public $errors = array();

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
	 * Create object.
	 *
	 * @param User $user      User that creates
	 * @param int  $notrigger 1=disable triggers
	 * @return int
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;

		if (empty($this->entity)) {
			$this->entity = (int) $conf->entity;
		}

		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Fetch object.
	 *
	 * @param int         $id  Object id
	 * @param string|null $ref Object ref
	 * @return int
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref);
	}

	/**
	 * Fetch all objects.
	 *
	 * @param string               $sortorder Sort order
	 * @param string               $sortfield Sort field
	 * @param int                  $limit     Limit
	 * @param int                  $offset    Offset
	 * @param array<string,string> $filter    Filters
	 * @param string               $filtermode Filter mode
	 * @return array<int,static>|int
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		return $this->fetchAllCommon($sortorder, $sortfield, $limit, $offset, $filter, $filtermode);
	}

	/**
	 * Update object.
	 *
	 * @param User $user      User that updates
	 * @param int  $notrigger 1=disable triggers
	 * @return int
	 */
	public function update($user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object.
	 *
	 * @param User $user      User that deletes
	 * @param int  $notrigger 1=disable triggers
	 * @return int
	 */
	public function delete($user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 * Return object URL label.
	 *
	 * @param int    $withpicto Include picto
	 * @param string $option    Option
	 * @return string
	 */
	public function getNomUrl($withpicto = 0, $option = '')
	{
		global $langs;

		$label = !empty($this->ref) ? $this->ref : (string) $this->id;
		$url = dol_buildpath('/lmdbzoning/'.$this->getCardPage(), 1).'?id='.(int) $this->id;
		$linkclose = '';
		$link = '<a href="'.$url.'">';
		if ($withpicto) {
			$link .= img_object($langs->trans('Show'), $this->picto).' ';
		}
		$linkclose .= '</a>';

		return $link.dol_escape_htmltag($label).$linkclose;
	}

	/**
	 * Return default card page name.
	 *
	 * @return string
	 */
	protected function getCardPage()
	{
		return str_replace('lmdbzoning_', '', $this->element).'_card.php';
	}

	/**
	 * Return status label.
	 *
	 * @param int $mode Display mode
	 * @return string
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->active, $mode);
	}

	/**
	 * Return status label.
	 *
	 * @param int $status Status
	 * @param int $mode   Display mode
	 * @return string
	 */
	public function LibStatut($status, $mode = 0)
	{
		global $langs;

		if ((int) $status === 1) {
			return dolGetStatus($langs->trans('Enabled'), '', '', 'status4', $mode);
		}

		return dolGetStatus($langs->trans('Disabled'), '', '', 'status5', $mode);
	}

	/**
	 * Initialize object as specimen.
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		$this->entity = 1;
		$this->ref = 'SPECIMEN';
		$this->label = 'Specimen';
		$this->active = 1;
	}
}
