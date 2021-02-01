<?php
/*
Singleton class of user-related functions
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
namespace CMSMS;

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\DeprecationNotice;
use CMSMS\HookOperations;
use CMSMS\User;
use Throwable;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use function get_userid;
use function sanitizeVal;

/**
 * Singleton class for doing user-related functions.
 * Many User-class methods are just wrappers around these.
 *
 * @final
 * @package CMS
 * @license GPL
 *
 * @since 0.6.1
 */
final class UserOperations
{
	//TODO namespaced global variables here
	/* *
	 * @ignore
	 */
//	private static $_instance = null;

	/**
	 * @ignore
	 */
	private $_user_groups;

	/**
	 * @ignore
	 */
	private $_users;

	/**
	 * @ignore
	 */
	private $_saved_users = [];

	/* *
	 * @ignore
	 */
//	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get the singleton instance of this class.
	 * @deprecated since 2.99 use CMSMS\AppSingle::UserOperations()
	 * @return UserOperations
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\AppSingle::UserOperations()'));
		return AppSingle::UserOperations();
	}

	/**
	 * Get a list of all users.
	 *
	 * @since 0.6.1
	 *
	 * @param int $limit  The maximum number of users to return
	 * @param int $offset Optional offset. Default 0
	 *
	 * @return array An array of User objects
	 */
	public function LoadUsers(int $limit = 10000, int $offset = 0) //: array
	{
		if (!is_array($this->_users)) {
			$db = AppSingle::Db();
			$result = [];
//, admin_access
			$query = 'SELECT user_id, username, password, first_name, last_name, email, active
FROM '.CMS_DB_PREFIX.'users ORDER BY username';
			$rst = $db->SelectLimit($query, $limit, $offset);

			while ($rst && !$rst->EOF()) {
				$row = $rst->fields;
				$userobj = new User();
				$userobj->id = $row['user_id'];
				$userobj->username = $row['username'];
				$userobj->firstname = $row['first_name'];
				$userobj->lastname = $row['last_name'];
				$userobj->email = $row['email'];
				$userobj->password = $row['password'];
				$userobj->active = $row['active'];
//				$userobj->adminaccess = $row['admin_access'];
				$result[] = $userobj;
				$rst->MoveNext();
			}
			if ($rst) { $rst->Close(); }
			$this->_users = $result;
		}

		return $this->_users;
	}

	/**
	 * Get all users in a given group.
	 *
	 * @param int $gid Group enumerator for the loaded users
	 *
	 * @return array of User objects
	 */
	public function LoadUsersInGroup(int $gid) //: array
	{
		$db = AppSingle::Db();
		$pref = CMS_DB_PREFIX;
		$result = [];
//, u.admin_access
		$query = <<<EOS
SELECT U.user_id, U.username, U.password, U.first_name, U.last_name, U.email, U.active
FROM {$pref}users U
JOIN {$pref}user_groups UG ON U.user_id = UG.user_id
JOIN {$pref}groups G ON UG.group_id = G.group_id
WHERE G.group_id = ?
ORDER BY username
EOS;
		$rst = $db->Execute($query, [$gid]);

		while ($rst && $row = $rst->FetchRow()) {
			$userobj = new User();
			$userobj->id = $row['user_id'];
			$userobj->username = $row['username'];
			$userobj->firstname = $row['first_name'];
			$userobj->lastname = $row['last_name'];
			$userobj->email = $row['email'];
			$userobj->password = $row['password'];
			$userobj->active = $row['active'];
//			$userobj->adminaccess = $row['admin_access'];
			$result[] = $userobj;
		}
		if ($rst) $rst->Close();
		return $result;
	}

	/**
	 * Load a user by login/account/name.
	 * Does not use the cache, so use sparingly.
	 *
	 * @since 0.6.1
	 *
	 * @param string $username		 Username to load
	 * @param string $password		 Optional (but not really) Password to check against
	 * @param bool $activeonly		 Optional flag whether to load the user only if [s]he is active Default true
	 * @param bool $adminaccessonly  Deprecated since 2.99 Optional flag whether to load the user only if [s]he may log in Default false
	 *
	 * @return mixed a User-class object or null or false
	 */
	public function LoadUserByUsername(string $username, string $password = '', bool $activeonly = true, bool $adminaccessonly = false)
	{
		// note: does not use cache
		$db = AppSingle::Db();

		$query = 'SELECT user_id,password FROM '.CMS_DB_PREFIX.'users WHERE ';
		$where = ['username = ?'];
		$params = [$username];

		if ($activeonly || $adminaccessonly) {
			$where[] = 'active = 1';
		}
/*		Deprecated since 2.99
		if ($adminaccessonly) {
			$where[] = 'admin_access = 1';
		}
*/
		$query .= implode(' AND ', $where);

		$row = $db->GetRow($query,$params);
		if ($row) {
			// ignore supplied invalid P/W chars
			$password = sanitizeVal($password, 0);
			$hash = $row['password'];
			$len = strlen(bin2hex($hash))/2; //ignore mb_ override
			if ($len > 32) { //bcrypt or argon2
				if (!password_verify($password, $hash)) {
					sleep(3);
					return;
				}
				if ((defined('PASSWORD_ARGON2I') && strncmp($hash, '$2y$', 4) == 0) //still uses bcrypt
				 || password_needs_rehash($hash, PASSWORD_DEFAULT)) {
					$this->trigger($db, $row['user_id'], $password);
				}
			} else { // old md5 methodology
				$tmp = md5(AppParams::get('sitemask', '').$password);
				if (!hash_equals($tmp, $hash)) {
					sleep(3);
					return;
				}
				$this->trigger($db, $row['user_id'], $password);
			}
			return $this->LoadUserByID($row['user_id']);
		}
	}

	protected function trigger($db, $uid, $password)
	{
		$query = 'UPDATE '.CMS_DB_PREFIX.'users SET password = ?';
		$args = [$this->PreparePassword($password)];
		if (!$this->PasswordCheck($uid, $password)) {
			$query .= ', set oldpassword = ?, passmodified_date = '. $db->DbTimeStamp(100);
			$args[] = $args[0]; // prevent repitition
		}
		$query .= ' WHERE user_id = ?';
		$args[] = $uid;
		$db->Execute($query, $args);
	}

	/**
	 * Load a user by user id.
	 *
	 * @since 0.6.1
	 *
	 * @param mixed $uid User id to load
	 *
	 * @return mixed If successful, the populated User object | false
	 */
	public function LoadUserByID(int $uid)
	{
		if ($uid < 1) {
			return false;
		}
		if (isset($this->_saved_users[$uid])) {
			return $this->_saved_users[$uid];
		}

		$result = false;
		$db = AppSingle::Db();
		//, admin_access
		$query = 'SELECT username, password, active, first_name, last_name, email FROM '.CMS_DB_PREFIX.'users WHERE user_id = ?';
		$row = $db->GetRow($query, [$uid]);
		if ($row) {
			$userobj = new User();
			$userobj->id = $uid;
			$userobj->username = $row['username'];
			$userobj->password = $row['password'];
			$userobj->firstname = $row['first_name'];
			$userobj->lastname = $row['last_name'];
			$userobj->email = $row['email'];
//			$userobj->adminaccess = $row['admin_access'];
			$userobj->active = $row['active'];
			$result = $userobj;
		}

		$this->_saved_users[$uid] = $result;
		return $result;
	}

	/**
	 * Save a new user to the database.
	 * @since 0.6.1
	 *
	 * @param mixed $user User object to save
	 *
	 * @return int The new user id upon success or -1 on failure
	 */
	public function InsertUser(User $user) //: int
	{
		$db = AppSingle::Db();
		$pref = CMS_DB_PREFIX;
		//setting create_date should be redundant with DT setting
		$now = $db->DbTimeStamp(time());
//admin_access,
		$query = <<<EOS
INSERT INTO {$pref}users
(username,password,active,first_name,last_name,email,create_date,passmodified_date)
SELECT ?,?,?,?,?,?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pref}users T WHERE T.username=?)
EOS;
//1,
		$dbr = $db->Execute($query, [
			$user->username,
			$user->password,
			$user->active,
			$user->firstname,
			$user->lastname,
			$user->email,
			$now,
			$now,
			$user->username
		]);

		return ($dbr) ? $db->Insert_ID() : -1;
	}

	/**
	 * Update an existing user in the database.
	 *
	 * @since 0.6.1
	 *
	 * @param mixed $user User object including the data to save
	 *
	 * @return bool indicating success
	 */
	public function UpdateUser($user) //: bool
	{
		$db = AppSingle::Db();
		// check for username conflict
		$query = 'SELECT 1 FROM '.CMS_DB_PREFIX.'users WHERE username = ? AND user_id != ?';
		$tmp = $db->GetOne($query, [$user->username, $user->id]);
		if ($tmp) {
			return false;
		}

		$now = $db->DbTimeStamp(time());
//admin_access = ?
		$query = 'UPDATE '.CMS_DB_PREFIX.'users SET username = ?, first_name = ?, last_name = ?, email = ?, active = ?, modified_date = '.$now.' WHERE user_id = ?';
//		$user->adminaccess,
		$dbr = $db->Execute($query, [$user->username, $user->firstname, $user->lastname, $user->email, $user->active, $user->id]);
		if ($dbr && !empty($user->repass)) {
			$query = 'UPDATE '.CMS_DB_PREFIX.'users SET oldpassword = password, password = ?, passmodified_date = '.$now.' WHERE user_id = ?';
			$dbr = $db->Execute($query, [$user->password, $user->id]);
		}
		return ($dbr != false);
	}

	/**
	 * Delete an existing user from the database.
	 *
	 * @since 0.6.1
	 *
	 * @param int $uid Id of the user to delete
	 * @return bool indicating success
	 */
	public function DeleteUserByID(int $uid) //: bool
	{
		if ($uid <= 1) {
			return false;
		}

		if (!$this->CheckPermission(get_userid(false), 'Manage Users')) {
			return false;
		}

		$db = AppSingle::Db();

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'user_groups WHERE user_id = ?';
		$dbr = $db->Execute($query, [$uid]);

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'additional_users WHERE user_id = ?';
		$dbr = $dbr && $db->Execute($query, [$uid]);

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'users WHERE user_id = ?';
		$dbr = $dbr && $db->Execute($query, [$uid]);

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'userprefs WHERE user_id = ?';
		$dbr = $dbr && $db->Execute($query, [$uid]);

		return $dbr;
	}

	/**
	 * Show the number of pages the given user owns.
	 *
	 * @since 0.6.1
	 *
	 * @param int $uid Id of the user to count
	 *
	 * @return int Number of owned pages, or 0 upon a problem
	 */
	public function CountPageOwnershipByID(int $uid) //: int
	{
		$db = AppSingle::Db();
		$query = 'SELECT count(*) AS count FROM '.CMS_DB_PREFIX.'content WHERE owner_id = ?';
		$result = $db->GetOne($query, [$uid]);
		return (int)$result;
	}

	/**
	 * Generates assoc. array of users, suitable for use in a dropdown.
	 *
	 * @since 2.2
	 *
	 * @return array, each member like userid=>username
	 */
	public function GetList() //: array
	{
		$out = [];
		$allusers = $this->LoadUsers();
		foreach ($allusers as $userobj) {
			$out[$userobj->id] = $userobj->username;
		}
		return $out;
	}

	/**
	 * Generate an HTML select element containing a user list.
	 *
	 * @deprecated since ?
	 *
	 * @param int	 $currentuserid
	 * @param string $name The HTML element name
	 *
	 * @return string maybe empty
	 */
	public function GenerateDropdown($currentuserid = null, $name = 'ownerid') //: string
	{
		$result = '';
		$list = $this->GetList();
		if ($list) {
			$result .= '<select name="'.$name.'">';
			foreach ($list as $uid => $username) {
				$result .= '<option value="'.$uid.'"';
				if ($uid == $currentuserid) {
					$result .= ' selected="selected"';
				}
				$result .= '>'.$username.'</option>';
			}
			$result .= '</select>';
		}
		return $result;
	}

	/**
	 * Test whether $uid is a member of the group identified by $gid.
	 *
	 * @param int $uid User ID to test
	 * @param int $gid Group ID to test
	 *
	 * @return true if test passes, false otherwise
	 */
	public function UserInGroup(int $uid, int $gid) //: bool
	{
		$groups = $this->GetMemberGroups($uid);
		return in_array($gid, $groups);
	}

	/**
	 * Test whether $uid is a member of the admin group, or is the first user account.
	 *
	 * @param int $uid
	 *
	 * @return bool
	 */
	public function IsSuperuser(int $uid) //: bool
	{
		if ($uid == 1) {
			return true;
		}
		$groups = $this->GetMemberGroups(1);
		if ($groups) {
			if (in_array($uid, $groups)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the ids of all groups to which the user belongs.
	 *
	 * @param int $uid
	 *
	 * @return array
	 */
	public function GetMemberGroups(int $uid) //: array
	{
		if (!is_array($this->_user_groups) || !isset($this->_user_groups[$uid])) {
			$db = AppSingle::Db();
			$query = 'SELECT group_id FROM '.CMS_DB_PREFIX.'user_groups WHERE user_id = ?';
			$col = $db->GetCol($query, [(int) $uid]);
			if (!is_array($this->_user_groups)) {
				$this->_user_groups = [];
			}
			$this->_user_groups[$uid] = $col;
		}
		return $this->_user_groups[$uid];
	}

	/**
	 * Adds the user to the specified group.
	 *
	 * @param int $uid
	 * @param int $gid
	 */
	public function AddMemberGroup(int $uid, int $gid)
	{
		if ($uid < 1 || $gid < 1) {
			return;
		}

		$db = AppSingle::Db();
		$now = $db->DbTimeStamp(time());
		$query = 'INSERT INTO '.CMS_DB_PREFIX."user_groups
(group_id,user_id,create_date) VALUES (?,?,$now)";
//		$dbr =
		$db->Execute($query, [$gid, $uid]);
		if (isset($this->_user_groups[$uid])) {
			unset($this->_user_groups[$uid]);
		}
	}

	/**
	 * Tests if any users-group which includes the specified user has the specified permission(s).
	 *
	 * @param int	$uid
	 * @param mixed $permname single string or (since 2.99) an array of them, optionally with following bool
	 *
	 * @return bool
	 */
	public function CheckPermission(int $uid, $permname) //: bool
	{
		if ($uid <= 0) {
			return false;
		}
		$groups = $this->GetMemberGroups($uid);
		if ($uid == 1) {
			array_unshift($groups, 1);
			$groups = array_unique($groups, SORT_NUMERIC);
		}
		if (!$groups) {
			return false;
		}
		if (is_string($permname)) {
			$perms = [$permname];
		} elseif (is_array($permname)) {
			$arr = func_get_args(); //since we're not breaking the method API
			unset($arr[0]);
			$perms = $arr;
		} else {
			return false;
		}
		$ops = AppSingle::GroupOperations();
		try {
			foreach ($groups as $gid) {
				if ($ops->CheckPermission($gid, ...$perms)) {
					return true;
				}
			}
		} catch (Throwable $t) {
			// nothing here
		}
		return false;
	}

	/**
	 * Check whether the current user's password-lifetime has expired
	 * @since 2.99
	 *
	 * @param mixed $user User object | null to process current user
	 *
	 * @return bool
	 */
	public function PasswordExpired($user = null) : bool
	{
		$val = AppParams::get('password_life', 0);
		if ($val > 0) {
			if ($user) {
				$uid = $user->id;
			} else {
				$uid = (AppSingle::LoginOperations())->get_loggedin_uid();
			}
			if ($uid < 1) {
				return true;
			}
			$db = AppSingle::Db();
			$stamp = $db->GetOne('SELECT UNIX_TIMESTAMP(passmodified_date) FROM '.CMS_DB_PREFIX.'users WHERE user_id = ?', [$uid]);
			if ((int)$stamp + $val * 86400 < time()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check validity of a posited password for the specified user
	 *
	 * @since 2.99
	 * @param mixed $user  int user enumerator | string username | populated User object (e.g. if not yet saved)
	 * @param string $candidate plaintext intended-password
	 * @param bool $force optional flag
	 * @return bool indicating validity (incl. true if no p/w-change and not $force)
	 */
	public function PasswordCheck($user, string $candidate, bool $force = false) : bool
	{
		$db = AppSingle::Db();
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.'users WHERE ';
		if (is_int($user)) {
			$query .= 'user_id = ?';
			$args = [$user];
			$user = $this->LoadUserByID($user);
		} elseif (is_string($user)) {
			$query .= 'username = ?';
			$args = [$user];
			$user = $this->LoadUserByUsername($user);
		} elseif ($user instanceof User) {
			$query .= 'user_id = ?';
			$args = [$user->id];
		} else {
			return false;
		}
		$row = $db->GetRow($query, $args);
		if ($row) {
			$hash = $user && $this->PreparePassword($candidate);
			if ($hash == $row['password'] && !$force) { return true; }// no change
			if ($hash == $row['oldpassword']) { return false; }// repitition not allowed
			//policy-checks
			if (function_exists('mb_strlen')) {
				$l = mb_strlen($candidate);
			} else {
				$l = strlen($candidate);
				//TODO kinda-adjust for multi-byte chars
			}
		    //per https://pages.nist.gov/800-63-3/sp800-63b.html : P/W 8-64 chars,
			if ($l < 8) { return false; } // and BCRYPT algo limit is 72 bytes
	        // TODO policy / blacklist check e.g not among values known to be commonly-used, expected or compromised
			$aout = HookOperations::do_hook_accumulate('Core::PasswordStrengthTest', $user, $candidate);
			// todo interpret result, display feedback mesage(s)
			if (1) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generate the content (hash) [to be] recorded for a password
	 * @since 2.99
	 * @param string $password
	 * @return string
	 */
	public function PreparePassword(string $password) : string
	{
/*		// for 'new' passwords, factor in a not-in-db string, per NIST recommendation
		$config = AppSingle::Config();
		$fp = cms_join_path($config['assets_path'], 'configs', 'siteuuid.dat');
		$str = @file_get_contents($fp);
		if ($str) {
			$password ^= $str;
//OR		$password = hash_hmac('sha3-256', $password, $str); //slow, <= 72 bytes (BCRYPT limit)
		}
*/
		return password_hash($password, PASSWORD_DEFAULT);
	}

	/**
	 * Check validity of a posited username for the specified user
	 *
	 * @since 2.99
	 * @param mixed $user  int user enumerator | string username | populated User object (e.g. if not yet saved)
	 * @param string $candidate intended-username
	 * @param bool $force optional flag
	 * @return bool indicating validity (incl. true if no change and not $force)
	 */
	public function UsernameCheck($user, string $candidate, bool $force = false) : bool
	{
/* TODO username policy
>= 8 chars ? OR 6 ?
NOT
< > [ ] | { }
/ @ :

multiple ! or ?
.*[!?]{3,}.*

URI like
.*[?&]+[^=]+=[^&]+.*

domain names
.*[\. ](?:com|org|uk|net|info|gov|kz|ru|ir|biz|info|кз|pt|br)\b.*

[in]valid IPv4
^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$

 valid IPv6 sans zero-only words
^(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$

not various control characters, unusual whitespace

No UTF-8 private use characters: U+0080–U+009F, U+00A0, U+2000–U+200F, U+2028–U+202F, U+3000, or U+E000–U+F8FF

no gibberish?

no spoofing
*/
		return true;
	}

	/**
	 * Get recorded data about the specified user, without password check.
	 * Does not use the cache, so use sparingly.
	 * At least one of $username or $uid must be provided.
	 *
	 * @since 2.99
	 * @param string $username Optional login/account name, used if provided
	 * @param int	 $uid Optional user id, used if $username not provided
	 *
	 * @return mixed User-class object | null if the user is not recognized.
	 */
	public function GetRecoveryData(string $username = '', int $uid = -1)
	{
		$query = 'SELECT user_id FROM '.CMS_DB_PREFIX.'users WHERE ';
		if ($username) {
			$query .= 'username=?';
			$args = [$username];
		} elseif ($uid > 0) {
			$query .= 'user_id=?';
			$args = [$uid];
		} else {
			return null;
		}

		$db = AppSingle::Db();
		$uid = $db->GetOne($query, $args);
		if ($uid) {
			return $this->LoadUserByID($uid);
		}
	}
} //class

//backward-compatibility shiv
\class_alias(UserOperations::class, 'UserOperations', false);
