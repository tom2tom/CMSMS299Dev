<?php
/*
Deprecated plugin to generate a site navigation-menu using the 'Navigator' module and a specified or default template
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

function smarty_function_site_mapper($params, $template)
{
	$params['module'] = 'Navigator';

	if( empty($params['template']) ) {
		$params['template'] = 'minimal_menu.tpl';
	}

	return cms_module_plugin($params, $template);
}
/*
function smarty_cms_about_function_site_mapper()
{
	echo lang_by_realm('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
	);
}
*/
/*
D function smarty_cms_help_function_site_mapper()
{
	echo lang_by_realm('tags', 'help_generic',
	'This plugin generates a site navigation-menu using the 'Navigator' module and a specified or default template',
	'site_mapper template=whatever',
	<<<'EOS'
<li>template: optional name of Navigator-module template</li>
EOS
	);
}
*/
