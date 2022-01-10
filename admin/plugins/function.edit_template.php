<?php
/*
Plugin to generate html and js for a syntax highlight textarea to edit a template
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\TemplateOperations;

// since 2.99
function smarty_function_edit_template($params, $template)
{
	if (!isset($params['template']) || (int)$params['template'] < 0) {
		$params['value'] = '';
	} else {
		try {
			$tplobj = TemplateOperations::get_template($params['template']);
			$params['value'] = $tplobj->get_content();
		} catch (Throwable $t) {
			//TODO nice handler
			return $t->getMessage();
		}
	}
	if (empty($params['name'])) {
		$params['name'] = 'template_content';
	}
	$params['typer'] = 'smarty';
	require_once __DIR__.DIRECTORY_SEPARATOR.'function.syntax_area.php';
	return smarty_function_syntax_area($params, $template);
}
/*
function smarty_cms_about_function_edit_template()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'May 2019', "<li>$n</li>");
}
*/
function smarty_cms_help_function_edit_template()
{
	echo _ld('tags', 'help_generic',
	'This plugin generates html and javascript for a syntax-highlight textarea to edit a template',
	'<li>template (identifier)
<ul>
<li>not provided: New template</li>
<li>number < 1: New template</li>
<li>number > 0: Template id</li>
<li>non-numeric string: Template name</li>
</ul></li>
<li>Other parameters as for the syntax_area plugin</li>'
	);
}