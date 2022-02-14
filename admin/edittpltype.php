<?php
/*
Edit template type
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
use CMSMS\Error403Exception;
use CMSMS\ScriptsMerger;
use CMSMS\SingleItem;
use CMSMS\TemplateType;
use function CMSMS\add_shutdown;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid();
if( !check_permission($userid, 'Modify Templates') ) {
//TODO some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage_Groups')), ...);
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$urlext = get_secure_param();
$themeObject = SingleItem::Theme();

if( isset($_REQUEST['cancel']) ) {
	$themeObject->ParkNotice('info', _ld('layout', 'msg_cancelled'));
	redirect('listtemplates.php'.$urlext.'&_activetab=types');
}

if( !isset($_REQUEST['type']) ) {
	$themeObject->ParkNotice('error', _ld('layout', 'error_missingparam'));
	redirect('listtemplates.php'.$urlext.'&_activetab=types');
}

de_specialize_array($_REQUEST);
if( is_numeric($_REQUEST['type']) ) {
	$id = (int)$_REQUEST['type'];
}
else {
	// string identifier should be like originator::name
	$id = sanitizeVal($_REQUEST['type'], CMSSAN_PUNCTX, ':'); // allow '::'
}
try {
	$type = TemplateType::load($id);

	if( isset($_REQUEST['reset']) ) {
		$type->reset_content_to_factory();
		$type->save();
	}
	elseif( isset($_REQUEST['dosubmit']) ) {
		if( isset($_REQUEST['dflt_content']) ) {
			//TODO how to sanitize template content?
			$type->set_dflt_contents($_REQUEST['dflt_content']);
		}
		$desc = trim($_REQUEST['description']); // AND sanitizeVal(, CMSSAN_NONPRINT) ? nl2br() ? striptags() ?
		$type->set_description($desc);
		$type->save();

		$themeObject->ParkNotice('success', _ld('layout', 'msg_type_saved'));
		redirect('listtemplates.php'.$urlext.'&_activetab=types');
	}

//	$nonce = get_csp_token();
	$type_id = $type->get_id();
	$lock_timeout = AppParams::get('lock_timeout', 60);
	$do_locking = ($type_id > 0 && $lock_timeout > 0) ? 1 : 0;
	if( $do_locking ) {
		add_shutdown(10, 'LockOperations::delete_for_nameduser', $userid);
	}
	$lock_refresh = AppParams::get('lock_refresh', 120);
	$s1 = json_encode(_ld('layout', 'error_lock'));
	$s2 = json_encode(_ld('layout', 'msg_lostlock'));
	$s3 = json_encode(_ld('layout', 'confirm_reset_type'));
	$cancel = _la('cancel');

	$jsm = new ScriptsMerger();
	$jsm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
	if( $do_locking ) {
		$jsm->queue_matchedfile('jquery.cmsms_lock.js', 2);
	}
	$js = $jsm->page_content();
	if( $js ) {
		add_page_foottext($js);
	}

	$pageincs = get_syntaxeditor_setup(['edit'=>true, 'htmlid'=>'edit_area', 'typer'=>'smarty']);
	if( !empty($pageincs['head']) ) {
		add_page_headtext($pageincs['head']);
	}

	$js = $pageincs['foot'] ?? '';
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
     cms_button_able($('#submitbtn, #applybtn'), false);
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
   cms_confirm_btnclick(this, $s3);
   return false;
 });
 $('#submitbtn, #applybtn, #cancelbtn').on('click', function(ev) {
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
	add_page_foottext($js);

	$selfurl = basename(__FILE__);
	$extras = get_secure_param_array();

	$smarty = SingleItem::Smarty();
	$smarty->assign([
	 'selfurl' => $selfurl,
	 'urlext' => $urlext,
	 'extraparms' => $extras,
	 'type' => $type,
	 ]);

	$content = $smarty->fetch('edittpltype.tpl');
	$sep = DIRECTORY_SEPARATOR;
	require ".{$sep}header.php";
	echo $content;
	require ".{$sep}footer.php";
}
catch( Throwable $t ) {
	$themeObject->ParkNotice('error', $t->GetMessage());
	redirect('listtemplates.php'.$urlext.'&_activetab=types');
}
