<?php
/*
CMSMS News module defaultadmin action items-tab populator
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\UserParams;
use News\Utils;
use function CMSMS\specialize_array;

if( isset($params['filteraction']) ) {
    switch ($params['filteraction']) {
        case 'apply':
            if( isset( $params['filter_category']) ) {
                UserParams::set_for_user($userid, 'article_category', trim($params['filter_category'])); //'' for all-categories
            }
            $withchildren = !empty($params['filter_descendants']);
            UserParams::set_for_user($userid, 'childcategories', $withchildren);
            break;
/*      case 'reset':
            UserParams::set_for_user($userid, article_category', '');
            UserParams::set_for_user($userid, 'childcategories', 0);
            break;
*/
    }
}

// TODO show icon/image for each

$curcategory = UserParams::get_for_user($userid, 'article_category'); //default '' >> all
$withchildren = UserParams::get_for_user($userid, 'childcategories', 0);

if( $curcategory && $withchildren ) {
    $query1 = 'SELECT news_category_name FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id=?';
    $str = $db->getOne($query1, [$curcategory]);
    if( $str ) {
        $wm  = $db->escStr($str) . '%';
    }
    else {
        $withchildren = false;
    }
}

//Load the (filtered) items
$query1 = 'SELECT N.news_id,N.news_title,N.start_time,N.end_time,N.status,NC.long_name
FROM '.CMS_DB_PREFIX.'module_news N
LEFT OUTER JOIN '.CMS_DB_PREFIX.'module_news_categories NC
ON N.news_category_id = NC.news_category_id';
$parms = [];
if( $curcategory ) {
    if( $withchildren ) {
        //TODO support case sensitive mmatching
        $query1 .=  ' WHERE NC.long_name LIKE ?';
        $parms[] = $wm;
    }
    else {
        $query1 .= ' WHERE NC.news_category_id=?';
        $parms[] = $curcategory;
    }
}
$query1 .= ' ORDER by N.news_title';

$rst = $db->execute($query1, $parms);

if( $rst ) {
    if( $papp ) {
        $iconcancel = $themeObj->DisplayImage('icons/system/true', $this->Lang('revert'), null, '', 'systemicon');
        $iconapprove = $themeObj->DisplayImage('icons/system/false', $this->Lang('approve'), null, '', 'systemicon');
    }
    else {
        $stati = [
        'draft' => $this->Lang('draft'),
        'final' => $this->Lang('final'),
        'published' => $this->Lang('published'),
        'archived' => $this->Lang('archived'),
        ];
    }
    if( $pmod ) {
        $titl = $this->Lang('editthis');
        $iconedit = $themeObj->DisplayImage('icons/system/edit', $this->Lang('edit'), '', '', 'systemicon');
        $iconcopy = $themeObj->DisplayImage('icons/system/copy', $this->Lang('copy'), '', '', 'systemicon');
    }
    if( $pdel ) {
        $icondel = $themeObj->DisplayImage('icons/system/delete', $this->Lang('delete'), '', '', 'systemicon');
    }

    $now = time();
    $entryarray = [];

    while( ($row = $rst->FetchRow()) ) {
        $onerow = new stdClass();

        $onerow->id = $row['news_id'];
        if( $pmod ) {
            $onerow->title = $this->CreateLink($id, 'editarticle', $returnid,
            $row['news_title'], ['articleid'=>$row['news_id']], '',
            false, false, 'title="'.$titl.'"');
        }
        else {
            $onerow->title = $row['news_title'];
        }

        $onerow->startdate = $this->FormatforDisplay($row['start_time']);
        $onerow->enddate = $this->FormatforDisplay($row['end_time']);
        $onerow->category = $row['long_name'];
        $onerow->expired = $row['end_time'] && strtotime($row['end_time']) < $now; // don't care about timezones
        if( $papp ) {
            if( $row['status'] == 'published' ) {
                $onerow->approve_link = $this->CreateLink(
                    $id, 'approvearticle', $returnid, $iconcancel, ['approve'=>0, 'articleid'=>$row['news_id']]);
            }
            else {
                $onerow->approve_link = $this->CreateLink(
                    $id, 'approvearticle', $returnid, $iconapprove, ['approve'=>1, 'articleid'=>$row['news_id']]);
            }
        }
        else {
            $onerow->approve_link = $stati[$row['status']];
        }

        if( $pmod ) {
            $onerow->edit_url = $this->create_action_url($id, 'editarticle', ['articleid'=>$row['news_id']]);
            $onerow->editlink = $this->CreateLink($id, 'editarticle', $returnid, $iconedit, ['articleid'=>$row['news_id']]);
            $onerow->copylink = $this->CreateLink($id, 'copyarticle', $returnid, $iconcopy, ['articleid'=>$row['news_id']]);
        }
        if( $pdel ) {
            $onerow->deletelink = $this->CreateLink($id, 'deletearticle', $returnid, $icondel, ['articleid'=>$row['news_id']], '', false, false, 'class="delete_article"');
        }

        $entryarray[] = $onerow;
    }
    $rst->Close();

    $numrows = count($entryarray);
    $tpl->assign('items', $entryarray)
     ->assign('itemcount', $numrows);

    $pagerows = (int)$this->GetPreference('article_pagelimit', 10); //OR user-specific?

    if ($numrows > $pagerows) {
        //setup for SSsort paging
        $navpages = ceil($numrows/$pagerows);
        $tpl->assign('totpg', $navpages);

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
        $tpl->assign('rowchanger',
            $this->CreateInputDropdown($id, 'pagerows', $choices, -1, $pagerows));
    }
    else {
        $navpages = 0;
    }

    if( $pdel ) {
        $tpl->assign('submit_massdelete', 1);
    }

    $query = 'SELECT news_category_id, long_name FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
    $dbr = $db->getAssoc($query);
    specialize_array($dbr);
    $categorylist = [''=>$this->Lang('all')] + $dbr;
    $bulkcategories = Utils::get_category_list(); //different order
    specialize_array($bulkcategories);

    $tpl->assign([
     'bulkcategories' => array_flip($bulkcategories),
     'categorylist' => $categorylist,
     'categorytext' => $this->Lang('category'),
     'curcategory' => $curcategory,
     'enddatetext' => $this->Lang('enddate'),
     'filter_descendants' => $withchildren,
     'filterimage' => cms_join_path(__DIR__, 'images', 'filter'), //TODO use theme->DisplayImage( new admin icon )
     'filtertext' => $this->Lang('filter'),
     'formstart_items' => $this->CreateFormStart($id, 'defaultadmin'),
     'formstart_itemsfilter' => $this->CreateFormStart($id, 'defaultadmin', $returnid, 'post', '', false, '', ['filteraction'=>'apply']),
     'label_filtercategory' => $this->Lang('prompt_category'),
     'label_filterinclude' => $this->Lang('showchildcategories'),
     'startdatetext' => $this->Lang('startdate'),
     'statustext' => $this->Lang('status'),
     'titletext' => $this->Lang('title'),
     'typetext' => $this->Lang('type'),
//     'prompt_pagelimit' => $this->Lang('prompt_pagelimit'),
//     'prompt_sorting' => $this->Lang('prompt_sorting'),
//     'reassigntext' => $this->Lang('reassign_category'),
//     'selecttext' => $this->Lang('select'),
    ]);

    $s1 = json_encode($this->Lang('confirm_delete'));
    $s2 = json_encode($this->Lang('confirm_bulk'));
    $submit = lang('submit');
    $cancel = lang('cancel');

    $js = <<<EOS
<script type="text/javascript">
//<![CDATA[
var itemstbl;
$(function() {
 $.fn.SSsort.addParser({
  id: 'icon',
  is: function(s,node) {
  var \$i = $(node).find('img');
  return \$i.length > 0;
  },
  format: function(s,node) {
  var \$i = $(node).find('img');
  return \$i[0].src;
  },
  watch: false,
  type: 'text'
 });
 $.fn.SSsort.addParser({
  id: 'publishat',
  is: function(s) {
  return true;
  },
  format: function(s,node) {
  var o = new Date(s),
   u = o ? o.valueOf() : 0;
  return u;
  },
  watch: false,
  type: 'numeric'
 });
 itemstbl = document.getElementById('articlelist');
 if($navpages > 1) {
  var xopts = $.extend({}, SSsopts, {
   paginate: true,
   pagesize: $pagerows,
   currentid: 'cpage',
   countid: 'tpage'
  });
  $(itemstbl).SSsort(xopts);
  $('#pagerows').on('change',function() {
   l = parseInt(this.value);
   if(l == 0) {
    //TODO hide move-links, 'rows per page', show 'rows'
   } else {
    //TODO show move-links, 'rows per page', hide 'rows'
   }
   $.fn.SSsort.setCurrent(itemstbl,'pagesize',l);
  });
 } else {
  $(itemstbl).SSsort(SSsopts);
 }

 $('#category_box').hide();
 var el = $('#bulk_action, #bulk_category');
 el.prop('disabled', true);
 var btn = $('#bulk_submit');
 cms_button_able(btn,false);
 var cb = $('#articlelist > tbody input:checkbox');
 cb.on('change', function() {
  var l = cb.filter(':checked').length;
  if(l > 0) {
   el.prop('disabled', false);
  } else {
   el.prop('disabled', true);
  }
  cms_button_able(btn,(l > 0));
 });
 $('#selectall').on('change', function() {
  cb.prop('checked',(this.checked || false)).eq(0).trigger('change');
 });
 $('#bulk_action').on('change', function() {
  var v = $(this).val();
  if(v === 'setcategory') {
   $('#category_box').show(50);
  } else {
   $('#category_box').hide(50);
  }
 });
 $('#bulk_submit').on('click', function(ev) {
  ev.preventDefault();
  var l = cb.filter(':checked').length;
  if(l > 0) {
   var form = $(this).closest('form');
   cms_confirm($s2).done(function() {
    form.submit();
   });
  }
  return false;
 });
 $('a.delete_article').on('click', function(ev) {
  ev.preventDefault();
  cms_confirm_linkclick(this,$s1);
  return false;
 });
 $('#toggle_filter').on('click', function() {
  cms_dialog($('#itemsfilter'), {
   modal: true,
   width: 'auto',
   buttons: {
    '$submit': function() {
     $(this).dialog('close');
     $('#itemsfilter').find('form').trigger('submit');
    },
    '$cancel': function() {
     $(this).dialog('close');
    }
   }
  });
 });
});
//]]>
</script>
EOS;
    add_page_foottext($js);
}
else { //no rows
     $tpl->assign('items', [])
      ->assign('itemcount', 0);
}

if( $pmod ) {
    $icon = $themeObj->DisplayImage('icons/system/newobject.gif', $this->Lang('addarticle'), '', '', 'systemicon');
    $tpl->assign('addlink', $this->CreateLink($id, 'addarticle', $returnid, $icon, [], '', false, false, '') .' '. $this->CreateLink($id, 'addarticle', $returnid, $this->Lang('addarticle'), [], '', false, false, 'class="pageoptions"'));
}
