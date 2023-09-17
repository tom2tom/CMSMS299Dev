<?php
/*
Method: retrieve template data from the database
Copyright (C) 2018-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//see also: class CMSMS\TemplateQuery which (for now at least) this replicates
use CMSMS\Lone;
use CMSMS\SQLException;
use CMSMS\TemplateOperations;
use CMSMS\TemplatesGroup;
use CMSMS\TemplateType;

$tbl1 = CMS_DB_PREFIX.TemplateOperations::TABLENAME;
$tbl2 = CMS_DB_PREFIX.TemplateType::TABLENAME;
$typejoin = false;
$catjoin = false;

$where = [
'category' => [],
'design' => [],
'id' => [],
'type' => [],
'user' => [],
];

$limit = 1000;
$offset = 0;
$sortby = 'name';
$sortorder = 'ASC';

$db = Lone::get('Db');

/*
 Acceptable filter-array keys (optional content in []):
  c[ategory]:## - A template group id
  d[esign]:##   - A design id NOPE
  e[ditable]:## - An additional editor id
  g[roup]:##    - A template group id
  i[dlist]:##,##,## - A sequence of template id's
  l[istable]:#  - A boolean (1 or 0) indicating listable or not
  o[riginator]:string - The originator name (module-name or '[Cc]ore')
  t[ype]:##     - A template type id
  u[ser]:##     - A template owner id
  offset        - Offset (>= 0) of first record to return Default 0
  limit         - Maximum no. (1...1000) of records to return Default 1000
  sortby        - Field name 'id' 'name' 'created' 'modified' 'type' Default 'id'
  sortorder     - ASC or DESC (any case)  Default ASC
  any number    - shortform like K:value, where K is one of the designators c..u above

Example: ['u:'=>get_userid(false),'limit'=>50]

throws UnexpectedValueException if anything else is present
	or SQLException if no matching data found
*/

foreach ($filter as $key => $val) {
	if (is_numeric($key) && isset($val[1]) && $val[1] == ':') {
		[$key, $second] = explode(':', $val, 2);
	} else {
		$second = $val;
	}

	switch (strtolower($key)) {
	  case 'o':
	  case 'originator':
		$second = trim($second);
		if (strcasecmp($second,'core') == 0) {
			$second = '__CORE__';
		}
		$q2 = 'SELECT id FROM '.$tbl2 .' WHERE originator = ?';
		$typelist = $db->getCol($q2, [$second]);
		if (!$typelist) {
			$typelist = [-999];
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
		$where['type'][] = 'type_id = '.$db->qStr($second);
		$typejoin = true;
		break;

	  case 'g':
	  case 'group':
	  case 'c':
	  case 'category':
		$second = (int)$second;
		$where['group'][] = 'group_id = '.$db->qStr($second);
		$catjoin = true;
		break;
/*
	  case 'd':
	  case 'design':
		// find all the templates in design: d
		$q2 = 'SELECT tpl_id FROM '.CMS_DB_PREFIX.DesignManager\Design::TPLTABLE.' WHERE design_id = ?'; DISABLED
		$tpls = $db->getCol($q2, [(int)$second]);
		if (!$tpls) {
			$tpls = [-999];
		} // this won't match anything
		$where['design'][] = 'id IN ('.implode(',',$tpls).')';
		break;
*/
	  case 'u':
	  case 'user':
		$second = (int)$second;
		$where['user'][] = 'owner_id = '.$db->qStr($second);
		break;

	  case 'e':
	  case 'editable':
		$second = (int)$second;
		$q2 = 'SELECT DISTINCT tpl_id FROM (
SELECT tpl_id FROM '.CMS_DB_PREFIX.TemplateOperations::ADDUSERSTABLE.' WHERE user_id = ?
UNION
SELECT id AS tpl_id FROM '.$tbl1.' WHERE owner_id = ?)
		 AS tmp1';
		$t2 = $db->getCol($q2, [$second,$second]);
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
		$limit = max(1, min(1000, $val));
		break;

	  case 'offset':
		$offset = max(0, $val);
		break;

	  case 'sortby':
		$val = strtolower($val);
		switch ($val) {
		  case 'id':
		  case 'name':
		  case 'created':
		  case 'modified':
			$sortby = $val;
			break;
		  case 'type':
			$sortby = 'CONCAT(TT.originator,TT.`name`)';  //no prefix for this one
			$typejoin = true;
			break;
		  default:
			throw new UnexpectedValueException($val.' is not a valid \'sortby\' property value in a templates filter');
		}
		break;

	  case 'sortorder':
		$val = strtoupper($val);
		switch ($val) {
		  case 'ASC':
		  case 'DESC':
			$sortorder = $val;
			break;
		  default:
			throw new UnexpectedValueException($val.' is not a valid \'sortorder\' property value in a templates filter');
		}
		break;
	}
}

$xprefixes = function(array $where): void {
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
	if (strncmp($sortby,'CONCAT', 6) != 0) $sortby = 'TPL.'.$sortby;
} elseif ($catjoin) {
	$tbl3 = CMS_DB_PREFIX.TemplatesGroup::TABLENAME;
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

$query .= ' ORDER BY '.$sortby.' '.$sortorder;

// execute the query
$rst = $db->SelectLimit($query, $limit, $offset);
if (!$rst) {
	throw new SQLException($db->sql.' -- '.$db->errorMsg());
}

$totalrows = $db->getOne('SELECT FOUND_ROWS()');
$numpages = ceil($totalrows / $limit);

$ids = [];
while (!$rst->EOF()) {
	$ids[] = $rst->fields['id'];
	$rst->MoveNext();
}

$templates = TemplateOperations::get_bulk_templates($ids);
