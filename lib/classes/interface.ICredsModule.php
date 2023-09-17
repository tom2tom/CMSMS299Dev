<?php
/*
An interface to define the minimum API for admin-user credentials-check modules.
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

/**
 * An interface to define methods in admin-user credentials-validation modules.
 * @since 3.0
 * @package CMS
 * @license GPL
 */
interface ICredsModule
{
	/**
	 * Report whether the supplied name conforms to the current user/account
	 * name policy for the site
	 * @param CMSMS\User $user object containing user parameters
	 * @param string $name the user/account name to be checked
	 * @param bool $update whether this check is part of an update of the user's data
	 * @return array 2-members
	 * [0] = boolean indicating compliance
	 * [1] = if [0] is false, a html string for feedback into the console
	 */
	public function check_username(User $user, string $name, bool $update = false): array;

	/**
	 * Report whether the supplied password conforms to the current password
	 * policy for the site
	 * @param CMSMS\User $user object containing user parameters
	 * @param string $pw the password to be checked
	 * @param bool $update whether this check is part of an update of the user's data
	 * @return array 2-members
	 * [0] = boolean indicating compliance
	 * [1] = if [0] is false, a html string for feedback into the console
	 */
	public function check_password (User $user, string $pw, bool $update = false): array;
} // interface
