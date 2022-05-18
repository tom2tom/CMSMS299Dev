<?php
/*
Plugin to retrieve information about the current user.
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppState;
use CMSMS\Lone;

function smarty_function_cms_admin_user($params, $template)
{
	$out = '';

	if( AppState::test(AppState::ADMIN_PAGE) ) {
		$uid = (int)($params['uid'] ?? 0);
		if( $uid > 0 ) {
			$user = Lone::get('UserOperations')->LoadUserByID((int)$params['uid']);
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
	$n = _la('none');
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004', "<li>$n</li>");
}
*/
function smarty_cms_help_function_cms_admin_user()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves information about the current user',
	'cms_admin_user uid=N mode=fullname',
	'<li>uid: user identifier (integer)</li>
<li>mode: optional property wanted, one of: username (default), email, firstname, lastname or fullname</li>'
	);
}