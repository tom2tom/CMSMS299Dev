<?php
/*
Plugin to generate html & js for a site-page picker
Copyright(C) 2006-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\AdminUtils;

function smarty_function_page_selector($params, $template)
{
	$selected = (int)($params['selected'] ?? $params['value'] ?? 0);
	$name = trim($params['name'] ?? 'parent_id');
	$allow_current = cms_to_bool($params['allowcurrent'] ?? false);
	$allow_all = cms_to_bool($params['allowall'] ?? false);
	$for_child = cms_to_bool($params['for_child'] ?? false);

	$out = AdminUtils::CreateHierarchyDropdown(0, $selected, $name, $allow_current, false, $allow_all, $for_child);
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_page_selector()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Robert Campbell 2006', "<li>$n</li>");
}
*/
/*
D function smarty_cms_help_function_page_selector()
{
	TODO parameter details
	echo _ld('tags', 'help_generic',
	'This plugin generates html & js for a website-page-selector',
	'page_selector ...',
	'<li>allowall: </li>
<li>allowcurrent: </li>
<li>for_child: </li>
<li>name: </li>
<li>selected: </li>
<li>value: </li>'
	);
}
*/