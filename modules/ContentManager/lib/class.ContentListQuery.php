<?php
/*
Class for building content lists
Copyright (C) 2013-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace ContentManager;

use CMSMS\DbQueryBase;
use CMSMS\Lone;
use CMSMS\SQLException;
use RuntimeException;
use const CMS_DB_PREFIX;

/**
 * A class for building content lists
 * @package CMS
 * @internal
 * @ignore
 * @final
 */
final class ContentListQuery extends DbQueryBase
{
	protected $_filter;

	/**
	 *
	 * @param ContentListFilter $filter
	 */
	public function __construct(ContentListFilter $filter)
	{
		$this->_filter = $filter;
		$this->_limit = 1000;
		$this->_offset = 0;
	}

	/**
	 * @param int $limit
	 */
	public function set_limit(int $limit)
	{
		$this->_limit = max(1, $offset);
	}

	/**
	 *
	 * @param int $offset
	 */
	public function set_offset(int $offset)
	{
		$this->_offset = max(0, $offset);
	}

	/**
	 *
	 * @throws SQLException
	 */
	public function execute()
	{
		if ($this->_rs) {
			return;
		}

		$sql = 'SELECT C.content_id FROM '.CMS_DB_PREFIX.'content C';
		$wheres = []; $parms = [];
		switch ($this->_filter->type) {
		case ContentListFilter::EXPR_OWNER:
			$wheres[] = 'C.owner_id = ?';
			$parms[] = (int) $this->_filter->expr;
			break;
		case ContentListFilter::EXPR_EDITOR:
			$sql .= ' INNER JOIN '.CMS_DB_PREFIX.'additional_users A ON C.content_id = A.content_id';
			$wheres[] = 'A.user_id = ?';
			$parms[] = (int) $this->_filter->expr;
			break;
		case ContentListFilter::EXPR_TEMPLATE:
			$wheres[] = 'C.template_id = ?';
			$parms[] = (int) $this->_filter->expr;
			break;
/*
		case ContentListFilter::EXPR_DESIGN:
			$sql .= ' INNER JOIN '.CMS_DB_PREFIX.'content_props P ON C.content_id = P.content_id';
			$wheres[] = 'P.prop_name = ?';
			$wheres[] = 'P.content = ?';
			$parms[] = 'design_id';
			$parms[] = (int) $this->_filter->expr;
			break;
*/
		// TODO stylesheet | group
//		case ContentListFilter::EXPR_STYLE:
		}

		if ($wheres) {
			$sql .= ' WHERE '.implode(' AND ', $wheres);
		}
		$sql .= ' ORDER BY C.id_hierarchy';

		$db = Lone::get('Db');
		$this->_rs = $db->SelectLimit($sql, $this->_limit, $this->_offset, $parms);
		if (!$this->_rs || $this->_rs->errno !== 0) {
			throw new SQLException($db->sql.' -- '.$db->errorMsg());
		}
		$this->_totalmatchingrows = $db->getOne(str_replace(['SELECT C.content_id'],['SELECT COUNT(C.content_id) AS num'],$sql));
	}

	/**
	 *
	 * @return int
	 * @throws RuntimeException
	 */
	public function GetObject()
	{
		$this->execute();
		if (!$this->_rs) {
			throw new RuntimeException('Cannot get stylesheet from invalid stylesheet query object');
		}
		$out = (int) $this->_rs->fields['content_id'];
		return $out;
	}
} // class
