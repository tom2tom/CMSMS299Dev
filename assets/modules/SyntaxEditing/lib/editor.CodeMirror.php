<?php
/*
SyntaxEditing module editor definition for CodeMirror
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
namespace SyntaxEditing\CodeMirror;

use cms_siteprefs;
use cms_userprefs;
use CmsApp;
use CMSModule;
//use CMSMS\AppState; 2.3
use function get_userid;

/**
 * Default cdn URL for retrieving CodeMirror text-editor code
 */
const CM_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.52.0';

/**
 * Default theme/style for CodeMirror text-editor
 */
const CM_THEME = 'elegant';

$const_prefix = 'CM_';

/**
 * Get javascript etc for running CodeMirror text-editor(s) on a webpage
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
function GetPageSetup(&$mod, array $params) //: array
{
    extract($params + [
        'edit' => false,
        'htmlclass' => '',
        'htmlid' => 'edit_area',
        'handle' => 'editor',
        'typer' => '',
        'theme' =>'',
    ]);
/* FOR DEBUGGING
    $edit = $edit;
    $htmlclass = $htmlclass;
    $htmlid = $htmlid;
    $handle = $handle;
    $typer = $typer;
    $theme = $theme;

    $edit = true;
    $handle = 'editor';
*/
    if (!($htmlclass || $htmlid)) {
        return [];
    }

    if ($typer) {
        if (is_file($typer)) {
            $filename = basename($typer);
            $mode = '';
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
                'phphp' => 'php', //i.e. SimpleTagOperations::PLUGEXT
//              'plugin' => 'php',
//              'cmsplugin' => 'php',
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

    if (CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE)) {//if (AppState::test_state(AppState::STATE_ADMIN_PAGE)) {
        if (!$theme) {
            // recorded theme-preference may be for a different editor
            $user_id = get_userid(false);
            $ed = cms_userprefs::get_for_user($user_id, 'syntax_editor');
            if (!$ed ) {
                $ed = cms_siteprefs::get('syntax_theme', CM_THEME);
            }
            if ($ed && stripos($ed, 'codemirror') !== false) {
                $theme = cms_userprefs::get_for_user($user_id, 'syntax_theme');
                if (!$theme) {
                    $theme = cms_siteprefs::get('syntax_theme', CM_THEME);
                }
            } else {
                $theme = ACE_THEME;
            }
        }
    } else {
        $theme = CM_THEME;
    }
    $theme = strtolower($theme);

    $urlroot = $mod->GetPreference('codemirror_source_url', CM_CDN); //local or CDN
    $fixed = ($edit) ? 'false' : 'true';
    $notfixed = ($edit) ? 'true' : 'false';

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
    //TODO support multi instances per $htmlclass as well as a single per $htmlid
    $js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
var $handle,\$src_elem,src_ta;
$(function() {

EOS;
    if ($htmlid) {
        $js .= <<<EOS
 \$src_elem = $('#$htmlid');

EOS;
    } else {
        $js .= <<<EOS
 \$src_elem = $('.$htmclass').eq(0);

EOS;
    }
    $js .= <<<EOS
 src_ta = (\$src_elem[0].tagName === 'TEXTAREA');
 if(src_ta) {
  $.valHooks.textarea = {
   get: function(el) {
EOS;
    $js .= <<<'EOS'
    return el.value.replace(/\r?\n/g,"\n");

EOS;
    $js .= <<<EOS
   }
  };
  $handle = CodeMirror.fromTextArea(\$src_elem[0],{
   foldGutter: true,
   gutters: ["CodeMirror-foldgutter"],
   lineNumbers: $notfixed,
   readOnly: $fixed,
   theme: '$theme'
  });
 } else {
  $handle = CodeMirror(\$src_elem[0],{
   foldGutter: true,
   gutters: ["CodeMirror-foldgutter"],
   lineNumbers: $notfixed,
   readOnly: $fixed,
   theme: '$theme'
  });
 }

EOS;
    if (!$mode && $filename) {
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
    //TODO support multi instances per $htmlclass as well as a single per $htmlid
    if ($edit) {
        $js .= <<<EOS
function seteditorcontent(v,m) {
 $handle.setValue(v);
 if(typeof m !== 'undefined') {
  $handle.setOption('mode',m);
// } else {
// TODO generic mode-interpreter
 }
}
function geteditorcontent() {
 return $handle.getValue();
}
function setpagecontent(v) {
 if(src_ta) {
  \$src_elem.val(v);
 } else {
  \$src_elem.html(v);
 }
}

EOS;
    }
    $js .= <<<EOS
//]]>
</script>

EOS;
    return ['head'=>$css, 'foot'=>$js];
}
