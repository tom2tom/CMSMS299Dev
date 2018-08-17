<?php
#utility-methods class for Microtiny
#Copyright (C) 2009-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of the Microtiny module for CMS Made Simple
# <http://dev.cmsmadesimple.org/projects/microtiny>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace MicroTiny;

use cms_utils;
use CmsApp;
use CmsFileSystemException;
use CmsLayoutStylesheet;
use CmsLogicException;
use CmsNlsOperations;
use MicroTiny;
use MicroTiny\Profile;
use PHPMailer\PHPMailer\Exception;
use const CMS_ROOT_URL;
use const PUBLIC_CACHE_LOCATION;
use function cms_join_path;
use function cms_to_bool;
use function file_put_contents;

class Utils
{
	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * @staticvar boolean $first_time
	 * @param type $selector
	 * @param string $css_name
	 * @return string
	 * @throws CmsLogicException
	 */
	public static function WYSIWYGGenerateHeader($selector='', $css_name='')
	{
		static $first_time = true;

		// Check if we are in object instance
		$mod = cms_utils::get_module('MicroTiny');
		if( !is_object($mod) ) throw new CmsLogicException('Could not find the MicroTiny module...');

		$frontend = CmsApp::get_instance()->is_frontend_request();
		$languageid = self::GetLanguageId($frontend);
		$mtime = time() - 300; // by defaul cache for 5 minutes ??

		// get the cssname that we're going to use (either passed in, or from profile)
		try {
			$profile = ( $frontend ) ?
				Profile::load(MicroTiny::PROFILE_FRONTEND):
				Profile::load(MicroTiny::PROFILE_ADMIN);

			if( !$profile['allowcssoverride'] ) {
				// not allowwing override
				$css_name = null;
				$css_id = (int) $profile['dfltstylesheet'];
				if( $css_id > 0 ) $css_name = $css_id;
			}
		}
		catch( Exception $e ) {
			// do nothing.
			$profile = null;
		}

		// if we have a stylesheet name, use it's modification time as our mtime
		if( $css_name ) {
			try {
				$css = CmsLayoutStylesheet::load($css_name);
				$css_name = $css->get_name();
				$mtime = $css->get_modified();
			}
			catch( Exception $e ) {
				// couldn't load the stylesheet for some reason.
				$css_name = null;
			}
		}

		// if this is an action for MicroTiny disable caching.
		$smarty = CmsApp::get_instance()->GetSmarty();
		$module = $smarty->getTemplateVars('actionmodule');
		if( $module == $mod->GetName() ) $mtime = time() + 60;

		// also disable caching if told to by the config.php
		$config = cms_utils::get_config();
		if( isset($config['mt_disable_cache']) && cms_to_bool($config['mt_disable_cache']) ) $mtime = time() + 60;

		$fn = cms_join_path(PUBLIC_CACHE_LOCATION,'mt_'.md5(__DIR__.session_id().$frontend.$selector.$css_name.$languageid).'.js');
		if( !file_exists($fn) || filemtime($fn) < $mtime ) {
			// we have to generate an mt config js file.
			self::_save_static_config($fn,$frontend,$selector,$css_name,$languageid);
		}

		if( $first_time ) {
			// only once per request.
			$first_time = FALSE;
			$output = '<script type="text/javascript" src="'.$mod->GetModuleURLPath().'/lib/js/tinymce/tinymce.min.js"></script>'."\n";
		} else {
			$output = '';
		}

		$configurl = $config['public_cache_url'].'/'.basename($fn);
//		$output .= '<script type="text/javascript" src="'.$configurl.'" defer="defer"></script>';
		$output .= '<script type="text/javascript" src="'.$configurl.'"></script>';

		return $output;
	}

	/**
	 * @ignore
	 * @param type $fn
	 * @param bool $frontend
	 * @param type $selector
	 * @param type $css_name
	 * @param type $languageid
	 * @throws CmsFileSystemException
	 */
	private static function _save_static_config($fn, bool $frontend=false, string $selector='', string $css_name='', string $languageid='')
	{
		if( !$fn ) return;
		$configcontent = self::_generate_config($frontend, $selector, $css_name, $languageid);
		$res = file_put_contents($fn,$configcontent);
		if( !$res ) throw new CmsFileSystemException('Problem writing data to '.$fn);
	}

	/**
	 * Generate a tinymce initialization file.
	 *
	 * @param bool $frontend Optional
	 * @param mixed$selector Optional
	 * @param mixed $css_name	Optional
	 * @param string $languageid	Optional
	 * @return string
	 */
	private static function _generate_config(bool $frontend=false, string $selector='', string $css_name='', string $languageid='en') : string
	{
		$ajax_url = function($url) {
			return str_replace('&amp;','&',$url).'&cmsjobtype=1';
		};

		$mod = cms_utils::get_module('MicroTiny');
		$_gCms = CmsApp::get_instance();
		$smarty = $_gCms->GetSmarty();
		$page_id = ($_gCms->is_frontend_request()) ? $smarty->getTemplateVars('content_id') : '';
		$tpl_ob = $smarty->CreateTemplate('module_file_tpl:MicroTiny;tinymce_configjs.tpl',null,null,$smarty); // child of the global smarty
		$tpl_ob->clearAssign('mt_profile')
		  ->clearAssign('mt_selector')
		  ->assign('mod',$mod)
		  ->assign('mt_actionid','m1_')
		  ->assign('isfrontend',$frontend)
		  ->assign('languageid',$languageid)
		  ->assign('root_url',CMS_ROOT_URL);
		$fp = cms_utils::get_filepicker_module();
		if( $fp ) {
			$url = $fp->get_browser_url();
			$tpl_ob->assign('filepicker_url',$ajax_url($url));
		}
		else
			$tpl_ob->assign('filepicker_url',null);
		$url = $mod->create_url('m1_','linker',$page_id);
		$tpl_ob->assign('linker_url',$ajax_url($url));
		$url = $mod->create_url('m1_','ajax_getpages',$page_id);
		$tpl_ob->assign('getpages_url',$ajax_url($url));
		if( $selector ) $tpl_ob->assign('mt_selector',$selector);

		try {
			$profile = ( $frontend ) ?
				Profile::load(MicroTiny::PROFILE_FRONTEND):
				Profile::load(MicroTiny::PROFILE_ADMIN);

			$tpl_ob->assign('mt_profile',$profile);
			if( $css_name ) $tpl_ob->assign('mt_cssname',$css_name);
		}
		catch( Exception $e ) {
			$profile = null;
			// oops, we gots a problem.
			die($e->Getmessage());
		}

		return $tpl_ob->fetch();
	}

	/**
	 * Convert user's current language to something tinymce can prolly understand.
	 *
	 * @since 1.0
	 * @return string
	 */
	private static function GetLanguageId() : string
	{
		$mylang = CmsNlsOperations::get_current_language();
		if ($mylang=="") return 'en'; //Lang setting "No default selected"
		$shortlang = substr($mylang,0,2);

		$mod = cms_utils::get_module('MicroTiny');
		$dir = cms_join_path($mod->GetModulePath(),'lib','js','tinymce','langs');
		$langs = [];
		$files = glob($dir.DIRECTORY_SEPARATOR.'*.js');
		if( is_array($files) && count($files) ) {
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
		if( file_exists($imagepath) ) {
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
