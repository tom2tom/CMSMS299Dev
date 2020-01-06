<?php
/*
CoreTextEditing module editor definition for CodeMirror
Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/
namespace CoreTextEditing\CodeMirror;

use cms_siteprefs;
use cms_userprefs;
use CMSModule;
use CMSMS\AppState;
use function get_userid;

/**
 * Default cdn URL for retrieving CodeMirror text-editor code
 */
const CM_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.48.4';

/**
 * Default theme/style for CodeMirror text-editor
 */
const CM_THEME = 'elegant';

$const_prefix = 'CM_';

/**
 * Get javascript etc for running CodeMirror text-editor on a webpage
 *
 * @param CMSModule $mod the current module
 * @param array $params  Configuration details. Recognized members are:
 *  bool   'edit'   whether the content is editable. Default false (i.e. just for display)
 *  string 'handle' js variable (name) for the created editor. Default 'editor'
 *  string 'htmlid' id of the page-element whose content is to be edited. Default 'edit_area'.
 *  string 'htmlclass' class of all page-elements whose content is to be edited. Default ''.
 *  string 'theme'  override for the normal editor theme/style.  Default ''
 *  string 'typer'  content-type identifier, an absolute filepath or filename or
 *    at least an extension or pseudo (like 'smarty'). Default ''
 *
 * @return array up to 2 members, being 'head' and/or 'foot'
 */
function GetPageSetup(&$mod, array $params) : array
{
	extract($params + [
		'edit' => false,
		'handle' => 'editor',
		'htmlid' => 'edit_area',
		'htmlclass' => '',
		'theme' =>'',
		'typer' => '',
	]);
	//FOR DEBUGGER
/*	$edit = $edit;
	$handle = $handle;
	$htmlid = $htmlid;
	$htmlclass = $htmlclass;
	$theme = $theme;
	$typer = $typer;
*/
	if (!($htmlid || $htmlclass)) {
		return [];
	}

	$fixed = ($edit) ? 'false' : 'true';

	if ($typer) {
		if (is_file($typer)) {
			$filename = basename($typer);
		} else {
			$filename = '';
			$p = strrpos($typer, '.');
			$mode = substr($typer, ($p !== false) ? $p+1:0);
			$mode = strtolower($mode);
			// some of the many modes which are more likely in this context
			$known = [
				'css' => 1,
				'htm' => 'xml',
				'html' => 'xml',
				'ini' => 'properties',
				'js' => 'javascript',
				'javascript' => 1,
				'php' => 1,
				'smarty' => 1,
				'tpl' => 'smarty',
				'text' => 'meta',
				'txt' => 'meta',
				'xml' => 1,
			];
			if (isset($known[$mode])) {
				if ($known[$mode] !== 1) {
					$mode = $known[$mode];
				}
			} else {
				$mode = '';
			}
		}
	} else {
		$filename = '';
		$mode = '';
	}

	$urlroot = $mod->GetPreference('codemirror_source_url', CM_CDN); //local or CDN

	if (AppState::test_state(AppState::STATE_ADMIN_PAGE)) {
		if (!$theme) {
			$theme = cms_userprefs::get_for_user(get_userid(false), 'syntax_theme');
			if (!$theme) {
				$theme = cms_siteprefs::get('syntax_theme', CM_THEME);
			}
		}
	}
	$theme = strtolower($theme);

	$css = <<<EOS
<link rel="stylesheet" href="$urlroot/codemirror.css">
<style>
pre.CodeMirror-line {
 display: inherit
}
</style>

EOS;
	if ($theme) {
		$css .= <<<EOS
<link rel="stylesheet" href="$urlroot/theme/$theme.css">

EOS;
	}

	$js = <<<EOS
<script type="text/javascript" src="$urlroot/codemirror.min.js" defer></script>

EOS;
	if ($mode) {
		$js .= <<<EOS
<script type="text/javascript" src="$urlroot/mode/$mode/$mode.js" defer></script>

EOS;
	} else {
		$js .= <<<EOS
<script type="text/javascript" src="$urlroot/addon/mode/loadmode.js" defer></script>
<script type="text/javascript" src="$urlroot/mode/meta.js" defer></script>

EOS;
	}
	$js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
var container,$handle;
$(function() {
 $.valHooks.textarea = {
  get: function(el) {

EOS;
	$js .= <<<'EOS'
   return el.value.replace(/\r?\n/g,"\n");

EOS;
	$js .= <<<EOS
  }
 };
 container = $('#$htmlid');
 $handle = CodeMirror.fromTextArea(container[0],{
  foldGutter: true,
  gutters: ["CodeMirror-foldgutter"],
  lineNumbers: false,
  readOnly: $fixed,
  theme: '$theme'
 });

EOS;
	if (!$mode) {
		$js .= <<<EOS
 var info = CodeMirror.findModeByExtension('$filename');
 if (info) {
  $handle.setOption('mode', info.mime);
  CodeMirror.autoLoadMode($handle, info.mode);
 } else {
  $handle.setOption('mode', 'meta');
 }

EOS;
	}
	if ($edit) {
		$js .= <<<EOS
 var dirty = false;
 $handle.on('change', function(ev) {
  dirty = true;
  $handle.off('change');
 });
 $handle.on('blur', function() {
  if(dirty) {
   $(document).trigger('cmsms_textchange');
  }
 });

EOS;
	}
	$js .= <<<EOS
});

EOS;
	//TODO handle class-selector instead of id
	if ($edit) {
		$js .= <<<EOS
function seteditorcontent(v,m) {
 $handle.setValue(v);
 if(typeof m !== 'undefined') {
  $handle.setOption('mode',m); //TODO generic mode-interpreter
 }
}
function geteditorcontent() {
 return $handle.getValue();
}
function setpagecontent(v) {
 $handle.setValue(v);
}

EOS;
	}
	$js .= <<<EOS
//]]>
</script>

EOS;
	return ['head'=>$css, 'foot'=>$js];
}
