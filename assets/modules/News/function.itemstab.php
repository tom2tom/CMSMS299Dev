<?php
/*
CMSMS News module defaultadmin action items-tab populator
Copyright (C) 2005-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
LEFT JOIN '.CMS_DB_PREFIX.'module_news_categories NC
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

// pre-defined data structure to support PHP optimisation
class NewsItemData
{
    public $approve_link;
    public $approve_mode;
    public $category;
    public $copylink;
    public $deletelink;
    public $edit_url;
    public $editlink;
    public $end;
    public $enddate;
    public $expired;
    public $id;
    public $movelink;
    public $start;
    public $startdate;
    public $title;
}

    if( $papp ) {
//      $iconcancel = $themeObj->DisplayImage('icons/system/true', $this->Lang('revert'), null, '', 'systemicon');
//      $iconapprove = $themeObj->DisplayImage('icons/system/false', $this->Lang('approve'), null, '', 'systemicon');
        $labelarchived = $this->Lang('archived');
        $labelpublished = $this->Lang('published');
        $titlepublished = $this->Lang('revert');
        $labelfinal = $this->Lang('final');
        $titlefinal = $this->Lang('approve');
    }
    else {
        $stati = [
        'draft' => $this->Lang('draft'),
        'pending' => $this->Lang('pending'), // CHECKME
        'final' => $this->Lang('final'),
        'published' => $this->Lang('published'),
        'archived' => $this->Lang('archived'),
        ];
    }
    if( $pmod || $pprop ) {
        $titl = $this->Lang('editthis');
        $iconedit = $themeObj->DisplayImage('icons/system/edit', $this->Lang('edit'), '', '', 'systemicon');
        $iconcopy = $themeObj->DisplayImage('icons/system/copy', $this->Lang('copy'), '', '', 'systemicon');
        //if count cats > 1 handled in template
        $iconmove = $themeObj->DisplayImage('icons/system/export', $this->Lang('move'), '', '', 'systemicon');
    }
    if( $pdel ) {
        $icondel = $themeObj->DisplayImage('icons/system/delete', $this->Lang('delete'), '', '', 'systemicon');
    }

    $now = time();
    $entryarray = [];

    while( ($row = $rst->FetchRow()) ) {
        $onerow = new NewsItemData(); //stdClass();

        $onerow->id = $row['news_id'];
        if( $pmod || $pprop ) {
            $onerow->title = $this->CreateLink($id, 'editarticle', $returnid,
            $row['news_title'], ['articleid'=>$row['news_id']], '',
            false, false, 'title="'.$titl.'"');
        }
        else {
            $onerow->title = $row['news_title'];
        }

        $onerow->start = (int)$row['start_time'];
        $onerow->startdate = $this->FormatforDisplay($row['start_time']);
        $onerow->end = (int)$row['end_time'];
        $onerow->enddate = $this->FormatforDisplay($row['end_time']);
        $onerow->category = $row['long_name'];
        $onerow->expired = $row['end_time'] && strtotime($row['end_time']) < $now; // don't care about timezones
        if( $papp ) {
            if( $onerow->expired ) {
                $onerow->approve_link = $labelarchived;
                $onerow->approve_mode = 3;
            }
            elseif( $row['status'] == 'published' ) {
                $onerow->approve_link = $this->CreateLink(
                    $id, 'approvearticle', $returnid, $labelpublished, ['approve'=>0, 'articleid'=>$row['news_id']], '', false, false, 'title="'.$titlepublished.'"');
                $onerow->approve_mode = 2;
            }
            else {
                //TODO properly handle draft | final | pending
                $onerow->approve_link = $this->CreateLink(
                    $id, 'approvearticle', $returnid, $labelfinal, ['approve'=>1, 'articleid'=>$row['news_id']], '', false, false, 'title="'.$titlefinal.'"');
                $onerow->approve_mode = 1;
            }
        }
        else {
            $onerow->approve_link = $stati[$row['status']];
            $onerow->approve_mode = 9;
        }

        if( $pmod || $pprop ) {
            $onerow->edit_url = $this->create_action_url($id, 'editarticle', ['articleid'=>$row['news_id']]);
            $onerow->editlink = $this->CreateLink($id, 'editarticle', $returnid, $iconedit, ['articleid'=>$row['news_id']]);
            $onerow->copylink = $this->CreateLink($id, 'copyarticle', $returnid, $iconcopy, ['articleid'=>$row['news_id']]);
            //TODO if count cats > 1
            $onerow->movelink = $this->CreateLink($id, 'movearticle', $returnid, $iconmove, ['articleid'=>$row['news_id']], '', false, false, 'class="move_article" data-id="'.$row['news_id'].'"');
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

    if( $numrows > $pagerows ) {
        //setup for SSsort paging
        $itemspaged = 'true';
        $navpages = ceil($numrows/$pagerows);
        $tpl->assign('totpg', $navpages);
        if( $navpages > 2 ) {
            $elid1 = '"pspage"';
            $elid2 = '"ntpage"';
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
        $tpl->assign('rowchanger',
            $this->CreateInputDropdown($id, 'pagerows', $choices, -1, $pagerows, '', ['htmlid'=>'pagerows']));
    }
    else {
        $itemspaged = 'false';
        $elid1 = 'null';
        $elid2 = 'null';
    }

    if( $pdel ) {
        $tpl->assign('submit_massdelete', 1);
        $massdelete = true;
    }

    $query = 'SELECT news_category_id, long_name FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
    $dbr = $db->getAssoc($query);
    specialize_array($dbr);
//    $categorylist = [''=>$this->Lang('all')] + $dbr;
    $categorylist = $dbr;
    $bulkcategories = Utils::get_category_list(); //different order
    specialize_array($bulkcategories);

    $bulkactions = [];
    $bulkactions['setpublished'] = $this->Lang('bulk_setpublished');
    $bulkactions['setdraft'] = $this->Lang('bulk_setdraft');
    if( count($categorylist ) > 1) { $bulkactions['setcategory'] = $this->Lang('bulk_setcategory'); }
    if( !empty($massdelete) ) { $bulkactions['delete'] = $this->Lang('bulk_delete'); }

    $tpl->assign([
     'bulkactions' => $bulkactions,
     'bulkcategories' => array_flip($bulkcategories),
     'categorylist' => $categorylist,
     'categorytext' => $this->Lang('category'),
     'curcategory' => $curcategory,
     'enddatetext' => $this->Lang('enddate'),
     'filter_descendants' => $withchildren,
     'filtertext' => $this->Lang('filter'),
     'formstart_items' => $this->CreateFormStart($id, 'defaultadmin'),
     'formstart_itemsfilter' => $this->CreateFormStart($id, 'defaultadmin', $returnid, 'post', '', false, '', ['filteraction'=>'apply']),
     'label_filtercategory' => $this->Lang('prompt_category'),
     'label_filterinclude' => $this->Lang('showchildcategories'),
     'startdatetext' => $this->Lang('startdate'),
     'statustext' => $this->Lang('status'),
     'titletext' => $this->Lang('title'),
     'typetext' => $this->Lang('type'),
     'formstart_catselector' => $this->CreateFormStart($id, 'movearticle'),
     'selectortext' => $this->Lang('reassign_category'),
//   'prompt_pagelimit' => $this->Lang('prompt_pagelimit'),
//   'prompt_sorting' => $this->Lang('prompt_sorting'),
//   'selecttext' => $this->Lang('select'),
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
   var \$el = $(node).find('img');
   return \$el.length > 0;
  },
  format: function(s,node) {
   var \$el = $(node).find('img');
   return \$el[0].src;
  },
  watch: false,
  type: 'text'
 });
 itemstbl = document.getElementById('articlelist');
 if($itemspaged) {
  var xopts = $.extend({}, SSsopts, {
   paginate: true,
   pagesize: $pagerows,
   firstid: 'ftpage',
   previd: $elid1,
   nextid: $elid2,
   lastid: 'ltpage',
   selid: 'pagerows',
   currentid: 'cpage',
   countid: 'tpage'//,
// onPaged: function(table,pageid){}
  });
  $(itemstbl).SSsort(xopts);
  $('#pagerows').on('change',function() {
   l = parseInt(this.value);
   if(l === 0) {
     $('#ipglink').hide();//TODO hide/toggle label-part 'per page'
   } else {
     $('#ipglink').show();//TODO show/toggle label-part 'per page'
   }
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
 $('a.move_article').on('click', function(ev) {
  ev.preventDefault();
  var id = $(this).attr('data-id');
  $('#catselector').find('#movedarticle').val(id);
  cms_dialog($('#catselector'), {
   modal: true,
   width: 'auto',
   buttons: {
    '$submit': function() {
     $(this).dialog('close');
     $('#catselector').find('form').trigger('submit');
    },
    '$cancel': function() {
     $(this).dialog('close');
    }
   }
  });
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

if( $pmod || $pprop ) {
    $icon = $themeObj->DisplayImage('icons/system/newobject.gif', $this->Lang('addarticle'), '', '', 'systemicon');
    $tpl->assign('addlink', $this->CreateLink($id, 'addarticle', $returnid, $icon, [], '', false, false, '')
     .' '
     .$this->CreateLink($id, 'addarticle', $returnid, $this->Lang('addarticle'), [], '', false, false, 'class="pageoptions"'));
}
