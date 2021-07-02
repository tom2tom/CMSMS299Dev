<?php
/*
Defaultadmin action templates tab populator.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\TemplateOperations;
use CMSMS\TemplateType;

$query = 'SELECT T.id,T.name,T.description,T.type_dflt,TY.name AS type
FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' T JOIN '.
CMS_DB_PREFIX.TemplateType::TABLENAME. ' TY ON T.type_id=TY.id
WHERE T.originator=\''.$this->GetName().'\' ORDER BY TY.name,T.name';

$dbr = $db->GetArray($query);
if( $dbr ) {
	$u = $this->create_url($id, 'edittemplate', $returnid, ['tpl'=>'XXX']);
	$editurl = str_replace('&amp;','&',$u);

	$t = $this->Lang('tip_edit_template');
	$icon = $themeObj->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
	$linkedit = '<a href="'.$editurl.'">'.$icon.'</a>'.PHP_EOL;

	$u = $this->create_url($id, 'copytemplate', $returnid, ['tpl'=>'XXX']);
	$t = $this->Lang('tip_copy_template');
	$icon = $themeObj->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
	$linkcopy = '<a href="'.str_replace('&amp;','&',$u).'">'.$icon.'</a>'.PHP_EOL;

	$u = $this->create_url($id, 'deletetemplate', $returnid, ['tpl'=>'XXX']);
	$t = $this->Lang('tip_delete_template');
	$icon = $themeObj->DisplayImage('icons/system/delete', $t, '', '', 'systemicon delete_tpl');
	$linkdel = '<a href="'.str_replace('&amp;','&',$u).'" class="delete_tpl">'.$icon.'</a>'.PHP_EOL;

	$u = $this->create_url($id, 'defaulttemplate', $returnid, ['tpl'=>'XXX']);
	$icon = $themeObj->DisplayImage('icons/system/false.gif',$this->Lang('tip_typedefault'),'','','systemicon default_tpl');
	$linkdefault = '<a href="'.str_replace('&amp;','&',$u).'" class="default_tpl">'.$icon.'</a>'.PHP_EOL;

	$icontrue = $themeObj->DisplayImage('icons/system/true.gif',lang('yes'),'','','systemicon');
	$iconfalse = $themeObj->DisplayImage('icons/system/false.gif',lang('no'),'','','systemicon');

	$templates = [];
	foreach ($dbr as $row) {
		$tid = $row['id'];
		$obj = new stdClass();
		$obj->id = $tid;
		$obj->name = $row['name'];
		$obj->desc = strip_tags($row['description']);
		$obj->type = $row['type'];
		if( strcasecmp($obj->type,'Moduleaction') != 0 ) {
            $obj->dflt = ($row['type_dflt']) ? $icontrue : (($pset) ? str_replace('XXX', $tid, $linkdefault) : $iconfalse);
		}
		else {
    		$obj->dflt = lang('n_a');
    	}
		$obj->url  = ($pmod) ? str_replace('XXX', $tid, $editurl) : null;
		$obj->edit = ($pmod) ? str_replace('XXX', $tid, $linkedit) : null;
		$obj->copy = ($pmod) ? str_replace('XXX', $tid, $linkcopy) : null;
		$obj->del  = ($pmod && $pdel && !$row['type_dflt']) ? str_replace('XXX', $tid, $linkdel) : null;
		$templates[] = $obj;
	}

	$numrows = count($templates);
	$pagerows = (int)$this->GetPreference('article_pagelimit',10); //OR user-specific?

	if ($numrows > $pagerows) {
		//setup for SSsort paging
		$tplpages = ceil($numrows/$pagerows);
		$tpl->assign('totpg2',$tplpages);

		$choices = [strval($pagerows) => $pagerows];
		$f = ($pagerows < 4) ? 5 : 2;
		$n = $pagerows * $f;
		if ($n < $numrows) {
			$choices[strval($n)] = $n;
		}
		$n += $n;
		if ($n < $numrows) {
			$choices[strval($n)] = $n;
		}
		$choices[$this->Lang('all')] = 0;
		$tpl->assign('rowchanger2',
			$this->CreateInputDropdown($id, 'pagerows2', $choices, -1, $pagerows));
	}
	else {
		$tplpages = 1;
	}

	$tpl->assign('tpllist',$templates)
		->assign('tplcount',$numrows)
		->assign('tplpages',$tplpages);

	$s1 = json_encode($this->Lang('confirm_delete'));
	$s2 = json_encode($this->Lang('confirm_tpldefault'));

	$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
var tpltable;
$(function() {
  tpltable = document.getElementById('tpltable');
  if($tplpages > 1) {
   xopts = $.extend({}, SSsopts, {
    paginate: true,
    pagesize: $pagerows,
    currentid: 'cpage2',
    countid: 'tpage2'
   });
   $(tpltable).SSsort(xopts);
   $('#pagerows2').on('change',function() {
    l = parseInt(this.value);
    if(l == 0) {
     //TODO hide move-links, 'rows per page', show 'rows'
    } else {
     //TODO show move-links, 'rows per page', hide 'rows'
    }
    $.fn.SSsort.setCurrent(tpltable,'pagesize',l);
   });
  } else {
    $(tpltable).SSsort(SSsopts);
  }
  $('a.delete_tpl').on('click',function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s1);
    return false;
  });
  $('a.default_tpl').on('click',function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s2);
    return false;
  });
});
//]]>
</script>
EOS;
	add_page_foottext($js);
}
else {
	$tpl->assign('tplcount',0);
}
