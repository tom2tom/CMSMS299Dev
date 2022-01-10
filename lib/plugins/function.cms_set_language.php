<?php
/*
Plugin to set the current language/translation.
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

use CMSMS\NlsOperations;

function smarty_function_cms_set_language($params, $template)
{
	if( isset($params['lang']) ) {
		$lang = trim($params['lang']);
	}
	else {
		$lang = '';
	}
//$res =
	NlsOperations::set_language($lang);
	return '';
}
/*
function smarty_about_function_cms_set_language() {
	$n = _la('none');
	echo _ld('tags', 'about_generic', Initial release Ted Kulp 2004', "<li>$n</li>");
}
*/
function smarty_help_function_cms_set_language()
{
	echo _ld('tags', 'help_generic',
	'This plugin sets the current language/translation',
	'cms_set_language ...',
	'<li>lang: optional translation name Default empty, hence try to interpret an appropriate language</li>'
	);
}
