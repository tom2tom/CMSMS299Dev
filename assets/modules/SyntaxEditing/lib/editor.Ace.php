<?php
/*
SyntaxEditing module editor definition for Ace
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
namespace SyntaxEditing\Ace;

use cms_siteprefs;
use cms_userprefs;
use CmsApp;
use CMSModule;
//use CMSMS\AppState; 2.3
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
function GetPageSetup(&$mod, array $params) //: array
{
    extract($params + [
        'edit' => false,
        'htmlclass' => '',
        'htmlid' => 'edit_area',
        'workid' => 'edit_work',
        'handle' => 'editor',
        'typer' => '',
        'theme' =>'',
    ]);
/* FOR DEBUGGING
    $edit = $edit;
    $htmlclass = $htmlclass;
    $htmlid = $htmlid;
    $workid = $workid;
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
                'phphp' => 'php', //i.e. SimpleTagOperations::PLUGEXT
//              'plugin' => 'php',
//              'cmsplugin' => 'php',
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

    if (CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE)) {//if (AppState::test_state(AppState::STATE_ADMIN_PAGE)) {
        if (!$theme) {
            // recorded theme-preference may be for a different editor
            $user_id = get_userid(false);
            $ed = cms_userprefs::get_for_user($user_id, 'syntax_editor');
            if (!$ed ) {
                $ed = cms_siteprefs::get('syntax_theme', ACE_THEME);
            }
            if ($ed && stripos($ed, 'Ace') !== false) {
                $theme = cms_userprefs::get_for_user($user_id, 'syntax_theme');
                if (!$theme) {
                    $theme = cms_siteprefs::get('syntax_theme', ACE_THEME);
                }
            } else {
                $theme = ACE_THEME;
            }
        }
    } else {
        $theme = ACE_THEME;
    }
    $theme = strtolower($theme);

    $urlroot = $mod->GetPreference('ace_source_url', ACE_CDN); //local or CDN
    $fixed = ($edit) ? 'false' : 'true';
    $notfixed = ($edit) ? 'true' : 'false';

    //TODO if relevant handle class-selector $htmlclass instead of id $htmlid
    //see  examples at https://stackoverflow.com/questions/6440439/how-do-i-make-a-textarea-an-ace-editor
    // and https://gist.github.com/duncansmart/5267653
    $js = <<<EOS
<script type="text/javascript" src="$urlroot/ace.js" defer></script>

EOS;
    if (!$mode) {
        $js .= <<<EOS
<script type="text/javascript" src="$urlroot/ext-modelist.js" defer></script>

EOS;
    }

    //TODO support multi instances per $htmlclass, some other $handle ?
    $js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
var $handle,src_ta;

EOS;
    if ($htmlid) {
        $js .= <<<EOS
var \$src_elem = $('#$htmlid');

EOS;
    } else {
        $js .= <<<EOS
var \$src_elem = $('.$htmclass').eq(0);

EOS;
    }
    $js .= <<<EOS
\$src_elem.hide();
$(function() {
 var worker;
 src_ta = (\$src_elem[0].tagName === 'TEXTAREA');
 if (src_ta) {
  $.valHooks.textarea = {
   get: function(el) {

EOS;
    // this bit must be processed literally
    $js .= <<<'EOS'
    return el.value.replace(/\r?\n/g,"\n");

EOS;
    $js .= <<<EOS
   }
  };
  \$src_elem.after('<div id=$workid style="display:none;" />');
  worker = $('#$workid');
  //pure js, cuz jQuery assignment corrupts things ?
   worker.text(\$src_elem.val());
 } else {
  worker = \$src_elem;
//worker.addClass(whatever);
 }
 $handle = ace.edit(worker[0]);

EOS;
    if ($mode) {
        $js .= <<<EOS
 $handle.session.setMode("ace/mode/$mode");

EOS;
    } elseif ($filepath) {
        $js .= <<<EOS
 var modelist = ace.require("ace/ext/modelist");
 var mode = modelist.getModeForPath("$filepath").mode;
 $handle.session.setMode(mode);

EOS;
    } else {
        //TODO some default syntax or runtime selector
    }

    //TODO runtime adjustment of maxLines, to keep hscrollbar at window-bottom
    $js .= <<<EOS
 var sz = \$src_elem.css('font-size');
 $handle.setOptions({
  autoScrollEditorIntoView: false,
  fontSize: sz,
  maxLines: Infinity,
  readOnly: $fixed,
  showPrintMargin: false,
 });
 $handle.renderer.setOptions({
  displayIndentGuides: $notfixed,
  showGutter: $notfixed,
  showLineNumbers: $notfixed,
  theme: 'ace/theme/$theme'
 });
 worker.show();

EOS;

    if ($edit) {
        //TODO support multi instances per $htmlclass
        $js .= <<<EOS
 var dirty = false;
 worker = worker.find('textarea'); // by now ace has injected this
 worker.on('input cut paste', function() {
  dirty = true;
  worker.off('input cut paste', worker);
 }).on('blur', function() {
  if(dirty) {
   if (src_ta) {
    \$src_elem.val($handle.session.getValue());
   } else {
    \$src_elem.html($handle.session.getValue());
   }
   $(document).trigger('cms_textchange');
  }
 });
 \$src_elem.closest('form').on('submit', function() {
  if(dirty) {
   if(src_ta) {
    \$src_elem.val($handle.session.getValue());
   } else {
    \$src_elem.html($handle.session.getValue());
   }
  }
 });
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
 if(src_ta) {
  \$src_elem.val(v);
 } else {
  \$src_elem.html(v);
 }
}

EOS;
    } else {
        $js .= <<< EOS
});

EOS;
    }
    $js .= <<< EOS
//]]>
</script>

EOS;
    return ['foot'=>$js];
}
