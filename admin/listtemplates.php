<?php
# List templates and groups and types.
# Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppState;
use CMSMS\FormUtils;
use CMSMS\LockOperations;
use CMSMS\ScriptOperations;
use CMSMS\TemplateOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();

$urlext = get_secure_param();
$userid = get_userid();
$pmod = check_permission($userid,'Modify Templates');
$padd = $pmod || check_permission($userid,'Add Templates');

cleanArray($_REQUEST);

if( $padd ) {
    if( isset($_REQUEST['submit_create']) ) {
        redirect('edittemplate.php'.$urlext.'&import_type='.$_REQUEST['import_type']);
    }
/*  elseif( isset($_REQUEST['bulk_submit']) ) {
        $tmp = base64_encode(serialize($_REQUEST));
        redirect('bulktemplates.php'.$urlext.'&allparms='.$tmp);
    }
*/
}

$themeObject = cms_utils::get_theme_object();
$lock_timeout = cms_siteprefs::get('lock_timeout');
$smarty = CmsApp::get_instance()->GetSmarty();

// individual templates

$filter = $_REQUEST['filter'] ?? [];

if( !check_permission($userid,'Modify Templates') ) {
    $filter[] = 'e:'.get_userid(false);
}

require_once __DIR__.DIRECTORY_SEPARATOR.'method.TemplateQuery.php';

try {
    if( $templates ) {

        $u = 'edittemplate.php'.$urlext.'&amp;tpl=XXX';
        $t = lang_by_realm('layout','title_edit_template');
        $icon = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
        $linkedit = '<a href="'.$u.'" class="edit_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";

/* see template
//      $u = ibid
        $t = lang_by_realm('layout','title_steal_lock');
        $icon = $themeObject->DisplayImage('icons/system/permissions', $t, '', '', 'systemicon edit_tpl steal_tpl_lock');
        $linksteal = '<a href="'.$u.'" class="steal_tpl_lock" data-tpl-id="XXX" accesskey="e">'.$icon.'</a>'."\n";
*/
        if( $padd ) {
            $u = 'templateoperations.php'.$urlext.'&amp;op=copy&amp;tpl=XXX';
            $t = lang_by_realm('layout','title_copy_template');
            $icon = $themeObject->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
            $linkcopy = '<a href="'.$u.'" class="copy_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";
        }

        $u = 'templateoperations.php'.$urlext.'&amp;op=applyall&amp;tpl=XXX';
        $t = lang_by_realm('layout','title_apply_template');
        $icon = $themeObject->DisplayImage('icons/extra/applyall', $t, '', '', 'systemicon');
        $linkapply = '<a href="'.$u.'" class="apply_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";

        $u = 'templateoperations.php'.$urlext.'&amp;op=replace&amp;tpl=XXX';
        $t = lang_by_realm('layout','title_replace_template');
        $icon = $themeObject->DisplayImage('icons/extra/replace', $t, '', '', 'systemicon');
        $linkreplace = '<a href="'.$u.'" class="replace_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";

        $u = 'templateoperations.php'.$urlext.'&amp;op=delete&amp;tpl=XXX';
        $t = lang_by_realm('layout','title_delete_template');
        $icon = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon');
        $linkdel = '<a href="'.$u.'" class="del_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";

//TODO where relevant, an action to revert template content to type-default

        $patn = CmsLayoutTemplate::CORE;
        $now = time();
        $menus = [];
        for( $i = 0, $n = count($templates); $i < $n; ++$i ) {
            $acts = [];
            $template = $templates[$i];
            $tid = $template->get_id();
            $origin = $template->get_originator();
            $core = $origin == '' || $origin == $patn;

//          if( !$lock_timeout || !$template->locked() ) {
                if( $pmod ) { $acts[] = ['content'=>str_replace('XXX', $tid, $linkedit)]; }
                if( $padd ) {
                    $acts[] = ['content'=>str_replace('XXX', $tid, $linkcopy)];
                }
                if( $pmod && $core ) { $acts[] = ['content'=>str_replace('XXX', $tid, $linkapply)]; }
                if( $pmod && $core ) { $acts[] = ['content'=>str_replace('XXX', $tid, $linkreplace)]; }
/*          } else {
                $lock = $template->get_lock();
                if( $lock['expires'] < $now ) {
                    $acts[] = ['content'=>str_replace('XXX', $tid, $linksteal)];
                }
            }
*/
            if( !$template->get_type_dflt() && !$template->locked() ) {
                if( $pmod || $template->get_owner_id() == get_userid() ) {
                    $acts[] = ['content'=>str_replace('XXX', $tid, $linkdel)];
                }
            }

            if( $acts ) {
                $menus[] = FormUtils::create_menu($acts, ['id'=>'Template'.$tid, 'class'=>CMS_POPUPCLASS]);
            }
        }

        $smarty->assign('templates', $templates)
         ->assign('tplmenus', $menus);

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
    }
    else {
        $navpages = 0;
        $pagelengths = [];
        $sellength = 1;

        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT EXISTS (SELECT 1 FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.')';
        if( $db->GetOne($query) ) {
            $smarty->assign('templates',false); //signal row(s) exist, but none matches
        }
    }

    $smarty->assign('navpages', $navpages)
     ->assign('pagelengths',$pagelengths)
     ->assign('currentlength',$sellength);

    // populate types (objects and their names)
    $types = CmsLayoutTemplateType::get_all();
    if( $types ) {
        $tmp = [];
        $tmp2 = [];
        for( $i = 0, $n = count($types); $i < $n; ++$i ) {
            $tmp[$types[$i]->get_id()] = $types[$i];
            $tmp2[$types[$i]->get_id()] = $types[$i]->get_langified_display_value();
        }

        $typepages = ceil($n / 10);
        //TODO $pagelengths if N/A already
        $smarty->assign('list_all_types',$tmp) //objects
         ->assign('list_types',$tmp2) //public-names
         ->assign('typepages',$typepages);
    }
    else {
        $typepages = 0;

        $smarty->assign('list_all_types',null)
         ->assign('list_types',null)
         ->assign('typepages',null);
    }

    $locks = LockOperations::get_locks('template');
//  $selfurl = basename(__FILE__);
    $extras = get_secure_param_array();
    $smarty->assign('have_locks',$locks ? count($locks) : 0)
     ->assign('lock_timeout',$lock_timeout)
     ->assign('coretypename',CmsLayoutTemplateType::CORE)
     ->assign('manage_templates',$pmod)
     ->assign('has_add_right',$padd)
     ->assign('extraparms',$extras);

}
catch( Exception $e ) {
    echo '<div class="error">'.$e->GetMessage().'</div>';
}

// templates filter

$smarty->assign('tpl_filter',$filter);

$opts = ['' => lang('all')];
// groups for display in filter selector
$groups = CmsLayoutTemplateCategory::get_all();
if( $groups ) {
    $tmp = [];
    foreach( $groups as $k => $val ) {
        $tmp['g:'.$k] = $val;
    }
    uasort($tmp,function($a,$b) {
        return strcasecmp($a->get_name(),$b->get_name());
    });
    $opts[lang_by_realm('layout','prompt_tpl_groups')] = $tmp;
}
// types for display in filter selector
if( $types ) {
    $tmp = [];
    foreach( $tmp2 as $k => $val ) {
        $tmp['t:'.$k] = $val;
    }
    uasort($tmp,function($a,$b) {
        return strcasecmp($a,$b);
    });
    $opts[lang_by_realm('layout','prompt_templatetypes')] = $tmp;
}
// originators for display in filter selector
$list = TemplateOperations::get_all_originators(true);
if( $list ) {
    $tmp = [];
    foreach( $list as $val ) {
        $tmp['o:'.$val] = $val;
    }
    $opts[lang_by_realm('layout','prompt_originators')] = $tmp;
}
$smarty->assign('filter_tpl_options',$opts);

// core templates for display in replacement selector
$list = TemplateOperations::get_originated_templates(CmsLayoutTemplate::CORE, true);
asort($list,SORT_STRING);
$replacements = [-1 => lang_by_realm('layout','select_one')] + $list;
$smarty->assign('tpl_choices',$replacements);

// templates script

$s1 = json_encode(lang_by_realm('layout','confirm_delete_bulk'));
$s2 = json_encode(lang_by_realm('layout','error_nothingselected'));
$s3 = json_encode(lang_by_realm('layout','confirm_steal_lock'));
$s4 = json_encode(lang_by_realm('layout','error_contentlocked'));
//$s5 = json_encode(lang_by_realm('layout','confirm_replace_template'));
$s6 = json_encode(lang_by_realm('layout','confirm_applytemplate'));
$s7 = json_encode(lang_by_realm('layout','confirm_deletetemplate'));
$s8 = json_encode(lang_by_realm('layout','confirm_removetemplate'));
$s9 = json_encode(lang_by_realm('layout','confirm_clearlocks'));
$title = lang_by_realm('layout','prompt_replace_typed',lang_by_realm('layout','prompt_template'));
$cancel = lang('cancel');
$submit = lang('submit');
$reset = lang('reset');
$secs = cms_siteprefs::get('lock_refresh', 120);
$secs = max(30,min(600,$secs));

$sm = new ScriptOperations();
$sm->queue_matchedfile('jquery.SSsort.js', 1);
$sm->queue_matchedfile('jquery.ContextMenu.js', 1);
$sm->queue_matchedfile('jquery.cmsms_poll.js', 2);
$sm->queue_matchedfile('jquery.cmsms_lock.js', 2);

$js = <<<EOS
var tpltable,typetable;
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
      row.find('.locked,.steal_lock').css('display','none');
      row.find('.action').removeAttr('disabled').css('pointer-events','auto');
      row.removeClass('locked');
    }
  });
  return n;
}
$(function() {
  tpltable = document.getElementById('tpllist');
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
    $(tpltable).SSsort(xopts);
    $('#pagerows').on('change',function() {
      l = parseInt(this.value);
      if(l == 0) {
       //TODO hide move-links, 'rows per page', show 'rows'
      } else {
        //TODO show move-links, 'rows per page', hide 'rows'
      }
      $.fn.SSsort.setCurrent(tpltable,'pagesize',l);
    });
  } else {
    $(tpltable).SSsort(opts);
  }
  $('#bulk_action').attr('disabled','disabled');
  cms_button_able($('#bulk_submit'),false);
  $('#tpl_selall').cmsms_checkall();
  $('#tpl_selall,.tpl_select').on('click',function() {
    l = $('.tpl_select:checked').length;
    if(l === 0) {
      $('#bulk_action').attr('disabled','disabled');
      cms_button_able($('#bulk_submit'),false);
    } else {
      $('#bulk_action').removeAttr('disabled');
      cms_button_able($('#bulk_submit'),true);
    }
  });
  $('#bulk_submit').on('click',function() {
    e.preventDefault();
    var l = $('input:checkbox:checked.tpl_select').length;
    if(l > 0) {
      cms_confirm_btnclick(this,$s1);
    } else {
      cms_alert($s2);
    }
    return false;
  });
  $('#tpllist [context-menu]').ContextMenu();

  typetable = document.getElementById('typelist');
  if($typepages > 1) {
   xopts = $.extend({}, opts, {
    paginate: true,
    pagesize: $sellength,
    currentid: 'cpage2',
    countid: 'tpage2'
   });
   $(typetable).SSsort(xopts);
   $('#typepagerows').on('change',function() {
    l = parseInt(this.value);
    if(l == 0) {
     //TODO hide move-links, 'rows per page', show 'rows'
    } else {
     //TODO show move-links, 'rows per page', hide 'rows'
    }
    $.fn.SSsort.setCurrent(typetable,'pagesize',l);
   });
  } else {
    $(typetable).SSsort(opts);
  }

  $('a.edit_filter').on('click', function() {
    cms_dialog($('#filterdialog'), {
    open: function(ev, ui) {
      cms_equalWidth($('#filterdialog label.boxchild'));
    },
    width: 'auto',
    buttons: {
      '$submit': function() {
        $(this).dialog('close');
        $('#filterdialog_form').trigger('submit');
      },
      '$reset': function() {
        $(this).dialog('close');
        var el = $('#filterdialog_form');
        el.find('#filter_tpl').val([]);
        el.trigger('submit');
      },
      '$cancel': function() {
        $(this).dialog('close');
      }
    }
    });
  });
  $('a.edit_tpl').on('click', function(e) {
    if($(this).hasClass('steal_lock')) return true; //TODO
    e.preventDefault();
    var url = this.href,
      tplid = this.getAttribute('data-tpl-id');
    // double-check whether this template is locked
    $.ajax('ajax_lock.php{$urlext}', {
      data: { opt: 'check', type: 'template', oid: tplid }
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
  $('a.replace_tpl').on('click', function(e) {
    e.preventDefault();
    //customize the dialog
    var dlg = $('#replacedialog'),
     sel = dlg.find('#replacement'),
     from = $(this).attr('data-tpl-id'),
     opt = sel.find('option[value='+from+']'),
     name = opt.text();
    sel.find('option[disabled="disabled"]').removeAttr('disabled');
    opt.attr('disabled','disabled');
    dlg.attr('title','$title').find('#from').html(name);

    cms_dialog($('#replacedialog'), {
      open: function(ev, ui) {
        $(this).find('input[name="tpl"]').val(old);
      },
      modal: true,
      width: 'auto',
      buttons: {
        '$submit': function() {
          var choice = $(this).find('input[name="newtpl"]').val();
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
  $('a.apply_tpl').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s6);
    return false;
  });
  $('a.del_tpl').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s7);
    return false;
  });
  $('a.remove_tpl').on('click', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this,$s8);
    return false;
  });
  var watcher = Poller.run({
    url: 'ajax_template_locks.php{$urlext}',
    interval: $secs,
    done_handler: function(json) {
      var lockdata = JSON.parse(json);
      adjust_locks('tpllist',lockdata.templates || {});
      adjust_locks('grouplist',lockdata.groups || {});
      adjust_locks('typelist',lockdata.types || {});
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
$sm->queue_string($js, 3);

// template groups

$groups = TemplateOperations::get_bulk_groups(); //TODO ensure member id's are also displayed
if( $groups ) {
    $u = 'edittplgroup.php'.$urlext.'&amp;tpl=XXX';
    $t = lang_by_realm('layout','prompt_edit');
    $icon = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
    $linkedit = '<a href="'.$u.'" class="edit_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";

/*    $u = 'templateoperations.php'.$urlext.'&amp;op=copy&amp;tpl=XXX';
    $t = lang_by_realm('layout','title_copy_group');
    $icon = $themeObject->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
    $linkcopy = '<a href="'.$u.'" class="copy_tpl">'.$icon.'</a>'."\n";
*/
    $u = 'templateoperations.php'.$urlext.'&amp;op=delete&amp;tpl=XXX';
    $t = lang_by_realm('layout','title_delete_shallow');
    $icon = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon del_grp');
    $linkdel = '<a href="'.$u.'" class="del_grp">'.$icon.'</a>'."\n";

    $u = 'templateoperations.php'.$urlext.'&amp;op=deleteall&amp;tpl=XXX';
    $t = lang_by_realm('layout','title_delete_deep');
    $icon = $themeObject->DisplayImage('icons/extra/deletedeep', $t, '', '', 'systemicon del_grp');
    $linkdelall = '<a href="'.$u.'" class="del_grpall">'.$icon.'</a>'."\n";

    $menus = [];
    foreach( $groups as $gid => &$group ) {
        $acts = [];
        if( $pmod ) { $acts[] = ['content'=>str_replace('XXX', $gid, $linkedit)]; }
//        $acts[] = ['content'=>str_replace('XXX', -$gid, $linkcopy)];
        if( $pmod ) { $acts[] = ['content'=>str_replace('XXX', -$gid, $linkdel)]; }
        if( $pmod ) { $acts[] = ['content'=>str_replace('XXX', -$gid, $linkdelall)]; }
//TODO item to revert template content to type-default, if any
//TODO lock processing, if relevant

        if( $acts ) {
            $menus[] = FormUtils::create_menu($acts, ['id'=>'Templategroup'.$gid, 'class'=>CMS_POPUPCLASS]);
        }
    }
    unset($group);

    $smarty->assign('list_groups', $groups)
     ->assign('grpmenus', $menus);

    $s1 = json_encode(lang_by_realm('layout','confirm_delete_group'));
    $s2 = json_encode(lang_by_realm('layout','confirm_delete_groupplus'));

    // groups supplementary script
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
    $sm->queue_string($js, 3);
}

$out = $sm->render_inclusion('', false, false);
if( $out ) {
    $themeObject->add_footertext($out);
}

// hidden inputs for filter form
$extras = get_secure_param_array() + [
    '_activetab' => 'templates',
];
// hidden inputs for replacement form
$extras2 = [
    CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY],
    'op' => 'replace',
    'tpl' => '', //populated by js
];
$selfurl = basename(__FILE__);

$smarty->assign('manage_templates',$pmod)
 ->assign('has_add_right', $pmod || check_permission($userid,'Add Templates'))
 ->assign('extraparms2',$extras)
 ->assign('selfurl',$selfurl)
 ->assign('urlext',$urlext)
 ->assign('activetab', $_REQUEST['_activetab'] ?? null)
 ->assign('extraparms3',$extras2)
 ->assign('coretypename',CmsLayoutTemplateType::CORE)
// ->assign('import_url',TODOfuncURL('import_template'))  N/A as standalone
 ->assign('lock_timeout',cms_siteprefs::get('lock_timeout'));

include_once 'header.php';
$smarty->display('listtemplates.tpl');
include_once 'footer.php';
