<?php
# Edit a templates group
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

$userid = get_userid();
$pmod = check_permission($userid,'Modify Templates');
$padd = $pmod || check_permission($userid,'Add Template');
if (!$padd && empty($_REQUEST['tpl'])) {
	return;
}
if (!$pmod) {
	return;
}

$urlext = get_secure_param();
$themeObject = cms_utils::get_theme_object();

if (isset($_REQUEST['cancel'])) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('listtemplates.php'.$urlext.'&_activetab=groups');
}

cleanArray($_REQUEST);

try {
	if (!empty($_REQUEST['tpl'])) {
		$group = CmsLayoutTemplateCategory::load(trim($_REQUEST['tpl']));
		$gid = $group->get_id();
		$group_members = $group->get_members(true); // as id=>name
	}
	else {
		$group = new CmsLayoutTemplateCategory();
		$gid = 0;
		$group_members = [];
	}
}
catch( CmsException $e) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('listtemplates.php'.$urlext.'&_activetab=groups');
}

try {
	if (isset($_REQUEST['dosubmit'])) {
		$group->set_name(strip_tags($_REQUEST['name'])); // or filter_var()
		$group->set_description(strip_tags($_REQUEST['description'])); // or filter_var()
		//TODO process members
		$group->save();
		$themeObject->ParkNotice('info',lang_by_realm('layout','group_saved'));
		redirect('listtemplates.php'.$urlext.'&_activetab=groups');
	}
}
catch( CmsException $e ) {
	$themeObject->RecordNotice('error',$e->GetMessage());
}

$lock_timeout = cms_siteprefs::get('lock_timeout', 60);
$do_locking = ($gid > 0 && $lock_timeout > 0) ? 1 : 0;
if ($do_locking) {
	register_shutdown_function(function($u) {
		LockOperations::delete_for_nameduser($u);
	}, $userid);
}
$lock_refresh = cms_siteprefs::get('lock_refresh', 120);
$s1 = json_encode(lang_by_realm('layout','error_lock'));
$s2 = json_encode(lang_by_realm('layout','msg_lostlock'));
$cancel = lang('cancel');

$sm = new ScriptOperations();
$sm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
if ($do_locking) {
	$sm->queue_matchedfile('jquery.cmsms_lock.js', 2);
}
$js = $sm->render_inclusion('', false, false);
if ($js) {
	$themeObject->add_footertext($js);
}

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
     $('#cancelbtn').fadeOut().attr('value', '$cancel').fadeIn();
     $('#edit_group').dirtyForm('option', 'dirty', false);
     cms_button_able($('#submitbtn, #applybtn'), false);
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
 $('#submitbtn,#applybtn').on('click', function(ev) {
   $('#edit_group').dirtyForm('option', 'dirty', false);
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
  hoverClass: 'ui-state-hover', //TODO
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
    $('#edit_group').dirtyForm('option', 'dirty', true);
    return false;
  }
 });
});
//]]>
</script>

EOS;
$themeObject->add_footertext($js);

$all_items = TemplateOperations::get_all_templates(true); //TODO include originator/type in the display
$legend1 = lang_by_realm('layout','prompt_members');
$legend2 = lang_by_realm('layout','prompt_nonmembers');
$placeholder = lang_by_realm('layout','table_droptip');
$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
if ($gid) {
	$extras['tpl'] = $gid;
}

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
	'selfurl'=>$selfurl,
	'extraparms'=>$extras,
	'urlext'=>$urlext,
	'group'=>$group,
	'group_items'=>$group_members,
	'all_items'=>$all_items,
	'attached_legend'=>$legend1,
	'unattached_legend'=>$legend2,
	'placeholder'=>$placeholder,
]);

include_once 'header.php';
$smarty->display('edittplgroup.tpl');
include_once 'footer.php';
