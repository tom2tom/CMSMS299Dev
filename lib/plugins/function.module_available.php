<?php
/*
Plugin to report whether a specified module is currently usable.
Copyright (C) 2012-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Utils;

function smarty_function_module_available($params, $template)
{
	$name = $params['module'] ?? $params['m'] ?? $params['name'] ?? '';
	$name = trim($name);

	if( $name ) {
		$out = Utils::module_available($name);
	}
	else {
		$out = false;
	}
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_module_available()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Robert Campbell 2012', "<li>$n</li>");
}
*/
function smarty_cms_help_function_module_available()
{
	echo _ld('tags', 'help_generic',
	'This plugin reports whether a specified module is currently usable',
	'module_available module=somename',
	'<li>module: name of wanted module</li>
<li>m: alias for module</li>
<li>name: alias for module</li>'
	);
}