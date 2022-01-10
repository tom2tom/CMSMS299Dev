<?php
/*
Plugin to generate page content for start-of-tab
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

function smarty_function_tab_start($params, $template)
{
	if( !empty($params['name']) ) {
		$out = CMSMS\AdminTabs::start_tab(trim($params['name']));
	}
	else {
		$out = ''; // no error feedback
	}
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_tab_start()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004', "<li>$n</li>");
}
*/
/*
D function smarty_cms_help_function_tab_start()
{
	echo _ld('tags', 'help_generic',
	'This plugin generates page content for the start of a tab',
	'tab_start name=...',
	'<li>name: the internal name of the tab (consistent with a tab header name)</li>'
	);
}
*/