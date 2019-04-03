<?php
/*
CoreTextEditing module editor definition for CodeMirror
Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//use function codemirror_GetScript as GetScript;

/**
 * Get javascript for initialization of CodeMirror text-editor
 *
 * @param CMSModule $mod the current module
 * @param array $params  Configuration details. Recognized members are:
 *  bool   'edit'   whether the content is editable. Default false (i.e. just for display)
 *  string 'handle' js variable (name) for the created editor. Default 'editor'
 *  string 'htmlid' id of the page-element whose content is to be edited. Mandatory.
 *  string 'style'  override for the normal editor theme/style.  Default ''
 *  string 'typer'  content-type identifier, an absolute filepath or at least
 *    an extension or pseudo (like 'smarty'). Default ''
 *
 * @return array up to 2 members, being 'head' and/or 'foot'
 */
function GetScript(&$mod, array $params) : array
{
	global $CMS_ADMIN_PAGE;

	extract($params + [
		'edit' => false,
		'handle' => 'editor',
		'htmlid' => '',
		'style' =>'',
		'typer' => '',
	]);
	//FOR DEBUGGER
/*	$edit = $edit;
	$handle = $handle;
	$htmlid = $htmlid;
	$style = $style;
	$typer = $typer;
*/
    if (!$htmlid) {
        return '';
    }

	$fixed = ($edit) ? 'false' : 'true';

	if ($typer) {
		if (is_file($typer)) {
			$filename = basename($typer);
		} else {
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
		$mode = '';
	}

	$urlroot = $mod->GetPreference('codemirror_url', CoreTextEditing::CM_CDN); //local or CDN

	if (!empty($CMS_ADMIN_PAGE)) {
		if (!$style) {
			$style = cms_userprefs::get_for_user(get_userid(false), 'editor_theme');
			if (!$style) {
				$style = cms_siteprefs::get('editor_theme', CoreTextEditing::CM_THEME);
			}
		}
	}
	$style = strtolower($style);

	$css = <<<EOS
<link rel="stylesheet" href="$urlroot/codemirror.css">
<style>
pre.CodeMirror-line {
 display: inherit
}
</style>

EOS;
	if ($style) {
		$css .= <<<EOS
<link rel="stylesheet" href="$urlroot/theme/$style.css">

EOS;
	}

	$js = <<<EOS
<script defer type="text/javascript" src="$urlroot/codemirror.min.js"></script>

EOS;
	if ($mode) {
		$js .= <<<EOS
<script defer type="text/javascript" src="$urlroot/mode/$mode/$mode.js"></script>

EOS;
    } else {
		$js .= <<<EOS
<script defer type="text/javascript" src="$urlroot/addon/mode/loadmode.js"></script>
<script defer type="text/javascript" src="$urlroot/mode/meta.js"></script>

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
  theme: '$style'
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
	$js .= <<<EOS
});
function getcontent() {
 return container.val();
}
function setcontent(v) {
 container.val(v);
}
//]]>
</script>

EOS;
	return ['head'=>$css, 'foot'=>$js];
}
