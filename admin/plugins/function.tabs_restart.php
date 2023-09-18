<?php
/*
Plugin to revert tabs-class data back to vanilla
Copyright (C) 2020-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

// since 3.0

function smarty_function_tabs_restart($params, $template)
{
	CMSMS\AdminTabs::reset();
	return '';
}
/*
function smarty_cms_about_function_tabs_restart()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', '2020', "<li>$n</li>");
}
*/
function smarty_cms_help_function_tabs_restart()
{
	$n = _la('none');
	echo _ld('tags', 'help_generic2',
	'This plugin resets background data for admin-page tabs-layout back to vanilla',
	'tabs_restart',
	"<li>$n</li>"
	);
}
