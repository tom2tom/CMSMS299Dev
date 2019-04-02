<?php
#Plugin to...
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

function smarty_function_cms_admin_user($params, $template)
{
	$smarty = $template->smarty;
	$out = null;

	if( cmsms()->test_state(CmsApp::STATE_ADMIN_PAGE) ) {
		$uid = (int)get_parameter_value($params,'uid');
		if( $uid > 0 ) {
			$user = (new UserOperations())->LoadUserByID((int)$params['uid']);
			if( is_object($user) ) {
				$mode = trim(get_parameter_value($params,'mode','username'));
				switch( $mode ) {
				case 'username':
					$out = $user->username;
					break;
				case 'email':
					$out = $user->email;
					break;
				case 'firstname':
					$out = $user->firstname;
					break;
				case 'lastname':
					$out = $user->lastname;
					break;
				case 'fullname':
					$out = "{$user->firstname} {$user->lastname}";
					break;
				}
			}
		}
	}

	if( isset($params['assign']) ) {
		$smarty->assign($params['assign'],$out);
		return;
	}
	return $out;
}

