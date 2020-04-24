<?php
#Plugin to get page-content representing an admin icon
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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
use CMSMS\AppState;

function smarty_function_admin_icon($params, $template)
{
	if( !AppState::test_state(AppState::STATE_ADMIN_PAGE) ) return;

	$icon = null;
	$tagparms = ['class'=>'systemicon'];
	foreach( $params as $key => $value ) {
		switch( $key ) {
		case 'icon':
		case 'module':
			$icon = trim($value);
			break;
		case 'width':
		case 'height':
		case 'alt':
		case 'rel':
		case 'class':
		case 'id':
		case 'name':
		case 'title':
		case 'accesskey':
			$tagparms[$key] = trim($value);
			break;
		case 'assign':
			break;
		}
	}

	if( !$icon ) return;

	if( !isset($tagparms['alt']) ) $tagparms['alt'] = pathinfo($icon, PATHINFO_FILENAME);

	if( isset($params['module']) ) {
		$out = AdminUtils::get_module_icon($icon,$tagparms);
	}
	else {
		$out = AdminUtils::get_icon($icon,$tagparms);
	}

	if( !$out ) return;

	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}

