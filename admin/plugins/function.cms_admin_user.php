<?php
#Plugin to retrieve information about the current user.
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

use CMSMS\AppState;
use CMSMS\UserOperations;

function smarty_function_cms_admin_user($params, $template)
{
	$out = '';

	if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) {
		$uid = (int)($params['uid'] ?? 0);
		if( $uid > 0 ) {
			$user = UserOperations::get_instance()->LoadUserByID((int)$params['uid']);
			if( is_object($user) ) {
				$mode = trim($params['mode'] ?? 'username');
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

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_cms_admin_user()
{
	echo lang_by_realm('tags', 'about_generic', 'intro', <<<'EOS'
<li>detail</li>
EOS
	);
}
*/
function smarty_cms_help_function_cms_admin_user()
{
	echo lang_by_realm('tags', 'help_generic',
	'This plugin retrieves information about the current user',
	'cms_admin_user uid=N mode=fullname',
	<<<'EOS'
<li>uid: user identifier (integer)</li>
<li>mode: optional property wanted, one of: username (default), email, firstname, lastname or fullname</li>
EOS
	);
}
