<?php
/*
Plugin to retrieve an url representing the site's top/base assets folder.
Copyright (C) 2020-2021 CMS Made Simnple Foundation <foundatio@cmsmadesimple.org>

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

// since 2.99
function smarty_function_assets_url($params, $template)
{
	$out = CMS_ASSETS_URL;
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_assets_url()
{
	echo lang_by_realm('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
function smarty_cms_help_function_assets_url()
{
	$n = lang('none');
	echo lang_by_realm('tags', 'help_generic',
	'This plugin retrieves an URL representing the site\'s topmost/base folder where assets are stored.',
	'assets_url',
	"<li>$n</li>");
}
