<?php
/*
Defaultadmin action templates tab populator.
Copyright (C) 2019-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
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

$query = 'SELECT T.id,T.`name`,T.description,T.type_dflt,TY.`name` AS type
FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' T JOIN '.
CMS_DB_PREFIX.TemplateType::TABLENAME. ' TY ON T.type_id=TY.id
WHERE T.originator=\''.$this->GetName().'\' ORDER BY TY.`name`,T.`name`';

$dbr = $db->getArray($query);
if( $dbr ) {
	$editurl = $this->create_action_url($id, 'edittemplate', ['tpl'=>'XXX']);
	$t = $this->Lang('tip_edit_template');
	$icon = $themeObj->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
	$linkedit = '<a href="'.$editurl.'">'.$icon.'</a>'.PHP_EOL;

	$url = $this->create_action_url($id, 'copytemplate', ['tpl'=>'XXX']);
	$t = $this->Lang('tip_copy_template');
	$icon = $themeObj->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
	$linkcopy = '<a href="'.$url.'">'.$icon.'</a>'.PHP_EOL;

	$url = $this->create_action_url($id, 'deletetemplate', ['tpl'=>'XXX']);
	$t = $this->Lang('tip_delete_template');
	$icon = $themeObj->DisplayImage('icons/system/delete', $t, '', '', 'systemicon delete_tpl');
	$linkdel = '<a href="'.$url.'" class="delete_tpl">'.$icon.'</a>'.PHP_EOL;

	$url = $this->create_action_url($id, 'defaulttemplate', ['tpl'=>'XXX']);
	$icon = $themeObj->DisplayImage('icons/system/false.gif',$this->Lang('tip_typedefault'),'','','systemicon default_tpl');
	$linkdefault = '<a href="'.$url.'" class="default_tpl">'.$icon.'</a>'.PHP_EOL;

	$icontrue = $themeObj->DisplayImage('icons/system/true.gif',lang('yes'),'','','systemicon'); // note: might be fonticon i.e. not necessarily an actual image
	$iconfalse = $themeObj->DisplayImage('icons/system/false.gif',lang('no'),'','','systemicon');

	$templates = [];
	foreach( $dbr as $row ) {
		$tid = $row['id'];
		$obj = new stdClass();
		$obj->id = $tid;
		$obj->name = $row['name'];
		$obj->desc = $row['description'] ? strip_tags($row['description']) : '';
		$obj->type = $row['type'];
		if( strcasecmp($obj->name,'moduleactions') != 0 ) {
			if( $row['type_dflt'] ) {
				$obj->dflt = $icontrue;
				$obj->dflt_mode = 1;
			}
			else {
				$obj->dflt = (($pset) ? str_replace('XXX', $tid, $linkdefault) : $iconfalse);
				$obj->dflt_mode = 2;
			}
		}
		else {
			$obj->dflt = lang('n_a');
			$obj->dflt_mode = 3;
		}
		$obj->url  = ($pmod) ? str_replace('XXX', $tid, $editurl) : null;
		$obj->edit = ($pmod) ? str_replace('XXX', $tid, $linkedit) : null;
		$obj->copy = ($pmod) ? str_replace('XXX', $tid, $linkcopy) : null;
		$obj->del  = ($pmod && $pdel && !$row['type_dflt']) ? str_replace('XXX', $tid, $linkdel) : null;
		$templates[] = $obj;
	}

	$numrows = count($templates);
	$pagerows = (int)$this->GetPreference('article_pagelimit', 10); //OR user-specific?

	if( $numrows > $pagerows ) {
		//setup for SSsort paging
		$tplpaged = 'true';
		$tplpages = (int)ceil($numrows/$pagerows);
		if( $tplpages > 2 ) {
			$elid1 = '"pspage2"';
			$elid2 = '"ntpage2"';
		}
		else {
			$elid1 = 'null';
			$elid2 = 'null';
		}
		$choices = [strval($pagerows) => $pagerows];
		$f = ($pagerows < 4) ? 5 : 2;
		$n = $pagerows * $f;
		if( $n < $numrows ) {
			$choices[strval($n)] = $n;
		}
		$n += $n;
		if( $n < $numrows ) {
			$choices[strval($n)] = $n;
		}
		$choices[$this->Lang('all')] = 0;
		$tpl->assign('rowchanger2',
			$this->CreateInputDropdown($id, 'pagerows2', $choices, -1, $pagerows));
	}
	else {
		$tplpaged = 'false';
		$tplpages = 1;
		$elid1 = 'null';
		$elid2 = 'null';
	}

	$tpl->assign('tpllist2', $templates)
	 ->assign('tplcount2', $numrows)
	 ->assign('tplpages2', $tplpages);

	$s1 = addcslashes($this->Lang('confirm_delete'), "'\n\r");
	$s2 = addcslashes($this->Lang('confirm_tpldefault'), "'\n\r");

	$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
var tpltable;
$(function() {
  tpltable = document.getElementById('tpltable');
  if($tplpaged) {
   var xopts = $.extend({}, SSsopts, {
    paginate: true,
    pagesize: $pagerows,
    firstid: 'ftpage2',
    previd: $elid1,
    nextid: $elid2,
    lastid: 'ltpage2',
    selid: 'pagerows2',
    currentid: 'cpage2',
    countid: 'tpage2'//,
//  onPaged: function(table,pageid){}
   });
   $(tpltable).SSsort(xopts);
   $('#pagerows2').on('change',function() {
    l = parseInt(this.value);
    if(l === 0) {
     $('#tpglink').hide();//TODO hide/toggle label-part 'per page'
    } else {
     $('#tpglink').show();//TODO show/toggle label-part 'per page'
    }
   });
  } else {
    $(tpltable).SSsort(SSsopts);
  }
  $('a.delete_tpl').on('click',function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,'$s1');
    return false;
  });
  $('a.default_tpl').on('click',function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,'$s2');
    return false;
  });
});
//]]>
</script>
EOS;
	add_page_foottext($js);
}
else {
	$tpl->assign('tplcount', 0);
}
