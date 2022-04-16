<?php
/*
Plugin to get the page-header content needed to set up and operate rich-text-editing
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\FormUtils;
use CMSMS\SingleItem;

// since 3.0
function smarty_function_cms_init_editor($params, $template)
{
	$wysiwyg = $params['wysiwyg'] ?? '';
	if( $wysiwyg ) {
		// we specified a wysiwyg, so we're gonna override every wysiwyg area on the page
		$selector = '.cmsms_wysiwyg';
	}
	else {
		// we're gonna poll the wysiwygs
		$wysiwygs = FormUtils::get_requested_wysiwyg_modules();
		if( !$wysiwygs || !is_array($wysiwygs) ) return '';
		$tmp = array_keys($wysiwygs);
		$wysiwyg = $tmp[0]; // use first of them
		$selector = '';
	}

//	$force = cms_to_bool($params['force'] ?? false);
	$mod = SingleItem::ModuleOperations()->GetWYSIWYGModule($wysiwyg);
	if( !is_object($mod) ) return '';

	// get the output
	$output = $mod->WYSIWYGGenerateHeader($selector); // no styling or $params
	if( !$output ) return '';

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $output);
		return '';
	}
	return $output;
}
/*
function smarty_cms_about_function_cms_init_editor()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Robert Campbell 2018', "<li>$n</li>");
}
*/
function smarty_cms_help_function_cms_init_editor()
{
	echo _ld('tags', 'help_generic',
	'This plugin gets the page-header content needed to set up and operate rich-text-editing',
	'cms_init_editor ...',
	'<li>(optional)wysiwyg: name of wanted editor/module</li>'
	);
}