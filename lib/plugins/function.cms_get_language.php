<?php
/*
Plugin to retrieve the identifier of the current language/translation
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

use CMSMS\NlsOperations;

function smarty_function_cms_get_language($params, $template)
{
	$lang = NlsOperations::get_current_language();
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $lang);
		return '';
	}
	return $lang;
}
/*
function smarty_cms_about_function_cms_get_language()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004', "<li>$n</li>");
}
*/
function smarty_cms_help_function_cms_get_language()
{
	$n = _la('none');
	echo _ld('tags', 'help_generic',
	'This plugin retrieves the identifier of the current language/translation',
	'cms_get_language',
	"<li>$n</li>"
	);
}