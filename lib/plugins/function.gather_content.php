<?php
/*
Plugin to get page content (of any sort) via a hooklist
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\HookOperations;

//since 2.99
function smarty_function_gather_content($params, $template)
{
	$listname = (!empty($params['list'])) ? $params['list'] : 'gatherlist';
	$aout = HookOperations::do_hook_accumulate($listname);
	$out = ($aout) ? implode(PHP_EOL, $aout) : ''; //TODO if multi-dimension array

	if (!empty($params['assign'])) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_gather_content()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', '2018', "<li>$n</li>");
}
*/
function smarty_cms_help_function_gather_content()
{
	echo _ld('tags', 'help_function_gather_content');
}