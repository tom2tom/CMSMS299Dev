<?php
/*
Plugin to get the page-header content needed to set up and operate rich-text-editing
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

use CMSMS\FormUtils;
use CMSMS\SingleItem;

// since 2.99
function smarty_function_cms_init_editor($params, $template)
{
	$wysiwyg = $params['wysiwyg'] ?? '';
	if( $wysiwyg ) {
		// we specified a wysiwyg, so we're gonna override every wysiwyg area on this page.
		$selector = 'textarea.cmsms_wysiwyg';
	}
	else {
		// we're gonna poll the wysiwygs
		$wysiwygs = FormUtils::get_requested_wysiwyg_modules();
		if( !$wysiwygs || !is_array($wysiwygs) ) return '';
		$tmp = array_keys($wysiwygs);
		$wysiwyg = $tmp[0]; // first wysiwyg only, for now.
		$selector = null;
	}

//	$force = cms_to_bool($params['force'] ?? false);
	$mod = SingleItem::ModuleOperations()->GetWYSIWYGModule($wysiwyg);
	if( !is_object($mod) ) return '';

	// get the output
	$output = $mod->WYSIWYGGenerateHeader($selector); // old API
	if( !$output ) return '';

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $output);
		return '';
	}
	return $output;
}

function smarty_cms_about_function_cms_init_editor()
{
	echo <<<'EOS'
<p>Author: Robert Campbell</p>
<p>Change History:</p>
<ul>
<li>None</li>
</ul>
EOS;
}
/*
function smarty_cms_help_function_cms_init_editor()
{
	echo lang_by_realm('tags', 'help_generic', 'This plugin does ...', 'cms_init_editor ...', <<<'EOS'
<li>wysiwyg</li>
EOS
	);
}
*/
