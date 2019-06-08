<?php
# Edit stylesheet
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
use CMSMS\CmsLockException;
use CMSMS\Lock;
use CMSMS\LockOperations;
use CMSMS\ScriptOperations;
use CMSMS\StylesheetOperations;
use DesignManager\utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
	exit;
}

check_login();

$urlext = get_secure_param();
$themeObject = cms_utils::get_theme_object();

if (isset($_REQUEST['cancel'])) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('liststyles.php'.$urlext);
}

$userid = get_userid();
if (!check_permission($userid,'Manage Stylesheets')) {
	return;
}

$response = 'success';
$apply = isset($_REQUEST['apply']);

try {
	$extraparms = [CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY]];

	$message = lang_by_realm('layout','msg_stylesheet_saved');
	if( isset($_REQUEST['css']) ) {
		$css_ob = StylesheetOperations::get_stylesheet($_REQUEST['css']);
		$extraparms['css'] = $_REQUEST['css'];
	} else {
		$css_ob = new CmsLayoutStylesheet();
	}

	try {
// TODO sanitize relevant $_REQUEST[] - NOT content
		if (($apply || isset($_REQUEST['dosubmit'])) && $response !== 'error') {
			if (isset($_REQUEST['description'])) $css_ob->set_description($_REQUEST['description']);
			if (isset($_REQUEST['content'])) $css_ob->set_content($_REQUEST['content']);
			$typ = [];
			if (isset($_REQUEST['media_type'])) $typ = $_REQUEST['media_type'];
			$css_ob->set_media_types($typ);
			if (isset($_REQUEST['media_query'])) $css_ob->set_media_query($_REQUEST['media_query']);
/*            if (check_permission($userid,'Manage Designs')) {
				$design_list = [];
				if (isset($_REQUEST['design_list'])) $design_list = $_REQUEST['design_list'];
				$css_ob->set_designs($design_list);
			}
			$old_export_name = $css_ob->get_content_filename();
			if (isset($_REQUEST['name'])) $css_ob->set_name($_REQUEST['name']);
			$css_ob->set_name($_REQUEST['name']);
			$new_export_name = $css_ob->get_content_filename();
			if ($old_export_name && $old_export_name != $new_export_name && is_file( $old_export_name)) {
				if (is_file( $new_export_name)) throw new Exception('Cannot rename exported stylesheet (destination name exists)');
				$res = rename($old_export_name,$new_export_name);
				if (!$res) throw new Exception( 'Problem renaming exported stylesheet');
			}
*/
			$css_ob->save();

			if (!$apply) {
				$themeObject->ParkNotice('info',$message);
				redirect('liststyles.php'.$urlext);
			}
		}
/*		elseif (isset($_REQUEST['export'])) {
			$outfile = $css_ob->get_content_filename();
			$dn = dirname($outfile);
			if (!is_dir($dn) || !is_writable($dn)) {
				throw new RuntimeException(lang_by_realm('layout','error_assets_writeperm'));
			}
			if (is_file($outfile) && !is_writable($outfile)) {
				throw new RuntimeException(lang_by_realm('layout','error_assets_writeperm'));
			}
			file_put_contents($outfile,$css_ob->get_content());
		}
		elseif (isset($_REQUEST['import'])) {
			$infile = $css_ob->get_content_filename();
			if (!is_file($infile) || !is_readable($infile) || !is_writable($infile)) {
				throw new RuntimeException(lang_by_realm('layout','error_assets_readwriteperm'));
			}
			$data = file_get_contents($infile);
			unlink($infile);
			$css_ob->set_content($data);
			$css_ob->save();
		}
*/
	} catch (Exception $e) {
		$message = $e->GetMessage();
		$response = 'error';
	}

	//
	// prepare to display
	//
	if (!$apply && $css_ob && $css_ob->get_id() && utils::locking_enabled()) {
		$lock_timeout = cms_siteprefs::get('lock_timeout', 0);
		$lock_refresh = cms_siteprefs::get('lock_refresh', 0);
		try {
			$lock_id = LockOperations::is_locked('stylesheet', $css_ob->get_id());
			$lock = null;
			if ($lock_id > 0) {
				// it's locked... by somebody, make sure it's expired before we allow stealing it.
				$lock = Lock::load('stylesheet',$css_ob->get_id());
				if (!$lock->expired()) throw new CmsLockException('CMSEX_L010');
				LockOperations::unlock($lock_id,'stylesheet',$css_ob->get_id());
			}
		} catch (CmsException $e) {
			$message = $e->GetMessage();
			$themeObject->ParkNotice('error',$message);
			redirect('liststyles.php'.$urlext);
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
				for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

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

	$smarty = CmsApp::get_instance()->GetSmarty();
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
	if (($css_id = $css_ob->get_id()) > 0) {
		$themeObject->SetSubTitle(lang_by_realm('layout','prompt_edit_stylesheet').' '.$css_ob->get_name()." ({$css_ob->get_id()})");
	} else {
		$themeObject->SetSubTitle(lang_by_realm('layout','create_stylesheet'));
	}

	$smarty->assign('has_designs_right', check_permission($userid,'Manage Designs'))
	 ->assign('extraparms', $extraparms)
	 ->assign('css', $css_ob);
	if ($css_ob && $css_ob->get_id()) {
		$smarty->assign('css_id', $css_ob->get_id());
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

	$editorjs = get_editor_script(['edit'=>true, 'htmlid'=>'edit_area', 'typer'=>'css']);
	if (!empty($editorjs['head'])) {
		$themeObject->add_headtext($editorjs['head']);
	}

	$do_locking = ($css_id > 0 && isset($lock_timeout) && $lock_timeout > 0) ? 1 : 0;
	if ($do_locking) {
		register_shutdown_function(function($u) {
			LockOperations::delete_for_nameduser($u);
		}, $userid);
	}
	$s1 = json_encode(lang_by_realm('layout','error_lock'));
	$s2 = json_encode(lang_by_realm('layout','msg_lostlock'));
	$cancel = lang('cancel');

	$js = $editorjs['foot'] ?? '';
	$js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  var do_locking = $do_locking;
  if(do_locking) {
    // initialize lock manager
    $('#form_editcss').lockManager({
      type: 'stylesheet',
      oid: $css_id,
      uid: $userid,
      lock_timeout: $lock_timeout,
      lock_refresh: $lock_refresh,
      error_handler: function(err) {
        cms_alert($s1 + ' ' + err.type + ' // ' + err.msg);
      },
      lostlock_handler: function(err) {
        // we lost the lock on this stylesheet... make sure we can't save anything.
        // and display a nice message.
        $('[name$="cancel"]').fadeOut().attr('value', '$cancel').fadeIn();
        $('#form_editcss').dirtyForm('option', 'dirty', false);
        cms_button_able($('#submitbtn, #applybtn'), false);
        $('.lock-warning').removeClass('hidden-item');
        cms_alert($s2);
      }
    });
  }
  $('#form_editcss').dirtyForm({
    beforeUnload: function() {
      if(do_locking) $('#form_editcss').lockManager('unlock');
    },
    unloadCancel: function() {
      if(do_locking) $('#form_editcss').lockManager('relock');
    }
  });
  $(document).on('cmsms_textchange', function() {
    // editor textchange, set the form dirty TODO something from the actual editor
    $('#form_editcss').dirtyForm('option', 'dirty', true);
  });
  $('#submitbtn,#cancelbtn,#importbtn,#exportbtn').on('click', function(ev) {
    if (this.id !== 'cancelbtn') {
      var v = geteditorcontent();
      setpagecontent(v);
      $('#form_editcss').dirtyForm('option', 'dirty', false);
    }
  });
  $('#applybtn').on('click', function(ev) {
    ev.preventDefault();
    var v = geteditorcontent();
    setpagecontent(v);
    var url = $('#form_editcss').attr('action') + '?apply=1',
      data = $('#form_editcss').serializeArray();
    $.post(url, data, function(data, textStatus, jqXHR) {
      if(data.status === 'success') {
        $('#form_editcss').dirtyForm('option', 'dirty', false);
        cms_notify('success', data.message);
      } else if(data.status === 'error') {
        cms_notify('error', data.message);
      }
    });
    return false;
  });
  // disable media-type checkboxes if media query is in use
  if($('#mediaquery').val() !== '') {
    $('.media-type :checkbox').attr({
      checked: false,
      disabled: 'disabled'
    });
  }
  $('#mediaquery').on('keyup', function(e) {
    if($('#mediaquery').val() !== '') {
      $('.media-type :checkbox').attr({
        checked: false,
        disabled: 'disabled'
      });
    } else {
      $('.media-type:checkbox').removeAttr('disabled');
    }
  });
});
//]]>
</script>
EOS;
	$themeObject->add_footertext($js); //not $sm->queue_script() embedded variables

	$selfurl = basename(__FILE__);
	$extras = get_secure_param_array();

	$smarty->assign('selfurl',$selfurl)
	 ->assign('extraparms',$extras)
	 ->assign('urlext',$urlext);

	include_once 'header.php';
	$smarty->display('editstylesheet.tpl');
	include_once 'footer.php';
} catch (CmsException $e) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('liststyles.php'.$urlext);
}
