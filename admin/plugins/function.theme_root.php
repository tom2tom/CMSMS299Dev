<?php
/*
Plugin to retrieve the root URL of the current admin theme.
Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of that license along with CMS Made Simple. 
If not, see <https://www.gnu.org/licenses/>.
*/

function smarty_function_theme_root($params, $template)
{
	$themeObject = CMSMS\Utils::get_theme_object();
	$url = $themeObject->root_url;

	if( !empty($params['assign']) ) {
		$template->assign($params['assign'], $url);
		return '';
	}
	return $url;
}
/*
function smarty_cms_about_function_theme_root()
{
	echo lang_by_realm('tags', 'about_generic', 'intro', <<<'EOS'
<li>detail</li>
EOS
	);
}
*/
function smarty_cms_help_function_theme_root()
{
	$n = lang('none');
	echo lang_by_realm('tags', 'help_generic',
	'This plugin retrieves the topmost/base URL of the current admin theme',
	'theme_root',
	"<li>$n</li>"
	);
}
