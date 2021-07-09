<?php
/*
Plugin to minimize and merge contents of stylesheets for frontend pages
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

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

namespace cms_stylesheet {

use CMSMS\Events;
use SmartyException;
use function endswith;

function writeCache($filename, $list, $trimbackground, &$template)
{
	$_contents = '';
	if( is_string($list) && !is_array($list) ) $list = [$list];

	// Smarty processing
	$template->smarty->left_delimiter = '[[';
	$template->smarty->right_delimiter = ']]';

	try {
		foreach( $list as $name ) {
			// force the stylesheet to compile because of smarty bug:  https://github.com/smarty-php/smarty/issues/72 FIXED upstream in 2015
//			$tmp = $template->smarty->force_compile;
//			$template->smarty->force_compile = 1;
			$_contents .= $template->fetch('cms_stylesheet:'.$name);
//			$template->smarty->force_compile = $tmp;
		}
	}
	catch (SmartyException $e) {
		$template->smarty->left_delimiter = '{';
		$template->smarty->right_delimiter = '}';
		// why not just re-throw the exception as it may have a smarty error in it ?
//		trigger_error('cms_stylesheet: '.$e->getMessage());
		cms_error('cms_stylesheet: Smarty compilation failed, is there an error in the template?');
		return '';
	}

	$template->smarty->left_delimiter = '{';
	$template->smarty->right_delimiter = '}';

	// Fix background
	if( $trimbackground ) {
		$_contents = preg_replace('/(\w*?background\-image.*?\:\w*?).*?(;.*?)/', '', $_contents);
		$_contents = preg_replace('/\w*?(background[\-image]*[\s\w]*\:[\#\s\w]*)url\(.*\)/','$1;',$_contents);
		$_contents = preg_replace('/\w*?(background[\-image]*[\s\w]*\:[\s]*\;)/','',$_contents);
		$_contents = preg_replace('/(\w*?background\-color.*?\:\w*?).*?(;.*?)/', '\\1transparent\\2', $_contents);
		$_contents = preg_replace('/(\w*?background\-image.*?\:\w*?).*?(;.*?)/', '', $_contents);
		$_contents = preg_replace('/(\w*?background.*?\:\w*?).*?(;.*?)/', '', $_contents);
	}

	Events::SendEvent( 'Core', 'StylesheetPostRender', [ 'content' => &$_contents ] );

	// Write file
	$fh = fopen($filename,'w');
	fwrite($fh, $_contents);
	fclose($fh);
}

function toString($filename, $media_query = '', $media_type = '', $root_url, &$stylesheet, &$params)
{
	if( !endswith($root_url,'/') ) $root_url .= '/';
	if( isset($params['nolinks']) )	{
		$stylesheet .= $root_url.$filename.',';
	}
	elseif( !empty($media_query) ) {
		$stylesheet .= '<link rel="stylesheet" type="text/css" href="'.$root_url.$filename.'" media="'.$media_query.'" />'.PHP_EOL;
	}
	elseif( !empty($media_type) ) {
		$stylesheet .= '<link rel="stylesheet" type="text/css" href="'.$root_url.$filename.'" media="'.$media_type.'" />'.PHP_EOL;
	}
	else {
		$stylesheet .= '<link rel="stylesheet" type="text/css" href="'.$root_url.$filename.'" />'.PHP_EOL;
	}
}

} //namespace

namespace {

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Crypto;
use CMSMS\StylesheetQuery;
use function cms_stylesheet\toString;
use function cms_stylesheet\writeCache;

function smarty_function_cms_stylesheet($params, $template)
{
	//---------------------------------------------
	// Trivial Exclusion
	//---------------------------------------------

	if( AppState::test_state(AppState::STATE_LOGIN_PAGE) ) return;

	//---------------------------------------------
	// Initials
	//---------------------------------------------

	AppState::add_state(AppState::STATE_STYLESHEET);
	$gCms = AppSingle::App();
	$config = AppSingle::Config();

	$cache_dir = $config['css_path'];
	$root_url = $config['css_url'];
	$name = null;
	$design_id = -1;
	$stylesheet = '';
	$combine_stylesheets = true;
	$fnsuffix = '';
	$trimbackground = false;
	$styles = null;

	try {
		//---------------------------------------------
		// Read parameters
		//---------------------------------------------

		if( !empty($params['name']) ) {
			$name = trim($params['name']); //sheet-name prefix
		}
		elseif( !empty($params['styles']) ) { //since 2.99
			$styles = trim($params['styles']);
		}
		elseif( !empty($params['designid']) ) { //deprecated since 2.99
			$design_id = (int)$params['designid'];
		}
		else {
			//TODO support $params['templatetype'] related to a theme
			$content_obj = $gCms->get_content_object();
			if( !is_object($content_obj) ) return;
			$styles = $content_obj->Styles();
			if( !$styles ) {
				$design_id = (int)$content_obj->GetPropertyValue('design_id');
			}
		}
		if( !($name || $styles || $design_id > 0) ) {
			throw new RuntimeException('Cannot identify stylesheet(s) for page');
		}
		if( isset($params['nocombine']) ) {
			$combine_stylesheets = !cms_to_bool($params['nocombine']);
		}
		if( isset($params['stripbackground']) )	{
			$trimbackground = cms_to_bool($params['stripbackground']);
			$fnsuffix = '_e_';
		}

		//---------------------------------------------
		// Build query
		//---------------------------------------------

		$query = null;
		if( $name ) {
			// stylesheet by name(prefix)
			$query = new StylesheetQuery([ 'name'=>$name ]);
		}
		elseif( $styles ) {
			// stylesheet(s) by id
			$query = new StylesheetQuery([ 'styles'=>$styles ]);
		}
		elseif( $design_id > 0 ) {
			// stylesheet(s) by design id
			$query = new StylesheetQuery([ 'design'=>$design_id ]);
		}
		if( !$query ) {
			throw new RuntimeException('Problem: failed to build a stylesheet query using the provided data');
		}

		//---------------------------------------------
		// Execute
		//---------------------------------------------

		$nrows = $query->TotalMatches();
		if( !$nrows ) {
			throw new RuntimeException('No stylesheets matched the criteria specified');
		}
		$res = $query->GetMatches();

		// we have some output, and the stylesheet objects have already been loaded.

		if( $combine_stylesheets ) {
			// combine stylesheets
			// group queries & types
			$all_media = [];
			$all_timestamps = [];
			foreach( $res as $one ) {
				$mq = $one->get_media_query();
				$mt = implode(',',$one->get_media_types());
				if( !empty($mq) ) {
					$key = Crypto::hash_string($mq);
					$all_media[$key][] = $one;
					$all_timestamps[$key][] = $one->get_modified();
				}
				elseif( !$mt ) {
					$all_media['all'][] = $one;
					$all_timestamps['all'][] = $one->get_modified();
				}
				else {
					$key = Crypto::hash_string($mt);
					$all_media[$key][] = $one;
					$all_timestamps[$key][] = $one->get_modified();
				}
			}

			// media parameter...
			if( isset($params['media']) && strtolower($params['media']) != 'all' ) {
				// media parameter is deprecated.

				// combine all matches into one stylesheet
				$filename = 'combined_'.Crypto::hash_string($design_id.serialize($params).serialize($all_timestamps).$fnsuffix).'.css';
				$fn = cms_join_path($cache_dir,$filename);

				if( !is_file($fn) ) {
					$list = [];
					foreach ($res as $one) {
						if( in_array($params['media'],$one->get_media_types()) ) $list[] = $one->get_name();
					}

					writeCache($fn, $list, $trimbackground, $template);
				}

				toString($filename, $params['media'], '', $root_url, $stylesheet, $params);
			}
			else {

				foreach($all_media as $hash=>$onemedia) {

					// combine all matches into one stylesheet
					$filename = 'combined_'.Crypto::hash_string($design_id.serialize($params).serialize($all_timestamps[$hash]).$fnsuffix).'.css';
					$fn = cms_join_path($cache_dir,$filename);

					// Get media_type and media_query
					$media_query = $onemedia[0]->get_media_query();
					$media_type = implode(',',$onemedia[0]->get_media_types());

					if( !is_file($fn) ) {
						$list = [];

						foreach( $onemedia as $one ) {
							$list[] = $one->get_name();
						}

						writeCache($fn, $list, $trimbackground, $template);
					}

					toString($filename, $media_query, $media_type, $root_url, $stylesheet, $params);
				}
			}
		}
		else {
			// do not combine stylesheets
			foreach ($res as $one) {

				if( isset($params['media']) ) {
					if( !in_array($params['media'],$one->get_media_types()) ) continue;
					$media_query = '';
					$media_type = $params['media'];
				}
				else {
					$media_query = $one->get_media_query();
					$media_type  = implode(',',$one->get_media_types());
				}

				$filename = 'stylesheet_'.Crypto::hash_string('single'.$one->get_id().$one->get_modified().$fnsuffix).'.css';
				$fn = cms_join_path($cache_dir,$filename);

				if( !is_file($fn) ) writeCache($fn, $one->get_name(), $trimbackground, $template);

				toString($filename, $media_query, $media_type, $root_url, $stylesheet, $params);
			}
		}

		//---------------------------------------------
		// Cleanup & output
		//---------------------------------------------

		if( $stylesheet ) {
			// Remove last comma at the end when $params['nolinks'] is set
			if( isset($params['nolinks']) && cms_to_bool($params['nolinks']) && endswith($stylesheet, ',') ) {
				$stylesheet = substr($stylesheet, 0, -1);
			}
		}
	}
	catch( Throwable $t ) {
//		trigger_error('cms_stylesheet: '.$t->getMessage());
		cms_error('cms_stylesheet',$t->GetMessage());
		$stylesheet = '<!-- cms_stylesheet error: '.$t->GetMessage().' -->';
	}

	// Notify core that we are no longer at stylesheet
	AppState::remove_state(AppState::STATE_STYLESHEET);

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $stylesheet);
		return '';
	}
	return $stylesheet;

} // main function

function smarty_cms_about_function_cms_stylesheet()
{
	echo <<<'EOS'
<p>Author: jeff&lt;jeff@ajprogramming.com&gt;</p>
<p>Change History:</p>
<ul>
 <li>Rework from {stylesheet}</li>
 <li>(Stikki and Calguy1000) Code cleanup, Added grouping by media type / media query, Fixed cache issues</li>
</ul>
EOS;
}
/*
D function smarty_cms_help_function_cms_stylesheet()
{
	echo lang_by_realm('tags', 'help_generic', 'This plugin does ...', 'cms_stylesheet ...', <<<'EOS'
<li>name</li>
<li>styles</li>
<li>designid</li>
<li>media</li>
<li>nocombine</li>
<li>nolinks</li>
<li>stripbackground</li>
EOS
	);
}
*/

} // global namespace
