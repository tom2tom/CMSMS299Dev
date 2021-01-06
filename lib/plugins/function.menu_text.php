<?php
/*
Plugin to retrieve the menu-item label for the current page.
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

use CMSMS\AppSingle;

function smarty_function_menu_text($params, $template)
{
	$content_obj = AppSingle::App()->get_content_object();
	if (!is_object($content_obj) || $content_obj->Id() == -1) {
		$result = lang('404description'); //TODO or AppParams::get(X, '404 Error');
	}
	else {
		$result = $content_obj->MenuText();
	}
	//TODO maybe disable SmartyBC-supported {php}{/php} in $result
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $result);
		return '';
	}
	return $result;
}

function smarty_cms_about_function_menu_text()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@cmsmadesimple.org&gt;</p>
<p>Change History:</p>
<ul>
<li>None</li>
</ul>
EOS;
}
/*
function smarty_cms_help_function_menu_text()
{
	echo lang_by_realm('tags', 'help_generic', 'This plugin does ...', 'menu_text', "<li>none</li>");
}
*/
