<?php
#Plugin to generate html & js for a site-page picker
#Copyright(C) 2006-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AdminUtils;

function smarty_function_page_selector($params, $template)
{
	$selected = ( isset($params['value']) ) ? (int)$params['value'] : 0;
	if( isset($params['selected']) ) $selected = (int)$params['selected'];
	$name = ( isset($params['name']) ) ? trim($params['name']) : 'parent_id';
	$allow_current = ( isset($params['allowcurrent']) ) ? cms_to_bool($params['allowcurrent']) : false;
	$allow_all = ( isset($params['allowall']) ) ? cms_to_bool($params['allowall']) : false;
	$for_child = ( isset($params['for_child']) ) ? cms_to_bool($params['for_child']) : false;

	$out = AdminUtils::CreateHierarchyDropdown(0,$selected,$name,$allow_current,false,$allow_all,$for_child);
	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}
