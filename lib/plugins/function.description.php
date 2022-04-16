<?php
/*
Plugin to get a page title
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Elijah Lofgren and all other contributors from the CMSMS Development Team.

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

function smarty_function_description($params, $template)
{
	$content_obj = CMSMS\SingleItem::App()->get_content_object();
	if( !is_object($content_obj) || $content_obj->Id() == -1 ) {
		$out = lang('404description'); //TODO or AppParams::get(X, '404 Error');
	}
	else {
		$out = $content_obj->TitleAttribute();
	}
	//TODO maybe disable SmartyBC-supported {php}{/php} in $out

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}

function smarty_cms_about_function_description()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Elijah Lofgren &lt;elijahlofgren@elijahlofgren.com&gt; 2004',
	"<li>$n</li>"
	);
}

function smarty_cms_help_function_description()
{
	$n = _la('none');
	echo _ld('tags', 'help_generic', 'This plugin retrieves the title of the current page', "<li>$n</li>");
}