<?php
/*
Editor definition for ysEditor rich-text editor
Copyright (C) 2019-2020 Tom Phane <tomph@cmsmadesimple.org>
This file is a component of the RichEditing module for use with
CMS Made Simple <http://www.cmsmadesimple.org>

This file is free software. You can redistribute it and/or modify it
under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This file is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
<https://www.gnu.org/licenses/#AGPL>.
*/

namespace RichEditing\ysEditor;

const YSED_THEME = 'light';

$const_prefix = 'YSED_';

/**
 * Get javascript, css for initialization and operation of editor(s)
 *
 * @param CMSModule $mod the current module
 * @param array $params  Configuration details. Recognized members are:
 *  bool   'edit'   whether the content is editable. Default false (i.e. just for display)
 *  bool   'frontend' whether the editor is being used in a frontend page. Default false.
 *  string 'handle' js variable (name) for the created editor. Default 'editor'
 *  string 'htmlclass' class of the page-element(s) whose content is to be edited. Default ''.
 *  string 'htmlid' id of the page-element whose content is to be edited. Default 'richeditor'.
 *  string 'theme'  override for the normal editor theme/style.  Default ''
 *  string 'workid' id of a div to be created to work on the content of htmlid-element. Default 'edit_work'
 *
 * @return array up to 2 members, being 'head' and/or 'foot'
 */
function GetPageSetup($mod, array $params) //: array
{
    static $ctr = 1; //multi-editor counter

    extract($params + [
        'edit' => false,
        'frontend' => false,
        'handle' => 'editor',
        'htmlid' => 'richeditor', //for single editor
        'htmlclass' => '', //support multiple editors
        'theme' =>'',
        'workid' => 'edit_work',
    ]);

    if (!($htmlclass || $htmlid)) {
        return [];
    } elseif (!$htmlclass) {
        $htmlid = trim($htmlid);
        if (!$htmlid) {
            return [];
        }
        $selector = '#'.$htmlid;
    } elseif (strpos($htmlclass, '.') !== false ) {
        $selector = $htmlclass;
    } else {
        $selector = '.'.$htmlclass;
    }
/*
    // get preferred editor theme
    if (CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE)) {
        if (!$theme) {
            $theme = cms_userprefs::get_for_user(get_userid(false), 'richeditor_theme');
            if (!$theme) {
                $theme = cms_siteprefs::get('richeditor_theme', YSED_THEME);
            }
        }
    } elseif (!$theme) {
        //TODO
    }
    $theme = strtolower($theme);
    $theme = 'dark'; //DEBUG
*/
    $baseurl = $mod->GetModuleURLPath();
    $basepath = $mod->GetModulePath();
    $relpath = substr(__DIR__, strlen($basepath.DIRECTORY_SEPARATOR.$mod->GetName()));
    $relurl = strtr($relpath, '\\', '/');
    $urlroot = $baseurl .'/'. $relurl; //no trailing separator
    $parenturl = $baseurl . '/lib';
/*
    // N/A for ysEditor?? get relevant translation info
    $wantlang = CmsNlsOperations::get_current_language();
    if ($wantlang == '') {
        $wantlang = 'en'; //Lang setting "No default selected"
    }
    list($uselang, $langpath) = RichEditing\Utils::GetLangData($wantlang, 'trumbowyg', __DIR__.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext', '.min.js');
    if ($langpath) {
        $relpath = substr($langpath, strlen($basepath));
        $relurl = strtr($relpath, '\\', '/');
        $langscript = <<<EOS
<script type="text/javascript" src="{$baseurl}{$relurl}"></script>
EOS;
    } else {
        $uselang = 'en';
        $langscript = '';
    }
*/
    $fixed = ($edit) ? 'false' : 'true';

 //OR .min
//TODO custom css
    $oh = <<<EOS
<link rel="stylesheet" href="{$urlroot}/yseditor.css" />
<script type="text/javascript" id="mainsource" src="{$urlroot}/yseditor.js"></script>
EOS;

/* TODO customisation
    $handle.defineButton("name", {
      command: "",
      text: "",
      title: "",
      value: "",
      callback: function(button, editor){}
    });
*/

    $jsf = <<<EOS
<script type="text/javascript">
//<![CDATA[
// jshint esversion: 6
if (typeof Symbol === 'undefined') {
 var xjS = document.createElement('script');
 xjS.type = 'text/javascript';
 xjS.rel = 'preload';
 xjS.src = "{$parenturl}/ec6support.min.js";
 document.getElementById('mainsource').insertBefore(xjS);
}

var $handle, $workid, container;
$(function() {
/*
    each predefinedButtons[] .title = translated
    $handle.defineButton("name", {
      command: "",
      text: "",
      title: "",
      value: "",
      callback: function(button, editor){}
    });
*/
  container = $('$selector');
  container.each(function(idx) {
    var t = this;
    t.style.display = 'none';
    $handle = new ysEditor({
     wrapper: t,
     toolbar: [
      'h1', 'h2', 'h3', 'p', 'quote',
      'bold', 'italic', 'underline', 'strikethrough', 'sup', 'sub',
      'left', 'center', 'right', 'justify',
      'removeformat',
      'ol', 'ul',
      'undo', 'redo'
      ],
     includeContent: true,
     footer: false
    });
  });

EOS;
    if ($edit) {
        if ($htmlclass) {
            $jsf .= <<<EOS
function todo(v) {
  container.each(function() {
  //TODO js foreach editor
  });
}

EOS;
        }
        $jsf .= <<<EOS
function seteditorcontent(v) {
 $handle.setText(v);
}
function geteditorcontent() {
 return $handle.getText();
}
function setpagecontent(v) {
 t.textContent = v;
}

EOS;
    }
    $jsf .= <<<EOS
//]]>
</script>

EOS;

    return ['head'=>$oh, 'foot'=>$jsf];
}
