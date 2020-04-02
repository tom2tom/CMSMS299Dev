<?php
/*
Editor definition for Trumbowyg rich-text editor
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

namespace RichEditing\Trumbowyg;

use CmsApp;
use CmsNlsOperations;
use RichEditing\Utils;

//const TWYG_URL = 'https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.18.0/trumbowyg.min.js';
const TWYG_THEME = 'light';

$const_prefix = 'TWYG_';

/*
    public function GetStyles(&$mod, $params)
    {
        $baseurl = $m?M

        <od->GetModuleURLPath();
    $out = <<<EOS
<link rel="stylesheet" href="{$baseurl}/dist/ui/trumbowyg.min.css">
EOS;
        return $out;
    }
*/

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
 *  //string 'workid' id of a div to be created to work on the content of htmlid-element. Default 'edit_work'
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
        'theme' => '',
//      'workid' => 'edit_work',
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

    // get preferred editor theme
    if (CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE)) {
        if (!$theme) {
            $theme = cms_userprefs::get_for_user(get_userid(false), 'wysiwyg_theme');
            if (!$theme) {
                $theme = cms_siteprefs::get('wysiwyg_theme', TWYG_THEME);
            }
        }
    } elseif (!$theme) {
        $theme = TWYG_THEME; //TODO
    }
    $theme = strtolower($theme);

    $url = $mod->create_url('m1_','ajax_getpages');
    $ajaxurl = str_replace('&amp;', '&', $url);
    if (defined('CMS_JOB_KEY')) { $ajaxurl .= '&'.CMS_JOB_KEY.'=1'; }
    $baseurl = $mod->GetModuleURLPath();
    $parenturl = $baseurl . '/lib';
    $basepath = $mod->GetModulePath();
    $relpath = substr(__DIR__, strlen($basepath)); //leading separator
    $relurl = strtr($relpath, '\\', '/');
    $urlroot = $baseurl . $relurl; //no trailing separator

    // get relevant translation info
    $wantlang = CmsNlsOperations::get_current_language();
    if ($wantlang == '') {
        $wantlang = 'en'; //Lang setting "No default selected"
    }
    list($uselang, $langpath) = Utils::GetLangData('Trumbowyg', $wantlang, __DIR__.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext', '.min.js');
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

    $fixed = ($edit) ? 'false' : 'true';

//TODO custom css
//TODO custom plugins
//<script type="text/javascript" src="{$urlroot}/trumbowyg-plugins.min.js"></script>
//if working on this: <script type="text/javascript" src="{$urlroot}/cmsms-plugins/mailto/trumbowyg.mailto.js"></script>

    $oh = <<<EOS
<link rel="stylesheet" href="{$urlroot}/css/trumbowyg-{$theme}.css">
<link rel="stylesheet" href="{$urlroot}/css/trumbowyg-plugins-{$theme}.css">
<link rel="stylesheet" href="{$urlroot}/cmsms-plugins/pagelink/ui/trumbowyg.pagelink.css">
<link rel="stylesheet" href="{$parenturl}/css/tools.css">
<script type="text/javascript" src="{$urlroot}/doofor.js"></script>
<script type="text/javascript" id="mainsource" src="{$urlroot}/trumbowyg.js"></script>
$langscript
<script type="text/javascript" src="{$urlroot}/trumbowyg-plugins.min.js"></script>
<script type="text/javascript" src="{$parenturl}/Trumbowyg/plugins-2.21-WORK/link/trumbowyg.link.js"></script>
<script type="text/javascript" src="{$urlroot}/trumbowyg.anchor.js"></script>
<script type="text/javascript" src="{$urlroot}/trumbowyg.audio.js"></script>
<script type="text/javascript" src="{$urlroot}/trumbowyg.emoji.js"></script>
<script type="text/javascript" src="{$urlroot}/trumbowyg.image.js"></script>
<script type="text/javascript" src="{$urlroot}/trumbowyg.pagelink.js"></script>
<script type="text/javascript" src="{$urlroot}/trumbowyg.specialchars.js"></script>
<script type="text/javascript" src="{$urlroot}/trumbowyg.table.js"></script>
<script type="text/javascript" src="{$urlroot}/trumbowyg.video.js"></script>

EOS;
/*
   frontend toolbar items
     no internal-page-link
     no upload operation for insertion-links

  btnsDef: {
   // Customizable dropdowns
   align: {
    dropdown: ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
    ico: 'justifyLeft'
   },
   image: {
    dropdown: ['insertImage', 'base64'],
    ico: 'insertImage'
   }
   link: {
    dropdown: [],
    ico: ''
   }
  },
// UI string adjustments, if any
$.extend(true, $.trumbowyg.langs, {
  $uselang: {
   align: {$mod->Lang()} //'Alignment',
   image: {$mod->Lang()} //'Image'
  }
});

TODO undo/redo via tool btns
TODO button defns for these
  insertVideo: 'Insert Video',
  anchor: 'Anchor',
  h5
  h6
*/
    $jsf = <<<EOS
<script type="text/javascript">
//<![CDATA[
if(typeof String.prototype.trim === 'undefined') {
 var xjS = document.createElement('script');
 xjS.type = 'text/javascript';
 xjs.rel = 'preload';
 xjS.src = "{$parenturl}/ec5support.min.js";
 document.getElementById('mainsource').insertBefore(xjS);
}

var container, $handle = null; //handle N/A?
$(function() {
 cms_data.ajax_autocomplete_url = '$ajaxurl'; // + '&' + cms_data.secure_param_name + '=' + cms_data.user_key;
 container = $('$selector');
 container.trumbowyg({
  autogrow: true,
  disabled: $fixed,
  lang: '$uselang',
  fontImages: true,
  fontPrefix: 'redi-',
  svgPath: false,
  changeActiveDropdownIcon: true,
  semantic: {
    'i': 'i'
  },
  tagsToKeep: ['hr', 'img', 'embed', 'iframe', 'input', 'i'], //full list, array-property not extensible
  fixedBtnPane: true,
  btnsDef: {
   formatting: {
    text: '&#182;',
    ico: 'p',
    dropdown: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote']
   },
   align: {
    text: '&#9776;',
    ico: 'justify-left',
    dropdown: ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull']
   },
   link: {
    text: '&#11814;&#11815;<sub>&#129171</sub>',
    ico: 'create-link',
    dropdown: ['createLink', 'anchor', 'pagelink', 'unlink']
   },
   feature: {
//    text: 'TODO',
    ico: 'insert-image',
    dropdown: ['insertImage', 'base64', 'insertVideo', 'insertAudio', 'mailto', 'emoji', 'specialChars']
   },
   table: {
//    text: 'TODO',
    ico: 'table',
    dropdown: ['tableAdd', 'tableDestroy', 'tableAddColumnLeft', 'tableAddColumn', 'tableDeleteColumn', 'tableAddRowAbove', 'tableAddRow', 'tableDeleteRow']
   }
  },
  btns: [  //another replacement-property
   'formatting',
   'semantize',
   ['strong', 'em', 'del', 'superscript', 'subscript', 'align', 'removeformat'] ,
   ['orderedList', 'unorderedList', 'link', 'table', 'feature', 'horizontalRule'],
   ['undo', 'redo'],
   'viewHTML'
  ]
 });
});

EOS;
    if ($edit) {
        if ($htmlclass) {
            $jsf .= <<<EOS
function todo(v) {
 container.each(function() {
  //TODO js foreach editor
  this.trumbowyg('html',v);
 });
}

EOS;
        }
        $jsf .= <<<EOS
function seteditorcontent(v) {
 container.trumbowyg('html',v);
}
function geteditorcontent() {
 return container.trumbowyg('html');
}
function setpagecontent(v) {
 container.val(v);
}

EOS;
    }
    $jsf .= <<<EOS
//]]>
</script>

EOS;

    return ['head' => $oh, 'foot' => $jsf];
}
