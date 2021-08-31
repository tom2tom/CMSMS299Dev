<?php
/*
Procedure to edit a template
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
use CMSMS\Exception;
use CMSMS\LockException;
use CMSMS\LockOperations;
use CMSMS\ScriptsMerger;
use CMSMS\SingleItem;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplatesGroup;
use CMSMS\TemplateType;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
$themeObject = SingleItem::Theme();

if (isset($_REQUEST['cancel'])) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('listtemplates.php'.$urlext);
}

$userid = get_userid();
$pmod = check_permission($userid,'Modify Templates');

$content = $_REQUEST['content'] ?? ''; // preserve this verbatim, for now
unset($_REQUEST['content']);
de_specialize_array($_REQUEST);

if (!$pmod) {
	// no manage templates permission
//TODO also support read-only display of template
	if (!check_permission($userid,'Add Templates')) {
		// no add templates permission
		if (isset($_REQUEST['tpl'])) {
			$val = (is_numeric($_REQUEST['tpl'])) ? (int)$_REQUEST['tpl'] : sanitizeVal($_REQUEST['tpl'], CMSSAN_FILE);
		}
		if (!isset($_REQUEST['tpl']) || !TemplateOperations::user_can_edit_template($val)) {
			// no parameter, or no ownership/addt_editors.
			return;
		}
	}
}

$response = 'success';
$apply = isset($_REQUEST['apply']);

try {
	$extraparms = get_secure_param_array();
	if (isset($_REQUEST['import_type'])) {
		$val = (is_numeric($_REQUEST['import_type'])) ? (int)$_REQUEST['import_type'] : sanitizeVal($_REQUEST['import_type'], CMSSAN_NAME);
		$tpl_obj = TemplateOperations::get_template_by_type($val);
		$tpl_obj->set_owner($userid);
/*		$design = DesignManager\Design::load_default(); DISABLED
		if ($design) {
			$tpl_obj->add_design($design);
		}
*/
		$extraparms['import_type'] = $val;
	} elseif (isset($_REQUEST['tpl'])) {
		$val = (is_numeric($_REQUEST['tpl'])) ? (int)$_REQUEST['tpl'] : sanitizeVal($_REQUEST['tpl'], CMSSAN_FILE);
		$tpl_obj = TemplateOperations::get_template($val);
//		$tpl_obj->get_designs();
		$extraparms['tpl'] = $val;
	} else {
		$tpl_obj = new Template();
	}

	$defaultable = false;
	$type_id = $tpl_obj->get_type_id();
	if ($type_id) {
		try {
			$type_obj = TemplateType::load($type_id);
			$defaultable = $type_obj->get_dflt_flag();
		} catch (Throwable $t) {
			//nothing here
		}
	}

	try {
		if ($apply || isset($_REQUEST['dosubmit'])) {
			// do the magic.
			if (isset($_REQUEST['description'])) {
				$val = sanitizeVal(trim($_REQUEST['description']), CMSSAN_NONPRINT); // AND nl2br() ? striptags() other than links ?
				// revert any munged textarea tag
				$matches = [];
				$val = preg_replace_callback('~&lt;(&sol;|&#47;)?(textarea)&gt;~i', function($matches) {
					$pre = ($matches[1]) ? '/' : '';
					return '<'.$pre.$matches[2].'>';
				}, $val);
				$tpl_obj->set_description($val);
			}
			if (isset($_REQUEST['type'])) {
				$val = (is_numeric($_REQUEST['type'])) ? (int)$_REQUEST['type'] : sanitizeVal($_REQUEST['type'], CMSSAN_NAME);
				if (!(is_numeric($val) || AdminUtils::is_valid_itemname($val))) {
					throw new Exception(lang('errorbadname'));
				}
				$tpl_obj->set_type($val);
			}
			$tpl_obj->set_type_dflt((bool)($_REQUEST['default'] ?? false));
			if (isset($_REQUEST['owner_id'])) {
				$val = (is_numeric($_REQUEST['owner_id'])) ? (int)$_REQUEST['owner_id'] : sanitizeVal($_REQUEST['owner_id'], CMSSAN_ACCOUNT);
				$tpl_obj->set_owner($val);
			}
			if (!empty($_REQUEST['addt_editors'])) {
				$val = $_REQUEST['addt_editors'];
				if (!is_array($val)) {
					$val = [$val];
				}
				if (is_numeric($val[0])) {
					foreach ($val as &$one) {
						$one = (int)$one;
					}
				} else {
					foreach ($val as &$one) {
						$one = sanitizeVal($one, CMSSAN_ACCOUNT);
					}
				}
				unset($one);
				$tpl_obj->set_additional_editors($val); //TODO support clearance
			}
/*			if (!empty($_REQUEST['category_id'])) $tpl_obj->set_category($_REQUEST['category_id']); //TODO support multiple categories
			$tpl_obj->set_listable($_REQUEST['listable'] ?? 0);
//TODO		$tpl_obj->set_content_file($_REQUEST['contentfile'] ?? 0);
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
//USELESS FOR SUCH TEST Utils::set_app_data('tmp_template', $_REQUEST['contents']);

			// if we got here, we're golden.
			// template content can be anything, really.
			// tho' arguably tags {php}{/php} (whatever delimiters) should be removed here
			// revert any munged textarea tag
			$matches = [];
			$content = preg_replace_callback('~&lt;(&sol;|&#47;)?(textarea)&gt;~i', function($matches) {
				$pre = ($matches[1]) ? '/' : '';
				return '<'.$pre.$matches[2].'>';
			}, $content);
			$tpl_obj->set_content($content);
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
/*		elseif (isset($_REQUEST['export'])) {
			$outfile = $tpl_obj->get_content_filename();
			$dn = dirname($outfile);
			if (!is_dir($dn) || !is_writable($dn)) throw new RuntimeException(lang_by_realm('layout','error_assets_writeperm'));
			if (is_file($outfile) && !is_writable($outfile)) throw new RuntimeException(lang_by_realm('layout','error_assets_writeperm'));
			file_put_contents($outfile,$tpl_obj->get_content());
		} elseif (isset($_REQUEST['import'])) {
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
	if (!$apply && $tpl_obj && $tpl_obj->get_id()) {
		$lock_timeout = AppParams::get('lock_timeout', 0);
		if ($lock_timeout > 0) {
			$lock_refresh = AppParams::get('lock_refresh', 0);
			try {
				$lock_id = LockOperations::is_locked('template', $tpl_obj->get_id());
				$lock = null;
				if ($lock_id > 0) {
					// it's locked... by somebody, make sure it's expired before we allow stealing it.
					$lock = LockOperations::load('template',$tpl_obj->get_id());
					if (!$lock->expired()) throw new LockException('CMSEX_L010');
					LockOperations::unlock($lock_id,'template',$tpl_obj->get_id());
				}
			} catch( Exception $e) {
				$message = $e->GetMessage();
				$themeObject->ParkNotice('error',$message);
				redirect('listtemplates.php'.$urlext);
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
		$themeObject->RecordNotice('error',$message);
	}

	if (($tpl_id = $tpl_obj->get_id()) > 0) {
		$name = $tpl_obj->get_name();
		$themeObject->SetSubTitle(lang_by_realm('layout','prompt_edit_template').": $name ($tpl_id)");
		$desc = specialize($tpl_obj->get_description());
		$tmp = preg_replace('~<\?(php|=|\s|\n)~i','&lt;&quest;$1',$tpl_obj->get_content());
//TODO alse entitize embedded smarty tags {php}...{/php} whatever left/right boundaries
		$matches = [];
		$content = preg_replace_callback('~<\s*(/?)\s*(textarea)\s*>~i', function($matches) {
			$pre = ($matches[1]) ? '&sol;' : ''; // ?? OR &#47;
			return '&lt;'.$pre.$matches[2].'&gt;';
		}, $tmp);
	} else {
		$tpl_id = 0;
		$name = '';
		$themeObject->SetSubTitle(lang_by_realm('layout','create_template'));
		$desc = '';
		$content = '';
	}

	$props = [
		'additional_editors' => $tpl_obj->get_additional_editors(),
		'category_id' => $tpl_obj->get_category_id(),
		'content_file' => $tpl_obj->get_content_file(),
		'content' => $content, // into textarea
		'created' => $tpl_obj->get_created(),
		'description' => $desc, // into textarea
//		'designs' => $tpl_obj->get_designs(),
		'id' => $tpl_id,
		'listable' => $tpl_obj->get_listable(),
		'lock' => $tpl_obj->get_lock(), // array
		'modified' => $tpl_obj->get_modified(),
		'name' => $name,
		'owner_id' => $tpl_obj->get_owner_id(),
		'type_id' => $tpl_obj->get_type_id(),
		'usage_string' => $tpl_obj->get_usage_string(), //immutable string
	];

	foreach (['content', 'description'] as $key) {
		// specialize e.g. munge possibly-malicious <[/]textarea> tags
		switch ($key) {
//			case 'content':
//			case 'description':
			default:
				$matches = [];
				$props[$key] = preg_replace_callback('~<\s*(/?)\s*(textarea)\s*>~i', function($matches) {
					$pre = ($matches[1]) ? '&sol;' : ''; // ?? OR &#47;
					return '&lt;'.$pre.$matches[2].'&gt;';
				}, $props[$key]);
		}
	}

	$smarty = SingleItem::Smarty();
	$smarty->assign('userid', $userid)
	 ->assign('can_manage', $pmod)
//	 ->assign('has_themes_right', check_permission($userid,'Manage Designs'));
	 ->assign('extraparms', $extraparms) // TODO $extraparms != $extras, both assigned to 'extraparms'
	 ->assign('tpl', $props)
	 ->assign('tpl_candefault', $defaultable);

/* for 'related file' message UNUSED
	if ($tpl_obj->get_content_file()) {
		$fn = $tpl_obj->content; // raw
		$filepath = cms_join_path('','assets','templates',$fn);
		$smarty->assign('relpath', $filepath);
	}
*/
	$grps = TemplatesGroup::get_all();
	$out = ['' => lang_by_realm('layout','prompt_none')];
	if ($grps) {
		foreach ($grps as $one) {
			$out[$one->get_id()] = $one->get_name();
		}
	}
	$smarty->assign('category_list', $out);

	$types = TemplateType::get_all();
	if ($types) {
		$out = [];
		$out2 = [];
		foreach ($types as $one) {
			$out2[] = $one->get_id();
			$out[$one->get_id()] = $one->get_langified_display_value();
		}
		$smarty->assign('type_list', $out);
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
	if ($pmod || $tpl_obj->get_owner_id() == $userid) {
		$userops = SingleItem::UserOperations();
		$allusers = $userops->LoadUsers();
		$tmp = [];
		foreach ($allusers as $one) {
// TODO why omit super-admin here? If template owner is that user, this would unset that user as owner
//			if ($one->id === 1) continue;
			$tmp[$one->id] = $one->username;
		}
		if ($tmp) { $smarty->assign('user_list', $tmp); }

		$groupops = SingleItem::GroupOperations();
		$allgroups = $groupops->LoadGroups();
		foreach ($allgroups as $one) {
			if ($one->id == 1) continue;
			if ($one->active == 0) continue;
			$tmp[$one->id * -1] = lang_by_realm('layout','prompt_group') . ': ' . $one->name;
			// appends to the tmp array.
		}
		if ($tmp) { $smarty->assign('addt_editor_list', $tmp); }
	}

	if (SingleItem::Config()['develop_mode']) {
		$smarty->assign('devmode', 1);
	}

	$jsm = new ScriptsMerger();
	$jsm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
	$jsm->queue_matchedfile('jquery.cmsms_lock.js', 2);
	$js = $jsm->page_content();
	if ($js) {
		add_page_foottext($js);
	}

	$pageincs = get_syntaxeditor_setup(['edit'=>true, 'htmlid'=>'edit_area', 'typer'=>'smarty']);
	if (!empty($pageincs['head'])) {
		add_page_headtext($pageincs['head']);
	}

//	$nonce = get_csp_token();
	$do_locking = ($tpl_id > 0 && isset($lock_timeout) && $lock_timeout > 0) ? 1 : 0;
	if ($do_locking) {
		SingleItem::App()->add_shutdown(10,'LockOperations::delete_for_nameduser',$userid);
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
    var fm = $('#form_edittemplate'),
      url = fm.attr('action') + '?apply=1',
    params = fm.serializeArray();
    $.ajax(url, {
      method: 'POST',
      data: params
    }).fail(function(jqXHR, textStatus, errorThrown) {
      cms_notify('error', errorThrown);
    }).done(function(data) {
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
	add_page_foottext($js); //not $jsm->queue_script() (fluctuating embedded variables)

	$selfurl = basename(__FILE__);
	// TODO see also $extraparms, set above
	$extras = get_secure_param_array() + [
		'tpl' => $tpl_id
	];

	$smarty->assign('selfurl', $selfurl)
	 ->assign('extraparms', $extras)
	 ->assign('urlext', $urlext);

	$content = $smarty->fetch('edittemplate.tpl');
	$sep = DIRECTORY_SEPARATOR;
	require ".{$sep}header.php";
	echo $content;
	require ".{$sep}footer.php";
} catch (Throwable $t) {
	$themeObject->ParkNotice('error',$t->getMessage());
	redirect('listtemplates.php'.$urlext);
}
