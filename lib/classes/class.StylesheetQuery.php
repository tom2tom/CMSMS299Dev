<?php
/*
Class to perform advanced queries on layout stylesheets
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
namespace CMSMS;

use CMSMS\DbQueryBase;
use CMSMS\SingleItem;
use CMSMS\SQLException;
use CMSMS\Stylesheet;
use CMSMS\StylesheetOperations;
use RuntimeException;
use const CMS_DB_PREFIX;

/**
 * @package CMS
 * @since 2.0
 * Class to perform advanced database queries on layout stylesheets
 * @see CMSMS\DbQueryBase
 * @property int $id The stylesheet id.  This will result in at most 1 result being returned.
 * @property string $name A stylesheet name to filter upon.  If a
 *  partial name is provided, it is assumed to be a prefix.
 * @property int $design A design id to filter upon.
 * @property string $sortby Possible values are
 *  id,item_order,design,name.  Default is to sort by name.
 * @property string $sortorder Possible values are ASC, DESC.  Default is ASC.
 */
class StylesheetQuery extends DbQueryBase
{
	/**
	 * Execute the query in this object.
	 *
	 * @return void
	 * @throws LogicException
	 * @throws SQLException
	 */
	public function execute()
	{
		if( !is_null($this->_rs) ) return; //already established

		$dflt_sort = TRUE;
		$have_design = FALSE;
		$have_styles = FALSE;
		foreach( $this->_args as $key => $val ) {
			switch( $key ) {
			case 'sortby':
				$dflt_sort = FALSE;
				break;
			case 's':
			case 'styles':
				$have_styles = TRUE;
				break;
			case 'd':
			case 'design':
				$have_design = TRUE;
				break;
			}
		}

		if( $dflt_sort && ($have_styles || $have_design)) $this->_args['sortby'] = 'item_order'; //CHECKME

		$query = 'SELECT S.id FROM '.CMS_DB_PREFIX.Stylesheet::TABLENAME.' S';
		$sortorder = 'ASC';
		$sortby = 'S.name';
		$this->_limit = 1000;
		$this->_offset = 0;
		$db = SingleItem::Db();
		$where = [];
		foreach( $this->_args as $key => $val ) {
			if( empty($val) ) continue;
			if( is_numeric($key) && $val[1] == ':' ) {
				list($key,$val) = explode(':',$val,2);
			} else {
 				$key = trim($key, ' :');
			}
			switch( strtolower($key) ) {
			case 'i':
			case 'id':
				$val = (int)$val;
				$where[] = 'S.id = '.$val;
				break;

			case 'n': // name (prefix)
			case 'name':
				$val = trim($val);
				if( strpos($val,'%') === FALSE ) $val .= '%';
				$where[] = 'S.name LIKE '.$db->qStr($val);
				break;

			case 'o':
			case 'originator':
				$val = trim($val);
				if ($val[0] !== '!') {
					$op = '=';
				} else {
					$op = '!=';
					$val = ltrim($val, ' !');
				}
				if (strcasecmp($val, 'core') == 0) {
					$val = '__CORE__';
				}
				$where[] = "S.originator $op ".$db->qStr($val);
				break;

			case 's': // stylesheet id's and/or stylesheet-group id's
			case 'styles':
				$grps = [];
				$all = array_filter(explode(',',$val));
				foreach( $all as $i => $id ) {
					if( is_numeric($id) && $id < 0 ) {
						$grps[] = -$id;
						unset($all[$i]);
					}
					elseif( !is_numeric($id) ) {
						unset($all[$i]);
					}
				}
				if( $grps ) {
					$q2 = 'SELECT css_id FROM '.CMS_DB_PREFIX.'layout_cssgroup_members WHERE group_id IN('.implode(',',$grps).') ORDER BY item_order';
					$extras = $db->getCol($q2);
					if( $extras ) {
						$all = array_unique(array_merge($extras,$all), SORT_NUMERIC);
						$sortby = $sortorder = '';
					}
				}
				if( $all ) {
					$where[] = 'S.id IN('.implode(',',$all).')';
				}
				break;
/*
			case 'd': // design
			case 'design':
				// if we are using a design id argument
				// we do join, and sort by item order in the design
				$query .= ' LEFT JOIN '.CMS_DB_PREFIX.DesignManager\Design::CSSTABLE.' D ON S.id = D.css_id'; DISABLED
				$val = (int)$val;
				$where[] = "D.design_id = $val";
				break;
*/
			case 'limit':
				$this->_limit = max(1,min(1000,$val));
				break;

			case 'offset':
				$this->_offset = max(0,$val);
				break;

			case 'sortby':
				$val = strtolower($val);
				switch( $val ) {
				case 'item_order':
					$sortby = $sortorder = '';
					break;
				case 'id':
					$sortby = 'S.id';
					break;
				case 'design':
					if( !$have_design ) {
						throw new LogicException('Cannot sort by design if design_id is not known');
					}
					$sortby = 'D.name';
					break;
				case 'name':
				default:
					$sortby = 'S.name';
					break;
				}
				break;

			case 'sortorder':
				$val = strtoupper($val);
				switch( $val ) {
				case 'DESC':
					$sortorder = 'DESC';
					break;

				case 'ASC':
				default:
					$sortorder = 'ASC';
					break;
				}
				break;
			}
		}

		if( $where ) $query .= ' WHERE '.implode(' AND ',$where);
		if( $sortby && empty($extras) ) {
			$query .= ' ORDER BY '.$sortby.' '.$sortorder;
		}

		$this->_rs = $db->SelectLimit($query,$this->_limit,$this->_offset);
		if( !$this->_rs || $this->_rs->errno !== 0 ) {
			throw new SQLException($db->sql.' -- '.$db->errorMsg());
		}
		$this->_totalmatchingrows = $db->getOne('SELECT FOUND_ROWS()');
	}

	/**
	 * Get a Stylesheet object for the current data in the fieldset.
	 *
	 * This method is not as efficient as the GetMatches() method when the resultset has multiple items.
	 *
	 * @return Stylesheet
	 * @throws RuntimeException
	 */
	public function GetObject()
	{
		$this->execute();
		if( !$this->_rs ) throw new RuntimeException('Cannot get stylesheet from invalid stylesheet query object');
		$id = $this->_rs->fields('id');
		return StylesheetOperations::get_stylesheet((int)$id);
	}

	/**
	 * Return all the matches for this query
	 *
	 * @return array Stylesheet object(s) | empty
	 * @throws RuntimeException
	 */
	public function GetMatches()
	{
		$this->execute();
		if( !$this->_rs ) throw new RuntimeException('Cannot get template from invalid template query object');

		$tmp = [];
		while( !$this->EOF() ) {
			$id = $this->_rs->fields('id');
			$tmp[] = (int)$id;
			$this->MoveNext();
		}

		$deep = !empty($this->_args['deep']);
		return StylesheetOperations::get_bulk_stylesheets($tmp,$deep);
	}
} // class
