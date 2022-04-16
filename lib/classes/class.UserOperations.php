<?php
/*
Singleton class of user-related functions
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
namespace CMSMS;

use CMSMS\AppParams;
use CMSMS\DeprecationNotice;
use CMSMS\Events;
use CMSMS\SingleItem;
use CMSMS\User;
use CMSMS\Utils;
use Throwable;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMSSAN_NONPRINT;
use function _la;
use function CMSMS\sanitizeVal;
use function get_userid;

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
	// @ignore
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

	// @ignore
	// @private to prevent direct creation (even by SingleItem class)
	//	private function __construct() {} TODO public iff wanted by SingleItem ?

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get the singleton instance of this class
	 * @deprecated since 3.0 use CMSMS\SingleItem::UserOperations()
	 *
	 * @return UserOperations object
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\SingleItem::UserOperations()'));
		return SingleItem::UserOperations();
	}

	/**
	 * Get a list of all users
	 * @since 0.6.1
	 *
	 * @param int $limit  The maximum number of users to return
	 * @param int $offset Optional offset. Default 0
	 * @return array of User objects
	 */
	public function LoadUsers(int $limit = 10000, int $offset = 0) //: array
	{
		if (!is_array($this->_users)) {
			$db = SingleItem::Db();
			$result = [];
			$query = 'SELECT user_id,username,password,first_name,last_name,email,active FROM '.
				CMS_DB_PREFIX.'users ORDER BY username';
			$rst = $db->SelectLimit($query, $limit, $offset);
			if ($rst) {
				while (!$rst->EOF()) {
					$row = $rst->fields;
					$userobj = new User();
					$userobj->id = $row['user_id'];
					$userobj->username = $row['username'];
					$userobj->firstname = $row['first_name'];
					$userobj->lastname = $row['last_name'];
					$userobj->email = $row['email'];
					$userobj->password = $row['password'];
					$userobj->active = $row['active'];
					$result[] = $userobj;
					$rst->MoveNext();
				}
				$rst->Close();
			}
			$this->_users = $result;
		}
		return $this->_users;
	}

	/**
	 * Get all users in a specified group
	 *
	 * @param int $gid Group enumerator for the loaded users
	 * @return array of User objects
	 */
	public function LoadUsersInGroup(int $gid) //: array
	{
		$db = SingleItem::Db();
		$pref = CMS_DB_PREFIX;
		$result = [];
		$query = <<<EOS
SELECT U.user_id, U.username, U.password, U.first_name, U.last_name, U.email, U.active
FROM {$pref}users U
JOIN {$pref}user_groups UG ON U.user_id = UG.user_id
JOIN {$pref}groups G ON UG.group_id = G.group_id
WHERE G.group_id = ?
ORDER BY username
EOS;
		$rst = $db->execute($query, [$gid]);
		if ($rst) {
			while ($row = $rst->FetchRow()) {
				$userobj = new User();
				$userobj->id = $row['user_id'];
				$userobj->username = $row['username'];
				$userobj->firstname = $row['first_name'];
				$userobj->lastname = $row['last_name'];
				$userobj->email = $row['email'];
				$userobj->password = $row['password'];
				$userobj->active = $row['active'];
				$result[] = $userobj;
			}
			$rst->Close();
		}
		return $result;
	}

	/**
	 * Load a user by login/account/name.
	 * Does not use the cache, so use sparingly.
	 * @since 0.6.1
	 *
	 * @param string $username      Username to load
	 * @param string $password      Optional (but not really) Password to check against
	 * @param bool $activeonly      Optional flag whether to load the user only if [s]he is active Default true
	 * @param bool $adminaccessonly Deprecated since 3.0 UNUSED Optional flag whether to load the user only if [s]he may log in Default false
	 * @return mixed User object | null | false
	 */
	public function LoadUserByUsername(string $username, string $password = '', bool $activeonly = true, bool $adminaccessonly = false)
	{
		// note: does not use cache
		$db = SingleItem::Db();

		$query = 'SELECT user_id,password FROM '.CMS_DB_PREFIX.'users WHERE ';
		$where = ['username = ?'];
		$params = [$username];

		if ($activeonly || $adminaccessonly) {
			$where[] = 'active = 1';
		}
		$query .= implode(' AND ', $where);

		$row = $db->getRow($query, $params);
		if ($row) {
			// ignore supplied invalid P/W chars
			$password = sanitizeVal($password, CMSSAN_NONPRINT);
			$hash = $row['password'];
			$len = strlen(bin2hex($hash)) / 2; //ignore mb_ override
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

	/**
	 * Load a user having a specified (numeric) identifier
	 * @since 0.6.1
	 *
	 * @param int $uid User id to load
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
		$db = SingleItem::Db();
		$query = 'SELECT username, password, active, pwreset, first_name, last_name, email FROM '.
			CMS_DB_PREFIX.'users WHERE user_id = ?';
		$row = $db->getRow($query, [$uid]);
		if ($row) {
			$userobj = new User();
			$userobj->id = $uid;
			$userobj->username = $row['username'];
			$userobj->password = $row['password'];
			$userobj->firstname = $row['first_name'];
			$userobj->lastname = $row['last_name'];
			$userobj->email = $row['email'];
			$userobj->active = $row['active'];
			$userobj->pwreset = $row['pwreset'];
			$result = $userobj;
		}

		$this->_saved_users[$uid] = $result;
		return $result;
	}

	/**
	 * Save a new user to the database
	 * @since 0.6.1
	 *
	 * @param mixed $user User object to save
	 * @return int The new user id upon success | -1 on failure
	 */
	public function InsertUser(User $user) //: int
	{
		$pref = CMS_DB_PREFIX;
		//just in case username is not unique-indexed by the db
		$query = <<<EOS
INSERT INTO {$pref}users
(user_id,username,password,active,first_name,last_name,email,pwreset,create_date)
SELECT ?,?,?,?,?,?,?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pref}users T WHERE T.username=?) LIMIT 1
EOS;
		$db = SingleItem::Db();
		$newid = $db->genID(CMS_DB_PREFIX.'users_seq');
		$nm = $db->addQ($user->username);
		//setting create_date should be redundant with DT setting on MySQL 5.6.5+
		$longnow = $db->DbTimeStamp(time(),false);
		$args = [
			$newid,
			$nm,
			$db->addQ($user->password),
			$user->active,
			$db->addQ($user->firstname),
			$db->addQ($user->lastname),
			$db->addQ($user->email),
			(int)$user->pwreset,
			$longnow,
			$nm
		];
		$dbr = $db->execute($query, $args);
		return ($dbr) ? $newid : -1;
	}

	/**
	 * Update an existing user in the database
	 * @since 0.6.1
	 *
	 * @param mixed $user User object including the data to save
	 * @return bool indicating success
	 */
	public function UpdateUser($user) //: bool
	{
		$db = SingleItem::Db();
		// check for username conflict
		$query = 'SELECT 1 FROM '.CMS_DB_PREFIX.'users WHERE username = ? AND user_id != ?';
		$dbr = $db->getOne($query, [$user->username, $user->id]);
		if ($dbr) {
			return false;
		}

		$longnow = $db->DbTimeStamp(time());
		$query = 'UPDATE '.CMS_DB_PREFIX.'users SET username = ?, first_name = ?, last_name = ?, email = ?, active = ?, pwreset = ?, modified_date = '.$longnow.' WHERE user_id = ?';
//		$dbr = useless for update
		$db->execute($query, [$user->username, $user->firstname, $user->lastname, $user->email, $user->active, $user->pwreset, $user->id]);
		if (($n = $db->errorNo()) === 0 && $user->newpass) {
			$query = 'UPDATE '.CMS_DB_PREFIX.'users SET password = ? WHERE user_id = ?';
//			$dbr =
			$db->execute($query, [$user->password, $user->id]);
			$n = $db->errorNo();
		}
		if ($n === 0) {
			unset($this->_saved_users[$user->id]); // ensure fresh report
			return true;
		}
		return false;
	}

	/**
	 * Delete an existing user from the database
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

		$db = SingleItem::Db();

		//TODO at least report failed attempts at related deletions
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'user_groups WHERE user_id = ?';
//		$dbr1 =
		$db->execute($query, [$uid]);

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'additional_users WHERE user_id = ?';
//		$dbr2 =
		$db->execute($query, [$uid]);

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'users WHERE user_id = ?';
		$res = $db->execute($query, [$uid]);

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'userprefs WHERE user_id = ?';
//		$dbr3 =
		$db->execute($query, [$uid]);

		if ($res != false) {
			unset($this->_saved_users[$uid]); // ensure not loadable
			return true;
		}
		return false;
	}

	/**
	 * Show the number of pages the given user owns
	 * @since 0.6.1
	 *
	 * @param int $uid Id of the user to count
	 * @return int Number of owned pages | 0 upon a problem
	 */
	public function CountPageOwnershipByID(int $uid) //: int
	{
		$db = SingleItem::Db();
		$query = 'SELECT COUNT(*) AS count FROM '.CMS_DB_PREFIX.'content WHERE owner_id = ?';
		$dbr = $db->getOne($query, [$uid]);
		return (int)$dbr;
	}

	/**
	 * Generate assoc. array of users, suitable for use in a dropdown
	 * @since 2.2
	 *
	 * @return array, each member like userid => username etc
	 */
	public function GetList() //: array
	{
		$out = [];
		$allusers = $this->LoadUsers();
		foreach ($allusers as $userobj) {
			if ($userobj->firstname) {
				if ($userobj->lastname) {
					$result = $userobj->firstname .' '.$userobj->lastname;
					// TODO check for duplicates, append ($userobj->username) for those
				} else {
					$result = $userobj->firstname .' ('.$userobj->username.')';
				}
			} elseif ($userobj->lastname) {
				$result = $userobj->lastname .' ('.$userobj->username.')';
			} else {
				$result = $userobj->username;
			}
			$out[$userobj->id] = $result;
		}
		return $out;
	}

	/**
	 * Generate an HTML select element containing a user list
	 * @deprecated since ? instead use GetList() and process results
	 * locally and/or in template
	 *
	 * @param int $currentuserid
	 * @param string $name The HTML element name
	 * @return string maybe empty
	 */
	public function GenerateDropdown($currentuserid = null, $name = 'ownerid') //: string
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'GetList'));
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
	 * Test whether $uid is a member of the group identified by $gid
	 *
	 * @param int $uid User ID to test
	 * @param int $gid Group ID to test
	 * @return bool indicating whether the test passes
	 */
	public function UserInGroup(int $uid, int $gid) //: bool
	{
		$groups = $this->GetMemberGroups($uid);
		return in_array($gid, $groups);
	}

	/**
	 * Test whether $uid is a member of the admin group, or is the
	 * first (super) user account
	 *
	 * @param int $uid
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
	 * Get the ids of all groups to which the specified user belongs
	 *
	 * @param int $uid
	 * @return array
	 */
	public function GetMemberGroups(int $uid) //: array
	{
		if (!is_array($this->_user_groups) || !isset($this->_user_groups[$uid])) {
			$db = SingleItem::Db();
			$query = 'SELECT group_id FROM '.CMS_DB_PREFIX.'user_groups WHERE user_id = ?';
			$col = $db->getCol($query, [(int) $uid]);
			if (!is_array($this->_user_groups)) {
				$this->_user_groups = [];
			}
			$this->_user_groups[$uid] = $col;
		}
		return $this->_user_groups[$uid];
	}

	/**
	 * Add the specified user to the specified group
	 *
	 * @param int $uid
	 * @param int $gid
	 */
	public function AddMemberGroup(int $uid, int $gid)
	{
		if ($uid < 1 || $gid < 1) {
			return;
		}

		$db = SingleItem::Db();
		$longnow = $db->DbTimeStamp(time());
		$query = 'INSERT INTO '.CMS_DB_PREFIX."user_groups
(group_id,user_id,create_date) VALUES (?,?,$longnow)";
//		$dbr =
		$db->execute($query, [$gid, $uid]);
		if (isset($this->_user_groups[$uid])) {
			unset($this->_user_groups[$uid]);
		}
// TODO SingleItem::LoadedData()->delete('menu_modules', $userid); if not installing
	}

	/**
	 * Test whether any users-group which includes the specified user
	 * has the specified permission(s).
	 *
	 * @param int	$uid
	 * @param mixed $permname single string or (since 3.0) an array of them,
	 *  optionally with following bool to indicate type (AND|OR) of check wanted
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
		$ops = SingleItem::GroupOperations();
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
	 * Report whether the password of the specified user has expired.
	 * We do not support P/W lifetime/timeout (per NIST recommendation),
	 * but there may have been a system-flag or a onetime-password applied
	 * @since 3.0
	 *
	 * @param mixed $user User object | null to process current user
	 * return bool indicating expiry
	 */
	public function PasswordExpired($user = null) : bool
	{
//		$val = AppParams::get('password_life', 0);
//		if ($val > 0) {
			if ($user) {
				$uid = $user->id;
			} else {
				$uid = (SingleItem::LoginOperations())->get_loggedin_uid();
			}
			if ($uid < 1) {
				return true;
			}
			$db = SingleItem::Db();
//			$stamp = $db->GetOne('SELECT UNIX_TIMESTAMP(passmodified_date) FROM '.CMS_DB_PREFIX.'users WHERE user_id = ?', [$uid]);
//			if ((int)$stamp + $val * 86400 < time()) {
//				return true;
//			}
			$flag = $db->GetOne('SELECT pwreset FROM '.CMS_DB_PREFIX.'users WHERE user_id = ?', [$uid]);
			if ($flag) {
				return true;
			}
//		}
 		return false;
	}

	/**
	 * Check validity of a posited password for the specified user
	 * @since 3.0
	 *
	 * @param mixed $a int userid | string username | populated User object (e.g. if not yet saved)
	 * @param string $candidate plaintext intended-password
	 * @param bool $update Optional flag indicating a user-update
	 *  (as opposed to addition) is in progress. Default false
	 * @return bool indicating validity (incl. true if no p/w-change)
	 */
	public function PasswordCheck($a, string $candidate, bool $update = false) : bool
	{
		if (is_numeric($a)) {
			$userobj = $this->LoadUserByID((int)$a);
		} elseif (is_string($a)) {
			$userobj = $this->LoadUserByUsername($a);
		} elseif ($a instanceof User) {
			$userobj = $a;
		} else {
			return false;
		}

		if ($userobj) {
			$msg = ''; //feedback err msg holder
			Events::SendEvent('Core', 'CheckUserData', [
				'user' => $userobj,
				'username' => null,
				'password' => $candidate,
				'update' => $update,
				'report' => &$msg,
			]);
			return $msg === '';
		}
		return false;
	}

	/**
	 * Generate the content (hash) [to be] recorded for a password
	 * @since 3.0
	 *
	 * @param string $password
	 * @return string
	 */
	public function PreparePassword(string $password) : string
	{
/*		// for 'new' passwords, factor in a not-in-db string, per NIST recommendation
		$config = SingleItem::Config();
		$fp = cms_join_path(CMS_ASSETS_PATH, 'configs', 'siteuuid.dat');
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
	 * @since 3.0
	 *
	 * @param User $user object with (at least) the properties to be used in checking
	 * @param string $candidate intended-username
	 * @param bool $update Optional flag indicating a user-update
	 *  (as opposed to addition) is in progress. Default false
	 * @return bool indicating validity (incl. true if no change)
	 */
	public function UsernameCheck(User $user, string $candidate, bool $update = false) : bool
	{
//		$mod = SingleItem::ModuleOperations()->GetAdminLoginModule();
//		list($valid, $msg) = $mod->check_username($user, $candidate, $update);
//		$aout = HookOperations::do_hook_accumulate('Core::UsernameTest', $user, $candidate);
//		if ($this->ReserveUsername($candidate)) { // TODO support no-change during update process
//			return false;
//		}
		$msg = ''; //feedback err msg holder
		Events::SendEvent('Core', 'CheckUserData', [
			'user' => $user,
			'username' => $candidate,
			'password' => null,
			'update' => $update,
			'report' => &$msg,
		]);
		if ($msg === '' && $this->UsernameAvailable($user, $candidate, $update)) {
			return true;
		}
		return false;
	}

	/**
	 * Check whether a posited username is available for use
	 * @since 3.0
	 *
	 * @param User $user
	 * @param string $candidate
	 * @param bool $update Flag indicating a user-update is in progress
	 * @return bool
	 */
	private function UsernameAvailable(User $user, string $candidate, bool $update) : bool
	{
		$db = SingleItem::Db();
		$save = $db->addQ(trim($candidate));
		$id = ($update) ? $user->id : 0;
		$query = 'SELECT user_id FROM '.CMS_DB_PREFIX.'users WHERE username=? AND user_id!=?';
		$dbr = $db->getOne($query, [$save, $id]);
		return $dbr == false;
	}

	/**
	 * Check validity of a posited username and/or password for the specified user
	 * @since 3.0
	 *
	 * @param User $user object with (at least) the properties to be used in checking
	 * @param mixed $username intended-username or null to skip check
	 * @param mixed $password intended-password or null to skip check
	 * @param bool $update Optional flag indicating a user-update
	 *  (as opposed to addition) is under way Default false
	 * @return string html error message or empty if no problem detected
	 */
	public function CredentialsCheck(User $user, $username, $password, bool $update = false) : string
	{
		$msg = ''; //feedback err msg holder (html)
		Events::SendEvent('Core', 'CheckUserData', [
			'user' => $user,
			'username' => $username,
			'password' => $password,
			'update' => $update,
			'report' => &$msg,
		]);
		if ($username && !$this->UsernameAvailable($user, $username, $update)) {
			if ($msg) { $msg .= '<br />'; }
			$msg .= _la('errorusernameexists', $username);
		}
		return $msg;
	}

	/* *
	 * Report whether the specified username/accountid is unused, hence available
	 * If available and a user id is specified, that account's name will be
	 * updated now, to reduce the race-risk.
	 * @since 3.0
	 *
	 * @param string $username
	 * @param int $updateby id of user who wants the name, or 0 to ignore
	 * @return bool indicating $username is not already taken
	 */
/*	public function ReserveUsername(string $username, int $updateby = 0) : bool
	{
		$db = SingleItem::Db();
		$save = $db->addQ(trim($username));
		$pref = CMS_DB_PREFIX;
		if ($updateby == 0) {
			$query = "SELECT 1 FROM {$pref}users WHERE username=?";
			$dbr = $db->getOne($query, [$save]);
			return $dbr == false;
		} elseif ($updateby < 0) {
// TODO support interim reservation for a new user, pending other stuff
		} else {
			$query = <<<EOS
UPDATE {$pref}users SET username=? WHERE user_id=?
AND NOT EXISTS (SELECT 1 FROM {$pref}users T WHERE T.username=? AND T.user_id!=?)
EOS;
			$db->execute($query, [$save, $updateby, $save, $updateby]);
			return $db->affected_rows() == 1;
		}
	}
*/
	/**
	 * Get recorded data about the specified user, without password check.
	 * Does not use the cache, so use sparingly.
	 * At least one of $username or $uid must be provided.
	 * @since 3.0
	 *
	 * @param string $username Optional login/account name, used if provided
	 * @param int	 $uid Optional user id, used if $username not provided
	 * @return mixed User object | null if the user is not recognized
	 */
	public function GetRecoveryData(string $username = '', int $uid = -1)
	{
		$db = SingleItem::Db();
		$query = 'SELECT user_id FROM '.CMS_DB_PREFIX.'users WHERE ';
		if ($username) {
			$query .= 'username=?';
			$args = [$db->addQ($username)];
		} elseif ($uid > 0) {
			$query .= 'user_id=?';
			$args = [$uid];
		} else {
			return null;
		}

		$uid = $db->getOne($query, $args);
		if ($uid) {
			return $this->LoadUserByID($uid);
		}
	}

	/**
	 * Record updated password for the specified user
	 * @ignore
	 * @param Connction $db
	 * @param int $uid
	 * @param string $password
	 */
	private function trigger($db, $uid, $password)
	{
		$hash = $this->PreparePassword($password);
		$query = 'UPDATE '.CMS_DB_PREFIX.'users SET password = ? WHERE user_id = ?';
		$db->execute($query, [$hash, $uid]);
	}

	/**
	 * Send lost-password recovery email to a specified admin user
	 *
	 * @param object $user user data
	 * @return bool result from the attempt to send the message
	 */
	public function Send_recovery_email(User $user) : bool
	{
		$to = trim($user->firstname . ' ' . $user->lastname . ' <' . $user->email . '>');
		if ($to[0] == '<') {
			$to = $user->email;
		}
		$name = AppParams::get('sitename', 'CMSMS Site');
		$subject = _la('lostpwemailsubject', $name);
		$salt = SingleItem::LoginOperations()->get_salt();
		$url = SingleItem::Config()['admin_url'] . '/login.php?repass=' . hash_hmac('tiger128,3', $user->password, $salt ^ $user->username);
		$message = _la('lostpwemail', $name, $user->username, $url);
		return Utils::send_email($to, $subject, $message);
	}

	/**
	 * Send replace-password email to a specified admin user
	 *
	 * @param object $user user data
	 * @param object $mod current module-object
	 * @return bool result from the attempt to send the message
	 */
	public function Send_replacement_email(User $user) : bool
	{
		$to = trim($user->firstname . ' ' . $user->lastname . ' <' . $user->email . '>');
		if ($to[0] == '<') {
			$to = $user->email;
		}
		$name = AppParams::get('sitename', 'CMSMS Site');
		$subject = _la('replacepwemailsubject', $name);
		$salt = SingleItem::LoginOperations()->get_salt();
		$url = SingleItem::Config()['admin_url'] . '/login.php?onepass=' . hash_hmac('tiger128,3', $user->password, $salt ^ $user->username);
		$message = _la('replacepwemail', $name, $user->username, $url);
		return Utils::send_email($to, $subject, $message);
	}
} //class

//backward-compatibility shiv
\class_alias(UserOperations::class, 'UserOperations', false);
