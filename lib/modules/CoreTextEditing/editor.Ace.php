<?php
/*
CoreTextEditing module editor definition for Ace
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

//use function ace_GetScript as GetScript;

/**
 * Get javascript for initialization of Ace text-editor
 *
 * @param CMSModule $mod the current module
 * @param array $params  Configuration details. Recognized members are:
 *  bool   'edit'   whether the content is editable. Default false (i.e. just for display)
 *  string 'handle' js variable (name) for the created editor. Default 'editor'
 *  string 'htmlid' id of the page-element whose content is to be edited. Default 'edit_area'.
 *  string 'style'  override for the normal editor theme/style.  Default ''
 *  string 'typer'  content-type identifier, an absolute filepath or at least
 *    an extension or pseudo (like 'smarty'). Default ''
 *  string 'workid' id of a div to be created to work on the content of htmlid-element. Default 'edit_work'
 *
 * @return array up to 2 members, being 'head' and/or 'foot'
 */
function GetScript(&$mod, array $params) : array
{
	global $CMS_ADMIN_PAGE;

	extract($params + [
		'edit' => false,
		'handle' => 'editor',
		'htmlid' => 'edit_area',
		'style' =>'',
		'typer' => '',
		'workid' => 'edit_work',
	]);
	//FOR DEBUGGER
/*	$edit = $edit;
	$handle = $handle;
	$htmlid = $htmlid;
	$style = $style;
	$typer = $typer;
	$workid = $workid;
*/
    if (!$htmlid) {
        return '';
    }

	$fixed = ($edit) ? 'false' : 'true';

	if ($typer) {
		if (is_file($typer)) {
			$filepath = $typer;
			$mode = '';
		} else {
			$filepath = __FILE__; //default php mode
			$p = strrpos($typer, '.');
			$mode = substr($typer, ($p !== false) ? $p+1:0);
			$mode = strtolower($mode);
			// some of ace's many lexers which are more likely in this context
			$known = [
				'css' => 1,
				'htm' => 'html',
				'html' => 1,
				'ini' => 1,
				'js' => 'javascript',
				'javascript' => 1,
				'php' => 1,
				'smarty' => 1,
				'tpl' => 'smarty',
				'text' => 1,
				'txt' => 'text',
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
		$filepath = __FILE__; //php mode
		$mode = '';
	}

	$urlroot = $mod->GetPreference('ace_url', CoreTextEditing::ACE_CDN); //local or CDN

	if (!empty($CMS_ADMIN_PAGE)) {
		if (!$style) {
			$style = cms_userprefs::get_for_user(get_userid(false), 'editor_theme');
			if (!$style) {
				$style = cms_siteprefs::get('editor_theme', CoreTextEditing::ACE_THEME);
			}
		}
	}
	$style = strtolower($style);

	$js = <<<EOS
<script defer type="text/javascript" src="$urlroot/ace.js"></script>

EOS;
	if (!$mode) {
		$js .= <<<EOS
<script defer type="text/javascript" src="$urlroot/ext-modelist.js"></script>

EOS;
	}

	$js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
var $handle,container = $('#$htmlid');
container.hide();
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
 container.after('<div id=$workid style="display:none;" />');
 var worker = $('#$workid');
 worker.text(container.val());
 $handle = ace.edit(worker[0]);

EOS;
	if ($mode) {
		$js .= <<<EOS
 $handle.session.setMode("ace/mode/$mode");

EOS;
	} else {
		$js .= <<<EOS
 (function () {
  var modelist = ace.require("ace/ext/modelist");
  var mode = modelist.getModeForPath("$filepath").mode;
  $handle.session.setMode(mode);
 }());

EOS;
	}
	//TODO runtime adjustment of maxLines, to keep hscrollbar at window-bottom
	$js .= <<<EOS
 var sz = container.css('font-size');
 $handle.setOptions({
  autoScrollEditorIntoView: false, //CHECKME
  fontSize: sz,
  maxLines: Infinity,
  readOnly: $fixed,
  showPrintMargin: false,
 });
 $handle.renderer.setOptions({
  displayIndentGuides: true,
  showGutter: true,
  showLineNumbers: false,
  theme: 'ace/theme/$style'
 });
 worker.show();

EOS;
    if ($edit) {
        $js .= <<<EOS
 worker.on('blur', function() {
  container.val($handle.session.getValue());
 });
 container.closest('form').on('submit', function() {
  container.val($handle.session.getValue());
 });

EOS;
    }
    $js .= <<< EOS
});
function seteditorcontent(v,m) {
 $('#$workid').text(v);
 if(typeof m !== 'undefined') {
  $handle.session.setMode('ace/mode/' + m);
 }
}
function geteditorcontent() {
 return $handle.session.getValue();
}
function setpagecontent(v) {
 container.val(v);
}
//]]>
</script>

EOS;
	return ['foot'=>$js];
}
