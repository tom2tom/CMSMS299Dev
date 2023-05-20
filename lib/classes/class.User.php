<?php
/*
Admin-user class for CMSMS
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppParams;
use CMSMS\Lone;

/**
 * Generic admin user class.
 * This can be used for any logged in user or user-related function.
 *
 * @package CMS
 * @since 0.6.1
 * @since 3.0 non-final status is deprecated
 * @license GPL
 */
/*final */class User
{
	/**
	 * @var int $id User id
	 */
	public $id;

	/**
	 * @var string $account Username hash
	 * @since 3.0
	 */
	public $account;

	/**
	 * @var string $username Username / login / account
	 * @since 3.0 plaintext here but stored encrypted
	 */
	public $username;

	/**
	 * @var string $password Password hash
	 */
	public $password;

	/**
	 * @var bool $newpass Flag whether the password property has been validly changed
	 * @since 3.0
	 */
	public $newpass;

	/**
	 * @var bool $pwreset Flag whether the current password needs to be reset
	 * @since 3.0
	 */
	public $pwreset;

	/**
	 * @var string $firstname User's 'first' name, if any
	 */
	public $firstname;

	/**
	 * @var string $lastname User's 'last' name, if any
	 */
	public $lastname;

	/**
	 * @var string $email User's email address, if any
	 * @since 3.0 plaintext here but stored encrypted
	 */
	public $email;

	/**
	 * @var bool $active Flag whether the user is 'normal' (FALSE implies 'ignored')
	 */
	public $active;

	/**
	 * @var bool $adminaccess Flag whether the user may log in to the admin console
	 * @deprecated since 3.0 This is of no practical use, as a distinction from the 'active' property
	 */
	public $adminaccess;

	/**
	 * Generic constructor. Runs the SetInitialValues method.
	 */
	public function __construct()
	{
		$this->SetInitialValues();
	}

	/**
	 * Sets object to some sane initial values
	 *
	 * @since 0.6.1
	 */
	public function SetInitialValues()
	{
		$this->id = 0;
		$this->account = '';
		$this->username = '';
		$this->password = '';
		$this->newpass = false;
		$this->pwreset = false;
		$this->firstname = '';
		$this->lastname = '';
		$this->email = '';
		$this->active = true;
		$this->adminaccess = true; //since 3.0 not false, and deprecated
	}

	/**
	 * Sets the user's active state.
	 *
	 * @since 3.0
	 * @param bool $flag The active state.
	 */
	public function SetActive($flag = true)
	{
		$this->active = (bool) $flag;
	}

	/**
	 * If possible, hashes and caches the password of this User
	 *
	 * @since 0.6.1
	 * @param string $password The plaintext password
	 * @return bool indicating validity (inc. true for no-change) since 3.0
	 */
	public function SetPassword($password)
	{
		$userops = Lone::get('UserOperations');
		if ($userops->PasswordCheck($this, $password)) {
			$this->newpass = true;
			$this->password = $userops->PreparePassword($password);
		} else {
			$this->newpass = false;
			//TODO interpret problem, advise upstream
		}
		return $this->newpass;
	}

	/**
	 * Authenticate a users password.
	 *
	 * @since 3.0
	 * @param string $password The plaintext password.
	 */
	public function Authenticate($password)
	{
		if (strlen($this->password) == 32 && strpos($this->password, '.') === false) {
			// old md5 methodology
			$hash = md5(AppParams::get('sitemask', '').$password);
			return hash_equals($hash, $this->password);
		} else {
			return password_verify($password, $this->password);
		}
	}

	/**
	 * Saves the user to the database.  If no user_id is set, then a new record
	 * is created.  If the user_id is set, then the record is updated to all
	 * values in the User object. If $newpass property id true, the password
     * value is also updated.
	 * @since 0.6.1
	 *
	 * @return bool indicating success
	 */
	public function Save()
	{
		$userops = Lone::get('UserOperations');
		if ($this->id > 0) {
			$result = $userops->UpdateUser($this);
		} else {
			$newid = $userops->InsertUser($this);
			if ($newid > 0) {
				$this->id = $newid;
				$result = true;
			} else {
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Deletes the record for this user from the database and resets
	 * all properties to their initial values.
	 * @since 0.6.1
	 *
	 * @return bool indicating success
	 */
	public function Delete()
	{
		$result = false;
		if ($this->id > 0) {
			$userops = Lone::get('UserOperations');
			$result = $userops->DeleteUserByID($this->id);
			if ($result) { $this->SetInitialValues(); }
		}
		return $result;
	}
} //class
//backward-compatiblity shiv
\class_alias(User::class, 'User', false);
