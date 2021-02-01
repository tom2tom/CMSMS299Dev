<?php
/*
Procedure to edit a stylesheet
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

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Lock;
use CMSMS\LockOperations;
use CMSMS\ScriptsMerger;
use CMSMS\Stylesheet;
use CMSMS\StylesheetOperations;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

/*if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    throw new CMSMS\Error403Exception(lang('informationmissing'));
}
*/
check_login();

$urlext = get_secure_param();
$themeObject = Utils::get_theme_object();

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

$content = $_REQUEST['content'] ?? ''; // preserve this verbatim
unset($_REQUEST['content']);
cms_specialchars_decode_array($_REQUEST);

try {
	$extraparms = [CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY]];

	$message = lang_by_realm('layout','msg_stylesheet_saved');
	if( isset($_REQUEST['css']) ) {
		$css_ob = StylesheetOperations::get_stylesheet($_REQUEST['css']); // sanitizeVal() ?
		$extraparms['css'] = $_REQUEST['css'];
	} else {
		$css_ob = new Stylesheet();
	}

	try {
		if (($apply || isset($_REQUEST['dosubmit'])) && $response !== 'error') {
			if (isset($_REQUEST['description'])) $css_ob->set_description($_REQUEST['description']);// sanitizeVal() ?
			$css_ob->set_content($content);
			$typ = [];
			if (isset($_REQUEST['media_type'])) $typ = $_REQUEST['media_type']; // sanitizeVal() ?
			$css_ob->set_media_types($typ);
			if (isset($_REQUEST['media_query'])) $css_ob->set_media_query($_REQUEST['media_query']); // sanitizeVal() ?
/*			if (check_permission($userid,'Manage Designs')) {
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
	} catch (Throwable $e) {
		$message = $e->GetMessage();
		$response = 'error';
	}

	//
	// prepare to display
	//
	if (!$apply && $css_ob && $css_ob->get_id()) {
		$lock_timeout = AppParams::get('lock_timeout', 0);
		if ($lock_timeout > 0) {
			$lock_refresh = AppParams::get('lock_refresh', 0);
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
			$lock_refresh = 0;
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

	$smarty = AppSingle::Smarty();
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
	$config = AppSingle::Config();
	if ($config['develop_mode']) {
		$smarty->assign('devmode', 1);
	}

	$jsm = new ScriptsMerger();
	$jsm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
	$jsm->queue_matchedfile('jquery.cmsms_lock.js', 2);
	$js = $jsm->page_content('', false, false);
	if ($js) {
		add_page_foottext($js);
	}

	$pageincs = get_syntaxeditor_setup(['edit'=>true, 'htmlid'=>'edit_area', 'typer'=>'css']);
	if (!empty($pageincs['head'])) {
		add_page_headtext($pageincs['head']);
	}

//	$nonce = get_csp_token();
	$do_locking = ($css_id > 0 && isset($lock_timeout) && $lock_timeout > 0) ? 1 : 0;
	if ($do_locking) {
		AppSingle::App()->add_shutdown(10,'LockOperations::delete_for_nameduser',$userid);
	}
	$s1 = json_encode(lang_by_realm('layout','error_lock'));
	$s2 = json_encode(lang_by_realm('layout','msg_lostlock'));
	$cancel = lang('cancel');

	$js = $pageincs['foot'] ?? '';
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
    var fm = $('#form_editcss'),
       url = fm.attr('action') + '?apply=1',
    params = fm.serializeArray();
    $.ajax(url, {
      type: 'POST',
      data: params,
      cache: false
    }).fail(function(jqXHR, textStatus, errorThrown) {
      cms_notify('error', errorThrown);
    }).done(function(data) {
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
      $('.media-type:checkbox').prop('disabled', false);
    }
  });
});
//]]>
</script>
EOS;
	add_page_foottext($js); //not $jsm->queue_script() embedded variables

	$selfurl = basename(__FILE__);
	$extras = get_secure_param_array();

	$smarty->assign('selfurl',$selfurl)
	 ->assign('extraparms',$extras)
	 ->assign('urlext',$urlext);

	$content = $smarty->fetch('editstylesheet.tpl');
	$sep = DIRECTORY_SEPARATOR;
	require ".{$sep}header.php";
	echo $content;
	require ".{$sep}footer.php";
} catch (Throwable $t) {
	$themeObject->ParkNotice('error',$t->GetMessage());
	redirect('liststyles.php'.$urlext);
}
