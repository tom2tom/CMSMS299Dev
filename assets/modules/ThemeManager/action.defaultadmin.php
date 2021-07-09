<?php
/*
Defaultadmin action (list themes) for CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

use CMSMS\App;
use CMSMS\FormUtils;
use CMSMS\ScriptsMerger;
use CMSMS\Utils as AppUtils;
use ThemeManager\Utils;
use function CMSMS\specialize;

/*
$utils->create_manifest('blutak');
$params['theme'] = 'blutak';
$this->DoAction('export', $id, $params, $returnid);
*/

if (!isset($gCms) || !($gCms instanceof App)) {
	exit;
}

$pmod = $this->CheckPermission('Modify Themes');
$pset = $pmod || $this->CheckPermission('Modify Site Settings');
$psee = $this->CheckPermission('View Themes');
if (!($pmod || $pset || $psee)) {
//	$this->SetError($this->Lang('nopermission'));
//OR $themeObject->ParkNotice('error', $this->Lang('nopermission'));
//	$this->RedirectToAdminTab('themes');
//	$this->Redirect($id, 'defaultadmin'); //self-redirect to show message
	exit;
}
$pdev = !empty($config['develop_mode']);
$themeObject = AppUtils::get_theme_object();
$utils = new Utils();

$path = cms_join_path(CMS_THEMES_PATH, '*', '*.cfg');
$themes = glob($path, GLOB_NOSORT);
if ($themes) {
	$current = $this->GetPreference('current_theme'); //TODO OR AppParams::get('current_theme');

	$icon_view = $themeObject->DisplayImage('icons/system/view.png', $this->Lang('inspect'), '', '', 'systemicon');
	$viewurl = $this->create_url($id, 'view', $returnid, ['theme' => 'XXX'], false, false, '', 0);
	$linkview = "<a href=\"$viewurl\">$icon_view</a>";
	if ($pmod) {
		$icon_true = $themeObject->DisplayImage('icons/system/true.png', '', '', '', 'systemicon');
		$icon_false = $themeObject->DisplayImage('icons/system/false.png', '', '', '', 'systemicon', ['title' => $this->Lang('select')]);
		$activateurl = $this->create_url($id, 'select', $returnid, [], false, false, '', 0);
		$importurl = $this->create_url($id, 'import', $returnid, [], false, false, '', 0);

		$icon_clone = $themeObject->DisplayImage('icons/system/copy.png', $this->Lang('clone'), '', '', 'systemicon');
		$cloneurl = $this->create_url($id, 'clone', $returnid, ['theme' => 'XXX'], false, false, '', 0);
		$linkclone = "<a href=\"$cloneurl\" class=\"copy_thm\">$icon_clone</a>";

		$icon_edit = $themeObject->DisplayImage('icons/system/edit.png', $this->Lang('edit'), '', '', 'systemicon');
		$editurl = $this->create_url($id, 'edit', $returnid, ['theme' => 'XXX'], false, false, '', 0);
		$linkedit = "<a href=\"$editurl\">$icon_edit</a>";

		$icon_del = $themeObject->DisplayImage('icons/system/delete.png', $this->Lang('delete'), '', '', 'systemicon');
//		$linkdel = "<a href=\"javascript:doDelete('XXX')\" class="del_thm">$icon_del</a>"; //TODO bad for CSP
		$delurl = $this->create_url($id, 'delete', $returnid, ['theme' => 'XXX'], false, false, '', 0);
		$linkdel = "<a href=\"$delurl\" class=\"del_thm\">$icon_del</a>";
	}
	if ($pdev) {
		$path = cms_join_path($this->GetModulePath(), 'images', 'xml');
		$icon_export = $themeObject->DisplayImage($path, 'export', '', '', 'systemicon', ['title' => $this->Lang('export')]);
		$exporturl = $this->create_url($id, 'export', $returnid, ['theme' => 'XXX'], false, false, '', 0);
		$linkexport = "<a href=\"$exporturl\">$icon_export</a>";
	}

	$items = [];
	$menus = [];
	$sel = ['' => $this->Lang('none')];
	foreach ($themes as $fp) {
		$props = parse_ini_file($fp);
		$trn = basename(dirname($fp));
		$props['rawname'] = $trn;
		$val = $props['name'];
		$props['current'] = ($val == $current);
		//c.f. $utils->unique_name() which is for folder names
		$nm = $val;
		$i = 1;
		while (isset($items[$nm])) {
			$nm = $val."($i)";
			++$i;
		}
		if ($nm != $val) {
			$props['name'] = $nm;
		}
		$val = $props['modified'] ?? '';
		if ($val) {
			$props['modified'] = strtotime($val);
		} else {
			$props['modified'] = (int)filemtime($fp);
		}
		$val = (!empty($props['description'])) ? trim($props['description']) : '';
		$props['description'] = ($val) ? specialize($utils->shorten_string($val)) : '';
		$props['fulldesc'] = ($val) ? specialize($val) : '';
		$items[$props['name']] = $props;
		$nm = basename(dirname($fp));
		$sel[$nm] = $props['name'];
		// actions context-menu
		$acts = [];
		if ($psee) {
			$acts[] = ['content' => str_replace('XXX', $trn, $linkview)];
		}
		if ($pmod) {
			$acts[] = ['content' => str_replace('XXX', $trn, $linkedit)];
			$acts[] = ['content' => str_replace('XXX', $trn, $linkclone)];
			$acts[] = ['content' => str_replace('XXX', $trn, $linkdel)];
		}
		if ($pdev) {
			$acts[] = ['content' => str_replace('XXX', $trn, $linkexport)];
		}
		if ($acts) {
			$menus[] = FormUtils::create_menu($acts, ['id' => 'Theme-'.$trn]);
		}
	}
	ksort($items); //TODO multibyte compares
}

if ($pmod) {
	//setup for manual-addition, imports
	$baseurl = $this->GetModuleURLPath();
	$out = <<<EOS
<link rel="stylesheet" type="text/css" href="{$baseurl}/lib/styles/dropzone.css" />
EOS;
	add_page_headtext($out);

	$icon_add = $themeObject->DisplayImage('icons/system/newobject.png', $this->Lang('add'), '', '', 'systemicon');
	$addurl = $this->create_url($id, 'construct', $returnid, [], false, false, '', 0);
	$delurl = $this->create_url($id, 'delete', $returnid, [], false, false, '', 2);
	$confirm = json_encode($this->Lang('confirm_delete_theme'));
	$submit = $this->Lang('submit');
	$cancel = $this->Lang('cancel');
	$max_chunksize = 10240; //TODO
//	$nonce = get_csp_token();

	$jsm = new ScriptsMerger();
	$jsm->queue_matchedfile('jquery.ContextMenu.js', 2);
	$jsm->queue_matchedfile('jquery.dm-uploader.js', 2);

	$upurl = str_replace('&amp;', '&', $importurl) . "&{$id}ajax=1";
	$out = <<<EOS
var progressBar, progressText, errmsg;
function refresh(elem, restart) {
  cms_busy(false);
  if (restart) {
    window.location.reload(true);
  } else {
    progressBar.remove();
    progressText.remove();
    elem.removeClass('visuallyhidden');
  }
}
function upload_finish(elem, restart) {
  if (errmsg && errmsg.length > 0) {
    var msg = cms_lang.error_title + ':\\n&nbsp;' + errmsg.join('\\n&nbsp;');
    cms_alert(msg, false, true).always(function() {
      refresh(elem, restart);
    });
  } else {
    setTimeout(function() {
      refresh(elem, restart);
    }, 1000);
  }
}
function upload_connect(elem) {
  var colorBar;
  elem.dmUploader({
    url: '$upurl',
    fieldName: '{$id}import_file', // \$_FILES[] key for DnD upload
    allowedTypes: 'text/xml',
    onBegin: function() { //the [first] upload is about to start
      cms_busy();
      errmsg = [];
      progressBar = $('<div/>',{id:'ul-progress'});
      colorBar = $('<div/>',{id:'ul-progress-inner'});
      progressText = $('<p/>',{id:'ul-progress-text'});
      elem.addClass('visuallyhidden');
      progressBar.prepend(colorBar).insertAfter(elem).show();
      progressText.insertAfter(progressBar).show();
    },
    onComplete: function() {
      upload_finish(elem, true);
    },
    onBeforeUpload: function(id) {
      progressText.text('');
      colorBar.width(0);
    },
    onUploadProgress: function(id, percent) {
      colorBar.animate({
        width: percent + '%'
      }, 200, function() {
        $(this).attr('aria-valuenow', percent);
        progressText.text(percent + '%');
      });
    },
    onUploadComplete: function(id) {
      upload_finish(elem, true);
    },
//    onUploadCanceled: function(id) {
//      upload_finish(elem, true);
//    },
    onUploadError: function(id, jqXHR, textStatus, errorThrown) {
      if (jqXHR.responseJSON) {
        errmsg.push(jqXHR.responseJSON.message);
      } else {
        errmsg.push(errorThrown.message);
      }
    }
  });
}
$(function() {
  $('tbody [context-menu]').ContextMenu();
  var dzone = $('#theme_dropzone'),
    test = dzone[0];
  if ('draggable' in test || ('ondragstart' in test && 'ondrop' in test)) {
    upload_connect(dzone);
  } else {
    dzone.hide();
  }
  upload_connect($('#theme_select'));
  $('a.del_thm').on('click activate', function(e) {
    e.preventDefault();
    cms_confirm_linkclick(this, $confirm);
    return false;
  });
  $('a.copy_thm').on('click activate', function(e) {
    e.preventDefault();
    var u = this.href,
       el = $('#clone_dlg');
    cms_dialog(el, {
      width: 'auto',
      buttons: {
        '$submit': function() {
          var cn = el.find('[name="{$id}name"]').val();
          $(this).dialog('close');
          if (cn) {
            $('#clonedialog_form').attr('action', u).trigger('submit');
          }
        },
        '$cancel': function() {
          $(this).dialog('close');
        }
      }
    });
    return false;
  });
});
EOS;
	$jsm->queue_string($out, 3);
	$out = $jsm->page_content();
	if ($out) {
		add_page_foottext($out);
	} else {
		$message = $this->Lang('err_TODO');
		$this->DisplayErrorPage($id, $params, $returnid, $message);
		return '';
	}
}

$seetab = $params['active_tab'] ?? 'themes';
$urlext = get_secure_param();
$extras = get_secure_param_array();

$tpl = $smarty->createTemplate($this->GetTemplateResource('defaultadmin.tpl')); //,null,null,$smarty);
$tpl->assign([
 'activateurl' => $activateurl ?? null,
 'addurl' => $addurl ?? null,
 'contextmenus' => $menus ?? null,
 'extraparms' => $extras,
 'iconadd' => $icon_add ?? null,
 'iconfalse' => $icon_false ?? null,
 'icontrue' => $icon_true ?? null,
 'importurl' => $importurl ?? null,
 'pdev' => $pdev,
 'pmod' => $pmod,
 'psee' => $psee,
 'pset' => $pset,
 'tab' => $seetab,
 'themes' => $items ?? null,
 'themeoptions' => $sel ?? null,
 'current_theme' => $current ?? null,
]);

$tpl->Display();
return '';
