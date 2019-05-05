<?php
# Edit template
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

use CMSMS\CmsLockException;
use CMSMS\Lock;
use CMSMS\LockOperations;
use CMSMS\ScriptOperations;
use CMSMS\TemplateOperations;
use DesignManager\utils;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();

$urlext = get_secure_param();
$themeObject = cms_utils::get_theme_object();

if (isset($_REQUEST['cancel'])) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('listtemplates.php'.$urlext);
}

$userid = get_userid();
$pmod = check_permission($userid,'Modify Templates');

if (!$pmod) {
	// no manage templates permission
	if (!check_permission($userid,'Add Templates')) {
		// no add templates permission
		if (!isset($_REQUEST['tpl']) || !TemplateOperations::user_can_edit_template($_REQUEST['tpl'])) {
			// no parameter, or no ownership/addt_editors.
			return;
		}
	}
}

$type_is_readonly = false;
$response = 'success';
$apply = isset($_REQUEST['apply']);

try {
	$extraparms = [CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY]];
	if (isset($_REQUEST['import_type'])) {
		$tpl_obj = TemplateOperations::get_template_by_type($_REQUEST['import_type']);
		$tpl_obj->set_owner(get_userid());
/*        $design = DesignManager\Design::load_default(); DISABLED
		if ($design) {
			$tpl_obj->add_design($design);
		}
*/
		$extraparms['import_type'] = $_REQUEST['import_type'];
		$type_is_readonly = true;
	} else if (isset($_REQUEST['tpl'])) {
		$tpl_obj = TemplateOperations::get_template($_REQUEST['tpl']);
/*        $tpl_obj->get_designs(); */
		$extraparms['tpl'] = $_REQUEST['tpl'];
	} else {
		$tpl_obj = new CmsLayoutTemplate();
	}

	$type_id = $tpl_obj->get_type_id();
	if ($type_id) {
		try {
			$type_obj = CmsLayoutTemplateType::load();
		} catch (Throwable $t) {
			$type_obj = null;
		}
	} else {
		$type_obj = null;
	}

	try {
// TODO sanitize relevant $_REQUEST[] - NOT content
		if ($apply || isset($_REQUEST['dosubmit'])) {
			// do the magic.
			if (isset($_REQUEST['description'])) $tpl_obj->set_description($_REQUEST['description']);
			if (isset($_REQUEST['type'])) $tpl_obj->set_type($_REQUEST['type']);
			$tpl_obj->set_type_dflt($_REQUEST['default'] ?? 0);
			if (isset($_REQUEST['owner_id'])) $tpl_obj->set_owner($_REQUEST['owner_id']);
			if (isset($_REQUEST['addt_editors']) && $_REQUEST['addt_editors']) {
				$tpl_obj->set_additional_editors($_REQUEST['addt_editors']); //TODO support clearance
			}
/*			if (!empty($_REQUEST['category_id'])) $tpl_obj->set_category($_REQUEST['category_id']); //TODO support multiple categories
			$tpl_obj->set_listable($_REQUEST['listable'] ?? 0);
//TODO      $tpl_obj->set_content_file($_REQUEST['contentfile'] ?? 0);
			$old_export_name = $tpl_obj->get_content_filename();
			$tpl_obj->set_name($_REQUEST['name']);
			$new_export_name = $tpl_obj->get_content_filename();
			if ($old_export_name != $new_export_name && is_file( $old_export_name)) {
				if (is_file( $new_export_name)) throw new Exception('Cannot rename exported template (destination name exists)');
				$res = rename($old_export_name,$new_export_name);
				if (!$res) throw new Exception( 'Problem renaming exported template');
			}
			if (check_permission($userid,'Manage Designs')) {
				$design_list = [];
				if (isset($_REQUEST['design_list'])) $design_list = $_REQUEST['design_list'];
				$tpl_obj->set_designs($design_list);
			}
*/
			// lastly, check for errors in the template before we save.
//USELESS FOR SUCH TEST cms_utils::set_app_data('tmp_template', $_REQUEST['contents']);

			// if we got here, we're golden.
		   	$tpl_obj->set_content($_REQUEST['contents']);
			TemplateOperations::save_template($tpl_obj);

			$message = lang_by_realm('layout','msg_template_saved');
			if ($apply) {
				$themeObject->RecordNotice('info',$message);
			}
			else {
				$themeObject->ParkNotice('info',$message);
				redirect('listtemplates.php'.$urlext);
			}
		}
/*        elseif (isset($_REQUEST['export'])) {
			$outfile = $tpl_obj->get_content_filename();
			$dn = dirname($outfile);
			if (!is_dir($dn) || !is_writable($dn)) throw new RuntimeException(lang_by_realm('layout','error_assets_writeperm'));
			if (is_file($outfile) && !is_writable($outfile)) throw new RuntimeException(lang_by_realm('layout','error_assets_writeperm'));
			file_put_contents($outfile,$tpl_obj->get_content());
		}
		elseif (isset($_REQUEST['import'])) {
			$infile = $tpl_obj->get_content_filename();
			if (!is_file($infile) || !is_readable($infile) || !is_writable($infile)) {
				throw new RuntimeException(lang_by_realm('layout','error_assets_readwriteperm'));
			}
			$data = file_get_contents($infile);
			unlink($infile);
			$tpl_obj->set_content($data);
			$tpl_obj->save();
		}
*/
	} catch( Throwable $t) {
		$message = $t->getMessage();
		$response = 'error';
	}

	//
	// prepare to display
	//
	if (!$apply && $tpl_obj && $tpl_obj->get_id() && utils::locking_enabled()) {
		$lock_timeout = cms_siteprefs::get('lock_timeout', 0);
		$lock_refresh = cms_siteprefs::get('lock_refresh', 0);
		try {
			$lock_id = LockOperations::is_locked('template', $tpl_obj->get_id());
			$lock = null;
			if ($lock_id > 0) {
				// it's locked... by somebody, make sure it's expired before we allow stealing it.
				$lock = Lock::load('template',$tpl_obj->get_id());
				if (!$lock->expired()) throw new CmsLockException('CMSEX_L010');
				LockOperations::unlock($lock_id,'template',$tpl_obj->get_id());
			}
		} catch( CmsException $e) {
			$message = $e->GetMessage();
			$themeObject->ParkNotice('error',$message);
			redirect('listtemplates.php'.$urlext);
		}
	} else {
		$lock_timeout = 0;
		$lock_refresh = 0;
	}

	// handle the response message
	if ($apply) {
		/**
		 * Return a JSON encoded response.
		 * @param  string $status The status of returned response : error, success, warning, info
		 * @param  string $message The message of returned response
		 * @param  mixed $data A string or array of response data
		 * @return string Returns a string containing the JSON representation of provided response data
		 */
		function GetJSONResponse($status, $message, $data = null)
		{
			if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

				$handlers = ob_list_handlers();
				for ($cnt = 0; $cnt < count($handlers); $cnt++) { ob_end_clean(); }

				header('Content-type:application/json; charset=utf-8');

				if ($data) {
					$json = json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
				} else {
					$json = json_encode(['status' => $status, 'message' => $message]);
				}

				echo $json;
				exit;
			}

			return false;
		}

		GetJSONResponse($response, $message);
	} elseif (!$apply && $response == 'error') {
		$themeObject->RecordNotice('error',$message);
	}

	if (($tpl_id = $tpl_obj->get_id()) > 0) {
		$themeObject->SetSubTitle(lang_by_realm('layout','prompt_edit_template').': '.$tpl_obj->get_name()." ($tpl_id)");
	} else {
		$tpl_id = 0;
		$themeObject->SetSubTitle(lang_by_realm('layout','create_template'));
	}

	$smarty = CmsApp::get_instance()->GetSmarty();

	$smarty->assign('type_obj', $type_obj)
	 ->assign('extraparms', $extraparms)
	 ->assign('template', $tpl_obj);

/* for 'related file' message UNUSED
  	if ($tpl_obj->get_content_file()) {
		$fn = $tpl_obj->content; // raw
		$filepath = cms_join_path('','assets','templates',$fn);
		$smarty->assign('relpath', $filepath);
	}
*/
	$grps = CmsLayoutTemplateCategory::get_all();
	$out = ['' => lang_by_realm('layout','prompt_none')];
	if ($grps) {
		foreach ($grps as $one) {
			$out[$one->get_id()] = $one->get_name();
		}
	}
	$smarty->assign('category_list', $out);

	$types = CmsLayoutTemplateType::get_all();
	if ($types) {
		$out = [];
		$out2 = [];
		foreach ($types as $one) {
			$out2[] = $one->get_id();
			$out[$one->get_id()] = $one->get_langified_display_value();
		}
		$smarty->assign('type_list', $out)
		 ->assign('type_is_readonly', $type_is_readonly);
	}
/*
	$designs = DesignManager\Design::get_all(); DISABLED
	if ($designs) {
		$out = [];
		foreach ($designs as $one) {
			$out[$one->get_id()] = $one->get_name();
		}
		$smarty->assign('design_list', $out);
	}
*/
	$smarty->assign('has_manage_right', $pmod);
//	 ->assign('has_themes_right', check_permission($userid,'Manage Designs'));

	if ($pmod || $tpl_obj->get_owner_id() == $userid) {
		$userops = cmsms()->GetUserOperations();
		$allusers = $userops->LoadUsers();
		$tmp = [];
		foreach ($allusers as $one) {
			//FIXME Why skip admin here? If template owner is admin this would unset admin as owner
			//if ($one->id == 1)
			//    continue;
			$tmp[$one->id] = $one->username;
		}
		if ($tmp) $smarty->assign('user_list', $tmp);

		$groupops = cmsms()->GetGroupOperations();
		$allgroups = $groupops->LoadGroups();
		foreach ($allgroups as $one) {
			if ($one->id == 1) continue;
			if ($one->active == 0) continue;
			$tmp[$one->id * -1] = lang_by_realm('layout','prompt_group') . ': ' . $one->name;
			// appends to the tmp array.
		}
		if ($tmp) $smarty->assign('addt_editor_list', $tmp);
	}
	$config = cms_config::get_instance();
	if (!empty($config['developer_mode'])) {
		$smarty->assign('devmode', 1);
	}

//TODO ensure flexbox css for .rowbox, .boxchild

	$sm = new ScriptOperations();
	$sm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
	$sm->queue_matchedfile('jquery.cmsms_lock.js', 2);
	$js = $sm->render_inclusion('', false, false);
	if ($js) {
		$themeObject->add_footertext($js);
	}

	$content = get_editor_script(['edit'=>true, 'htmlid'=>'content', 'typer'=>'smarty']);
	if (!empty($content['head'])) {
		$themeObject->add_headtext($content['head']);
	}

	$do_locking = ($tpl_id > 0 && isset($lock_timeout) && $lock_timeout > 0) ? 1 : 0;
	if ($do_locking) {
		register_shutdown_function(function($u) {
			LockOperations::delete_for_nameduser($u);
		}, $userid);
	}
	$s1 = json_encode(lang_by_realm('layout','error_lock'));
	$s2 = json_encode(lang_by_realm('layout','msg_lostlock'));
	$cancel = lang('cancel');

	$js = $content['foot'] ?? '';
	$js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  var do_locking = $do_locking;
  if(do_locking) {
    // initialize lock manager
    $('#form_edittemplate').lockManager({
      type: 'template',
      oid: $tpl_id,
      uid: $userid,
      lock_timeout: $lock_timeout,
      lock_refresh: $lock_refresh,
      error_handler: function(err) {
        cms_alert($s1 + ' ' + err.type + ' // ' + err.msg);
      },
      lostlock_handler: function(err) {
       // we lost the lock on this template... make sure we can't save anything.
       // and display a nice message.
        $('[name$="cancel"]').fadeOut().attr('value', '$cancel').fadeIn();
        $('#form_edittemplate').dirtyForm('option', 'dirty', false);
        cms_button_able($('#submitbtn, #applybtn'), false);
        $('.lock-warning').removeClass('hidden-item');
        cms_alert($s2);
      }
    });
  }
  $('#form_edittemplate').dirtyForm({
    beforeUnload: function() {
      if(do_locking) $('#form_edittemplate').lockManager('unlock');
    },
    unloadCancel: function() {
      if(do_locking) $('#form_edittemplate').lockManager('relock');
    }
  });
  $(document).on('cmsms_textchange', function() {
    // editor textchange, set the form dirty TODO something from the actual editor
    $('#form_edittemplate').dirtyForm('option', 'dirty', true);
  });
  $('#submitbtn,#cancelbtn,#importbtn,#exportbtn').on('click', function(ev) {
    if (this.id !== 'cancelbtn') {
      var v = geteditorcontent();
      setpagecontent(v);
      $('#form_edittemplate').dirtyForm('option', 'dirty', false);
    }
  });
  $('#applybtn').on('click', function(ev) {
    ev.preventDefault();
    var v = geteditorcontent();
    setpagecontent(v);
    var url = $('#form_edittemplate').attr('action') + '?apply=1',
      data = $('#form_edittemplate').serializeArray();
    $.post(url, data, function(data, textStatus, jqXHR) {
      if(data.status === 'success') {
        cms_notify('success', data.message);
        $('#form_edittemplate').dirtyForm('option', 'dirty', false);
      } else if(data.status === 'error') {
        cms_notify('error', data.message);
      }
    });
    return false;
  });
});
//]]>
</script>
EOS;
	$themeObject->add_footertext($js); //not $sm->queue_script() (embedded variables)

	$selfurl = basename(__FILE__);

	$smarty->assign('selfurl',$selfurl)
	 ->assign('urlext',$urlext);

	include_once 'header.php';
	$smarty->display('edittemplate.tpl');
	include_once 'footer.php';
} catch (Throwable $t) {
	$themeObject->ParkNotice('error',$t->getMessage());
	redirect('listtemplates.php'.$urlext);
}
