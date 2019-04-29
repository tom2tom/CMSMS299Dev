<?php
# Edit template type
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

use CMSMS\LockOperations;
use CMSMS\ScriptOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();

$userid = get_userid();
if( !check_permission($userid,'Modify Templates') ) {
	return;
}

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$themeObject = cms_utils::get_theme_object();

if( isset($_REQUEST['cancel']) ) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('listtemplates.php'.$urlext.'&_activetab=types');
}

if( !isset($_REQUEST['type']) ) {
	$themeObject->ParkNotice('error',lang_by_realm('layout','error_missingparam'));
	redirect('listtemplates.php'.$urlext.'&_activetab=types');
}

cleanArray($_REQUEST);

try {
	$type = CmsLayoutTemplateType::load($_REQUEST['type']);

	if( isset($_REQUEST['reset']) ) {
		$type->reset_content_to_factory();
		$type->save();
	}
	else if( isset($_REQUEST['dosubmit']) ) {
		if( isset($_REQUEST['dflt_contents']) ) {
			$type->set_dflt_contents($_REQUEST['dflt_contents']);
		}
		$type->set_description($_REQUEST['description']);
		$type->save();

		$themeObject->ParkNotice('info',lang_by_realm('layout','msg_type_saved'));
		redirect('listtemplates.php'.$urlext.'&_activetab=types');
	}

	$type_id = $type->get_id();
	$lock_timeout = cms_siteprefs::get('lock_timeout', 60);
	$do_locking = ($type_id > 0 && $lock_timeout > 0) ? 1 : 0;
	if( $do_locking ) {
		register_shutdown_function(function($u) {
          LockOperations::delete_for_nameduser($u);
        }, $userid);
	}
	$lock_refresh = cms_siteprefs::get('lock_refresh', 120);
	$s1 = json_encode(lang_by_realm('layout','error_lock'));
	$s2 = json_encode(lang_by_realm('layout','msg_lostlock'));
	$s3 = json_encode(lang_by_realm('layout','confirm_reset_type'));
	$cancel = lang('cancel');

	$sm = new ScriptOperations();
	$sm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
	if( $do_locking ) {
		$sm->queue_matchedfile('jquery.cmsms_lock.js', 2);
	}
	$js = $sm->render_inclusion('', false, false);
	if ($js) {
		$themeObject->add_footertext($js);
	}

	$content = get_editor_script(['edit'=>true, 'htmlid'=>'content', 'typer'=>'smarty']);
	if (!empty($content['head'])) {
		$themeObject->add_headtext($content['head']);
	}
	$js = $content['foot'] ?? '';

	$js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 var do_locking = $do_locking;
 if(do_locking) {
  // initialize lock manager
  $('#form_edittype').lockManager({
    type: 'templatetype',
    oid: $type_id,
    uid: $userid,
    lock_timeout: $lock_timeout,
    lock_refresh: $lock_refresh,
    error_handler: function(err) {
      cms_alert($s1 + ' ' + err.type + ' // ' + err.msg);
    },
    lostlock_handler: function(err) {
     // we lost the lock on this type ... block saving and display a nice message.
     $('#cancelbtn').fadeOut().attr('value', '$cancel').fadeIn();
     $('#form_edittype').dirtyForm('option', 'dirty', false);
     $('#submitbtn, #applybtn').attr('disabled', 'disabled');
     $('.lock-warning').removeClass('hidden-item');
     cms_alert($s2);
    }
  });
 }
 $('#form_edittype').dirtyForm({
   beforeUnload: function() {
    if(do_locking) $('#form_edittype').lockManager('unlock');
   },
   unloadCancel: function() {
    if(do_locking) $('#form_edittype').lockManager('relock');
   }
 });
 $('[name="reset"]').on('click', function(ev) {
   ev.preventDefault();
   cms_confirm_btnclick(this,$s3);
   return false;
 });
 $('#submitbtn,#applybtn,#cancelbtn').on('click', function(ev) {
   if (this.id !== 'cancelbtn') {
     var v = geteditorcontent();
     setpagecontent(v);
     $('#form_edittype').dirtyForm('option', 'dirty', false);
   }
 });
});
//]]>
</script>

EOS;
	$themeObject->add_footertext($js);

	$selfurl = basename(__FILE__);
	$extraparms = [CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY]];

	$smarty = CmsApp::get_instance()->GetSmarty();
	$smarty->assign('type',$type)
	 ->assign('selfurl',$selfurl)
	 ->assign('urlext',$urlext)
	 ->assign('extraparms',$extraparms);

	include_once 'header.php';
	$smarty->display('edittpltype.tpl');
	include_once 'footer.php';
}
catch( CmsException $e ) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('listtemplates.php'.$urlext.'&_activetab=types');
}
