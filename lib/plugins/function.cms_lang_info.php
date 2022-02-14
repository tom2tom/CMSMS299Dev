<?php
/*
Plugin to retrieve information about the specified or default language/translation.
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\NlsOperations;

function smarty_function_cms_lang_info($params, $template)
{
	if( isset($params['lang']) ) {
		$lang = trim($params['lang']);
	}
	else {
		$lang = NlsOperations::get_current_language();
	}
	$info = NlsOperations::get_language_info($lang);
	if( !$info ) return '';
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $info);
		return '';
	}
	return $info;
}
/*
function smarty_cms_about_function_cms_lang_info()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', '2004',"<li>$n</li>");
}
*/
function smarty_cms_help_function_cms_lang_info()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves information (if any) about the specified or default language/translation',
	'cms_lang_info ...',
	'<li>lang: optional language/translation identifier</li>'
	);
}