<?php
/*
Plugin to retrieve an URL representing to base/top folder containing jquery scripts & styles.
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundatio@cmsmadesimple.org>

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
function smarty_function_scripts_url($params, $template)
{
	$out = cms_path_to_url(CMS_SCRIPTS_PATH);
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_scripts_url()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', Initial release 2018', "<li>$n</li>");
}
*/
function smarty_cms_help_function_scripts_url()
{
	$n = _la('none');
	echo _ld('tags', 'help_generic',
	'This plugin retrieves an URL representing the topmost/base website-folder where jquery scripts &amp; related styles are located',
	'scripts_url',
	"<li>$n</li>"
	);
}