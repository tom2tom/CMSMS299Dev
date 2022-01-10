<?php
/*
Plugin to retrieve the highest-priority language that's consistent with
the supplied params and browser capabilities
Copyright (C) 2010-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\NlsOperations;

function smarty_function_browser_lang($params, $template)
{
	$default = 'en';

	//
	// get the default language
	//
	if( isset($params['default']) ) {
		$default = strtolower(substr($params['default'], 0, 2));
	}

	//
	// get the accepted languages
	//
	if( !isset($params['accepted']) ) {
		return $default;
	}
	$tmp = trim($params['accepted']);
	$tmp = trim($tmp, ',');
	$tmp2 = explode(',', $tmp);
	if( !$tmp2 || !is_array($tmp2) ) {
		return $default;
	}

	$accepted = [];
	for( $i = 0, $n = count($tmp2); $i < $n; $i++ ) {
		if( strlen($tmp2[$i]) < 2 ) continue;
		$accepted[] = strtolower(substr($tmp2[$i],0,2));
	}
	//
	// process the accepted languages and the default
	// make sure the array is unique, and that the default
	// is listed first
	//
	$accepted = array_merge([$default], $accepted);
	$accepted = array_unique($accepted);

	$res = $default;
	//
	// process browser language
	//
	$langs = NlsOperations::get_browser_languages();
	if( $langs ) {
		foreach( $langs as $one => $weight ) {
			if( in_array($one, $accepted) ) {
				$res = $one;
				break;
			}
		}
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $res);
		return '';
	}
	return $res;
}
/*
function smarty_cms_about_function_browser_lang()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Robert Campbell Nov 2010', <li>$n</li>");
}
*/
function smarty_cms_help_function_browser_lang()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves the highest-priority language that\'s consistent with the supplied params and browser capabilities',
	'browser_lang ...',
	'<li>(optional) default: Default language</li>
<li>(optional) accepted: Comma-separated list of acceptable translations</li>'
	);
}