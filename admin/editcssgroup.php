<?php
/*
Edit an existing or new stylesheets-group
Copyright (C) 2019-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\Error403Exception;
use CMSMS\ScriptsMerger;
use CMSMS\Lone;
use CMSMS\StylesheetOperations;
use CMSMS\StylesheetsGroup;
use function CMSMS\add_shutdown;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid();
if (!check_permission($userid, 'Manage Stylesheets')) {
	throw new Error403Exception(_la('permissiondenied'));
//TODO OR some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage_Groups')), ...);
// OR display error.tpl ?
}

$urlext = get_secure_param();
$themeObject = Lone::get('Theme');
if (isset($_REQUEST['cancel'])) {
	$themeObject->ParkNotice('info', _ld('layout', 'msg_cancelled'));
	redirect('liststyles.php'.$urlext.'&_activetab=groups');
}

de_specialize_array($_REQUEST);
if (!empty($_REQUEST['grp'])) { //name or numeric id
	$id = (is_numeric($_REQUEST['grp'])) ? (int)$_REQUEST['grp'] : sanitizeVal($_REQUEST['grp'], CMSSAN_NAME);
	try {
		if (!(is_numeric($id) || AdminUtils::is_valid_itemname($id))) {
			throw new Exception(_la('errorbadname'));
		}
		$group = StylesheetsGroup::load($id);
		$gid = $group->get_id();
		$group_members = $group->get_members(true); // as id=>name
	} catch (Throwable $t) {
		$themeObject->ParkNotice('error', $t->GetMessage());
		redirect('liststyles.php'.$urlext.'&_activetab=groups');
	}
} else {
	$group = new StylesheetsGroup();
	$gid = 0;
	$group_members = [];
}

try {
	if (isset($_REQUEST['dosubmit'])) {
		$name = sanitizeVal(trim($_REQUEST['name']), CMSSAN_NAME);
		if (!AdminUtils::is_valid_itemname($name)) {
			throw new Exception(_la('errorbadname'));
		}
		$group->set_name($name);
		$desc = sanitizeVal(trim($_REQUEST['description']), CMSSAN_NONPRINT); // AND nl2br() ? striptags() other than links?
		// revert any munged textarea tag
		$matches = [];
		$desc = preg_replace_callback('~&lt;(&sol;|&#47;)?(textarea)&gt;~i', function($matches) {
			$pre = ($matches[1]) ? '/' : '';
			return '<'.$pre.$matches[2].'>';
		}, $desc);
		$group->set_description($desc);
		if ($_REQUEST['member']) { // e.g. [12 => '1', 4 => '0']
			$tmp = array_keys(array_filter($_REQUEST['member']));
			$group->set_members($tmp);
		}
		$group->save();
		$themeObject->ParkNotice('success', _ld('layout', 'group_saved'));
		redirect('liststyles.php'.$urlext.'&_activetab=groups');
	}
} catch (Throwable $t) {
	$themeObject->RecordNotice('error', $t->GetMessage());
}

$props = [
	'id' => $gid,
	'name' => $group->get_name(),
	'description' => $group->get_description(),
];

if ($props['description']) {
	// specialize e.g. munge malicious <[/]textarea> tags
	$matches = [];
	$props['description'] = preg_replace_callback('~<\s*(/?)\s*(textarea)\s*>~i', function($matches) {
		$pre = ($matches[1]) ? '&sol;' : ''; // OR &#47;
		return '&lt;'.$pre.$matches[2].'&gt;';
	}, $props['description']);
}

$lock_timeout = AppParams::get('lock_timeout', 60);
$do_locking = ($gid > 0 && $lock_timeout > 0) ? 1 : 0;
if ($do_locking) {
	add_shutdown(10, 'LockOperations::delete_for_nameduser', $userid);
}

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
if ($do_locking) {
	$jsm->queue_matchedfile('jquery.cmsms_lock.js', 2);
}
$js = $jsm->page_content();
if ($js) {
	add_page_foottext($js);
}

//$nonce = get_csp_token();
$lock_refresh = AppParams::get('lock_refresh', 120);
$s1 = addcslashes(_ld('layout', 'error_lock'), "'\n\r");
$s2 = addcslashes(_ld('layout', 'msg_lostlock'), "'\n\r");
$cancel = _la('cancel');

$js = <<<EOS
<script>
$(function() {
  var do_locking = $do_locking;
  if(do_locking) {
   // initialize lock manager
   $('#edit_group').lockManager({
     type: 'stylesheetgroup',
     oid: $gid,
     uid: $userid,
     lock_timeout: $lock_timeout,
     lock_refresh: $lock_refresh,
     error_handler: function(err) {
       cms_alert('{$s1} ' + err.type + ' // ' + err.msg);
     },
     lostlock_handler: function(err) {
      // we lost the lock on this type ... block saving and display a nice message.
      $('#cancelbtn').fadeOut().attr('value','$cancel').fadeIn();
      $('#edit_group').dirtyForm('option','dirty',false);
      cms_button_able($('#submitbtn,#applybtn'),false);
      $('.lock-warning').removeClass('hidden-item');
      cms_alert('$s2');
     }
   });
  }
  $('#edit_group').dirtyForm({
    beforeUnload: function() {
      if(do_locking) $('#edit_group').lockManager('unlock');
    },
    unloadCancel: function() {
      if(do_locking) $('#edit_group').lockManager('relock');
    }
  });
  $('#submitbtn').on('click',function(ev) {
    $('#edit_group').dirtyForm('option','dirty',false);
  });

  var tbl = $('.draggable'),
   tbod = tbl.find('tbody.rsortable');
  //hide placeholder in tbods with extra rows
  tbod.each(function() {
   var t = $(this);
   if(t.children('tr').length > 1) {
    t.children('tr.placeholder').css('display','none');
   }
  });

  tbod.sortable({
   connectWith: '.rsortable',
   items: '> tr:not(".placeholder")',
   appendTo: tbl,
//  helper: 'clone',
   zIndex: 9999
  }).disableSelection();

  tbl.droppable({
   accept: '.rsortable tr',
   hoverClass: 'ui-state-hover',//TODO
   drop: function(ev,ui) {
    //update submittable dropped hidden input
    var row = ui.draggable[0],
     srcbody = row.parentElement || row.parentNode,
     inp = $(row).find('input[type="hidden"]'),
     state = ($(this).hasClass('selected')) ? 1 : 0;
    inp.val(state);
    //adjust display of tbod placeholder rows
    tbod.each(function() {
     var t = $(this),
      len = t.children('tr').length;
     row = t.children('tr.placeholder');
     if(len < 2) {
      row.css('display','table');
     } else if(len === 2 && this === srcbody) {
      row.css('display','table');
     } else {
      row.css('display','none');
     }
    });
    $('#edit_group').dirtyForm('option','dirty',true);
    return false;
   }
  });
});
</script>

EOS;
add_page_foottext($js);

$all_items = StylesheetOperations::get_all_stylesheets(true);
$legend1 = _ld('layout', 'prompt_members');
$legend2 = _ld('layout', 'prompt_nonmembers');
$placeholder = _ld('layout', 'table_droptip');
$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
if ($gid) {
	$extras['grp'] = $gid;
}

$smarty = Lone::get('Smarty');
$smarty->assign([
	'selfurl' => $selfurl,
	'extraparms' => $extras,
	'urlext' => $urlext,
	'group' => $props,
	'group_items' => $group_members,
	'all_items' => $all_items,
	'attached_legend' => $legend1,
	'unattached_legend' => $legend2,
	'placeholder' => $placeholder,
]);

$content = $smarty->fetch('editcssgroup.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
