<?php
/*
ContentManger module action: defaultadmin
Copyright (C) 2013-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppParams;
use CMSMS\Lone;
use CMSMS\ScriptsMerger;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use CMSMS\UserParams;
use ContentManager\ContentListBuilder;
use ContentManager\ContentListFilter;

if (!$this->CheckContext()) {
	exit;
}

$modname = $this->GetName();
$builder = new ContentListBuilder($this);

if (isset($params['curpage'])) {
	$curpage = max(1, min(500, (int)$params['curpage']));
}

if (isset($params['expandall']) || isset($_GET['expandall'])) {
	$builder->expand_all();
	$curpage = 1;
} elseif (isset($params['collapseall']) || isset($_GET['collapseall'])) {
	$builder->collapse_all();
	$curpage = 1;
}

if (isset($params['setoptions'])) {
	$pagelimit = $params['pagelimit'] ?? 500;
	$pagelimit = max(1, min(500, (int)$pagelimit));
	UserParams::set($modname.'_pagelimit', $pagelimit);
	$filter = null;
	$filter_type = $params['filter_type'] ?? null;
	switch ($filter_type) {
	case ContentListFilter::EXPR_DESIGN:
		$filter = new ContentListFilter();
		$filter->type = ContentListFilter::EXPR_DESIGN;
		$filter->expr = $params['filter_design'];
		break;
	case ContentListFilter::EXPR_TEMPLATE:
		$filter = new ContentListFilter();
		$filter->type = ContentListFilter::EXPR_TEMPLATE;
		$filter->expr = $params['filter_template'];
		break;
	case ContentListFilter::EXPR_OWNER:
		$filter = new ContentListFilter();
		$filter->type = ContentListFilter::EXPR_OWNER;
		$filter->expr = $params['filter_owner'];
		break;
	case ContentListFilter::EXPR_EDITOR:
		$filter = new ContentListFilter();
		$filter->type = ContentListFilter::EXPR_EDITOR;
		$filter->expr = $params['filter_editor'];
		break;
	default:
		UserParams::remove($modname.'_userfilter');
	}
	if ($filter) {
		//record for use by ajax processor
		UserParams::set($modname.'_userfilter', serialize($filter));
	}
	$curpage = 1;
} else {
	$pagelimit = UserParams::get($modname.'_pagelimit', 500);
	$filter = UserParams::get($modname.'_userfilter', null); // empty string invalid
	if ($filter) {
		$filter = unserialize($filter);
	}
}

// see included file $builder->set_filter($filter);

if (isset($params['expand'])) {
	$builder->expand_section($params['expand']);
}

if (isset($params['collapse'])) {
	$builder->collapse_section($params['collapse']);
	$curpage = 1;
}

if (isset($params['setinactive'])) {
	$res = $builder->set_active($params['setinactive'], false);
	if (!$res) {
		$this->ShowErrors($this->Lang('error_setinactive'));
	}
}

if (isset($params['setactive'])) {
	$res = $builder->set_active($params['setactive'], true);
	if (!$res) {
		$this->ShowErrors($this->Lang('error_setactive'));
	}
}

if (isset($params['setdefault'])) {
	$res = $builder->set_default($params['setdefault'], true);
	if (!$res) {
		$this->ShowErrors($this->Lang('error_setdefault'));
	}
}

if (isset($params['moveup'])) {
	$res = $builder->move_content($params['moveup'], -1);
	if (!$res) {
		$this->ShowErrors($this->Lang('error_movecontent'));
	}
}

if (isset($params['movedown'])) {
	$res = $builder->move_content($params['movedown'], 1);
	if (!$res) {
		$this->ShowErrors($this->Lang('error_movecontent'));
	}
}

if (isset($params['delete'])) {
	$res = $builder->delete_content($params['delete']);
	if ($res) {
		$this->ShowErrors($res);
	}
}

function urlsplit(string $u): array
{
//  $u = str_replace('&amp;', '&', $u);
	$parts = parse_url($u);
	$u = $parts['scheme'].'://'.$parts['host'].$parts['path'];
	if ($parts['query']) {
		$ob = new stdClass();
		$parts = explode('&', $parts['query']);
		foreach ($parts as $one) {
			if (strpos($one, '=') !== false) {
				[$k, $v] = explode('=', $one);
				if (is_numeric($v)) {
					$ob->$k = $v;
				} else {
					$ob->$k = "'".addcslashes($v, "'")."'";
				}
			} else {
				$k = trim($one);
				$ob->$k = '\1'; // replace this later
			}
		}
		$k = CMS_SECURE_PARAM_NAME;
		$ob->$k = 'cms_data.user_key';
		$s = json_encode($ob);
		$s = str_replace(['{"', '"}', '":"', '","', '\\\\1'], ["{\n  ", "\n }", ': ', ",\n  ", "''"], $s);
		return [$u, $s];
	}
	return [$u, '{}'];
}

$have_locks = 0; // default value, adjusted downstream
$firstlist = 1; // status indicator for included code
// used by included code
$tpl = $smarty->createTemplate($this->GetTemplateResource('defaultadmin.tpl')); //,null,null,$smarty);

require __DIR__.DIRECTORY_SEPARATOR.'action.ajax_get_content.php';

if (isset($curpage)) {
	$_SESSION[$modname.'_curpage'] = $curpage;
}

$find_url = $this->create_action_url($id, 'ajax_pagelookup', ['forjs' => 1, CMS_JOB_KEY => 1]);

$url = $this->create_action_url($id, 'ajax_get_content', ['forjs' => 1, CMS_JOB_KEY => 1]);
[$page_url, $page_data] = urlsplit($url);

$url = $this->create_action_url($id, 'ajax_check_locks', ['forjs' => 1, CMS_JOB_KEY => 1]);
[$watch_url, $watch_data] = urlsplit($url);

$securekey = CMS_SECURE_PARAM_NAME;
$jobkey = CMS_JOB_KEY;

//TODO any other action-specific js
//TODO flexbox css for multi-row .colbox, .rowbox.flow, .boxchild

$s1 = addcslashes($this->Lang('confirm_setinactive'), "'\n\r");
$s2 = addcslashes($this->Lang('confirm_setdefault'), "'\n\r");
$s3 = addcslashes($this->Lang('confirm_delete_page'), "'\n\r");
$s4 = addcslashes($this->Lang('confirm_steal_lock'), "'\n\r");
$s5 = addcslashes($this->Lang('error_contentlocked'), "'\n\r");
$s8 = addcslashes($this->Lang('confirm_clearlocks'), "'\n\r");
$s9 = addcslashes($this->Lang('error_action_contentlocked'), "'\n\r");
$submit = $this->Lang('submit');
$cancel = $this->Lang('cancel');
$secs = AppParams::get('lock_refresh', 120);
$secs = max(30, min(600, $secs));

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery.SSsort.js',1);
$jsm->queue_matchedfile('jquery.cmsms_poll.js', 2);
$jsm->queue_matchedfile('jquery.ContextMenu.js', 2);

$out = $jsm->page_content();
if ($out) {
	add_page_foottext($out);
}
/* replaced
 $('#ajax_find').autocomplete({
  source: '$find_url', //TODO widget expects only array|string|function?
  minLength: 2,
  position: {
   my: 'right top',
   at: 'right bottom'
  },
  change: function(e, ui) {
   // goes back to the full list, no options
   $('#ajax_find').val('');
   Poller.request(refresher);
  },
  select: function(e, ui) {
   e.preventDefault();
   $(this).val(ui.item.label);
   var params = $.extend({}, pagedata, {
     {$id}seek: ui.item.value,
   });
   Poller.oneshot({
    url: pageurl,
    data: params,
    done_handler: function() {
     Poller.request(refresher).done(function() {
      $('html,body').animate({
       scrollTop: $('#row_' + ui.item.value).offset().top
      });
     });
    }
   });
  }
 });
*/

$js = <<<EOS
<script>
var pageurl = '$page_url',
 pagedata = $page_data,
 refresher,watcher;
function decode(str) {
  return str.trim()
   .replace(/[ +]/g, '%20')
   .replace(/(%[a-f0-9]{2})+/ig, function(match) {
     return decodeURIComponent(match);
   });
}
function process_link(link, prompt) {
 $(link).on('click', function(e) {
  e.preventDefault();
  var params = $.extend({}, pagedata, {
    {$id}ajax: 1,
  });
  var url = this.href;
  if(url.indexOf('=') !== -1) {
    url = this.origin + this.pathname;
    //pending widespread new URLSearchParams(this.search)
    var props = this.search.split('&');
    if (props[0].indexOf('?') === 0) {
      props[0] = props[0].substring(1);
    }
    for(var i = 0; i < props.length; i++) {
      var value = props[i],
        index = value.indexOf('='),
        nm;
      if (index !== -1) {
        nm = decode(value.substring(0, index));
        params[nm] = decode(value.substring(index + 1));
      } else if (value) {
        nm = decode(value);
        params[nm] = '';
      }
    }
  }
  var _do_ajax = function() {
   $.ajax(url, {
     data: params
   }).done(function() {
     Poller.request(refresher);
   });
  };
  $('#ajax_find').val('');
  if(typeof prompt === 'string' && prompt.length > 0) {
    cms_confirm(prompt).done(_do_ajax);
  } else {
    _do_ajax();
  }
  return false;
 });
}
function gethelp(tgt) {
  var p = tgt.parentNode,
    s = $(p).attr('data-cmshelp-key');
  if (s) {
   $.get(cms_data.ajax_help_url, {
    key: s
   }, function(text) {
    s = $(p).attr('data-cmshelp-title');
    var data = {
     cmshelpTitle: s
    };
    cms_help(tgt, data, text);
   });
  }
}
function adjust_locks(json) {
  var lockdata = JSON.parse(json);
  if ($.isEmptyObject(lockdata)) {
    return 0;
  }
  var n = 0;
  $('#contenttable > tbody > tr').each(function() {
    var row = $(this),
     id = row.attr('data-id');
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
      row.find('.action').prop('disabled',false).css('pointer-events','auto');
      row.removeClass('locked');
    }
  });
  return n;
}
function setuplist(pause) {
 $('#contenttable').SSsort({
  sortClass: 'SortAble',
  ascClass: 'SortUp',
  descClass: 'SortDown',
  oddClass: 'row1',
  evenClass: 'row2',
  oddsortClass: 'row1s',
  evensortClass: 'row2s'
 });
 var el = $('#bulkaction');
 el.prop('disabled',true);
 var btn = $('#bulk_submit');
 cms_button_able(btn,false);
 var cb = $('#contenttable > tbody input:checkbox');
 cb.on('change', function() {
  var l = cb.filter(':checked').length;
  if(l > 0) {
   el.prop('disabled',false);
  } else {
   el.prop('disabled',true);
  }
  cms_button_able(btn,(l > 0));
 });
 $('#selectall').on('change', function() {
  cb.prop('checked',(this.checked || false)).eq(0).trigger('change');
 });
 $('[context-menu]').ContextMenu();
 $('#ajax_find').on('keypress', function(e) {
   if(e.which == 13) {
    e.preventDefault();
    var p = $(this).val().trim();
    if(p.length < 3) {
      $(this).val('');
      if(pause) { // pause normally undefined
        //resume normal polling
        Poller.request(refresher);
        pause = false;
      }
    } else {
      var params = $.extend({}, pagedata, {
        {$id}ajax: 1,
        {$id}search: p
      });
      Poller.oneshot({
        url: pageurl,
        data: params,
        done_handler: function(content, textStatus, jqXHR) {
          $('#content_area').html(content);
          $('html,body').animate({
            scrollTop: 0
          });
          setuplist(true); //recurse, signalling paused
        }
      });
    }
   }
 });
 $('#filterdisplay').on('click', function() {
  cms_dialog($('#filterdialog'), {
   minWidth: 400,
   minHeight: 225,
   resizable: false,
   buttons: {
    '$submit': function() {
     $(this).dialog('close');
     $('#filter_form').submit();
    },
    '$cancel': function() {
     $(this).dialog('close');
    }
   }
  });
  return false;
 });
/* these links can't use ajax as they affect pagination
 process_link('a.expandall');
 process_link('a.collapseall');
 process_link('a.page_collapse');
 process_link('a.page_expand');
*/
 process_link('a.page_sortup');
 process_link('a.page_sortdown');
 process_link('a.page_setinactive', '$s1');
 process_link('a.page_setactive');
 process_link('a.page_setdefault', '$s2');
 process_link('a.page_delete', '$s3');

 $('a.steal_lock').on('click', function(e) {
  // we're gonna confirm stealing this lock
  e.preventDefault();
  var url = this.href;
  cms_confirm('$s4').done(function() {
   window.location.href = url + '&{$id}steal=1';
  });
  return false;
 });

 $('a.page_edit').on('click', function(e) {
  var v = $(this).data('steal_lock');
  $(this).removeData('steal_lock');
  if(typeof(v) !== 'undefined' && v !== null && !v) return false;
  if(typeof(v) === 'undefined' || v !== null) return true;
  e.preventDefault();
  var url = this.href,
   content_id = this.getAttribute('data-cms-content'),
   lockurl = 'ajax_lock.php',
   parms = {
    $securekey: cms_data.user_key,
    $jobkey: 1,
    dataType: 'json',
    op: 'check',
    type: 'content',
    oid: content_id
   };
  // double-check whether this page is locked
  $.ajax(lockurl, {
   method: 'POST',
   data: parms
  }).done(function(data) {
   if(data.status == 'success') {
     if(data.stealable) {
       cms_confirm('$s4').done(function() {
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
       cms_alert('$s5');
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
}

$(function() {
/*$.fn.metadata.defaults = {
  type: 'attr',
  name: 'ssmeta',
  ldelim: '#',
  rdelim: '#'
 };
*/
 $.fn.SSsort.addParser({
  id: 'icon',
  is: function(s,node) {
   return true;
  },
  format: function(s,node) {
   var el = node.getElementsByTagName('a');
   if (el.length > 0) {
    return el[0].innerHTML;
   }
   return node.innerHTML;
  },
  watch: false,
  type: 'text'
 });
 $.fn.SSsort.addParser({
  id: 'linktext',
  is: function(s,node) {
   return node.getElementsByTagName('a').length > 0
  },
  format: function(s,node) {
   return node.getElementsByTagName('a')[0].textContent;
  },
  watch: false,
  type: 'text'
 });
 refresher = Poller.add({
  url: pageurl,
  data: pagedata,
  onetime: true,
  insert: true,
  element: $('#content_area'),
  done_handler: setuplist
 });
 setuplist();
 watcher = Poller.run({
  url: '$watch_url',
  data: $watch_data,
  interval: $secs,
  done_handler: adjust_locks
 });
 // stuff not affected by page-refresh TODO confirm this
 // filter dialog
 $('#filter_type').on('change', function() {
  var map = {
   'TEMPLATE_ID': '#filter_template',
   'OWNER_UID': '#filter_owner',
   'EDITOR_UID': '#filter_editor'
  };
  var v = $(this).val();
  $('.filter_fld').hide();
  $(map[v]).show();
 });
 $('#filter_type').trigger('change');
 // other events
 // go to page on option change
 $('#{$id}curpage').on('change', function() {
  $(this).closest('form').submit();
 });
 $(document).ajaxComplete(function() {
  $('tr.selected').css('background', 'yellow');
 });
 $('a#clearlocks').on('click', function(e) {
  e.preventDefault();
  cms_confirm_linkclick(this, '$s8');
  return false;
 });
 $('a#ordercontent').on('click', function(e) {
  var have_locks = $have_locks;
  if(!have_locks) {
   e.preventDefault();
   var url = this.href,
    lockurl = 'ajax_lock.php',
    parms = {
     $securekey: cms_data.user_key,
     $jobkey: 1,
     dataType: 'json',
     op: 'check',
     type: 'content'
    };
   // double check whether any page is locked
   $.ajax(lockurl, {
    method: 'POST',
    data: parms
   }).done(function(data) {
    if(data.status == 'success') {
     if(data.stealable) {
      cms_confirm('$s4').done(function() {
       parms.op = 'unlock';
 // TODO remove single anonymous lock
       parms.lock_id = data.lock_id;
// TODO security : parms.X = Y suitable for ScriptsMerger
       $.ajax(lockurl, {
        method: 'POST',
        data: parms
       });
       window.location.href = url;
      });
     } else if(data.locked) {
      cms_alert('$s9');
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
  }
 });
});
</script>
EOS;
add_page_foottext($js); //NOT for ScriptsMerger

if (!isset($pmanage)) {
	// this should have been set in the included list-populator file
	$pmanage = $this->CheckPermission('Manage All Content');
}

if ($pmanage) {
	// filter-selector items
	$opts = ['' => $this->Lang('none'),
//	'DESIGN_ID' => $this->Lang('prompt_design'),
	'TEMPLATE_ID' => $this->Lang('prompt_template'),
	'OWNER_UID' => $this->Lang('prompt_owner'),
	'EDITOR_UID' => $this->Lang('prompt_editor')];
	$tpl->assign('opts', $opts);
	$templates = TemplateOperations::template_query(['originator' => TemplateType::CORE, 'as_list' => 1]);
	if ($templates) {
		natcasesort($templates);
	}
	$eds = Lone::get('UserOperations')->GetList();
	if ($eds) {
/* TODO
		$eds = utf8_sort($eds, true); after Collator setup fixed ...
OR		$in = 'UTF-8'; $out = 'ASCII//TRANSLIT//IGNORE';
		uasort($eds, function (/*mixed * /$a, /*mixed * /$b) use ($in, $out): int {
			$a = @iconv($in, $out, $a);
			$b = @iconv($in, $out, $b);
			return strnatcasecmp($a, $b);
		});
*/
		natcasesort($eds);
	}
	$tpl->assign('template_list', $templates)
		->assign('user_list', $eds);
	// list of designs for filtering
//		->assign('design_list',DesignManager\Design::get_list()) TODO replacement :: stylesheets and/or groups
}

$tpl->display();