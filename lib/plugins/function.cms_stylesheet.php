<?php
/*
Plugin to minimize and merge contents of stylesheets for frontend pages
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

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

namespace cms_stylesheet {

use CMSMS\Events;
use SmartyException;
use function endswith;
use function CMSMS\log_error;

/**
 * @param string $filename
 * @param type $list
 * @param bool $trimbackground
 * @param bool $min
 * @param Smarty_Internal_Template $template
 */
function writeCache(string $filename, $list, bool $trimbackground, bool $min, \Smarty_Internal_Template $template)
{
	if( is_string($list) && !is_array($list) ) $list = [$list];

	// Change Smarty demimiters
	$template->smarty->left_delimiter = '[[';
	$template->smarty->right_delimiter = ']]';

	$content = '';
	try {
		foreach( $list as $name ) {
			// force the stylesheet to compile because of smarty bug:  https://github.com/smarty-php/smarty/issues/72 FIXED upstream in 2015
//			$tmp = $template->smarty->force_compile;
//			$template->smarty->force_compile = 1;
			if( $content ) { $content .= PHP_EOL; }
			$content .= $template->fetch('cms_stylesheet:'.$name);
//			$template->smarty->force_compile = $tmp;
		}
	}
	catch (SmartyException $e) {
		$template->smarty->left_delimiter = '{';
		$template->smarty->right_delimiter = '}';
		// why not just re-throw the exception as it may have a smarty error in it ?
//		trigger_error('cms_stylesheet: '.$e->getMessage());
		log_error('Smarty stylesheet-compilation failed: '.$e->getMessage(), 'cms_stylesheet plugin, '.$name);
		return;
	}

	// Revert demimiters
	$template->smarty->left_delimiter = '{';
	$template->smarty->right_delimiter = '}';

	if( $trimbackground ) {
		// Scrub background styles
		$content = preg_replace(
		[
		'/(\w*?background-image.*?\:\w*?).*?(;.*?)/',
		'/\w*?(background(-image)?[\s\w]*\:[\#\s\w]*)url\(.*\)/',
		'/\w*?(background(-image)?[\s\w]*\:[\s]*\;)/',
		'/(\w*?background-color.*?\:\w*?).*?(;.*?)/',
		'/(\w*?background.*?\:\w*?).*?(;.*?)/'
		],
		[
		'',
		'$1;',
		'',
		'$1transparent$2',
		''
		], $content);
	}

	Events::SendEvent('Core', 'StylesheetPostRender', ['content' => &$content]);

	if ($min) {
		// Minimize
		$str = preg_replace(
			['~^\s+~', '~\s+$~', '~\s+~', '~/\*[^!](\*(?!\/)|[^*])*\*/~'],
			[''      , ''      , ' '    , ''],
			$content);
		$str = strtr($str, ['\r' => '', '\n' => '']);
		$content = str_replace(
			['  ', ': ', ', ', '{ ', '; ', '( ', '} ', ' :', ' {', '; }', ';}', ' }', ' )', '*/'],
			[' ' , ':' , ',' , '{' , ';' , '(' , '}' , ':' , '{' , '}'  , '}' , '}' , ')' , "*/\n"],
			$str);
	}

	// Write file
	$fh = fopen($filename, 'w');
	fwrite($fh, $content);
	fclose($fh);
}

/**
 * @param string $filename
 * @param string $media_query (if present, used in preference to $media_type)
 * @param string $media_type
 * @param string $root_url
 * @param string $out in/out parameter, supplemented here
 * @param array $params
 */
function toString(string $filename, string $media_query = '', string $media_type = '', $root_url, string &$out, array &$params)
{
	// TODO CSP support
	if( !endswith($root_url, '/') ) $root_url .= '/';
	if( isset($params['nolinks']) )	{
		$out .= $root_url.$filename.',';
	}
	elseif( $media_query ) {
		$out .= '<link rel="stylesheet" href="'.$root_url.$filename.'" media="'.$media_query.'" />'.PHP_EOL;
	}
	elseif( $media_type && $media_type != 'all' ) {
		$out .= '<link rel="stylesheet" href="'.$root_url.$filename.'" media="'.$media_type.'" />'.PHP_EOL;
	}
	else {
		$out .= '<link rel="stylesheet" href="'.$root_url.$filename.'" />'.PHP_EOL;
	}
}

} // cms_stylesheet namespace

namespace {

use CMSMS\AppState;
use CMSMS\Crypto;
use CMSMS\Lone;
use CMSMS\StylesheetQuery;
use function cms_stylesheet\toString;
use function cms_stylesheet\writeCache;
use function CMSMS\log_error;

function smarty_function_cms_stylesheet($params, $template)
{
	// trivial exclusion
	if( AppState::test(AppState::LOGIN_PAGE) ) return;

	// initialize
	AppState::add(AppState::STYLESHEET);
	$config = Lone::get('Config');
	$root_url = $config['css_url'];
	$cache_dir = $config['css_path'];

	$val = $params['nocombine'] ?? false;
	$combine_files = !cms_to_bool($val);
	$val = $params['stripbackground'] ?? false;
	$trimbackground = cms_to_bool($val);
	$fnsuffix = ( $val ) ? '_e_' : '';
	if( isset($params['min']) ) {
		$minimise = cms_to_bool($val);
	}
	else {
		$minimise = !constant('CMS_DEBUG');
	}
	//TODO support $params['templatetype'] related to a theme

//	$design_id = -1;
	$name = '';
	$out = '';
	$styles = '';

	try {
		if( !empty($params['name']) ) {
			$name = trim($params['name']); //sheet-name prefix
		}
		elseif( !empty($params['styles']) ) { //since 3.0
			$styles = trim($params['styles']);
		}
/*		elseif( !empty($params['designid']) ) { //deprecated since 3.0
			$design_id = (int)$params['designid'];
		}
*/
		else {
			//TODO support $params['templatetype'] related to a theme
			$content_obj = cmsms()->get_content_object();
			if( !is_object($content_obj) ) {
				throw new RuntimeException('Cannot find page-content object');
			}
			$styles = $content_obj->Styles();
/*			if( !$styles ) {
				$design_id = (int)$content_obj->GetPropertyValue('design_id');
			}
*/
		}
		if( !($name || $styles/* || $design_id > 0*/) ) {
			throw new RuntimeException('Cannot identify stylesheet(s) for page');
		}

		// build query
		$query = null;
		if( $name ) {
			// stylesheet by name(prefix)
			$query = new StylesheetQuery(['name'=>$name]);
		}
		elseif( $styles ) {
			// stylesheet(s) by id
			$query = new StylesheetQuery(['styles'=>$styles]);
		}
/*		elseif( $design_id > 0 ) {
			// stylesheet(s) by design id
			$query = new StylesheetQuery([ 'design'=>$design_id ]);
		}
*/
		if( !$query ) {
			throw new RuntimeException('Failed to build a stylesheet query using the provided parameters');
		}

		// execute
		$nrows = $query->TotalMatches();
		if( !$nrows ) {
			throw new RuntimeException('No stylesheet matched the specified criteria');
		}
		$res = $query->GetMatches(); // we have sheet(s)

		// prepare for output-order optimisation

		// media-output-order sorters
		$orders = [
			'all' => 1, // 1st, so it can be overridden by specifics
			'screen' => 10,
			'handheld' => 20, // last of screeen-related, so it prevails
			'tv' => 12,
			'projection' => 12,
			'print' => 2, // 2nd-lowest priority
			'embossed' => 2,
			'tty' => 2,
			'braille' => 25,
			'speech' => 25,
			'aural' => 25, // CSS2 version of 'speech'
		];

		$all_media = []; // each member: type => [ [0]=original order [1]=priority [2]=modified-timestamp ]

		foreach( $res as $o => $cssobj ) {
			$st = $cssobj->get_modified();
			$val = $cssobj->get_media_query();
			if( $val ) {
				$key = trim(strtr($val, ['  ' => ' ']), ' ,');
				$p = 1;
				foreach( $orders as $type => $priority ) {
					if( stripos($key, $type) !== false ) {
						$p = $priority;
						break;
					}
				}
				$all_media[$key][] = [$o, $p, $st];
			}
			else {
				$types = $cssobj->get_media_types();
				if( $types ) {
					foreach( $types as $val ) {
						$key = trim($val);
						$p = $orders[$key] ?? 1;
						$all_media[$key][] = [$o, $p, $st];
					}
				}
				else {
					$all_media['all'][] = [$o, 1, $st];
				}
			}
		}
		if( count($all_media) > 1 ) {
			uasort($all_media, function($a, $b) {
				if( $a[0][1] != $b[0][1] ) {
					return $a[0][1] <=> $b[0][1]; // priority-order
				}
				return $a[0][0] <=> $b[0][0]; // original-order
			});
		}

		if( $combine_files ) {
			// merge stylesheets
			// specific media-type ?
			// BUT sheet 'media-type' parameter is deprecated in favour of 'media-query'
			if( isset($params['media']) && strtolower($params['media']) != 'all' ) {
				// merge all matches into one file
				$filename = ( count($res) > 1 ) ? 'combined_' : '_'; // should be count($list) but circular!
				$filename .= Crypto::hash_string(/*$design_id.*/serialize($params).serialize($all_timestamps).$fnsuffix).'.css';
				$fp = cms_join_path($cache_dir, $filename);
				if( !is_file($fp) ) {
					$list = [];
					foreach( $all_media as $onemedia ) {
						$cssobj = $res[$onemedia[0][0]];
						if( in_array($params['media'], $cssobj->get_media_types()) ) {
							$list[] = $cssobj->get_name();
						}
					}
					writeCache($fp, $list, $trimbackground, $minimise, $template);
				}
				toString($filename, $params['media'], '', $root_url, $out, $params);
			}
			else {
				foreach( $all_media as $onemedia ) {
					// combine match(es) into one file
					$filename = ( count($onemedia) > 1 ) ? 'combined_' : '_';
					$filename .= Crypto::hash_string(/*$design_id.*/serialize($params).$onemedia[0][2].$fnsuffix).'.css';
					$fp = cms_join_path($cache_dir, $filename);
					if( !is_file($fp) ) {
						$list = [];
						foreach( $onemedia as $data ) {
							$list[] = $res[$data[0]]->get_name();
						}
						writeCache($fp, $list, $trimbackground, $minimise, $template);
					}
					// get common media_type and media_query
					$cssobj = $res[$onemedia[0][0]];
					$val = $cssobj->get_media_query();
					$media_query = ($val) ? trim(strtr($val, ['  ' => ' ']), ' ,') : '';
					$media_type = implode(',', $cssobj->get_media_types());
					toString($filename, $media_query, $media_type, $root_url, $out, $params);
				}
			}
		}
		else {
			// do not merge stylesheets
			foreach( $all_media as $onemedia ) {
				$cssobj = $res[$onemedia[0][0]];

				if( isset($params['media']) ) {
					if( !in_array($params['media'], $cssobj->get_media_types()) ) continue;
					$media_query = '';
					$media_type = $params['media'];
				}
				else {
					$val = $cssobj->get_media_query();
					$media_query = ($val) ? trim(strtr($val, ['  ' => ' ']), ' ,') : '';
					$media_type  = implode(',', $cssobj->get_media_types());
				}

				$filename = 'stylesheet_'.Crypto::hash_string('_'.$cssobj->get_id().$onemedia[0][2].$fnsuffix).'.css';
				$fp = cms_join_path($cache_dir, $filename);
				if( !is_file($fp) ) {
					writeCache($fp, $cssobj->get_name(), $trimbackground, $minimise, $template);
				}
				toString($filename, $media_query, $media_type, $root_url, $out, $params);
			}
		}

		// cleanup & output
		if( $out ) {
			// remove trailing comma when $params['nolinks'] is set
			if( isset($params['nolinks']) && cms_to_bool($params['nolinks']) && endswith($out, ',') ) {
				$out = substr($out, 0, -1);
			}
		}
	}
	catch (Throwable $t) {
//		trigger_error('cms_stylesheet: '.$t->getMessage());
		log_error($t->GetMessage(), 'cms_stylesheet plugin');
		$out = '<!-- cms_stylesheet error: '.$t->GetMessage().' -->';
	}

	// notify the system that we are no longer doing a stylesheet
	AppState::remove(AppState::STYLESHEET);

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
} // main function

function smarty_cms_about_function_cms_stylesheet()
{
	echo _ld('tags', 'about_generic', 'jeff &lt;jeff@ajprogramming.com&gt;',
	'<li>Rework from {stylesheet}</li>
<li>(Stikki and Calguy1000) Code cleanup, added grouping by media type / media query, fixed cache issues</li>
<li>Added optional \'min\' parameter (default true)</li>'
	);
}
/*
D function smarty_cms_help_function_cms_stylesheet()
{
	//TODO support <li>templatetype</li>  related to a theme
	// TODO parameter details
	echo _ld('tags', 'help_generic',
	'This plugin minimizes and merges contents of stylesheets for frontend pages',
	'cms_stylesheet ...',
	'<li>name: </li>
<li>styles: </li>
<li>designid: </li>
<li>media: </li>
<li>min: </li>
<li>nocombine: </li>
<li>nolinks: </li>
<li>stripbackground: </li>'
	);
}
*/
} // global namespace