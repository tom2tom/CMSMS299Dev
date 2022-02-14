<?php
/*
Plugin to disable template processing during the rest of the current request
Copyright (C) 2017-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use function CMSMS\disable_template_processing;

// since 2.99
function smarty_function_disable_template($params, $template)
{
	disable_template_processing();
	return '';
}
/*
function smarty_cms_about_function_disable_template()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', '2017', "<li>$n</li>");
}
*/
function smarty_cms_help_function_disable_template()
{
	$n = _la('none');
	echo _ld('tags', 'help_generic',
	'This plugin disables Smarty-template processing during the rest of the current request',
	'disable_template',
	"<li>$n</li>"
	);
}