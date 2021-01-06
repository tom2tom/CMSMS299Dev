<?php
/*
Plugin to update cached 'MODULE_HINT' property-values
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\Utils;

function smarty_function_cms_module_hint($params, $template)
{
	if( !isset($params['module']) ) return '';

	$module = trim($params['module']);
	$modobj = Utils::get_module($module);
	if( !is_object($modobj) ) return '';

	$data = Utils::get_app_data('__CMS_MODULE_HINT__'.$module);
	if( !$data ) $data = [];

	// NOTE no check here whether the module understands the parameter
	foreach( $params as $key => $value ) {
		if( $key == 'module' ) continue;
		$data[$key] = $value;
	}

	Utils::set_app_data('__CMS_MODULE_HINT__'.$module, $data);
	return '';
}
/*
function smarty_cms_about_function_cms_module_hint()
{
	echo lang_by_realm('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR lang('none')
EOS
	);
}
*/
/*
function smarty_cms_help_function_cms_module_hint()
{
	echo lang_by_realm('tags', 'help_generic', 'This plugin does ...', 'cms_module_hint ...', <<<'EOS'
<li>module</li>
<li>anything else to be cached</li>
EOS
	);
}
*/
