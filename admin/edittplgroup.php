<?php
/*
Edit an existing or new templates group
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Error403Exception;
use CMSMS\ScriptsMerger;
use CMSMS\TemplateOperations;
use CMSMS\TemplatesGroup;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$userid = get_userid();
//TODO new template-group-specific permission(s) ?
$pmod = check_permission($userid, 'Modify Templates');
$padd = $pmod || check_permission($userid, 'Add Template');
if (!$padd && empty($_REQUEST['grp'])) {
	return;
}
if (!$pmod) {
	throw new Error403Exception(lang('permissiondenied'));
//TODO OR some pushed popup c.f. javascript:cms_notify('error', lang('no_permission') OR lang('needpermissionto', lang('perm_Manage_Groups')), ...);
// OR display error.tpl ?
}

$urlext = get_secure_param();
$themeObject = AppSingle::Theme();

if (isset($_REQUEST['cancel'])) {
	$themeObject->ParkNotice('info', lang_by_realm('layout', 'msg_cancelled'));
	redirect('listtemplates.php'.$urlext.'&_activetab=groups');
}

de_specialize_array($_REQUEST);
if (!empty($_REQUEST['grp'])) {
	$id = (is_numeric($_REQUEST['grp'])) ? (int)$_REQUEST['grp'] : sanitizeVal($_REQUEST['grp'], CMSSAN_NAME);
	try {
		if (!(is_numeric($id) || AdminUtils::is_valid_itemname($id))) {
			throw new Exception(lang('errorbadname'));
		}
		$group = TemplatesGroup::load($id);
		$gid = $group->get_id();
		$group_members = $group->get_members(true); // as id=>name
	} catch (Throwable $t) {
		$themeObject->ParkNotice('error', $t->GetMessage());
		redirect('listtemplates.php'.$urlext.'&_activetab=groups');
	}
} else {
	$group = new TemplatesGroup();
	$gid = 0;
	$group_members = [];
}

try {
	if (isset($_REQUEST['dosubmit'])) {
		$name = sanitizeVal($_REQUEST['name'], CMSSAN_NAME);
		if (!AdminUtils::is_valid_itemname($name)) {
			throw new Exception(lang('errorbadname'));
		}
		$group->set_name($name);
		$desc = sanitizeVal(trim($_REQUEST['description']), CMSSAN_NONPRINT); // AND nl2br() ? striptags() other than links ?
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
		$themeObject->ParkNotice('success', lang_by_realm('layout', 'group_saved'));
		redirect('listtemplates.php'.$urlext.'&amp_activetab=groups');
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
		$pre = ($matches[1]) ? '&sol;' : ''; // ?? OR &#47;
		return '&lt;'.$pre.$matches[2].'&gt;';
	}, $props['description']);
}

$lock_timeout = AppParams::get('lock_timeout', 60);
$do_locking = ($gid > 0 && $lock_timeout > 0) ? 1 : 0;
if ($do_locking) {
	AppSingle::App()->add_shutdown(10, 'LockOperations::delete_for_nameduser', $userid);
}

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
if ($do_locking) {
	$jsm->queue_matchedfile('jquery.cmsms_lock.js', 2);
}
$js = $jsm->page_content('', false, false);
if ($js) {
	add_page_foottext($js);
}

//$nonce = get_csp_token();
$lock_refresh = AppParams::get('lock_refresh', 120);
$s1 = json_encode(lang_by_realm('layout', 'error_lock'));
$s2 = json_encode(lang_by_realm('layout', 'msg_lostlock'));
$cancel = lang('cancel');

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 var do_locking = $do_locking;
 if(do_locking) {
  // initialize lock manager
  $('#edit_group').lockManager({
    type: 'templategroup',
    oid: $gid,
    uid: $userid,
    lock_timeout: $lock_timeout,
    lock_refresh: $lock_refresh,
    error_handler: function(err) {
      cms_alert($s1 + ' ' + err.type + ' // ' + err.msg);
    },
    lostlock_handler: function(err) {
     // we lost the lock on this type ... block saving and display a nice message.
     $('#cancelbtn').fadeOut().attr('value','$cancel').fadeIn();
     $('#edit_group').dirtyForm('option','dirty',false);
     cms_button_able($('#submitbtn,#applybtn'),false);
     $('.lock-warning').removeClass('hidden-item');
     cms_alert($s2);
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
 $('#submitbtn,#applybtn').on('click',function(ev) {
   $('#edit_group').dirtyForm('option','dirty',false);
 });
 var tbl = $('.draggable'),
  tbod = tbl.find('tbody.rsortable');
  //hide placeholder in tbods with extra rows
 tbod.each(function() {
   var t = $(this);
   if(t.find('>tr').length > 1) {
    t.find('>tr.placeholder').css('display','none');
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
      len = t.find('> tr').length;
     row = t.find('> tr.placeholder');
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
//]]>
</script>

EOS;
add_page_foottext($js);

$all_items = TemplateOperations::get_all_templates(true); //TODO include originator/type in the display
$legend1 = lang_by_realm('layout', 'prompt_members');
$legend2 = lang_by_realm('layout', 'prompt_nonmembers');
$placeholder = lang_by_realm('layout', 'table_droptip');
$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
if ($gid) {
	$extras['grp'] = $gid;
}

$smarty = AppSingle::Smarty();
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

$content = $smarty->fetch('edittplgroup.tpl');
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
