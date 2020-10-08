<?php
#Plugin to...
#Copyright (C) 2013-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\Utils;

function smarty_function_cms_module_hint($params, $template)
{
	if( !isset($params['module']) ) return;

	$module = trim($params['module']);
	$modobj = Utils::get_module($module);
	if( !is_object($modobj) ) return;

	$data = Utils::get_app_data('__CMS_MODULE_HINT__'.$module);
	if( !$data ) $data = [];

	// warning, no check here if the module understands the parameter.
	foreach( $params as $key => $value ) {
	  if( $key == 'module' ) continue;
	  $data[$key] = $value;
	}

	Utils::set_app_data('__CMS_MODULE_HINT__'.$module,$data);
}

