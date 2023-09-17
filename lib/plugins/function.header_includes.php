<?php
/*
Plugin to retrieve all inside<header></header> positioned page content
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

function smarty_function_header_includes($params, $template)
{
	$out = CMSMS\get_page_headtext();
	if (!empty($params['assign'])) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}

function smarty_cms_about_function_header_includes()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'May 2021', "<li>$n</li>");
}

function smarty_cms_help_function_header_includes()
{
	$n = _la('none');
	echo _ld('tags', 'help_generic',
	'This plugin retrieves all page-header positioned page content',
	'header_includes',
	"<li>$n</li>"
	);
}