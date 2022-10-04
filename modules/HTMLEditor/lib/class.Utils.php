<?php
/*
Utility-methods class for HTMLEditor module
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of the HTMLEditor module for CMS Made Simple
<http://dev.cmsmadesimple.org/projects/HTMLEditor>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace HTMLEditor;

//use CMSMS\AdminUtils;
use CMSModule;
use CMSMS\NlsOperations;
use CMSMS\ScriptsMerger;
use CMSMS\StylesheetOperations;
use CMSMS\Utils as AppUtils;
use HTMLEditor;
use HTMLEditor\Profile;
use RuntimeException;
use Throwable;
use const CMS_ASSETS_URL;
use const CMS_JOB_KEY;
use const CMS_ROOT_PATH;
use const CMS_ROOT_URL;
use const TMP_CACHE_LOCATION;
use function _la;
use function _ld;
use function add_page_headtext;
use function cms_get_script;
use function cms_path_to_url;
use function cmsms;
use function CMSMS\is_frontend_request;
use function CMSMS\preferred_lang;
use function endswith;
use function startswith;

class Utils
{
    /**
     * Get page-header content (js and/or css) needed to use this WYSIWYG.
     * Used during post-action page-processing and/or by cms_init_editor plugin.
     *
     * @staticvar int $ctr
     * @param string $selector Optional .querySelector()-compatible CSS selector
     * @param string $css_name Optional stylesheet name
     * @param array $params Optional expanded setup parameters. Recognized members are:
     *  bool   'edit'   whether the content is editable. Default true (i.e. not just for display)
     *  bool   'frontend' whether the editor is being used in a frontend page. Default false.
     *  string 'handle' js variable (name) for the created editor. Default 'editor'
     *  string 'htmlclass' class of the page-element(s) whose content is to be edited. Default ''.
     *  string 'htmlid' id of the page-element whose content is to be edited. Default 'richeditor'.
     *  string 'stylesheet' name of a stylesheet to include c.f. {cms_stylesheet name=$css_name nolinks=1}.
     *  string 'theme'  override for the normal editor theme/style.  Default 'light'
     *  string 'workid' id of a div to be created to work on the content of htmlid-element. Default 'edit_work'
     * @return string
     * @throws RuntimeException
     */
    public static function WYSIWYGGenerateHeader($selector='', $css_name='', $params=[])
    {
        // static properties here >> Lone property|ies ?
        static $usedselectors = []; //selectors cache

        extract($params + [
            'edit' => true,
//          'frontend' => see below
            'handle' => 'editor',
            'htmlid' => '', //for single editor
            'htmlclass' => '', //possibly for multiple editors
            'stylesheet' => '',
            'theme' => 'light', // TODO
            'workid' => 'edit_work',
        ]);

        if ($htmlid) {
            $selector = '#'.trim($htmlid);
        } elseif ($htmlclass) {
            $selector = '.'.trim($htmlclass);
        }
        if (!$selector) {
            $selector = 'textarea.HTMLEditor';
        }
        if (!in_array($selector, $usedselectors)) {
            $usedselectors[] = $selector;
        } else {
            return '';
        }

        // confirm module presence
        $mod = AppUtils::get_module('HTMLEditor');
        if (!is_object($mod)) {
            throw new RuntimeException('Could not find the HTMLEditor module...');
        }

        if (!isset($frontend)) {
            $frontend = is_frontend_request();
        }

        try {
            $profile = ($frontend) ?
                Profile::load(HTMLEditor::PROFILE_FRONTEND):
                Profile::load(HTMLEditor::PROFILE_ADMIN);
        }
        catch (Throwable $t) {
            // oops, we gots a problem.
            exit($t->Getmessage());
        }

        // get the stylesheet that we're going to use (either passed in, or from profile)
        if (!$profile['allowcssoverride']) {
            // not allowing override
            $css_id = (int)$profile['dfltstylesheet'];
            if ($css_id > 0) {
                $css_name = $css_id;
            } else {
                $css_name = '';
            }
        } elseif ($stylesheet) {
            $css_name = trim($stylesheet);
        }
        // or else some default css to supplement editor skin ?

        // if we have a stylesheet name, use it
        if ($css_name) {
            try {
                $css = StylesheetOperations::get_stylesheet($css_name);
                $css_name = $css->get_name();
            }
            catch (Throwable $t) {
                // couldn't load the stylesheet
                $css_name = '';
            }
        }

        $ctr = count($usedselectors); // differentiator
        $base_url = $mod->GetModuleURLPath();
        $srcurl = $mod->GetPreference('source_url'); // TODO check trailing '/summernote[.min].js', drop|append as appropriate

        $theme = $mod->GetPreference('theme'); // TODO site or user-specific param value
        $theme = false; //DEBUG
        if ($theme) { // 'dark', 'light' etc?
            if (startswith($theme, 'http') && endswith($theme, '.css')) {
                $themeurl = $theme;
            } elseif (strpos($theme, '/lib/summernote/') !== false) {
                $themeurl = $base_url . '/lib/summernote/' . trim($theme, ' /');
            } else { // if(?)
                $themeurl = CMS_ROOT_URL . '/' . trim($theme, ' /');
            }
        } else {
            $themeurl = $base_url.'/lib/summernote/summernote-lite.css';
        }
        if ($css_name !== '') {
            $xcss = "\n{cms_stylesheet name=$css_name nolinks=1}\n";
        } else {
            $xcss = '';
        }

        $jsm = new ScriptsMerger();

//      $pickurl = cms_get_script('jquery.cmsms_filepicker.js'); //TODO could be @ bottom or in $jsm
//      $pickurl1 = cms_get_script('filebrowser.js'); //ditto
        $pickurl2 = $base_url.'/lib/js/jquery-stack-menu.min.js'; //ditto
//      $pickurl3 = cms_get_script('jquery.basictable.js');
        //$output = " ... "; add_page_foottext($output);
//      $pickurl4 = cms_get_css('basictable.css');
// TODO replace some of the above with FilePicker::get_browsedata($parms, bool $framed)
        $cssinc = '';
        $jsinc = '';
        $fpm = AppUtils::get_filepicker_module();
        if ($fpm) {
            $parms = [ //TODO
//'container' => $container, //unframed
//'cwd' => $cwd, //framed ?
//'upurl' => $upurl, //framed ?
//'exts' => $extensions,
//'inst' => $inst,
//'listurl' => $this->get_browser_url(),
//'mime' => $mime,
//'typename' => $typename
            ];
            list($incpaths, $customjs) = $fpm->get_browsedata($parms, false);
            foreach ($incpaths as $fp) {
                if (endswith($fp, 'css')) {
                    $url = cms_path_to_url($fp);
                    $cssinc .= '<link rel="stylesheet" href="' . $url . '">'."\n";
                } else {
//DEBUG             $jsm->queue_file($fp);
                    $url = cms_path_to_url($fp);
                    $jsinc .= '<script type="text/javascript" src="' .$url . '"></script>'."\n";
                }
            }
            $cssinc = rtrim($cssinc);
            $jsinc = rtrim($jsinc);
        } else {
            throw new RuntimeException('Could not find the FilePicker module...');
        }

        // TODO module/custom css for plugin-dialogs incl. tabs, rtl if appropriate

        if ($ctr == 1) {
            // once-per-request setup
            $cspext = '';
            $local = startswith($srcurl, CMS_ROOT_URL);
            if ($local) {
                $s = substr($srcurl, strlen(CMS_ROOT_URL));
                $fp = CMS_ROOT_PATH . strtr($s, '/', DIRECTORY_SEPARATOR);
                $fp = cms_get_script('summernote-lite.js', false, $fp);
                $mainfile = basename($fp);
            } else {
                $mainfile = 'summernote-lite.min.js';
                $s = $mod->GetPreference('source_sri');
                if ($s) {
                    $cspext = ' integrity="'.$s.'" crossorigin="anonymous" referrerpolicy="same-origin"';
                }
            }
            $shareurl = CMS_ASSETS_URL.'/js';
//<script type="text/javascript" src="$srcurl/$mainfile"$cspext></script>
//<script type="text/javascript" src="$base_url/lib/UNUSED-summernote-0.8.20/dist/summernote-lite.js"></script>
// TODO module.css might be .min and/or need rtl >> $csm = new CMSMS\StylesMerger();$csm->queue_matchedfile(); ...
//<link rel="stylesheet" href="$pickurl4">
//<script type="text/javascript" src="$pickurl"></script>
//<script type="text/javascript" src="$pickurl1"></script>
//<script type="text/javascript" src="$pickurl3"></script>
/* if es6 support is needed: after last css link ...
<script type="text/javascript" id="shimsource">
//<![CDATA[
if (typeof Symbol === 'undefined') {
 var xjS = document.createElement('script');
 xjS.type = 'text/javascript';
 xjS.async = false;
 xjS.rel = 'preload';
 xjS.src = '$shareurl/core-js.min.js';
 var el = document.getElementById('shimsource');
 el.parentNode.insertBefore(xjS, el.nextSibling);
}
//]]>
</script>
*/
            $output = <<<EOS
$cssinc
<link rel="stylesheet" href="$themeurl">$xcss
<link rel="stylesheet" href="$base_url/styles/module.css">
$jsinc
<script type="text/javascript" src="$pickurl2"></script>
<script type="text/javascript" src="$srcurl/$mainfile"$cspext></script>
<script type="text/javascript" src="$srcurl/lang/summernote-en-US.min.js"></script>
$customjs

EOS;
/*
if (typeof Symbol === 'undefined') {
 var xjS = document.createElement('script');
 xjS.type = 'text/javascript';
 xjS.src = '$shareurl/es6-shim.min.js';
 var el = document.getElementById('shimsource'); // TODO better way to get current node
 el.parentNode.insertBefore(xjS, el.nextSibling); // insert after
 if (typeof String.prototype.trim === 'undefined') {
  var xjS5 = document.createElement('script');
  xjS5.type = 'text/javascript';
  xjS5.src = '$shareurl/es5-shim.min.js';
  el.parentNode.insertBefore(xjS5, xjS);
 }
}
*/
//<script type="text/javascript" src="$base_url/lib/js/purify.min.js"></script>

            $languageid = self::GetLanguageId($frontend);
            if ($languageid && !startswith($languageid, 'summernote-en-US')) {
                $output .= "\n<script type=\"text/javascript\" src=\"$srcurl/lang/$languageid\"></script>";
            }

            $plugs = self::GetPlugins($languageid);
            if ($plugs[0]) {
                foreach ($plugs[0] as $fp) {
                    $jsm->queue_file($fp);
                }
            }
            if ($plugs[1]) {
                foreach ($plugs[1] as $fp) {
                    $url = cms_path_to_url($fp);
//                  $relurl = ''; // TODO func($url etc)
//                  $output .= "\n<link rel=\"stylesheet\" href=\"$base_url/$relurl.css\">";
                    $output .= "\n<link rel=\"stylesheet\" href=\"$url\">";
                }
            }

            $js = self::GenerateVars($frontend, $profile, $mod);
            $jsm->queue_string($js);
        } else {
            $output = '';
        }

        // TODO use multi-editor-counter $ctr
        $js = self::GenerateInit($frontend, $profile, $base_url, $selector, $css_name, $local, $edit);
        $jsm->queue_string($js);
        //TODO generate merged js once per request
        $fn = $jsm->render_scripts('', false, false);
        $url = cms_path_to_url(TMP_CACHE_LOCATION).'/'.$fn;

        $output .= <<<EOS
<script type="text/javascript" src="$url"></script>
EOS;
        if ($frontend) {
            return $output;
        } else {
            add_page_headtext($output);
            return '';
        }
    }

    /**
     * Poll filesystem to get all available plugins' js and css files.
     * @since 3.2
     *
     * @param string $languageid
     * @return 2-member array
     * [0] = array of js filepaths
     * [1] = array of css filepaths
     */
    private static function GetPlugins(string $languageid) : array
    {
        if ($languageid && !startswith($languageid, 'summernote-en-US')) {
            $languageid = str_replace('summernote-', '', $languageid);
        } else {
            $languageid = '';
        }
        $out = [[],[]];
        $fp = __DIR__.DIRECTORY_SEPARATOR.'summernote'.DIRECTORY_SEPARATOR.'plugin'.DIRECTORY_SEPARATOR.'*';
        $places = glob($fp, GLOB_NOESCAPE | GLOB_ONLYDIR);
        if ($places) {
            foreach ($places as $bp) {
                if ($languageid) {
                    $files = glob($bp.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'*.js');
                    if ($files) {
                        //TODO accumulate matching <locale>[.min].js as filepath akin to GetLanguageId()
                    }
                }
                // accumulate summernote-ext-*[.min].js as filepath
                $files = glob($bp.DIRECTORY_SEPARATOR.'summernote-ext-*.js');
                $fp = ($files) ? self::GetPreferred($files) : false;
                if ($fp) { $out[0][] = $fp; }
                // accumulate [.min].css as filepath
                $files = glob($bp.DIRECTORY_SEPARATOR.'*.css');
                $fp = ($files) ? self::GetPreferred($files) : false;
                if ($fp) { $out[1][] = $fp; }
            }
        }

        $fp = __DIR__.DIRECTORY_SEPARATOR.'CMSMS-plugins'.DIRECTORY_SEPARATOR.'*';
        $places = glob($fp, GLOB_NOESCAPE | GLOB_ONLYDIR);
        if ($places) {
            foreach ($places as $bp) {
                if ($languageid) {
                    $files = glob($bp.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'*.js');
                    if ($files) {
                        //TODO accumulate ibid
                    }
                }
                $files = glob($bp.DIRECTORY_SEPARATOR.'summernote-ext-*.js');
                $fp = ($files) ? self::GetPreferred($files) : false;
                if ($fp) { $out[0][] = $fp; }
                $files = glob($bp.DIRECTORY_SEPARATOR.'*.css');
                $fp = ($files) ? self::GetPreferred($files) : false;
                if ($fp) { $out[1][] = $fp; }
            }
        }
        return $out;
    }

    /**
     * Select preferred (i.e. .min) filepath, if any
     * @since 3.2
     *
     * @param array $files 0, 1 or 2 filepaths, one of which might include '.min'
     * @return string preferred filepath or empty
     */
    private static function GetPreferred(array $files) : string
    {
        if ($files) {
            if (isset($files[1]) && strpos($files[1], '.min') !== false) {
                return $files[1];
            }
            return $files[0]; // .min or not
        }
        return '';
    }

    /**
     * Onetime generation of summernote javascript parameters-cache.
     *
     * @param bool  $frontend Flag
     * @param Profile $profile
     * @param CMSModule $mod HTMLEditor module object
     * @return string
     */
    private static function GenerateVars(bool $frontend, Profile $profile, CMSModule $mod) : string
    {
//      $base_url = $mod->GetModuleURLPath();
        $root_url = CMS_ROOT_URL;
        if ($frontend) {
            $page_id = cmsms()->get_content_object()->Id();
        } else {
            $page_id = '';
        }
//      $linker_url = $mod->create_url('m1_', 'linker', $page_id, [CMS_JOB_KEY=>1], false, false, '', false, 2); // TODO what is this?
//      $pagepicker_url = $mod->create_url('m1_', 'ajax_getpages', $page_id, [CMS_JOB_KEY=>1], false, false, '', false, 2);
//TODO something other than autocompletion for page selection c.f. CMSMS 1
//      $linkpicker_element = json_encode(trim(AdminUtils::CreateHierarchyDropdown(0, -1, 'all_pages', true, false, true, true)));

        $fpm = AppUtils::get_filepicker_module();
        if ($fpm) {
            $files_populate_url = $fpm->get_browser_url(); // to initiate FilePicker::action.filepicker
//          $parms = []; //TODO
//          list($paths, $js) = $fpm->get_browsedata($parms, false);
        } else {
            $files_populate_url = '';
        }
        $pages_populate_url = $mod->create_action_url('', 'ajax_getpages', ['forjs'=>1, CMS_JOB_KEY=>1]);

        $menu = ($profile['menubar']) ? 'true' : 'false';
//      $resize = ($profile['allowresize']) ? 'true' : 'false'; // relevant?
        $s1 = _la('apply');
        $s2 = _la('close');
        $s3 = _la('default');
// filebrowser_title: '{_ld('HTMLEDitor','title_cmsms_filebrowser")}',
// filepicker_title: '{_ld('FilePicker','filepickertitle")}',

// resize: $resize,
// schema: 'html5',
//TODO menubar, resize, schema? to GenerateInit
/* TODO
missing
 default: '{_ld('HTMLEDitor','default")}', needed ?
 other selector-dialog(s) strings
*/
// linkpicker_element: $linkpicker_element,
// constrain: '{$mod->Lang("prompt_constrain")}', TMCE-specific
//TODO re-factor for alternate approach to picker_fill_url:
//TODO translations for added status messages allremoved, success, unknownerror
        $js = <<<EOS
// runtime variables
if (typeof cms_data === 'undefined') {
 var cms_data;
}
cms_data = $.extend(cms_data || {}, {
 alias: '{$mod->Lang("prompt_alias")}',
 allremoved: 'All elements removed',
 alternate: '{$mod->Lang("alternate")}',
 apply: '$s1',
 attrclass: '{$mod->Lang("prompt_attrclass")}',
 attrrel: '{$mod->Lang("prompt_attrrel")}',
 author: '{$mod->Lang("author")}',
 base_url: '$root_url/',
 bdrstyle: '{$mod->Lang("prompt_bdrstyle")}',
 bdrwidth: '{$mod->Lang("prompt_bdrwidth")}',
 blank: '{$mod->Lang("blank")}',
 bookmark: '{$mod->Lang("bookmark")}',
 browse: '{$mod->Lang("browse")}',
 close: '$s2',
 copy_btn_title: '{$mod->Lang("copy_btn_title")}',
 cut_btn_title: '{$mod->Lang("cut_btn_title")}',
 dashed: '{$mod->Lang("dashed")}',
 default: '$s3',
 description: '{$mod->Lang("description")}',
 dimensions: '{$mod->Lang("prompt_dimensions")}',
 dotted: '{$mod->Lang("dotted")}',
 double: '{$mod->Lang("double")}',
 emailaddr: '{$mod->Lang("prompt_emailaddr")}',
 external: '{$mod->Lang("external")}',
 framename: '{$mod->Lang("framename")}',
 groove: '{$mod->Lang("groove")}',
 height: '{$mod->Lang("height")}',
 help: '{$mod->Lang("helpopt")}',
 hidden: '{$mod->Lang("hidden")}',
 horzspace: '{$mod->Lang("prompt_horzspace")}',
 image_btn_title: '{$mod->Lang("image_btn_title")}',
 image_dlg_title: '{$mod->Lang("image_dlg_title")}',
 image_select_title: '{$mod->Lang("image_browsedlg_title")}',
 inset: '{$mod->Lang("inset")}',
 license: '{$mod->Lang("license")}',
 linker_btn_title: '{$mod->Lang("linker_btn_title")}',
 linker_dlg_title: '{$mod->Lang("linker_dlg_title")}',
 linker_fill_url: '$pages_populate_url',
 linker_select_title: '{$mod->Lang("linker_browsedlg_title")}',
 mailto_btn_title: '{$mod->Lang("mailto_btn_title")}',
 mailto_dlg_title: '{$mod->Lang("mailto_dlg_title")}',
 menubar: $menu,
 next: '{$mod->Lang("next")}',
 nofollow: '{$mod->Lang("nofollow")}',
 none:' {$mod->Lang("none")}',
 nonedef: '{$mod->Lang("nonedef")}',
 noopener: '{$mod->Lang("noopener")}',
 noreferrer: '{$mod->Lang("noreferrer")}',
 outset: '{$mod->Lang("outset")}',
 parent: '{$mod->Lang("parent")}',
 paste_btn_title: '{$mod->Lang("paste_btn_title")}',
 pgid: '{$mod->Lang("prompt_pgid")}',
 pgtitle: '{$mod->Lang("prompt_pgtitle")}',
 picker_fill_url: '$files_populate_url',
 previous: '{$mod->Lang("previous")}',
 relpage: '{$mod->Lang("relpage")}',
 ridge: '{$mod->Lang("ridge")}',
 search: '{$mod->Lang("search")}',
 select: '{$mod->Lang("browse_btn_label")}',
 self: '{$mod->Lang("self")}',
 solid: '{$mod->Lang("solid")}',
 source: '{$mod->Lang("prompt_source")}',
 style: '{$mod->Lang("prompt_style")}',
 success: 'Success',
 tab_advanced: '{$mod->Lang("tab_advanced")}',
 tab_general: '{$mod->Lang("tab_general")}',
 tag: '{$mod->Lang("tag")}',
 target: '{$mod->Lang("target")}',
 texttodisplay: '{$mod->Lang("prompt_texttodisplay")}',
 title: '{$mod->Lang("prompt_title")}',
 top: '{$mod->Lang("top")}',
 unknownerror:  'Unexpected error',
 vertspace: '{$mod->Lang("prompt_vertspace")}',
 width: '{$mod->Lang("width")}'
});

EOS;
        return $js;
    }

    /**
     * Generate summernote initialization javascript, for each distinct $selector.
     *
     * @param bool $frontend
     * @param Profile $profile
     * @param string $base_url
     * @param string $selector .querySelector()-compatible CSS selector
     * @param string $css_name stylesheet name
     * @param bool $local
     * @param bool $edit
     * @return string
     */
    private static function GenerateInit(
        bool $frontend, Profile $profile, string $base_url,
        string $selector, string $css_name, bool $local, bool $edit) : string
    {
//      $parent_url = $base_url . '/lib/summernote';
/* $profile members
'allowcssoverride',
'allowimages',
'allowresize',
'allowtables',
'dfltstylesheet',
'formats',
'label',
'menubar',
'name',
'system',
*/
        $handle = 'editor'; // TODO from upstream
        $workid = 'edit_work'; // ditto
        $jsedit = ($edit) ? 'true' : 'false';
//      $image1 = ($profile['allowimages']) ? " , 'siteimage'" : '';
//      $image1 = ($profile['allowimages']) ? " , 'siteimage', 'picture'" : '';
//      $image2 = ($profile['allowimages']) ? ' media image' : ''; //'picture' etc
        $table = ($profile['allowtables']) ? "\n   ['table', ['table']]," : '';

        if ($frontend) {
            $insacts = "'link', 'mailto', 'siteimage', 'specialchars'";
        } else {
            $insacts = "'link', 'sitelink', 'mailto', 'siteimage', 'specialchars'";
        }
        $s1 = addcslashes(_ld('HTMLEditor', 'inserts_btn_title'), "'");

//      $pref = ($local) ? '' : '-'; // plugin-name prefix
// en/disable context.toolbar
// see e.g. https://www.jqueryscript.net/text/wysiwyg-editor-summernote.html for setup details

//TODO adapt this to use Preference-sourced theme
//$mod->GetPreference('skin_url');
/*      // get preferred editor theme
        if (!$frontend) {
            if (!$theme) {
                $theme = UserParams::get_for_user(get_userid(false), 'wysiwyg_theme');
                if (!$theme) {
                    $theme = AppParams::get('wysiwyg_theme', SOME DEFAULT)
                }
            }
        } elseif (!$theme) {
            $theme = SOME DEFAULT; //TODO
        }
        $theme = strtolower($theme);
        $fp = __DIR__.DIRECTORY_SEPARATOR."whatever-{$theme}.css";
        if (!is_file($fp)) {
            $fp = __DIR__.DIRECTORY_SEPARATOR."whatever-{$theme}.min.css";
            if (!is_file($fp)) {
                $theme = SOME DEFAULT;
            }
        }
*/
 //  ['color', ['color']],
/*
 image_title: true,
 language: '$languageid',
 menubar: cms_data.menubar,
 paste_as_text: true,
 relative_urls: true,
 removed_menuitems: 'newdocument',
 readonly: $fixed,
 resize: cms_data.resize,
*/
//TODO add class note-codeview-keep to undo, redo buttons
//dialogsInBody: true, TODO
//height: 150, // editable area's height
//codemirror: { // codemirror options
//  theme: 'monokai'
//}
        $js = <<<EOS
var $handle, $workid, container;
$(function() {
 container = $('$selector');
 $('$selector').summernote({
  editing: $jsedit,
  tabsize: 4,
  buttons: {
    inserters: function(context) {
      var ui = context.ui;
      return ui.buttonGroup({
        className: 'note-inserters',
        children: [
          ui.button({
            className: 'dropdown-toggle',
            container: context.layoutInfo.editor,
            contents: ui.dropdownButtonContents(
              '<i class="note-iconc-inserts"></i>', context.options
            ),
            tooltip: '$s1',
            data: {
              toggle: 'dropdown'
            }
          }),
          ui.dropdown({
            callback: function(\$node) {
              [$insacts].forEach(function(name) {
                var \$btn = context.memos['button.' + name].call();
                if (\$btn.length > 0) {
                  var s = \$btn.attr('aria-label'); //TODO split e.g. 'Label (CTRL+SHIFT+J)' into label + tip
                  var t = \$btn[0].innerText || s || name + ' action';
                  var h = '<a class="note-dropdown-item" href="#" role="listitem" data-item="" data-value=""';
                  if (s) {
                    h += ' aria-label="' + s + '"'
                  }
                  h += '>' + t + '</a>';
                  $(h).appendTo(\$node).on('click', function(ev) {
                    ev.preventDefault();
                    \$btn.trigger('click');
                  });
                }
              });
              //FURTHER SETUP IF ANY
            }
          })
        ]
      }).render();
    },
    layouts: function(context) {
      var ui = context.ui;
      return ui.buttonGroup({
        className: 'note-layouts',
        children: [
          ui.button({
            className: 'dropdown-toggle',
            container: context.layoutInfo.editor,
            contents: ui.dropdownButtonContents(
              '<i class="note-iconc-para"></i>', context.options
            ),
            tooltip: context.options.langInfo.paragraph.paragraph,
            data: {
              toggle: 'dropdown'
            }
          }),
          ui.dropdown({
            callback: function(\$node) {
              ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull', 'indent', 'outdent']
              .forEach(function(name) {
                var \$btn = context.memos['button.' + name].call();
                if (\$btn.length > 0) {
                  var s = \$btn.attr('aria-label'); //TODO split e.g. 'Label (CTRL+SHIFT+J)' into label + tip
                  var t = \$btn[0].innerText || s || name + ' action';
                  var h = '<a class="note-dropdown-item" href="#" role="listitem" data-item="" data-value=""';
                  if (s) {
                    h += ' aria-label="' + s + '"'
                  }
                  h += '>' + t + '</a>';
                  $(h).appendTo(\$node).on('click', function(ev) {
                    ev.preventDefault();
                    \$btn.trigger('click');
                  });
                }
              });
              //FURTHER SETUP IF ANY
            }
          })
        ]
      }).render();
    }
  }, //buttons
  toolbar: [
   ['dos', ['undo', 'redo']],
   ['basic', ['cut', 'copy', 'paste']],

EOS;
/* internal toolbar-buttons
picture
link
video
table
hr
fontname
fontsize
fontsizeunit
color
forecolor
backcolor
bold
italic
underline
strikethrough
superscript
subscript
clear
style
ol
ul
paragraph
height
fullscreen
codeview
undo
redo
help
*/
        if ($frontend) {
// plugins: ['tabfocus hr autolink paste link {$pref}mailto anchor wordcount lists{$image2}{$table}'],
// toolbar: en/disable context.toolbar
// 'undo |
// cut copy paste |
// bold italic underline |
// alignleft aligncenter alignright alignjustify indent outdent |
// bullist numlist | link mailto{$image1}',
//   ['insert', ['link', 'mailto'$image1]],
//   ['para', ['paragraph', 'ul', 'ol']],$table
//   ['columns'],
            $js .= <<<EOS
   ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
   ['para', ['layouts', 'ul', 'ol']],$table
   ['columns'],
   ['extras', ['inserters']],
   ['view', ['fullscreen', 'seeblocks', 'help']]

EOS;
        } else {
// plugins: ['tabfocus hr paste autolink link lists {$pref}mailto {$pref}cmsms_linker charmap anchor searchreplace wordcount code fullscreen insertdatetime{$table}{$image2} {$pref}cmsms_filepicker'],
// toolbar:  en/disable context.toolbar
// 'undo redo |
//cut copy paste |
//styleselect |
//bold italic underline |
// alignleft aligncenter alignright alignjustify indent outdent | bullist numlist | anchor link unlink mailto cmsms_linker{$image1}',
//['custom', ['hello', 'specialchars']], // DEBUG a custom button in a new toolbar area
/*
  ['custom', ['blocks']]
  blocks:{
   icon: '<i class="note-icon"><svg xmlns="http://www.w3.org/2000/svg" width="96" height="96"><path d="M0 96h40V56H0zM56 96h40V56H56zM0 40h40V0H0zM56 40h40V0H56z" fill="currentColor"/></svg></i>',
   templates: '/var/www/html/cmsms-23DEV/modules/HTMLEditor/lib/summernote/plugin/pageTemplates/block-templates/' // TODO actual path, and not for frontend
  },
*/
//   ['insert', ['link', 'sitelink', 'mailto'$image1]],
//   ['para', ['paragraph', 'ul', 'ol']],$table
//   ['columns'],
            $js .= <<<EOS
   ['style', ['style']],
   ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
   ['para', ['layouts', 'ul', 'ol']],$table
   ['columns'],
   ['extras', ['inserters']],
   ['template'],
   ['view', ['fullscreen', 'seeblocks', 'codeview', 'help']]

EOS;
        }
        $js .= <<<EOS
  ], //toolbar
  pluginopts: {
    columns: {
     wrapper: 'row', //TODO class(es) for flexbox row no-wrap expand
     columns: [
      'col-md-12', //TODO class(es) for flexbox 1 column no-wrap
      'col-md-6', //TODO class(es) for each of 2 flexbox columns no-wrap
      'col-md-4', //TODO class(es) for each of 3 flexbox columns no-wrap
      'col-md-3' //TODO class(es) for each of 4 flexbox columns no-wrap
     ],
     columnsInsert: null,
     onColumnsInsert: false
    },
    template: {
     urlroot: '$base_url/lib/CMSMS-plugins/template',
     manifest: 'list.json'
    }
  } // plugin parameters
 }); //summernote
}); // ready

EOS;
        return $js;
    } // GenerateInit
/*
function seteditorcontent(v) {
 $('$selector').summernote('code', v);
}

// Update form content when needed e.g. submit/apply/save
container.each(function(idx, el) {
  var ta = $(el),
   content = ta.summernote('code');
  clean it
  ta.val(cleaned);
});
// if (preferred_lang() === ENT_XHTML) {
//  //TODO also deploy the closeformat plugin
//}

$("#formId").submit(function(){
  for(var i=0; i<summernoteObjects.length; i++){
    var objectPointerName = summernoteObjects[i];
>>>> KEY    var summernoteValue = $("#" + objectPointerName).summernote('code');
>>>>    $("#formId input[name='"+objectPointerName+"']").val(summernoteValue);
  }
});

});

$('.summernote').each(function(i, obj) {
   $(obj).summernote({
    onblur: function(e) { SAVE ?
      var sHTML = $(obj).code();
      process it
    }
  });
});
*/
/*
//TODO need this pair for each textarea/editor
// i.e. var el = $(selector); el.val(el.summernote('code'));
//OR el.val(sanitized(el.summernote('code')));
function geteditorcontent(selector) {
  return $('$selector').summernote('code');
}
function setpagecontent(v, selector) {
  $('$selector').val(v);
}
*/

    /**
     * Convert user's current language to something summernote can prolly understand.
     *
     * @since 1.0
     * @return string like 'summernote-WHATEVER[.min].js' or empty if no match
     */
    private static function GetLanguageId() : string
    {
        $mylang = NlsOperations::get_current_language();
        if (!$mylang) return ''; //Lang setting "No default selected"
        $shortlang = substr($mylang,0,2);
        $mymin = $mylang.'.min';
        $shtmin = $shortlang.'.min';
        // try to interrogate list of all translations
        $fp = __DIR__.DIRECTORY_SEPARATOR.'summernote'.DIRECTORY_SEPARATOR.'langs.manifest';
        if (is_file($fp)) {
            $cnt = file_get_contents($fp);
            if ($cnt) {
                $matches = [];
                foreach ([$mymin,$mylang,$shtmin,$shortlang] as $test) {
                    if (($p = strpos($cnt,$test)) !== false) {
                        //get whole line containing $p i.e. 'summernote.'.stuff.'.js'
                        $patn = '/(^|\r|\n).+'.preg_quote($test).'/';
                        if (preg_match($patn,$cnt,$matches,PREG_OFFSET_CAPTURE)) {
                            $so = $matches[0][1];
                            preg_match('/\r|\n|$/',$cnt,$matches,PREG_OFFSET_CAPTURE,$p);
                            $eo = $matches[0][1];
                            return trim(substr($cnt,$so,$eo - $so));
                        }
                    }
                }
            }
        } else {
            // TODO try to generate missing list of all js translations
            $langs = [];
            $fp = __DIR__.DIRECTORY_SEPARATOR.'summernote'.DIRECTORY_SEPARATOR.'lang';
            $files = glob($fp.DIRECTORY_SEPARATOR."{summernote-$mylang*.js,summernote-$shortlang*.js}",GLOB_BRACE); // TODO if GLOB_BRACE N/A
            if ($files) {
                foreach ($files as $one) {
                    $one = basename($one);
                    $one = substr($one,11,-3); // strip leading 'summernote-', trailing '.js', ignore any '.min'
                    $langs[] = $one;
                }
            }
            if ($langs) {
                foreach ([$mymin,$mylang,$shtmin,$shortlang] as $test) {
                    if (in_array($test,$langs)) return 'summernote-'.$test.'.js';
                }
            }
        }
        return '';
    }

    /**
     * Get an img tag for a thumbnail file if one exists.
     *
     * @since 1.0
     * @param string $file
     * @param string $path
     * @param string $url
     * @return string
     */
    public static function GetThumbnailFile(string $file, string $path, string $url) : string
    {
        $imagepath = str_replace(['\\','/'],[DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR], $path.DIRECTORY_SEPARATOR.'thumb_'.$file);
        if (is_file($imagepath)) {
            $imageurl = self::Slashes($url.'/thumb_'.$file);
            //TODO omit extension from alt, title
            $image = "<img src='".$imageurl."' alt='".$file."' title='".$file."'>";
        } else {
            $image = '';
        }
        return $image;
    }

    /**
     * Replace any backslash(es)
     *
     * @since 1.0
     * @return string
     */
    private static function Slashes(string $url) : string
    {
        return str_replace('\\','/',$url);
    }
} // class
