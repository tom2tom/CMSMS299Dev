<?php
/*
CoreTextEditing module editor definition for Ace
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CoreTextEditing\Ace;

use cms_siteprefs;
use cms_userprefs;
use CMSModule;
use CMSMS\AppState;
use function get_userid;

/**
 * Default cdn URL for retrieving Ace text-editor code
 */
const ACE_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.7';

/**
 * Default theme/style for Ace text-editor
 */
const ACE_THEME = 'clouds';

$const_prefix = 'ACE_';

/**
 * Get javascript etc for running Ace text-editor(s) on a webpage
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
 *  string 'workid' id of a div to be created to work on the content of htmlid-element. Default 'edit_work'
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
		'workid' => 'edit_work',
	]);
	//FOR DEBUGGER
/*	$edit = $edit;
	$handle = $handle;
	$htmlid = $htmlid;
	$htmlclass = $htmlclass;
	$theme = $theme;
	$typer = $typer;
	$workid = $workid;
*/
	if (!($htmlid || $htmlclass)) {
		return [];
	}

	$fixed = ($edit) ? 'false' : 'true';

	if ($typer) {
		if (is_file($typer)) {
			$filepath = $typer;
			$mode = '';
		} else {
			$filepath = '';
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
				'phphp' => 'php', //i.e. UserTagOperations::PLUGEXT
//				'plugin' => 'php',
//				'cmsplugin' => 'php',
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
		$filepath = '';
		$mode = '';
	}

	$urlroot = $mod->GetPreference('ace_source_url', ACE_CDN); //local or CDN

	if (AppState::test_state(AppState::STATE_ADMIN_PAGE)) {
		if (!$theme) {
			$theme = cms_userprefs::get_for_user(get_userid(false), 'syntax_theme');
			if (!$theme) {
				$theme = cms_siteprefs::get('syntax_theme', ACE_THEME);
			}
		}
	}
	$theme = strtolower($theme);

	$js = <<<EOS
<script type="text/javascript" src="$urlroot/ace.js" defer></script>

EOS;
	if (!$mode) {
		$js .= <<<EOS
<script type="text/javascript" src="$urlroot/ext-modelist.js" defer></script>

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
  theme: 'ace/theme/$theme'
 });
 worker.show();

EOS;
	//TODO handle class-selector instead of id
	if ($edit) {
		$js .= <<<EOS
 var dirty = false;
 worker = worker.find('textarea'); // by now ace has injected this
 worker.on('input cut paste', function() {
  dirty = true;
  worker.off('input cut paste', worker);
 }).on('blur', function() {
  if(dirty) {
   container.val($handle.session.getValue());
   $(document).trigger('cms_textchange');
  }
 });
 container.closest('form').on('submit', function() {
  if(dirty) {
	container.val($handle.session.getValue());
  }
 });

EOS;
	}
	$js .= <<< EOS
});
function seteditorcontent(v,m) {
 $handle.session.setValue(v);
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
