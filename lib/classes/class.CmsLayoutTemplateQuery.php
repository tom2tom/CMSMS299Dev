<?php
#Class to represent a template database query and its results.
#Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

//namespace CMSMS;

use CMSMS\TemplateOperations;

/**
 * A class to represent a template query and its results.
 * This class accepts in its constructor an array or comma-separated string of filter arguments.
 * Acceptable filter-array keys (optional content in []):
 *  c[ategory]:## - A template category id
 *  d[esign]:##   - A design id
 *  e[ditable]:## - An additional editor id
 *  i[dlist]:##,##,## - A sequence of template id's
 *  l[istable]:#  - A boolean (1 or 0) indicating listable or not
 *  o[riginator]:string - The originator name (module-name or 'core')
 *  t[ype]:##     - A template type id
 *  u[ser]:##     - A template owner id
 *  offset        - Offset (>= 0) of first record to return Default 0
 *  limit         - Maximum no. (1...1000) of records to return Default 1000
 *  sortby        - Field name 'id' 'name' 'created' 'modified' 'type' Default 'id'
 *  sortorder     - ASC or DESC (any case)  Default ASC
 *  any number    - shortform like K:value, where K is one of the designators c..u above
 *
 * Example:
 * $qry = new CmsTemplateQuery(['u:'=>get_userid(false),'limit'=>50]);
 * $list = $qry->GetMatches();
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @see CmsDbQueryBase
 * @property string $sortby The sorting field for the returned results.  Possible values are: id,name,created,modified,type.  The default is to sort by template name.';
 * @property string $sortorder The sorting order for the returned results.  Possible values are: ASC,DESC.  The default is ASC.
 */
class CmsLayoutTemplateQuery extends CmsDbQueryBase
{
	/**
	 * @ignore
	 */
	private $_sortby = 'name';

	/**
	 * @ignore
	 */
	private $_sortorder = 'ASC';

	/**
	 * Execute the query given the parameters saved in the query
	 *
	 * @throws CmsInvalidDataException if anything else is present
	 * @throws CmsSQLErrorException if no matching data found
	 * Though this method can be called directly, it is also called by other members automatically.
	 */
	public function execute()
	{
		if (!is_null($this->_rs)) {
			return;
		}

		$db = CmsApp::get_instance()->GetDb();
		$tbl1 = CMS_DB_PREFIX.TemplateOperations::TABLENAME;
		$tbl2 = CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME;
		$typejoin = false;
		$catjoin = false;

		$where = [
		'category' => [],
		'design' => [],
		'id' => [],
		'type' => [],
		'user' => [],
		];

		$this->_limit = 1000;
		$this->_offset = 0;

		foreach ($this->_args as $key => $val) {
			if (is_numeric($key) && $val[1] == ':') {
				list($key, $second) = explode(':', $val, 2);
			}

			switch (strtolower($key)) {
			  case 'o':
			  case 'originator':
				$second = trim($second);
				$q2 = 'SELECT id FROM '.$tbl2 .' WHERE originator = ?';
				$typelist = $db->GetCol($q2, [$second]);
				if (!count($typelist)) {
					$typelist = [-999]; // this won't match anything
				}
				$where['type'][] = 'type_id IN ('.implode(',', $typelist).')';
				break;

			  case 'i':
			  case 'idlist':
				$second = trim($second);
				$tmp = explode(',', $second);
				$tmp2 = [];
				for ($i = 0, $n = count($tmp); $i < $n; ++$i) {
					$tmp3 = (int)$tmp[$i];
					if ($tmp3 < 1) {
						continue;
					}
					if (in_array($tmp3, $tmp2)) {
						continue;
					}
					$tmp2[] = $tmp3;
				}
				$where['id'][] = 'id IN '.implode(',', $tmp2);
				break;

			  case 't':
			  case 'type':
				$second = (int)$second;
				$where['type'][] = 'type_id = '.$db->qstr($second);
				$typejoin = true;
				break;

			  case 'c':
			  case 'category':
				$second = (int)$second;
				$where['category'][] = 'category_id = '.$db->qstr($second);
				$catjoin = true;
				break;

			  case 'd':
			  case 'design':
				// find all the templates in design: d
				$q2 = 'SELECT tpl_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE.' WHERE design_id = ?';
				$tpls = $db->GetCol($q2, [(int)$second]);
				if (!count($tpls)) {
					$tpls = [-999]; // this won't match anything
				}
				$where['design'][] = 'id IN ('.implode(',', $tpls).')';
				break;

			  case 'u':
			  case 'user':
				$second = (int)$second;
				$where['user'][] = 'owner_id = '.$db->qstr($second);
				break;

			  case 'e':
			  case 'editable':
				$second = (int)$second;
				$q2 = 'SELECT DISTINCT tpl_id FROM (
SELECT tpl_id FROM '.CMS_DB_PREFIX.TemplateOperations::ADDUSERSTABLE.' WHERE user_id = ?
UNION
SELECT id AS tpl_id FROM '.$tbl1.' WHERE owner_id = ?)
AS tmp1';
				$t2 = $db->GetCol($q2, [$second,$second]);
				if ($t2) {
					$where['user'][] = 'id IN ('.implode(',', $t2).')';
				}
				break;

			  case 'l':
			  case 'listable':
				$second = (cms_to_bool($second)) ? 1 : 0;
				$where['listable'] = ['listable = '.$second];
				break;

			  case 'limit':
				$this->_limit = max(1, min(1000, $val));
				break;

			  case 'offset':
				$this->_offset = max(0, $val);
				break;

			  case 'sortby':
				$val = strtolower($val);
				switch ($val) {
				  case 'id':
				  case 'name':
				  case 'created':
				  case 'modified':
					$this->_sortby = $val;
					break;
				  case 'type':
					$this->_sortby = 'CONCAT(TT.originator,TT.name)';  //no prefix for this one
					$typejoin = true;
					break;
				  default:
					throw new CmsInvalidDataException($val.' is an invalid sortfield');
				}
				break;

			  case 'sortorder':
				$val = strtoupper($val);
				switch ($val) {
				  case 'ASC':
				  case 'DESC':
					$this->_sortorder = $val;
					break;
				  default:
					throw new CmsInvalidDataException($val.' is an invalid sortorder');
				}
				break;
			}
		}

		$xprefixes = function ($where) {
			foreach ($where['design'] as &$one) {
				$one = 'TPL.'.$one;
			}
			foreach ($where['user'] as &$one) {
				$one = 'TPL.'.$one;
			}
			unset($one);
		};

		if ($typejoin) {
			$query = "SELECT TPL.id FROM $tbl1 TPL LEFT JOIN $tbl2 TT ON TPL.type_id = TT.id";
			$xprefixes($where);
			if (strncmp($this->_sortby, 'CONCAT', 6) != 0) {
				$this->_sortby = 'TPL.'.$this->_sortby;
			}
		} elseif ($catjoin) {
			$tbl3 = CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME;
			$query = "SELECT TPL.id FROM $tbl1 TPL LEFT JOIN $tbl3 TC ON TPL.type_id = TC.id";
			$xprefixes($where);
			$sortby = 'TPL.'.$sortby;
		} else {
			$query = "SELECT id FROM $tbl1";
		}

		$tmp = [];
		foreach ($where as $key => $exprs) {
			if ($exprs) {
				$tmp[] = '('.implode(' OR ', $exprs).')';
			}
		}

		if ($tmp) {
			$query .= ' WHERE ' . implode(' AND ', $tmp);
		}

		$query .= ' ORDER BY '.$this->_sortby.' '.$this->_sortorder;

		// execute the query
		$this->_rs = $db->SelectLimit($query, $this->_limit, $this->_offset);
		if (!$this->_rs || $this->_rs->errno !== 0) {
			throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		}
		$this->_totalmatchingrows = $db->GetOne('SELECT FOUND_ROWS()'); //$this->_rs->RecordCount(); N/A until all processed
	}

	/**
	 * Get the template object for the current member of the resultset (if any)
	 *
	 * This method calls the execute method.
	 *
	 * @throws CmsLogicException
	 * @return CmsLayoutTemplate
	 */
	public function &GetTemplate()
	{
		return $this->GetObject();
	}

	/**
	 * Get the template object for the current member of the resultset (if any).
	 *
	 * This method is not as efficient as GetMatches() when the resultset has multiple items.
	 *
	 * This method calls the execute method.
	 *
	 * @throws CmsLogicException
	 * @return CmsLayoutTemplate
	 */
	public function &GetObject()
	{
		$this->execute();
		if (!$this->_rs) {
			throw new CmsLogicException('Cannot get template from invalid template query object');
		}
		return TemplateOperations::get_template($this->fields['id']);
	}
	/**
	 * Get the list of matched template ids
	 *
	 * This method calls the execute method.
	 *
	 * @throws CmsLogicException
	 * @return array Array of integers
	 */
	public function GetMatchedTemplateIds()
	{
		$this->execute();
		if (!$this->_rs) {
			throw new CmsLogicException('Cannot get template from invalid template query object');
		}

		$out = [];
		while (!$this->EOF()) {
			$out[] = $this->fields['id'];
			$this->MoveNext();
		}
		$this->_rs->MoveFirst();
		return $out;
	}

	/**
	 * Get all matches
	 *
	 * This method calls the execute method
	 *
	 * @return array of CmsLayoutTemplate objects
	 */
	public function GetMatches()
	{
		return TemplateOperations::get_bulk_templates($this->GetMatchedTemplateIds());
	}
} // class
