<?php
/*
List templates and groups and types.
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

use CMSMS\AppParams;
use CMSMS\FormUtils;
use CMSMS\LockOperations;
use CMSMS\ScriptsMerger;
use CMSMS\SingleItem;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplatesGroup;
use CMSMS\TemplateType;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
$userid = get_userid();
$pmod = check_permission($userid,'Modify Templates');
$padd = $pmod || check_permission($userid,'Add Templates');

if( $padd ) {
    if( isset($_REQUEST['submit_create']) ) {
        $tmp = sanitizeVal($_REQUEST['import_type'], CMSSAN_PUNCTX, ':');
        redirect('edittemplate.php'.$urlext.'&import_type='.$tmp);
    }
/*  elseif( isset($_REQUEST['bulk_submit']) ) {
        $tmp = base64_encode(serialize($_REQUEST));
        redirect('bulktemplates.php'.$urlext.'&allparms='.$tmp);
    }
*/
}

$themeObject = SingleItem::Theme();
$lock_timeout = AppParams::get('lock_timeout', 60);
$smarty = SingleItem::Smarty();

// individual templates
// $_REQUEST members all cleaned individually, as needed
$tmp = $_REQUEST['filter'] ?? null;
if( $tmp ) {
    if( is_array($tmp) ) {
        $filter = array_map(function ($v) {
            return sanitizeVal($v, CMSSAN_NAME); // OR CMSSAN_PUNCT, CMSSAN_PURESPC?
        }, $tmp);
    }
    else {
        $filter = [sanitizeVal($tmp, CMSSAN_NAME)]; // OR CMSSAN_PUNCT, CMSSAN_PURESPC?
    }
}
else {
    $filter = [];
}

if( !check_permission($userid,'Modify Templates') ) {
    $filter[] = 'e:'.$userid; // restrict to the user's templates
}

require_once __DIR__.DIRECTORY_SEPARATOR.'method.templatequery.php';

try {
    if( $templates ) {

        $u = 'edittemplate.php'.$urlext.'&tpl=XXX';
        $t = _ld('layout','title_edit_template');
        $icon = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
        $linkedit = '<a href="'.$u.'" class="edit_tpl" data-tpl-id="XXX">'.$icon.'</a>'.PHP_EOL;

/* see template
//      $u = ibid
        $t = _ld('layout','title_steal_lock');
        $icon = $themeObject->DisplayImage('icons/system/permissions', $t, '', '', 'systemicon edit_tpl steal_tpl_lock');
        $linksteal = '<a href="'.$u.'" class="steal_tpl_lock" data-tpl-id="XXX" accesskey="e">'.$icon.'</a>'.PHP_EOL;
*/
        if( $padd ) {
            $u = 'templateoperations.php'.$urlext.'&op=copy&tpl=XXX';
            $t = _ld('layout','title_copy_template');
            $icon = $themeObject->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
            $linkcopy = '<a href="'.$u.'" class="copy_tpl" data-tpl-id="XXX">'.$icon.'</a>'.PHP_EOL;
        }

        $u = 'templateoperations.php'.$urlext.'&op=applyall&tpl=XXX';
        $t = _ld('layout','title_apply_template');
        $icon = $themeObject->DisplayImage('icons/extra/applyall', $t, '', '', 'systemicon');
        $linkapply = '<a href="'.$u.'" class="apply_tpl" data-tpl-id="XXX">'.$icon.'</a>'.PHP_EOL;

        $u = 'templateoperations.php'.$urlext.'&op=replace&tpl=XXX';
        $t = _ld('layout','title_replace_template');
        $icon = $themeObject->DisplayImage('icons/extra/replace', $t, '', '', 'systemicon');
        $linkreplace = '<a href="'.$u.'" class="replace_tpl" data-tpl-id="XXX">'.$icon.'</a>'.PHP_EOL;

        $u = 'templateoperations.php'.$urlext.'&op=delete&tpl=XXX';
        $t = _ld('layout','title_delete_template');
        $icon = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon');
        $linkdel = '<a href="'.$u.'" class="del_tpl" data-tpl-id="XXX">'.$icon.'</a>'.PHP_EOL;

//TODO where relevant, an action to revert template content to type-default

        $patn = Template::CORE;
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
/*          }
            else {
                $lock = $template->get_lock();
                if( $lock['expires'] < $now ) {
                    $acts[] = ['content'=>str_replace('XXX', $tid, $linksteal)];
                }
            }
*/
            if( !$template->get_type_default() && !$template->locked() ) {
                if( $pmod || $template->get_owner_id() == $userid ) {
                    $acts[] = ['content'=>str_replace('XXX', $tid, $linkdel)];
                }
            }

            if( $acts ) {
                $menus[] = FormUtils::create_menu($acts, ['id'=>'Template'.$tid]);
            }
        }

        $smarty->assign([
            'templates' => $templates,
            'tplmenus' => $menus,
        ]);

        $pagerows = 10;
        $navpages = ceil($n / $pagerows);
        if( $navpages > 1 ) {
            $pagelengths = [10=>10];
            $pagerows += $pagerows;
            if( $pagerows < $n ) $pagelengths[20] = 20;
            $pagerows += $pagerows;
            if( $pagerows < $n ) $pagelengths[40] = 40;
            $pagelengths[0] = _la('all');
        }
        else {
            $pagelengths = null;
        }
        $sellength = 10; //OR some $_REQUEST[]
    }
    else {
        $navpages = 0;
        $pagelengths = [];
        $sellength = 1;

        $db = SingleItem::Db();
        $query = 'SELECT EXISTS (SELECT 1 FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.')';
        if( $db->getOne($query) ) {
            $smarty->assign('templates', false); //signal row(s) exist, but none matches
        }
    }

    $smarty->assign([
        'navpages' => $navpages,
        'pagelengths' => $pagelengths,
        'currentlength' => $sellength,
    ]);

    // populate types (objects and their names)
    $types = TemplateType::get_all();
    if( $types ) {
        $tmp = [];
        $tmp2 = [];
        for( $i = 0, $n = count($types); $i < $n; ++$i ) {
            $tmp[$types[$i]->get_id()] = $types[$i];
            $tmp2[$types[$i]->get_id()] = $types[$i]->get_langified_display_value();
        }

        $typepages = ceil($n / 10);
        //TODO $pagelengths if N/A already
        $smarty->assign([
            'list_all_types' => $tmp, //objects
            'list_types' => $tmp2, //public-names
            'typepages' => $typepages,
        ]);
    }
    else {
        $typepages = 0;

        $smarty->assign([
            'list_all_types' => null,
            'list_types' => null,
            'typepages' => null,
        ]);
    }

    $locks = LockOperations::get_locks('template');
//  $selfurl = basename(__FILE__);
    $extras = get_secure_param_array();
    $smarty->assign([
        'have_locks' => ($locks ? count($locks) : 0),
        'lock_timeout' => $lock_timeout,
        'coretypename' => TemplateType::CORE,
        'manage_templates' => $pmod,
        'has_add_right' => $padd,
        'extraparms' => $extras,
    ]);

}
catch( Throwable $e ) {
    echo '<div class="error">'.$e->GetMessage().'</div>';
}

// templates filter

$smarty->assign('tpl_filter',$filter);
// populate items for display in filter selector
$opts = ['' => _la('all')];
// group filters
$groups = TemplatesGroup::get_all();
if( $groups ) {
    $tmp = [];
    foreach( $groups as $k => $val ) {
        $tmp['g:'.$k] = $val;
    }
    uasort($tmp,function($a,$b) {
        return strcasecmp($a->get_name(),$b->get_name());
    });
    $opts[_ld('layout','prompt_tpl_groups')] = $tmp;
}
// type filters
if( $types ) {
    $tmp = [];
    foreach( $tmp2 as $k => $val ) {
        $tmp['t:'.$k] = $val;
    }
    uasort($tmp,function($a,$b) {
        return strcasecmp($a,$b);
    });
    $opts[_ld('layout','prompt_templatetypes')] = $tmp;
}
// originator filters
$list = TemplateOperations::get_all_originators(true);
if( $list ) {
    $tmp = [];
    foreach( $list as $val ) {
        $tmp['o:'.$val] = $val;
    }
    $opts[_ld('layout','prompt_originators')] = $tmp;
}
$smarty->assign('filter_tpl_options',$opts);

// core templates for display in replacement selector
$list = TemplateOperations::get_originated_templates(Template::CORE, true);
asort($list,SORT_STRING);
$replacements = [-1 => _ld('layout','select_one')] + $list;
$smarty->assign('tpl_choices',$replacements);

// templates script

$securekey = CMS_SECURE_PARAM_NAME;
$jobkey = CMS_JOB_KEY;
$s1 = json_encode(_ld('layout','confirm_delete_bulk'));
$s2 = json_encode(_ld('layout','error_nothingselected'));
$s3 = json_encode(_ld('layout','confirm_steal_lock'));
$s4 = json_encode(_ld('layout','error_contentlocked'));
//$s5 = json_encode(_ld('layout','confirm_replace_template'));
$s6 = json_encode(_ld('layout','confirm_applytemplate'));
$s7 = json_encode(_ld('layout','confirm_deletetemplate'));
$s8 = json_encode(_ld('layout','confirm_removetemplate'));
$s9 = json_encode(_ld('layout','confirm_clearlocks'));
$title = _ld('layout','prompt_replace_typed',_ld('layout','prompt_template'));
$cancel = _la('cancel');
$submit = _la('submit');
$reset = _la('reset');
$secs = AppParams::get('lock_refresh',120);
$secs = max(30,min(600,$secs));

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery.SSsort.js',1);
$jsm->queue_matchedfile('jquery.ContextMenu.js',1);
$jsm->queue_matchedfile('jquery.cmsms_poll.js',2);
$jsm->queue_matchedfile('jquery.cmsms_lock.js',2);

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
      row.find('.action').prop('disabled',true).css('pointer-events','none'); //IE 11+ ?
      row.addClass('locked');
    } else if(row.hasClass('locked')) {
      row.find('.locked,.steal_lock').css('display','none');
      row.find('.action').prop('disabled', false).css('pointer-events','auto');
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
      var l = parseInt(this.value);
      if(l === 0) {
       //TODO hide move-links, 'rows per page', show 'rows'
      } else {
        //TODO show move-links, 'rows per page', hide 'rows'
      }
      $.fn.SSsort.setCurrent(tpltable,'pagesize',l);
    });
  } else {
    $(tpltable).SSsort(opts);
  }
  $('#bulkaction').prop('disabled',true);
  cms_button_able($('#bulk_submit'),false);
  $('#tpl_selall').cmsms_checkall();
  $('#tpl_selall,.tpl_select').on('click',function() {
    var l = $('.tpl_select:checked').length;
    if(l === 0) {
      $('#bulkaction').prop('disabled',true);
      cms_button_able($('#bulk_submit'),false);
    } else {
      $('#bulkaction').prop('disabled', false);
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
    var l = parseInt(this.value);
    if(l === 0) {
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
    if(this.classList.contains('steal_lock')) return true;
    e.preventDefault();
    var url = this.href,
     tplid = this.getAttribute('data-tpl-id'),
     lockurl = 'ajax_lock.php',
     parms = {
      $securekey: cms_data.user_key,
      $jobkey: 1,
      dataType: 'json',
      op: 'check',
      type: 'template',
      oid: tplid
     };
    // double-check whether this template is locked
    $.ajax(lockurl, {
      method: 'POST',
      data: parms
    }).done(function(data) {
      if(data.status === 'success') {
        if(data.stealable) {
          cms_confirm($s3).done(function() {
            parms.op = 'unlock';
            parms.lock_id = data.lock_id;
// TODO security : parms.X = Y suitable for ScriptsMerger
            $.ajax(lockurl, {
              method: 'POST',
              data: parms
            });
            window.location.href = url;
          });
        } else if(data.locked) {
          cms_alert($s4);
        } else {
          window.location.href = url;
        }
      } else {
        cms_alert(data.error.msg);
      }
    }).fail(function() {
      cms_alert('AJAX ERROR');
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
    sel.find('option[disabled="disabled"]').prop('disabled', false);
    opt.prop('disabled',true);
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
$jsm->queue_string($js, 3);

// template groups

$groups = TemplateOperations::get_bulk_groups(); //TODO ensure member id's are also displayed
if( $groups ) {
    $u = 'edittplgroup.php'.$urlext.'&grp=XXX';
    $t = _ld('layout','prompt_edit');
    $icon = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
    $linkedit = '<a href="'.$u.'" class="edit_tpl" data-tpl-id="XXX">'.$icon.'</a>'.PHP_EOL;

/*    $u = 'templateoperations.php'.$urlext.'&op=copy&grp=XXX';
    $t = _ld('layout','title_copy_group');
    $icon = $themeObject->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
    $linkcopy = '<a href="'.$u.'" class="copy_tpl">'.$icon.'</a>'.PHP_EOL;
*/
    $u = 'templateoperations.php'.$urlext.'&op=delete&grp=XXX';
    $t = _ld('layout','title_delete_shallow');
    $icon = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon del_grp');
    $linkdel = '<a href="'.$u.'" class="del_grp">'.$icon.'</a>'.PHP_EOL;

    $u = 'templateoperations.php'.$urlext.'&op=deleteall&grp=XXX';
    $t = _ld('layout','title_delete_deep');
    $icon = $themeObject->DisplayImage('icons/extra/deletedeep', $t, '', '', 'systemicon del_grp');
    $linkdelall = '<a href="'.$u.'" class="del_grpall">'.$icon.'</a>'.PHP_EOL;

    $menus = [];
    foreach( $groups as $gid => &$group ) {
        $acts = [];
        if( $pmod ) { $acts[] = ['content'=>str_replace('XXX', $gid, $linkedit)]; }
//        $acts[] = ['content'=>str_replace('XXX', $gid, $linkcopy)];
        if( $pmod ) { $acts[] = ['content'=>str_replace('XXX', $gid, $linkdel)]; }
        if( $pmod ) { $acts[] = ['content'=>str_replace('XXX', $gid, $linkdelall)]; }
//TODO item to revert template content to type-default, if any
//TODO lock processing, if relevant

        if( $acts ) {
            $menus[] = FormUtils::create_menu($acts, ['id'=>'Templategroup'.$gid]);
        }
    }
    unset($group);

    $smarty->assign([
        'list_groups' => $groups,
        'grpmenus' => $menus,
    ]);

    $s1 = json_encode(_ld('layout','confirm_delete_group'));
    $s2 = json_encode(_ld('layout','confirm_delete_groupplus'));

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
    $jsm->queue_string($js, 3);
}

$out = $jsm->page_content();
if( $out ) {
    add_page_foottext($out);
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
$seetab = isset($_REQUEST['_activetab']) ? sanitizeVal($_REQUEST['_activetab'], CMSSAN_NAME) : null;

$smarty->assign([
   'curuser' => $userid,
   'manage_templates' => $pmod,
   'has_add_right' => $pmod || check_permission($userid, 'Add Templates'),
   'selfurl' => $selfurl,
   'urlext' => $urlext,
   'activetab' => $seetab,
   'extraparms2' => $extras,
   'extraparms3' => $extras2,
   'coretypename' => TemplateType::CORE,
//   'import_url' => TODOfuncURL('import_template'),  N/A as standalone
   'lock_timeout' => AppParams::get('lock_timeout', 60),
 ]);

$content = $smarty->fetch('listtemplates.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
