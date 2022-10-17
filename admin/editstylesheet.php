<?php
/*
Script to edit a stylesheet
Copyright (C) 2012-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CMSMS\LockException;
use CMSMS\LockOperations;
use CMSMS\Lone;
use CMSMS\ScriptsMerger;
use CMSMS\Stylesheet;
use CMSMS\StylesheetOperations;
use function CMSMS\add_shutdown;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;
//use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
$themeObject = Lone::get('Theme');

if (isset($_REQUEST['cancel'])) {
	$themeObject->ParkNotice('info', _ld('layout', 'msg_cancelled'));
	redirect('liststyles.php'.$urlext);
}

//TODO also support read-only display of sheet
$userid = get_userid();
if (!check_permission($userid, 'Manage Stylesheets')) {
//TODO some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage_Groups')), ...);
	throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$response = 'success';
$apply = isset($_REQUEST['apply']);

$content = $_REQUEST['content'] ?? ''; // preserve this verbatim for now
unset($_REQUEST['content']);
de_specialize_array($_REQUEST);

$extras = get_secure_param_array();

try {
	$message = _ld('layout', 'msg_stylesheet_saved');
	if( !empty($_REQUEST['css']) ) {
		$val = sanitizeVal($_REQUEST['css'], CMSSAN_FILE); //TODO CMSSAN_NAME if not file-stored?
		$css_ob = StylesheetOperations::get_stylesheet($val);
		if ($css_ob) {
			$extras['css'] = $val;
		} else {
			throw new RuntimeException('Internal error: unrecognised stylesheet identifier: '.$val);
		}
	} else {
		$css_ob = new Stylesheet();
	}

	try {
		if (($apply || isset($_REQUEST['dosubmit'])) && $response !== 'error') {
			//TODO downstream clean changed name per relevant storage-type
			$val = sanitizeVal(trim($_REQUEST['name']), CMSSAN_FILE); // TODO relevant name-cleaner
			if ($val != $css_obj->get_name()) {
				$css_obj->set_name($val);
			}
			if (!empty($_REQUEST['description'])) {
				$val = sanitizeVal(trim($_REQUEST['description']), CMSSAN_NONPRINT); // AND nl2br() ? striptags() other than links ?
				$val = preg_replace_callback('~&lt;(&sol;|&#47;)?(textarea)&gt;~i', function($matches) {
					$pre = ($matches[1]) ? '/' : '';
					return '<'.$pre.$matches[2].'>';
				}, $val);
				$css_ob->set_description($val);
			}

			$typ = [];
			if (isset($_REQUEST['media_type'])) {
				$typ = sanitizeVal($_REQUEST['media_type'], CMSSAN_NAME); // TODO preg replace for [^letters|,|space]
			}
			$css_ob->set_media_types($typ);

			if (isset($_REQUEST['media_query'])) {
				$val = sanitizeVal($_REQUEST['media_query'], CMSSAN_PUNCT); // letters, numbers, spaces, nons like ()-:
				$css_ob->set_media_query($val);
			}
			/*
			per https://www.w3.org/TR/CSS22/syndata.html#characters
			stylesheet content may include anything other than NUL chars,
			but in many cases, 'uncommon' chars must be escaped.
			Here, we will be just a bit restrictive ...
			*/
			$content = preg_replace(['/\x00-\x08\x0b\x0c\x0e-\x1f\x7f-\x9f/', '    '], ['', "\t"], $content);
			// revert any munged (stupid|malicious) textarea tag
			$matches = [];
			$content = preg_replace_callback('~&lt;(&sol;|&#47;)?(textarea)&gt;~i', function($matches) {
				$pre = ($matches[1]) ? '/' : '';
				return '<'.$pre.$matches[2].'>';
			}, $content);
			$css_ob->set_content($content);
/*			if (check_permission($userid, 'Manage Designs')) {
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
				$res = rename($old_export_name, $new_export_name);
				if (!$res) throw new Exception( 'Problem renaming exported stylesheet');
			}
*/
			$css_ob->save();

			if (!$apply) {
				$themeObject->ParkNotice('info', $message);
				redirect('liststyles.php'.$urlext);
			}
		}
/*		elseif (isset($_REQUEST['export'])) {
			$outfile = $css_ob->get_content_filename();
			$dn = dirname($outfile);
			if (!is_dir($dn) || !is_writable($dn)) {
				throw new RuntimeException(_ld('layout', 'error_assets_writeperm'));
			}
			if (is_file($outfile) && !is_writable($outfile)) {
				throw new RuntimeException(_ld('layout', 'error_assets_writeperm'));
			}
			file_put_contents($outfile, $css_ob->get_content());
		} elseif (isset($_REQUEST['import'])) {
			$infile = $css_ob->get_content_filename();
			if (!is_file($infile) || !is_readable($infile) || !is_writable($infile)) {
				throw new RuntimeException(_ld('layout', 'error_assets_readwriteperm'));
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
	$props = [
//		'content_file' => $css_ob->get_content_file(), DISABLED
		'content' => $css_ob->get_content(),
		'created' => $css_ob->get_created(), // i.e. create_date
		'description' => $css_ob->get_description(),
//		'designs' => $css_ob->get_designs(), DISABLED
		'id' => $css_ob->get_id(),
		'lock' => null, // populated later if relevant
		'media_query' => $css_ob->get_media_query(),
		'modified' => $css_ob->get_modified().'', // i.e. modified_date, maybe empty
		'name' => $css_ob->get_name(),
		'types' => [],
	];

	foreach (['content', 'description', 'media_query'] as $key) {
		if ($props[$key]) {
			// specialize e.g. munge malicious <[/]textarea> tags
			$matches = [];
			$props[$key] = preg_replace_callback('~<\s*(/?)\s*(textarea)\s*>~i', function($matches) use($key) {
				switch ($key) {
					case 'media_query':
						return '';
					case 'content':
						$pre = ($matches[1]) ? '&sol;' : ''; // OR &#47;
						return '/* &lt;'.$pre.$matches[2].'&gt; */';
					default:
						$pre = ($matches[1]) ? '&sol;' : ''; // OR &#47;
						return '&lt;'.$pre.$matches[2].'&gt;';
				}
			}, $props[$key]);
		}
	}

	$all_types = ['all','aural','speech','braille','embossed','handheld','print','projection','screen','tty','tv'];
	foreach ($all_types as $key) {
		if ($css_ob->has_media_type($key)) {
			$props['types'][$key] = 1;
		}
	}

	if (!$apply && $props['id'] > 0) {
		$lock_timeout = AppParams::get('lock_timeout', 0);
		if ($lock_timeout > 0) {
			$lock_refresh = AppParams::get('lock_refresh', 0);
			try {
				$lock = null;
				$lock_id = LockOperations::is_locked('stylesheet', $props['id']);
				if ($lock_id > 0) {
					// it's locked... by somebody. Steal if expired TODO if stealable
					$lock = LockOperations::load('stylesheet', $props['id']);
					if (!$lock->expired()) { throw new LockException('CMSEX_L010'); }
					LockOperations::unlock($lock_id, 'stylesheet', $props['id']);
					$props['lock'] = [
						'uid' => $lock['uid'], // TODO etc
//						'stealable' => TODO,
					];
				}
			} catch (Throwable $t) {
				$message = $t->GetMessage();
				$themeObject->ParkNotice('error', $message);
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
		 * @return string containing the JSON representation of provided response data
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
		$themeObject->RecordNotice('error', $message);
	}

	$smarty = Lone::get('Smarty');
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
	if (($css_id = $props['id']) > 0) {
		$themeObject->SetSubTitle(_ld('layout', 'prompt_edit_stylesheet')." {$props['name']} ($css_id)");
		$smarty->assign('css_id', $css_id);
	} else {
		$themeObject->SetSubTitle(_ld('layout', 'create_stylesheet'));
	}

	$smarty->assign('has_designs_right', check_permission($userid, 'Manage Designs'))
	 ->assign('css', $props)
	 ->assign('all_types', $all_types);
	if (Lone::get('Config')['develop_mode']) {
		$smarty->assign('devmode', 1);
	}

	$jsm = new ScriptsMerger();
	$jsm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
	$jsm->queue_matchedfile('jquery.cmsms_lock.js', 2);
	$js = $jsm->page_content();
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
		add_shutdown(10, 'LockOperations::delete_for_nameduser', $userid);
	}
	$s1 = addcslashes(_ld('layout', 'error_lock'), "'\n\r");
	$s2 = addcslashes(_ld('layout', 'msg_lostlock'), "'\n\r");
	$cancel = _la('cancel');

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
        cms_alert('{$s1} ' + err.type + ' // ' + err.msg);
      },
      lostlock_handler: function(err) {
        // we lost the lock on this stylesheet... make sure we can't save anything.
        // and display a nice message.
        $('[name$="cancel"]').fadeOut().attr('value', '$cancel').fadeIn();
        $('#form_editcss').dirtyForm('option', 'dirty', false);
        cms_button_able($('#submitbtn, #applybtn'), false);
        $('.lock-warning').removeClass('hidden-item');
        cms_alert('$s2');
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
      method: 'POST',
      data: params
    }).done(function(data) {
      if(data.status === 'success') {
        $('#form_editcss').dirtyForm('option', 'dirty', false);
        cms_notify('success', data.message);
      } else if(data.status === 'error') {
        cms_notify('error', data.message);
      }
    }).fail(function(jqXHR, textStatus, errorThrown) {
      cms_notify('error', errorThrown);
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
	add_page_foottext($js); //not $jsm->queue_script() : fluid embedded variables

	$selfurl = basename(__FILE__);
	$extras += get_secure_param_array();

	$smarty->assign('userid', $userid)
	 ->assign('selfurl', $selfurl)
	 ->assign('extraparms', $extras)
	 ->assign('urlext', $urlext);

	$content = $smarty->fetch('editstylesheet.tpl');
	$sep = DIRECTORY_SEPARATOR;
	require ".{$sep}header.php";
	echo $content;
	require ".{$sep}footer.php";
} catch (Throwable $t) {
	$themeObject->ParkNotice('error', $t->GetMessage());
	redirect('liststyles.php'.$urlext);
}
