<?php
/*
utility-methods class for Microtiny
Copyright (C) 2009-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of the Microtiny module for CMS Made Simple
<http://dev.cmsmadesimple.org/projects/microtiny>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace MicroTiny;

use CMSMS\AppState;
use CMSMS\NlsOperations;
use CMSMS\ScriptsMerger;
use CMSMS\SingleItem;
use CMSMS\StylesheetOperations;
use CMSMS\Utils as AppUtils;
use Exception;
use MicroTiny;
use MicroTiny\Profile;
use RuntimeException;
use const CMS_JOB_KEY;
use const CMS_ROOT_URL;
use const TMP_CACHE_LOCATION;
use function add_page_headtext;
use function cms_join_path;
use function cms_path_to_url;
use function cms_to_bool;

class Utils
{
	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * @staticvar boolean $first_time
	 * @param string $selector Optional page-element id
	 * @param string $css_name Optional stylesheet name
	 * @return string
	 * @throws Exception, CMSMS\LogicException
	 */
	public static function WYSIWYGGenerateHeader($selector='', $css_name='')
	{
		// static properties here >> SingleItem property|ies ?
		static $first_time = true;

		// Check if we are in object instance
		$mod = AppUtils::get_module('MicroTiny');
		if( !is_object($mod) ) throw new RuntimeException('Could not find the MicroTiny module...');

		$frontend = SingleItem::App()->is_frontend_request();
		$languageid = self::GetLanguageId($frontend);

		// get the cssname that we're going to use (either passed in, or from profile)
		$profile = ( $frontend ) ?
			Profile::load(MicroTiny::PROFILE_FRONTEND):
			Profile::load(MicroTiny::PROFILE_ADMIN);

		if( !$profile['allowcssoverride'] ) {
			// not allowing override
			$css_id = (int) $profile['dfltstylesheet'];
			if( $css_id > 0 ) {
				$css_name = $css_id;
			}
			else {
				$css_name = '';
			}
		}

		// if we have a stylesheet name, use it
		if( $css_name ) {
			try {
				$css = StylesheetOperations::get_stylesheet($css_name);
				$css_name = $css->get_name();
			}
			catch( Exception $e ) {
				// couldn't load the stylesheet for some reason.
				$css_name = '';
			}
		}

		if( $first_time ) {
			// only once per request
			$first_time = false;
			$url = $mod->GetModuleURLPath().'/lib/js';
			//main source file doesn't like relocation into a merged-scripts file ? & must be addressable
			$output = <<<EOS
<script type="text/javascript">
//<![CDATA[
if(typeof String.prototype.trim === 'undefined') {
 var xjS = document.createElement('script');
 xjS.type = 'text/javascript';
 xjS.src = "{$url}/ec5support.min.js";
 document.getElementById('mainjssource').insertBefore(xjS); //CHECK compiles ok?
}
//]]>
</script>
<script type="text/javascript" id="mainjssource" src="{$url}/tinymce/tinymce.min.js"></script>
<script type="text/javascript" src="{$url}/tinymce/jquery.tinymce.min.js"></script>
EOS;
		} else {
			$output = '';
		}

		$jsm = new ScriptsMerger();
//		$fn = cms_join_path(__DIR__,'js','tinymce','jquery.tinymce.min.js'); 1st time only
//		$jsm->queue_file($fn, 1);
		$configcontent = self::_generate_config($frontend, $selector, $css_name, $languageid);
		$jsm->queue_string($configcontent);
		$config = AppUtils::get_config();
		$force = isset($config['mt_disable_cache']) && cms_to_bool($config['mt_disable_cache']);

		$fn = $jsm->render_scripts('', $force, false);
		$url = cms_path_to_url(TMP_CACHE_LOCATION).'/'.$fn;
		$output .=<<< EOS
<script type="text/javascript" src="$url"></script>
EOS;
		if (AppState::test(AppState::ADMIN_PAGE)) {
			add_page_headtext($output);
			return '';
		} else {
			return $output;
		}
	}

	/**
	 * Generate tinymce initialization javascript.
	 *
	 * @param bool  $frontend Optional flag Default false
	 * @param mixed $selector Optional Default ''
	 * @param mixed $css_name	Optional Default ''
	 * @param string $languageid Optional Default 'en'
	 * @return string
	 */
	private static function _generate_config(bool $frontend=false, string $selector='', string $css_name='', string $languageid='en') : string
	{
		$ajax_url = function($url) {
			return str_replace('&amp;','&',$url).'&'.CMS_JOB_KEY.'=1';
		};

		try {
			$profile = ( $frontend ) ?
				Profile::load(MicroTiny::PROFILE_FRONTEND):
				Profile::load(MicroTiny::PROFILE_ADMIN);
		}
		catch( Throwable $e ) {
//			$profile = null;
			// oops, we gots a problem.
			exit($e->Getmessage());
		}

		if( !$selector ) $selector = 'textarea.MicroTiny';
		$root_url = CMS_ROOT_URL;

		$resize = ($profile['allowresize']) ? 'true' : 'false';
		$status = ($profile['showstatusbar']) ? 'true' : 'false';
		$menu = ($profile['menubar']) ? 'true' : 'false';
		$image1 = ($profile['allowimages']) ? ' | image' : '';
		$image2 = ($profile['allowimages']) ? ' media image' : '';
		$table = ($profile['allowtables']) ? ' table' : '';

		$mod = AppUtils::get_module('MicroTiny');
		$gCms = SingleItem::App();
		$smarty = SingleItem::Smarty();
		$page_id = ($gCms->is_frontend_request()) ? $smarty->getTemplateVars('content_id') : '';
		$linker_url = $mod->create_url('m1_','linker',$page_id,[CMS_JOB_KEY=>1],false,false,'',false,2);
		$getpages_url = $mod->create_url('m1_','ajax_getpages',$page_id,[CMS_JOB_KEY=>1],false,false,'',false,2);

		$js = <<<EOS
// microtiny data
var cmsms_tiny = {
 base_url: '{$root_url}/',
 filebrowser_title: '{$mod->Lang('title_cmsms_filebrowser')}',

EOS;
		$fp = AppUtils::get_filepicker_module();
		if( $fp ) {
			$url = $fp->get_browser_url();
			$filepicker_url = $ajax_url($url);
			$js .= <<<EOS
 filepicker_title: '{$mod->Lang('filepickertitle')}',
 filepicker_url: '{$filepicker_url}&field=',

EOS;
		}
		$js .= <<<EOS
 linker_autocomplete_url: '{$getpages_url}',
 linker_image: '{$mod->GetModuleURLPath()}/lib/images/cmsmslink.gif',
 linker_text: '{$mod->Lang('cmsms_linker')}',
 linker_title: '{$mod->Lang('title_cmsms_linker')}',
 linker_url: '{$linker_url}',
 loading_info: '{$mod->Lang('loading_info')}',
 mailto_image: '{$mod->GetModuleURLPath()}/lib/images/mailto.gif',
 mailto_text: '{$mod->Lang('mailto_text')}',
 mailto_title: '{$mod->Lang('mailto_image')}',
 menubar: $menu,
 prompt_alias: '{$mod->Lang('prompt_selectedalias')}',
 prompt_alias_info : '{$mod->Lang('tooltip_selectedalias')}',
 prompt_anchortext: '{$mod->Lang('prompt_anchortext')}',
 prompt_class: '{$mod->Lang('prompt_class')}',
 prompt_email: '{$mod->Lang('prompt_email')}',
 prompt_insertmailto: '{$mod->Lang('prompt_insertmailto')}',
 prompt_linktext: '{$mod->Lang('prompt_linktext')}',
 prompt_page: '{$mod->Lang('prompt_linker')}',
 prompt_page_info: '{$mod->Lang('info_linker_autocomplete')}',
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

// tinymce initialization
tinymce.init({
 browser_spellcheck: true,
 document_base_url: cmsms_tiny.base_url,
 image_title: true,
 language: '$languageid',
 menubar: cmsms_tiny.menubar,
 mysamplesetting: 'foobar',
 paste_as_text: true,
 relative_urls: true,
 removed_menuitems: 'newdocument',
 resize: cmsms_tiny.resize,
 selector: '$selector',
 statusbar: cmsms_tiny.statusbar,
 // smarty logic stuff

EOS;

		if ($css_name !== '') {
			$js .= <<<EOS
 content_css: '{cms_stylesheet name=$css_name nolinks=1}',

EOS;
		}
		if ($frontend) {
			$js .= <<<EOS
 toolbar: 'undo | bold italic underline | alignleft aligncenter alignright alignjustify indent outdent | bullist numlist | link mailto{$image1}',
 plugins: ['tabfocus hr autolink paste link mailto anchor wordcount lists{$image2}{$table}'],

EOS;
		} else {
			$js .= <<<EOS
 image_advtab: true,
 toolbar: 'undo redo | cut copy paste | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify indent outdent | bullist numlist | anchor link mailto unlink cmsms_linker{$image1}',
 plugins: ['tabfocus hr paste autolink link lists mailto cmsms_linker charmap anchor searchreplace wordcount code fullscreen insertdatetime{$table}{$image2} cmsms_filepicker'],

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
  // Convert to relative
  if (settings.relative_urls) {
    return self.documentBaseURI.toRelative(url);
  }
  // Convert to absolute
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
});

EOS;
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

		$mod = AppUtils::get_module('MicroTiny');
		$dir = cms_join_path($mod->GetModulePath(),'lib','js','tinymce','langs');
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
	public static function GetThumbnailFile($file, $path, $url)
	{
		$imagepath = str_replace(['\\','/'],[DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR], $path.'/thumb_'.$file);
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
	 * Fix Slashes
	 *
	 * @since 1.0
	 * @return string
	 */
	private static function Slashes(string $url) : string
	{
		return str_replace('\\','/',$url);
	}
} // class
