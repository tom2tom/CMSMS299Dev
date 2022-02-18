<?php
/*
utility-methods class for Microtiny
Copyright (C) 2009-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of the Microtiny module for CMS Made Simple
<http://dev.cmsmadesimple.org/projects/microtiny>

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
namespace MicroTiny;

use CMSMS\NlsOperations;
use CMSMS\ScriptsMerger;
use CMSMS\SingleItem;
use CMSMS\StylesheetOperations;
use CMSMS\Utils as AppUtils;
use MicroTiny;
use MicroTiny\Profile;
use RuntimeException;
use Throwable;
use const CMS_ASSETS_URL;
use const CMS_JOB_KEY;
use const CMS_ROOT_URL;
use const TMP_CACHE_LOCATION;
use function add_page_headtext;
use function cms_join_path;
use function cms_path_to_url;

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
		// static properties here >> SingleItem property|ies ?
		static $usedselectors = []; //selectors cache

		extract($params + [
			'edit' => true,
//			'frontend' => see below
			'handle' => 'editor',
			'htmlid' => '', //for single editor
			'htmlclass' => '', //possibly for multiple editors
			'stylesheet' => '',
			'theme' => 'light', // TODO
			'workid' => 'edit_work',
		]);

		if( $htmlid ) {
			$selector = '#'.trim($htmlid);
		} elseif( $htmlclass ) {
			$selector = '.'.trim($htmlclass);
		}
		if( !$selector ) {
			$selector = 'textarea.MicroTiny';
		}
		if( !in_array($selector, $usedselectors) ) {
			$usedselectors[] = $selector;
		} else {
			return '';
		}

		// confirm module presence
		$mod = AppUtils::get_module('MicroTiny');
		if( !is_object($mod) ) {
			throw new RuntimeException('Could not find the MicroTiny module...');
		}

		if( !isset($frontend) ) {
			$frontend = SingleItem::App()->is_frontend_request();
		}

		try {
			$profile = ( $frontend ) ?
				Profile::load(MicroTiny::PROFILE_FRONTEND):
				Profile::load(MicroTiny::PROFILE_ADMIN);
		}
		catch (Throwable $t) {
			// oops, we gots a problem.
			exit($t->Getmessage());
		}

		// get the stylesheet that we're going to use (either passed in, or from profile)
		if( !$profile['allowcssoverride'] ) {
			// not allowing override
			$css_id = (int)$profile['dfltstylesheet'];
			if( $css_id > 0 ) {
				$css_name = $css_id;
			}
			else {
				$css_name = '';
			}
		} elseif( $stylesheet ) {
			$css_name = trim($stylesheet);
		}
		// or else some default css to supplement editor skin ?

		// if we have a stylesheet name, use it
		if( $css_name ) {
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
		$srcurl = $mod->GetPreference('source_url'); // TODO check trailing '/timymce[.min].js', drop|append as appropriate
		$local = startswith($srcurl, CMS_ROOT_URL);

		$jsm = new ScriptsMerger();

		if( $ctr == 1 ) {
			// once-per-request setup
			$cspext = '';
			if (!$local) {
				$hash = $mod->GetPreference('source_sri');
				if ($hash) {
					$cspext = ' integrity="'.$hash.'" crossorigin="anonymous" referrerpolicy="no-referrer"';
				}
			}
// TODO need FilePicker::HeaderJsContent() stuff?
			$shareurl = CMS_ASSETS_URL.'/js';
			$output = <<<EOS
<script type="text/javascript" id="shimsource">
// jshint esversion: 5
//<![CDATA[
if(typeof String.prototype.trim === 'undefined') {
 var xjS = document.createElement('script');
 xjS.type = 'text/javascript';
 xjS.src = '$shareurl/es5-shim.min.js';
 var el = document.getElementById('shimsource'); // TODO better way to get current node
 el.parentNode.insertBefore(xjS, el.nextSibling); // insert after
}
//]]>
</script>
<script type="text/javascript" src="$srcurl/tinymce.min.js"$cspext></script>
<script type="text/javascript" src="$base_url/lib/tinymce/jquery.tinymce.js"></script>

EOS;
			$js = self::GenerateVars($frontend, $profile, $mod);
			$jsm->queue_string($js);
		} else {
			$output = '';
		}

		// TODO use multi-editor-counter $ctr
		$js = self::GenerateInit($frontend, $profile, $base_url, $selector, $css_name, $local, $edit);
		$jsm->queue_string($js);
		//TODO generate merged js once per request
		$force = $mod->GetPreference('disable_cache');
		$fn = $jsm->render_scripts('', $force, false);
		$url = cms_path_to_url(TMP_CACHE_LOCATION).'/'.$fn;

		$output .=<<< EOS
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
	 * Onetime generation of tinymce javascript parameters-cache.
	 *
	 * @param bool  $frontend Flag
	 * @param Profile $profile
	 * @param CMSModule $mod MicroTiny module object
	 * @return string
	 */
	private static function GenerateVars(bool $frontend, Profile $profile, $mod) : string
	{
		$base_url = $mod->GetModuleURLPath();
		$root_url = CMS_ROOT_URL;
		if ($frontend) {
			$page_id = SingleItem::App()->get_content_object()->Id();
		} else {
			$page_id = '';
		}
//		$linker_url = $mod->create_url('m1_', 'linker', $page_id, [CMS_JOB_KEY=>1], false, false, '', false, 2); // TODO what is this?
		$getpages_url = $mod->create_url('m1_', 'ajax_getpages', $page_id, [CMS_JOB_KEY=>1], false, false, '', false, 2);

		$menu = ($profile['menubar']) ? 'true' : 'false';
		$resize = ($profile['allowresize']) ? 'true' : 'false';
		$status = ($profile['showstatusbar']) ? 'true' : 'false';

		$js = <<<EOS
// runtime variables
var cmsms_tiny = {
 base_url: '$root_url/',
 filebrowser_title: '{$mod->Lang('title_cmsms_filebrowser')}',

EOS;
		$fp = AppUtils::get_filepicker_module();
		if( $fp ) {
			$url = $fp->get_browser_url();
			$filepicker_url = str_replace('&amp;','&',$url).'&'.CMS_JOB_KEY.'=1';
//			$config = SingleItem::Config();
//			$max = $config['max_upload_size'];
//			$url2 = $config['uploads_url'];
			$js .= <<<EOS
 filepicker_title: '{$mod->Lang('filepickertitle')}',
 filepicker_url: '{$filepicker_url}&field=',

EOS;
		}
// linker_url: '$linker_url',
//TODO menubar, resize, schema?, statusbar to GenerateInit
// max_upload_size: $max,
// uploads_url: '$url2'
		$js .= <<<EOS
 linker_autocomplete_url: '$getpages_url',
 linker_image: '$base_url/lib/images/cmsmslink.png',
 linker_text: '{$mod->Lang('cmsms_linker')}',
 linker_title: '{$mod->Lang('title_cmsms_linker')}',
 loading_info: '{$mod->Lang('loading_info')}',
 mailto_image: '$base_url/lib/images/mailto.png',
 mailto_text: '{$mod->Lang('mailto_text')}',
 mailto_title: '{$mod->Lang('mailto_image')}',
 menubar: $menu,
 prompt_alias_info: '{$mod->Lang('tooltip_selectedalias')}',
 prompt_alias: '{$mod->Lang('prompt_selectedalias')}',
 prompt_anchortext: '{$mod->Lang('prompt_anchortext')}',
 prompt_class: '{$mod->Lang('prompt_class')}',
 prompt_email: '{$mod->Lang('prompt_email')}',
 prompt_insertmailto: '{$mod->Lang('prompt_insertmailto')}',
 prompt_linktext: '{$mod->Lang('prompt_linktext')}',
 prompt_page_info: '{$mod->Lang('info_linker_autocomplete')}',
 prompt_page: '{$mod->Lang('prompt_linker')}',
 prompt_rel: '{$mod->Lang('prompt_rel')}',
 prompt_target: '{$mod->Lang('prompt_target')}',
 prompt_text: '{$mod->Lang('prompt_texttodisplay')}',
 resize: $resize,
 schema: 'html5',
 statusbar: $status,
 tab_advanced: '{$mod->Lang('tab_advanced_title')}',
 tab_general: '{$mod->Lang('tab_general_title')}',
 target_new_window: '{$mod->Lang('newwindow')}',
 target_none: '{$mod->Lang('none')}'
};
EOS;
		return $js;
 	}

	/**
	 * Generate tinymce initialization javascript, for each distinct $selector.
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
		$parent_url =  $base_url . '/lib/tinymce';
		$languageid = self::GetLanguageId($frontend);
		$image1 = ($profile['allowimages']) ? ' | image' : '';
		$image2 = ($profile['allowimages']) ? ' media image' : '';
		$table = ($profile['allowtables']) ? ' table' : '';
		$fixed = ($edit) ? 'false' : 'true';
		$pref = ($local) ? '' : '-'; // plugin-name prefix

//TODO adapt this to use Preference-sourced URLs
//$mod->GetPreference('skin_url');
/*		// get preferred editor theme
		if (AppState::test(AppState::ADMIN_PAGE)) {
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
		// inline: true needs a block element (not textarea) see https://developer.mozilla.org/en-US/docs/Web/HTML/Block-level_elements
		$js = <<<EOS
// tinymce initialization
tinymce.init({
 branding: false,
 browser_spellcheck: true,
 document_base_url: cmsms_tiny.base_url,
 image_title: true,
 language: '$languageid',
 menubar: cmsms_tiny.menubar,
//mysamplesetting: 'foobar',
 paste_as_text: true,
 relative_urls: true,
 removed_menuitems: 'newdocument',
 readonly: $fixed,
 resize: cmsms_tiny.resize,
 schema: cmsms_tiny.schema,
 selector: '$selector',
 statusbar: cmsms_tiny.statusbar,

EOS;
		// smarty logic stuff
		if ($css_name !== '') {
			$js .= <<<EOS
 content_css: '{cms_stylesheet name=$css_name nolinks=1}',

EOS;
		}
		if ($frontend) {
			if (!$local) {
				$js .= <<<EOS
 external_plugins: {
  'mailto': '$parent_url/CMSMS-plugins/mailto/plugin.min.js'
 },

EOS;
			}
			$js .= <<<EOS
 plugins: ['tabfocus hr autolink paste link {$pref}mailto anchor wordcount lists{$image2}{$table}'],
 toolbar: 'undo | cut copy paste | bold italic underline | alignleft aligncenter alignright alignjustify indent outdent | bullist numlist | link mailto{$image1}',

EOS;
		} else {
			$js .= <<<EOS
 image_advtab: true,

EOS;
			if (!$local) {
				$js .= <<<EOS
 external_plugins: {
  'mailto': '$parent_url/CMSMS-plugins/mailto/plugin.min.js',
  'cmsms_filepicker': '$parent_url/CMSMS-plugins/cmsms_filepicker/plugin.min.js',
  'cmsms_linker': '$parent_url/CMSMS-plugins/cmsms_linker/plugin.min.js'
 },

EOS;
			}
			$js .= <<<EOS
 plugins: ['tabfocus hr paste autolink link lists {$pref}mailto {$pref}cmsms_linker charmap anchor searchreplace wordcount code fullscreen insertdatetime{$table}{$image2} {$pref}cmsms_filepicker'],
 toolbar: 'undo redo | cut copy paste | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify indent outdent | bullist numlist | anchor link unlink mailto cmsms_linker{$image1}',

EOS;
		}
		// callback functions
		$js .= <<<EOS
 urlconverter_callback: function(url, elm, onsave, name) {
  var self = this;
  var settings = self.settings;
  if (!settings.convert_urls || ( elm && elm.nodeName == 'LINK' ) || url.indexOf('file:') === 0 || url.length === 0) {
    return url;
  }
  // fix entities in cms_selflink urls
  if (url.indexOf('cms_selflink') != -1) {
    decodeURI(url);
    url = url.replace('%20', ' ');
    return url;
  }
  // convert to relative
  if (settings.relative_urls) {
    return self.documentBaseURI.toRelative(url);
  }
  // convert to absolute
  url = self.documentBaseURI.toAbsolute(url, settings.remove_script_host);
  return url;
 },
 setup: function(editor) {
  editor.addMenuItem('mailto', {
   text: cmsms_tiny.prompt_insertmailto,
   cmd:  'mailto',
   context: 'insert'
  });
  editor.on('change', function(e) {
   $(document).trigger('cmsms_formchange');
  });
 }
}).then(function(editors) {
 // all editors have been initialized
 editors.forEach(function(ed) {
  var here = 1;
 });
});

EOS;
/* TODO API for editor(s) using this selector
function seteditorcontent(v) {
// HTML contents of the currently active editor
tinymce.activeEditor.setContent(v + ' html');
// raw contents of the currently active editor
tinymce.activeEditor.setContent(v + ' html', {format: 'raw'});
// contents of a specific editor (my_editor in this example)
tinymce.get('my_editor').setContent(data);
}
function geteditorcontent() {
// HTML contents of the currently active editor
return tinymce.activeEditor.getContent();
// raw contents of the currently active editor
return tinymce.activeEditor.getContent({format: 'raw'});
// contents of a specific editor
return tinymce.get('my_editor').getContent()
}
function setpagecontent(v) {
  var t = container[0]; // TODO c.f. tinyMCE.triggerSave()
  t.textContent = v;
}
var $handle, $workid, container;
$(function() {
  container = $('$selector');
  container.each(function(idx) {
   SETUP js for each editor, remember each one for cleanup etc
  });
});
*/
		return $js;
	}

	/**
	 * Convert user's current language to something tinymce can prolly understand.
	 *
	 * @since 1.0
	 * @return string
	 */
	private static function GetLanguageId() : string
	{
		$mylang = NlsOperations::get_current_language();
		if (!$mylang) return 'en'; //Lang setting "No default selected"
		$shortlang = substr($mylang,0,2);

		//TODO a langs 'manifest' to interrogate when sourcing from CDN
		$mod = AppUtils::get_module('MicroTiny');
		$dir = cms_join_path($mod->GetModulePath(),'lib','tinymce','langs');
		$langs = [];
		$files = glob($dir.DIRECTORY_SEPARATOR.'*.js');
		if( $files ) {
			 foreach( $files as $one ) {
				 $one = basename($one);
				 $one = substr($one,0,-3);
				 $langs[] = $one;
			 }
		}

		if( in_array($mylang,$langs) ) return $mylang;
		if( in_array($shortlang,$langs) ) return $shortlang;
		return 'en';
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
		if( is_file($imagepath) ) {
			$imageurl = self::Slashes($url.'/thumb_'.$file);
			//TODO omit extension from alt, title
			$image = "<img src='".$imageurl."' alt='".$file."' title='".$file."' />";
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
