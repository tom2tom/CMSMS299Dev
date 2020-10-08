<?php
# List stylesheets and groups.
# Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\FormUtils;
use CMSMS\ScriptsMerger;
use CMSMS\StylesheetOperations;
use CMSMS\StylesheetQuery;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();

$urlext = get_secure_param();
$userid = get_userid();
$pmanage = check_permission($userid,'Manage Stylesheets');

cleanArray($_REQUEST);

if( $pmanage ) {
    if( isset($_REQUEST['submit_create']) ) {
        redirect('editstylesheet.php'.$urlext);
    }
}

$smarty = AppSingle::Smarty();

// individual stylesheets

try {
    $css_query = new StylesheetQuery(); //$filter);
    $sheetslist = $css_query->GetMatches();
    if( $sheetslist ) {
        $themeObject = Utils::get_theme_object();
        $u = 'editstylesheet.php'.$urlext.'&amp;css=XXX';
        $t = lang_by_realm('layout','title_edit_stylesheet');
        $icon = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
        $linkedit = '<a href="'.$u.'" class="edit_css" data-css-id="XXX">'.$icon.'</a>'.PHP_EOL;

//      $u = ibid
        $t = lang_by_realm('layout','title_steal_lock');
        $icon = $themeObject->DisplayImage('icons/system/permissions', $t, '', '', 'systemicon');
        $linksteal = '<a href="'.$u.'" class="steal_css_lock" data-css-id="XXX" accesskey="e">'.$icon.'</a>'.PHP_EOL;

        $u = 'stylesheetoperations.php'.$urlext.'&amp;op=copy&amp;css=XXX';
        $t = lang_by_realm('layout','title_copy_stylesheet');
        $icon = $themeObject->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
        $linkcopy = '<a href="'.$u.'" class="copy_css" data-css-id="XXX">'.$icon.'</a>'.PHP_EOL;

        $u = 'stylesheetoperations.php'.$urlext.'&amp;op=prepend&amp;css=XXX';
        $t = lang_by_realm('layout','title_prepend_stylesheet');
        $icon = $themeObject->DisplayImage('icons/extra/prepend', $t, '', '', 'systemicon');
        $linkprepend = '<a href="'.$u.'" class="prepend_css" data-css-id="XXX">'.$icon.'</a>'.PHP_EOL;

        $u = 'stylesheetoperations.php'.$urlext.'&amp;op=append&amp;css=XXX';
        $t = lang_by_realm('layout','title_append_stylesheet');
        $icon = $themeObject->DisplayImage('icons/extra/append', $t, '', '', 'systemicon');
        $linkappend = '<a href="'.$u.'" class="append_css" data-css-id="XXX">'.$icon.'</a>'.PHP_EOL;

        $u = 'stylesheetoperations.php'.$urlext.'&amp;op=replace&amp;css=XXX';
        $t = lang_by_realm('layout','title_replace_stylesheet');
        $icon = $themeObject->DisplayImage('icons/extra/replace', $t, '', '', 'systemicon');
        $linkreplace = '<a href="'.$u.'" class="replace_css" data-css-id="XXX">'.$icon.'</a>'.PHP_EOL;

        $u = 'stylesheetoperations.php'.$urlext.'&amp;op=remove&amp;css=XXX';
        $t = lang_by_realm('layout','title_remove_stylesheet');
        $icon = $themeObject->DisplayImage('icons/extra/removeall', $t, '', '', 'systemicon');
        $linkremove = '<a href="'.$u.'" class="remove_css" data-css-id="XXX">'.$icon.'</a>'.PHP_EOL;

        $u = 'stylesheetoperations.php'.$urlext.'&amp;op=delete&amp;css=XXX';
        $t = lang_by_realm('layout','title_delete_stylesheet');
        $icon = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon');
        $linkdel = '<a href="'.$u.'" class="del_css" data-css-id="XXX">'.$icon.'</a>'.PHP_EOL;

        $now = time();
        $menus = [];
        for( $i = 0, $n = count($sheetslist); $i < $n; ++$i ) {
            $acts = [];
            $sheet = $sheetslist[$i];
            $sid = $sheet->get_id();
            if( !$sheet->locked() ) {
                $acts[] = ['content'=>str_replace('XXX', $sid, $linkedit)];
                $acts[] = ['content'=>str_replace('XXX', $sid, $linkcopy)];
                $acts[] = ['content'=>str_replace('XXX', $sid, $linkprepend)];
                $acts[] = ['content'=>str_replace('XXX', $sid, $linkappend)];
                $acts[] = ['content'=>str_replace('XXX', $sid, $linkreplace)];
                $acts[] = ['content'=>str_replace('XXX', $sid, $linkremove)];
                $acts[] = ['content'=>str_replace('XXX', $sid, $linkdel)];
            }
            else {
                $lock = $sheet->get_lock();
                if( $lock['expires'] < $now ) {
                    $acts[] = ['content'=>str_replace('XXX', $sid, $linksteal)];
                }
            }
            if( $acts ) {
                $menus[] = FormUtils::create_menu($acts, ['id'=>'Stylesheet'.$sid]);
            }
        }

        $smarty->assign('stylesheets',$sheetslist)
         ->assign('cssmenus',$menus);

        $pagerows = 10;
        $navpages = ceil($n / $pagerows);
        if( $navpages > 1 ) {
            $pagelengths = [10=>10];
            $pagerows += $pagerows;
            if( $pagerows < $totalrows ) $pagelengths[20] = 20;
            $pagerows += $pagerows;
            if( $pagerows < $totalrows ) $pagelengths[40] = 40;
            $pagelengths[0] = lang('all');
        } else {
            $pagelengths = null;
        }
        $sellength = 10; //OR some $_REQUEST[]

        $smarty->assign('navpages', $navpages)
         ->assign('pagelengths',$pagelengths)
         ->assign('currentlength',$sellength);
    }
    else {
        $db = AppSingle::Db();
        $query = 'SELECT EXISTS (SELECT 1 FROM '.CMS_DB_PREFIX.StylesheetOperations::TABLENAME.')';
        if( $db->GetOne($query) ) {
            $smarty->assign('stylesheets',false); //signal rows exist, but none matches
        }
    }


    $extras = get_secure_param_array();

    $smarty->assign('urlext',$urlext)
     ->assign('extraparms',$extras);
}
catch( Throwable $e ) {
    echo '<div class="error">'.$e->GetMessage().'</div>';
}

// stylesheeets script

$s1 = json_encode(lang_by_realm('layout','confirm_delete_bulk'));
$s2 = json_encode(lang_by_realm('layout','error_nothingselected'));
$s3 = json_encode(lang_by_realm('layout','confirm_steal_lock'));
$s4 = json_encode(lang_by_realm('layout','error_contentlocked'));
//$s5 = json_encode(lang_by_realm('layout','confirm_replacestyle'));
$s6 = json_encode(lang_by_realm('layout','confirm_deletestyle'));
$s7 = json_encode(lang_by_realm('layout','confirm_removestyle'));
$s8 = json_encode(lang_by_realm('layout','confirm_applystyle'));
$s9 = json_encode(lang_by_realm('layout','confirm_clearlocks'));
$t1 = lang_by_realm('layout','prompt_replace_typed',lang_by_realm('layout','prompt_stylesheet'));
$t2 = lang_by_realm('layout','prompt_replace_typed',lang_by_realm('layout','prompt_stylesgroup'));
$cancel = lang('cancel');
$submit = lang('submit');
$secs = AppParams::get('lock_refresh', 120);
$secs = max(30,min(600,$secs));

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery.SSsort.js', 1);
$jsm->queue_matchedfile('jquery.ContextMenu.js', 1);
$jsm->queue_matchedfile('jquery.cmsms_poll.js', 2);
$jsm->queue_matchedfile('jquery.cmsms_lock.js', 2);

$js = <<<EOS
var pagetable;
function pagefirst(tbl) {
  $.fn.SSsort.movePage(tbl,false,true);
}
function pagelast(tbl) {
  $.fn.SSsort.movePage(tbl,true,true);
}
function pageforw(tbl) {
  $.fn.SSsort.movePage(tbl,true,false);
}
function pageback(tbl) {
  $.fn.SSsort.movePage(tbl,false,false);
}
function adjust_locks(tblid,lockdata) {
  var n = 0;
  $('#'+tblid).find(' > tbody > tr').each(function() {
    var row = $(this),
     id = row.find('td:first').text();
    if(lockdata.hasOwnProperty(id)) {
      n++;
      var status = lockdata[id];
      //set lock-indicators for this row
      if(status === 1) {
       //stealable
       row.find('.locked').css('display','none');
       row.find('.steal_lock').css('display','inline');
      } else if(status === -1) {
        //blocked
        row.find('.steal_lock').css('display','none');
        row.find('.locked').css('display','inline');
      }
      row.find('.action').attr('disabled','disabled').css('pointer-events','none'); //IE 11+ ?
      row.addClass('locked');
    } else if(row.hasClass('locked')) {
      row.find('.locked, .steal_lock').css('display','none');
      row.find('.action').removeAttr('disabled').css('pointer-events','auto');
      row.removeClass('locked');
    }
  });
  return n;
}
$(function() {
  pagetable = document.getElementById('csslist');
  var opts = {
   sortClass: 'SortAble',
   ascClass: 'SortUp',
   descClass: 'SortDown',
   oddClass: 'row1',
   evenClass: 'row2',
   oddsortClass: 'row1s',
   evensortClass: 'row2s'
  };
  if($navpages > 1) {
    var xopts = $.extend({}, opts, {
     paginate: true,
     pagesize: $sellength,
     currentid: 'cpage',
     countid: 'tpage'
    });
    $(pagetable).SSsort(xopts);
    $('#pagerows').on('change',function() {
      l = parseInt(this.value);
      if(l == 0) {
       //TODO disable move-links, 'rows per page', show 'rows'
      } else {
        //TODO enable move-links, 'rows per page', hide 'rows'
      }
      $.fn.SSsort.setCurrent(tpltable,'pagesize',l);
    });
  } else {
    $(pagetable).SSsort(opts);
  }
  $('#bulk_action').attr('disabled', 'disabled');
  cms_button_able($('#bulk_submit'), false);
  $('#css_selall').cmsms_checkall();
  $('#css_selall,.css_select').on('click', function() {
    l = $('.css_select:checked').length;
    if(l === 0) {
      $('#bulk_action').attr('disabled', 'disabled');
      cms_button_able($('#bulk_submit'), false);
    } else {
      $('#bulk_action').removeAttr('disabled');
      cms_button_able($('#bulk_submit'), true);
    }
  });
  $('#bulk_submit').on('click', function() {
    e.preventDefault();
    var l = $('input:checkbox:checked.css_select').length;
    if(l > 0) {
      cms_confirm_btnclick(this,$s1);
    } else {
      cms_alert($s2);
    }
    return false;
  });
  $('#csslist [context-menu]').ContextMenu();
  $('a.edit_css').on('click', function(e) {
    if($(this).hasClass('steal_lock')) return true; //TODO
    e.preventDefault();
    var url = this.href,
      cssid = this.getAttribute('data-css-id');
    // double-check whether this sheet is locked
    $.ajax('ajax_lock.php{$urlext}', {
      data: { opt: 'check', type: 'stylesheet', oid: cssid }
    }).done(function(data) {
      if(data.status === 'success') {
        if(data.locked) {
          cms_alert($s4);
        } else {
          window.location.href = url;
        }
      } else {
        cms_alert('AJAX ERROR');
      }
    });
    return false;
  });
  $('a.replace_css').on('click', function(e) {
    e.preventDefault();
    //customize the dialog
    var dlg = $('#replacedialog'),
     sel = dlg.find('#replacement'),
     from = $(this).attr('data-tpl-id'),
     opt = sel.find('option[value='+from+']'),
     name = opt.text();
    sel.find('option[disabled="disabled"]').removeAttr('disabled');
    opt.attr('disabled','disabled');
    var titl = (from > 0) ? '$t1':'$t2';
    dlg.attr('title',titl).find('#from').html(name);

    cms_dialog($('#replacedialog'), {
      open: function(ev, ui) {
        $(this).find('input[name="css"]').val(old);
      },
      modal: true,
      width: 'auto',
      buttons: {
        '$submit': function() {
          var choice = $(this).find('input[name="newcss"]').val();
          $(this).dialog('close');
          // TODO check for id or name
          if(!choice) return;
          // want another confirmation here ?
          $('#replacedialog_form').submit();
        },
        '$cancel': function() {
          $(this).dialog('close');
        }
      }
    });
    return false;
  });
  $('a.del_css').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s6);
    return false;
  });
  $('a.remove_css').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s7);
    return false;
  });
  $('a.prepend_css').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s8);
    return false;
  });
  $('a.append_css').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s8);
    return false;
  });
  var watcher = Poller.run({
    url: 'ajax_stylesheet_locks.php{$urlext}',
    interval: $secs,
    done_handler: function(json) {
      var lockdata = JSON.parse(json);
      adjust_locks('csslist',lockdata.sheets || {});
      adjust_locks('grouplist',lockdata.groups || {});
    }
  });
  $('#clearlocks').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s9);
    return false;
  });
  $('a.steal_lock').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s3);
    return false;
  });
});
EOS;
$jsm->queue_string($js, 3);

$themeObject = Utils::get_theme_object();

// stylesheet groups

$groups = StylesheetOperations::get_bulk_groups(); //TODO ensure member id's are also displayed
if( $groups ) {
    $u = 'editcssgroup.php'.$urlext.'&amp;css=XXX';
    $t = lang_by_realm('layout','title_edit_group');
    $icon = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
    $linkedit = '<a href="'.$u.'" data-css-id="XXX" class="edit_css">'.$icon.'</a>'.PHP_EOL;

/*    $u = 'stylesheetoperations.php'.$urlext.'&amp;op=copy&amp;css=XXX';
    $t = lang_by_realm('layout','title_copy_group');
    $icon = $themeObject->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
    $linkcopy = '<a href="'.$u.'" class="copy_css">'.$icon.'</a>'.PHP_EOL;
*/
    $u = 'stylesheetoperations.php'.$urlext.'&amp;op=delete&amp;css=XXX';
    $t = lang_by_realm('layout','title_delete_shallow');
    $icon = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon');
    $linkdel = '<a href="'.$u.'" class="del_grp">'.$icon.'</a>'.PHP_EOL;

    $u = 'stylesheetoperations.php'.$urlext.'&amp;op=deleteall&amp;css=XXX';
    $t = lang_by_realm('layout','title_delete_deep');
    $icon = $themeObject->DisplayImage('icons/extra/deletedeep', $t, '', '', 'systemicon');
    $linkdelall = '<a href="'.$u.'" class="del_grpall">'.$icon.'</a>'.PHP_EOL;

    $u = 'stylesheetoperations.php'.$urlext.'&amp;op=prepend&amp;css=XXX';
    $t = lang_by_realm('layout','title_prepend_stylesheet');
    $icon = $themeObject->DisplayImage('icons/extra/prepend', $t, '', '', 'systemicon');
    $linkprepend = '<a href="'.$u.'" class="prepend_css">'.$icon.'</a>'.PHP_EOL;

    $u = 'stylesheetoperations.php'.$urlext.'&amp;op=append&amp;css=XXX';
    $t = lang_by_realm('layout','title_append_stylesheet');
    $icon = $themeObject->DisplayImage('icons/extra/append', $t, '', '', 'systemicon');
    $linkappend = '<a href="'.$u.'" class="append_css">'.$icon.'</a>'.PHP_EOL;

    $u = 'stylesheetoperations.php'.$urlext.'&amp;op=replace&amp;css=XXX';
    $t = lang_by_realm('layout','title_replace_stylesheet');
    $icon = $themeObject->DisplayImage('icons/extra/replace', $t, '', '', 'systemicon');
    $linkreplace = '<a href="'.$u.'" class="replace_css" data-css-id="XXX">'.$icon.'</a>'.PHP_EOL;

    $u = 'stylesheetoperations.php'.$urlext.'&amp;op=remove&amp;css=XXX';
    $t = lang_by_realm('layout','title_remove_stylesheet');
    $icon = $themeObject->DisplayImage('icons/extra/removeall', $t, '', '', 'systemicon');
    $linkremove = '<a href="'.$u.'" class="remove_css">'.$icon.'</a>'.PHP_EOL;

    $menus = [];
    foreach( $groups as $gid => &$group ) {
        $acts = [];
        $acts[] = ['content'=>str_replace('XXX', $gid, $linkedit)];
//        $acts[] = ['content'=>str_replace('XXX', -$gid, $linkcopy)];
        $acts[] = ['content'=>str_replace('XXX', -$gid, $linkprepend)];
        $acts[] = ['content'=>str_replace('XXX', -$gid, $linkappend)];
        $acts[] = ['content'=>str_replace('XXX', -$gid, $linkreplace)];
        $acts[] = ['content'=>str_replace('XXX', -$gid, $linkremove)];
        $acts[] = ['content'=>str_replace('XXX', -$gid, $linkdel)];
        $acts[] = ['content'=>str_replace('XXX', -$gid, $linkdelall)];
//TODO lock processing, if relevant

        if( $acts ) {
            $menus[] = FormUtils::create_menu($acts, ['id'=>'Sheetsgroup'.$gid]);
        }
    }
    unset($group);

    $smarty->assign('list_groups', $groups)
     ->assign('grpmenus', $menus);

    $s1 = json_encode(lang_by_realm('layout','confirm_delete_group'));
    $s2 = json_encode(lang_by_realm('layout','confirm_delete_groupplus'));
    $title = lang_by_realm('layout','prompt_replace_typed',lang_by_realm('layout','prompt_stylesgroup'));

    // groups supplementary-script
    $js = <<<EOS
$(function() {
  $('#grouplist [context-menu]').ContextMenu();
  $('a.del_grp').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s1);
    return false;
  });
  $('a.del_grpall').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s2);
    return false;
  });
});

EOS;
    $jsm->queue_string($js, 3);
}

$out = $jsm->page_content('', false, false);
if( $out ) {
    add_page_foottext($out);
}

$list = StylesheetOperations::get_all_stylesheets(true);
if( $groups ) {
    $tmp = ' ('.lang_by_realm('layout','group').')';
    foreach( $groups as $gid => &$group ) {
        $list[-$gid] = $group->get_name().$tmp;
    }
    unset($group);
}
if( $list ) {
    asort($list,SORT_STRING);
    $replacements = [-1 => lang_by_realm('layout','select_one')] + $list;
}
else {
    $replacements = null;
}
$smarty->assign('css_choices',$replacements);

$extras = get_secure_param_array();
$extras2 = $extras + [
    'op' => 'replace',
    'css' => '', //populted by js
];
//$selfurl = basename(__FILE__);

$smarty->assign('manage_stylesheets',$pmanage)
 ->assign('has_add_right',$pmanage)
 ->assign('activetab', $_REQUEST['_activetab'] ?? null)
// ->assign('selfurl',$selfurl)
 ->assign('urlext',$urlext)
 ->assign('extraparms',$extras)
 ->assign('extraparms2',$extras2);
// ->assign('lock_timeout',AppParams::get('lock_timeout', 60))

$content = $smarty->fetch('liststyles.tpl');
require './header.php';
echo $content;
require './footer.php';
