<?php
/*
Singleton class of user-related methods
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CMSMS\Lone;
use CMSMS\User;
use CMSMS\UserParams;
use CMSMS\Utils;
use Collator;
use RuntimeException;
use Throwable;
use const CMS_ASSETS_PATH;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMSSAN_NONPRINT;
use function _la;
use function cms_join_path;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;
use function get_userid;

/**
 * Singleton class for doing user-related functions.
 * Many User-class methods are just wrappers around these.
 * Multi-byte sorting here uses a Collator object hence
 * PHP's intl extension is needed
 *
 * @final
 * @package CMS
 * @license GPL
 *
 * @since 0.6.1
 */
final class UserOperations
{
	/**
	 * Users' group-memberships
	 * @var array each member like uid => [gid1,gid2, ...]
	 * @ignore
	 */
	private $_user_groups;

	/* *
	 * @var array User objects for some|all users loaded from the db,
	 *  anonymously keyed
	 * @see UserOperations::LoadUsers()
	 * A count-limit or offset may have been applied. See $_offset, $_limit
	 * @ignore
	 */
//	private $_users;

	/* *
	 * @var int The recordset offset used to populate $_users
	 * @since 3.0
	 * @ignore
	 */
//	private $_offset = 0;

	/* *
	 * @var int The recordset maximum count used to populate $_users
	 * @since 3.0
	 * @ignore
	 */
//	private $_limit = PHP_INT_MAX;

	/**
	 * @var array individually-sought users, each like id => User, or
	 *  id => null if such sought user does not exist
	 * @ignore
	 */
	private $_filled_users = [];

	// @ignore
	// @private to prevent direct creation (even by Lone class)
//	private function __construct() {} TODO public iff wanted by Lone ?

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	private function __clone() {}// : void {}

	/**
	 * Get the singleton instance of this class
	 * @deprecated since 3.0 use CMSMS\Lone::get('UserOperations')
	 *
	 * @return UserOperations object
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'UserOperations\')'));
		return Lone::get('UserOperations');
	}

	/**
	 * Get some|all users
	 * @since 0.6.1
	 *
	 * @param int $limit  Optional recordset maximum number. Default 10000.
	 * @param int $offset Optional recordset offset. Default 0.
	 * @return array of User objects, or maybe empty
	 */
	public function LoadUsers(int $limit = 10000, int $offset = 0) : array
	{
//		if (!is_array($this->_users) || $limit != $this->_limit || $offset != $this->_offset) {
		$db = Lone::get('Db');
		$result = [];
		$query = 'SELECT user_id,account,username,password,first_name,last_name,email,active FROM '.
			CMS_DB_PREFIX.'users';
		$rst = $db->selectLimit($query, $limit, $offset);
		if ($rst) {
			//TODO single retrieval per $rst->getArray() or batched e.g. 100-each
			$salt = $this->DefaultKey();
			while (!$rst->EOF()) {
				$row = $rst->fields;
				$userobj = new User();
				$userobj->id = $row['user_id'];
				$userobj->account = $row['account'];
				$userobj->username = $this->Restore($row['username'], $salt);
				$userobj->firstname = $row['first_name'];
				$userobj->lastname = $this->Restore($row['last_name'], $salt);
				$userobj->email = $this->Restore($row['email'], $salt);
				$userobj->password = $row['password'];
				$userobj->active = (bool)$row['active'];
				$result[] = $userobj;
				$rst->MoveNext();
			}
			$rst->Close();
		}
		$coll = new Collator('root'); // TODO relevant locale for usernames
		$coll->setStrength(Collator::SECONDARY); // caseless
		usort($result, function($a, $b) use ($coll) {
			return $coll->compare($a->username, $b->username);
		});
		return $result;
//			$this->_users = $result;
//			$this->_limit = $limit;
//			$this->_offset = $offset;
//		}
//		return $this->_users;
	}

	/**
	 * Get all users in the specified group
	 *
	 * @param int $gid Group enumerator for the wanted users
	 * @return array of User objects, or maybe empty
	 */
	public function LoadUsersInGroup(int $gid) : array
	{
		$db = Lone::get('Db');
		$pref = CMS_DB_PREFIX;
		$result = [];
		$query = <<<EOS
SELECT U.user_id,U.account,U.username,U.password,U.first_name,U.last_name,U.email,U.active
FROM {$pref}users U
JOIN {$pref}user_groups UG ON U.user_id=UG.user_id
JOIN {$pref}groups G ON UG.group_id=G.group_id
WHERE G.group_id=?
EOS;
		$rst = $db->execute($query, [$gid]);
		if ($rst) {
			//TODO single retrieval per $rst->getArray() or batched e.g. 100-each
			$salt = $this->DefaultKey();
			while ($row = $rst->FetchRow()) {
				$userobj = new User();
				$userobj->id = $row['user_id'];
				$userobj->account = $row['account'];
				$userobj->username = $this->Restore($row['username'], $salt);
				$userobj->firstname = $row['first_name'];
				$userobj->lastname = $this->Restore($row['last_name'], $salt);
				$userobj->email = $this->Restore($row['email'], $salt);
				$userobj->password = $row['password'];
				$userobj->active = (bool)$row['active'];
				$result[] = $userobj;
			}
			$rst->Close();
			$coll = new Collator('root'); // TODO relevant locale for usernames
			$coll->setStrength(Collator::SECONDARY); // caseless
			usort($result, function($a, $b) use ($coll) {
				return $coll->compare($a->username, $b->username);
			});
		}
		return $result;
	}

	/**
	 * Load a user by login/accountname.
	 * Does not use the intra-request cache, so use sparingly.
	 * @since 0.6.1
	 *
	 * @param string $username Username to load
	 * @param string $password Optional (but not really) password to check against
	 * @param bool $activeonly Optional flag whether to load the user only if [s]he is active Default true
	 * @param bool $adminaccessonly Deprecated since 3.0 UNUSED Optional flag whether to load the user only if [s]he may log in Default false
	 * @return mixed User object | null | false
	 */
	public function LoadUserByUsername(string $username, string $password = '', bool $activeonly = true, bool $adminaccessonly = false)// : mixed
	{
		$db = Lone::get('Db');
		$query = 'SELECT user_id,password FROM '.CMS_DB_PREFIX.'users WHERE ';
		$wheres = ['account = ?'];
		$acct = $this->PrepareUserhash($username);
		$params = [$acct];

		if ($activeonly || $adminaccessonly) {
			$wheres[] = 'active = 1';
		}
		$query .= implode(' AND ', $wheres);

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
				//PASSWORD_ARGON2I in PHP 7.2, PASSWORD_ARGON2ID in 7.3+
				if (((defined('PASSWORD_ARGON2I') || defined('PASSWORD_ARGON2ID')) &&
					  strncmp($hash, '$2y$', 4) == 0) //still uses bcrypt
				 || password_needs_rehash($hash, PASSWORD_DEFAULT)) {
					$this->trigger($db, $row['user_id'], $password);
				}
			} else { // rubbish old md5 methodology
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
	 * @return mixed populated User object | null
	 */
	public function LoadUserByID(int $uid) : ?User
	{
		if ($uid < 1) {
			return null;
		}
		if (isset($this->_filled_users[$uid])) {
			return $this->_filled_users[$uid];
		}

		$db = Lone::get('Db');
		$query = 'SELECT account,username,password,pwreset,first_name,last_name,email,active FROM '.
			CMS_DB_PREFIX.'users WHERE user_id=?';
		$row = $db->getRow($query, [$uid]);
		if ($row) {
			$salt = $this->DefaultKey();
			$userobj = new User();
			$userobj->id = $uid;
			$userobj->account = $row['account'];
			$userobj->username = $this->Restore($row['username'], $salt);
			$userobj->password = $row['password'];
			$userobj->firstname = $row['first_name'];
			$userobj->lastname = $this->Restore($row['last_name'], $salt);
			$userobj->email = $this->Restore($row['email'], $salt);
			$userobj->active = (bool)$row['active'];
			$userobj->pwreset = (bool)$row['pwreset'];
		} else {
			$userobj = null;
		}

		$this->_filled_users[$uid] = $userobj;
		return $userobj;
	}

	/**
	 * Load a user having the specified token provided that it's not expired
	 * @since 3.0
	 *
	 * @param string $token
	 * @param int $limit Optional timestamp latest time when the token is valid. Default time()
	 * @return mixed populated User object | null
	 */
	public function LoadUserByToken(string $token, int $limit = 0) : ?User
	{
		if ($limit == 0) { $limit = time(); }
		$db = Lone::get('Db');
		$pref = CMS_DB_PREFIX;
		$query = <<<EOS
SELECT user_id FROM {$pref}userprefs
WHERE preference='tokenstamp' AND `value` IS NOT NULL AND `value`<=? AND user_id IN (
SELECT user_id FROM {$pref}userprefs
WHERE preference='token' AND `value`=?
)
EOS;
		$uid = $db->getOne($query, [$limit, $token]);
		if ($uid) {
			$this->RecordToken($uid, null, null); //cleanup
			return $this->LoadUserByID($uid);
		}
		return null;
	}

	/**
	 * Add or remove a user-identification token
	 * @since 3.0
	 *
	 * @param int $uid User enumerator
	 * @param mixed $token string | null A falsy value initiates removal
	 * @param mixed $limit Optional timestamp latest time when the token is valid.
	 *  Default time() + 24 hrs Or null
	 */
	public function RecordToken(int $uid, $token, $limit = 0) : void
	{
		if ($token) {
			if ($limit == 0) { $limit = time() + 86400; }
			UserParams::set_for_user($uid, 'token', $token);
			UserParams::set_for_user($uid, 'tokenstamp', $limit);
		} else {
			UserParams::remove_for_user($uid, 'token');
			UserParams::remove_for_user($uid, 'tokenstamp');
		}
	}

	/**
	 * Get identifiers of the specified user, if any
	 * @since 3.0
	 *
	 * @param mixed $a int enumerator| string login
	 * @return array 2 members like [id, login], or
	 *  empty if the user is not recognized or not active
	 */
	public function GetUserByIdentifier($a) : array
	{
		if (is_numeric($a)) {
			$a = (int)$a;
			if (isset($this->_filled_users[$a])) {
				$userobj = $this->_filled_users[$a];
			} else {
				$userobj = $this->LoadUserByID($a);
			}
			if ($userobj && $userobj->active) {
				return [$a, $userobj->username];
			}
		} else {
			$acct = $this->PrepareUserhash($a);
			$db = Lone::get('Db');
			$query = 'SELECT user_id,account,username,password,first_name,last_name,email,active FROM '.
				CMS_DB_PREFIX.'users WHERE account=?';
			$row = $db->getRow($query, [$acct]);
			if ($row) {
				$uid = (int)$row['user_id'];
				if (isset($this->_filled_users[$uid])) {
					$userobj = $this->_filled_users[$uid];
				} else {
					$salt = $this->DefaultKey();
					$userobj = new User();
					$userobj->id = $uid;
					$userobj->account = $row['account'];
					$userobj->username = $this->Restore($row['username'], $salt);
					$userobj->password = $row['password'];
					$userobj->firstname = $row['first_name'];
					$userobj->lastname = $this->Restore($row['last_name'], $salt);
					$userobj->email = $this->Restore($row['email'], $salt);
					$userobj->active = (bool)$row['active'];
					$this->_filled_users[$uid] = $userobj;
				}
				if ($userobj && $userobj->active) {
					return [$uid, $userobj->username];
				}
			}
		}
		return [];
	}

	/**
	 * Get identifiers of all, or all active, users
	 * @since 3.0
	 *
	 * @param bool $active Optional flag, whether to get only active users. Default false.
	 * @param bool $friendly Optional flag, whether to prefer a 'public' name over login. Default false.
	 * @return array ordered by id each member like id => login, or possibly empty
	 */
	public function GetUsers($active = false, $friendly = false) : array
	{
		$db = Lone::get('Db');
		if ($friendly) {
			$query = 'SELECT user_id,username,first_name,last_name FROM '.CMS_DB_PREFIX.'users';
			if ($active) {
				$query .= ' WHERE active>0'; // OR active IS NULL ?
			}
			$query .= ' ORDER BY user_id';
			$dbr = $db->getAssoc($query);
			if ($dbr) {
				$salt = $this->DefaultKey();
				foreach ($dbr as $uid => &$row) {
					$ln = $this->Restore($row['last_name'], $salt);
					$nm = trim($row['first_name'].' '.$ln);
					if ($nm) {
						$row = specialize($nm);
					}
					else {
						$row = $this->Restore($row['username'], $salt);
					}
				}
			}
			unset($row);
			return $dbr;
		}
		$query = 'SELECT user_id,username FROM '.CMS_DB_PREFIX.'users';
		if ($active) {
			$query .= ' WHERE active>0'; // OR active IS NULL ?
		}
		$query .= ' ORDER BY user_id';
		$dbr = $db->getAssoc($query);
		if ($dbr) {
		  if (!isset($salt)) {
			  $salt = $this->DefaultKey();
		  }
  		  foreach ($dbr as $uid => &$nm) {
			  $nm = $this->Restore($nm, $salt);
		  }
		  unset($nm);
		}
		return $dbr;
	}

	/**
	 * Save a new user to the database
	 * @since 0.6.1
	 *
	 * @param mixed $userobj User object to save
	 * @return int The new user id upon success | -1 on failure
	 */
	public function InsertUser(User $userobj) : int
	{
		$pref = CMS_DB_PREFIX;
		//just in case account is not unique-indexed by the db
		$query = <<<EOS
INSERT INTO {$pref}users
(user_id,account,username,password,active,first_name,last_name,email,pwreset,create_date)
SELECT ?,?,?,?,?,?,?,?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pref}users T WHERE T.account=?) LIMIT 1
EOS;
		$db = Lone::get('Db');
		$newid = $db->genID(CMS_DB_PREFIX.'users_seq');
		$tval = trim($userobj->username);
		$acct = $this->PrepareUserhash($tval);
		$salt = $this->DefaultKey();
		//setting create_date should be redundant with DT setting on MySQL 5.6.5+
		$longnow = $db->DbTimeStamp(time(),false);
		$args = [
			$newid,
			$acct,
			$this->Distort($tval, $salt), //TODO $db->addQ() then revert where needed?
			$userobj->password, // addQ() prob. irrelevant for this hash
			(int)$userobj->active,
			$userobj->firstname, // ditto
			$this->Distort($userobj->lastname, $salt),// ditto
			$this->Distort($userobj->email, $salt),// ditto
			(int)$userobj->pwreset,
			$longnow,
			$acct
		];
		$dbr = $db->execute($query, $args);
		return ($dbr) ? $newid : -1;
	}

	/**
	 * Update an existing user in the database
	 * @since 0.6.1
	 *
	 * @param mixed $userobj User object including the data to save
	 * @return bool indicating success
	 */
	public function UpdateUser($userobj) : bool
	{
		$db = Lone::get('Db');
		// check for username conflict
		$tval = trim($userobj->username);
		$acct = $this->PrepareUserhash($tval); // OR $userobj->account?
		$query = 'SELECT 1 FROM '.CMS_DB_PREFIX.'users WHERE account=? AND user_id!=?';
		$dbr = $db->getOne($query, [$acct, $userobj->id]);
		if ($dbr) {
			return false;
		}

		$longnow = $db->DbTimeStamp(time());
		$query = 'UPDATE '.CMS_DB_PREFIX.'users SET account=?, username=?, first_name=?, last_name=?, email=?, active=?, pwreset=?, modified_date='.$longnow.' WHERE user_id=?';
		$salt = $this->DefaultKey();
		$cname = $this->Distort($tval, $salt);
		$clast = $this->Distort($userobj->lastname, $salt);
		$cmail = $this->Distort($userobj->email, $salt);
		$db->execute($query, [$acct, $cname, $userobj->firstname, $clast, $cmail, (int)$userobj->active, (int)$userobj->pwreset, $userobj->id]);
		if (($n = $db->errorNo()) === 0 && $userobj->newpass) {
			$query = 'UPDATE '.CMS_DB_PREFIX.'users SET password=? WHERE user_id=?';
			$db->execute($query, [$userobj->password, $userobj->id]);
			$n = $db->errorNo();
		}
		if ($n === 0) {
			unset($this->_filled_users[$userobj->id]); // force reload next time
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
	public function DeleteUserByID(int $uid) : bool
	{
		if ($uid <= 1) {
			return false;
		}

		if (!$this->CheckPermission(get_userid(false), 'Manage Users')) {
			return false;
		}

		$db = Lone::get('Db');

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
			unset($this->_filled_users[$uid]); // hence (failed) reload if accessed again
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
	public function CountPageOwnershipByID(int $uid) : int
	{
		$db = Lone::get('Db');
		$query = 'SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'content WHERE owner_id = ?';
		$dbr = $db->getOne($query, [$uid]);
		return (int)$dbr;
	}

	/**
	 * Generate assoc. array of some|all users, suitable for use in a dropdown.
	 * @since 2.2
	 *
	 * @param int $limit  Since 3.0 Optional recordset maximum number. Default 1000.
	 * @param int $offset Since 3.0 Optional recordset offset. Default 0.
	 * @return array, each member like userid => user public name etc
	 */
	public function GetList(int $limit = 10000, int $offset = 0) : array
	{
		$result = [];
		$allusers = $this->LoadUsers($limit, $offset); // want id,username,firstname,lastname,
		foreach ($allusers as $userobj) {
			if ($userobj->firstname) {
				if ($userobj->lastname) {
					$show = specialize($userobj->firstname .' '.$userobj->lastname);
					// TODO check for duplicates, append ($userobj->username) for those
				} else {
					$show = specialize($userobj->firstname) .' ('.$userobj->username.')';
				}
			} elseif ($userobj->lastname) {
				$show = specialize($userobj->lastname) .' ('.$userobj->username.')';
			} else {
				$show = $userobj->username;
			}
			$result[$userobj->id] = $show;
		}
		return $result;
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
	public function GenerateDropdown(int $currentuserid = 0, string $name = 'ownerid') : string
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
	 * Report whether $uid is a member of the group identified by $gid
	 *
	 * @param int $uid User ID to test
	 * @param int $gid Group ID to test
	 * @return bool indicating whether the test passes
	 */
	public function UserInGroup(int $uid, int $gid) : bool
	{
		$groups = $this->GetMemberGroups($uid);
		return in_array($gid, $groups);
	}

	/**
	 * Report whether $uid represents the admin/super user or is a
	 * member of the admin/super users group
	 *
	 * @param int $uid
	 * @return bool
	 */
	public function IsSuperuser(int $uid) : bool
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
	public function GetMemberGroups(int $uid) : array
	{
		if (!is_array($this->_user_groups) || !isset($this->_user_groups[$uid])) {
			$db = Lone::get('Db');
			$query = 'SELECT group_id FROM '.CMS_DB_PREFIX.'user_groups WHERE user_id = ?';
			$col = $db->getCol($query, [(int)$uid]);
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
	public function AddMemberGroup(int $uid, int $gid) : void
	{
		if ($uid < 1 || $gid < 1) {
			return;
		}

		$db = Lone::get('Db');
		$longnow = $db->DbTimeStamp(time());
		$query = 'INSERT INTO '.CMS_DB_PREFIX."user_groups
(group_id,user_id,create_date) VALUES (?,?,$longnow)";
//		$dbr =
		$db->execute($query, [$gid, $uid]);
		if (isset($this->_user_groups[$uid])) {
			unset($this->_user_groups[$uid]);
		}
// TODO Lone::get('LoadedData')->delete('menu_modules', $userid); if not installing
	}

	/**
	 * Report whether any users-group which includes the specified user
	 * has the specified permission(s).
	 *
	 * @param int	$uid
	 * @param mixed $permname single string or (since 3.0) an array of them,
	 *  optionally with following bool to indicate type (AND|OR) of check wanted
	 * @return bool
	 */
	public function CheckPermission(int $uid, $permname) : bool
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
		$ops = Lone::get('GroupOperations');
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
	 * @param mixed $userobj User object | null to process current user
	 * return bool indicating expiry
	 */
	public function PasswordExpired(?User $userobj = null) : bool
	{
//		$val = AppParams::get('password_life', 0);
//		if ($val > 0) {
			if ($userobj) {
				$uid = $userobj->id;
			} else {
				$uid = (Lone::get('AuthOperations'))->get_loggedin_uid();
			}
			if ($uid < 1) {
				return true;
			}
			$db = Lone::get('Db');
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
	 * @param mixed $a int user id | string username | populated User object (e.g. if not yet saved)
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
		$config = Lone::get('Config');
		$fp = cms_join_path(CMS_ASSETS_PATH, 'configs', 'siteuuid.dat'); OR 'master.dat'
		$str = @file_get_contents($fp);
		if ($str) {
			$password ^= $str;
//OR		$password = hash_hmac('sha3-256', $password, trim($str)); //slow, <= 72 bytes (BCRYPT limit)
		}
*/
		return password_hash($password, PASSWORD_DEFAULT);
	}

	/**
	 * Generate the hash [to be] recorded for a user's account name
	 * @since 3.0
	 *
	 * @param string $username
	 * @return string, 40 bytes
	 */
	public function PrepareUserhash(string $username) : string
	{
		$val = hash('sha3-224', $username); // 56-bytes
		$blocks = str_split($val, 7); // segment it to avoid 32-bit integer overflow in base10
		$range = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_'; //RFC3986 unreserved characters
		$result = '';
		foreach ($blocks as $part) {
			$base10 = hexdec($part);
			$l = strlen($part);
			$build = str_repeat(' ', $l);
			$i = $l - 1;
			do {
				$n = $base10 % 64;
				$build[$i--] = $range[$n];
			} while (($base10 >>= 6) > 0);
			$result .= ltrim($build);
		}
		return $result;
	}

	/**
	 * Check validity of a posited username for the specified user
	 * @since 3.0
	 *
	 * @param User $userobj object with (at least) the properties to be used in checking
	 * @param string $candidate intended-username
	 * @param bool $update Optional flag indicating a user-update
	 *  (as opposed to addition) is in progress. Default false
	 * @return bool indicating validity (incl. true if no change)
	 */
	public function UsernameCheck(User $userobj, string $candidate, bool $update = false) : bool
	{
//		$mod = Lone::get('ModuleOperations')->GetAdminLoginModule();
//		list($valid, $msg) = $mod->check_username($userobj, $candidate, $update);
//		$aout = HookOperations::do_hook_accumulate('Core::UsernameTest', $userobj, $candidate);
//		if ($this->ReserveUsername($candidate)) { // TODO support no-change during update process
//			return false;
//		}
		$msg = ''; //feedback err msg holder
		Events::SendEvent('Core', 'CheckUserData', [
			'user' => $userobj,
			'username' => $candidate,
			'password' => null,
			'update' => $update,
			'report' => &$msg,
		]);
		if ($msg === '' && $this->UsernameAvailable($userobj, $candidate, $update)) {
			return true;
		}
		return false;
	}

	/**
	 * Check whether a posited username is available for use
	 * @since 3.0
	 *
	 * @param User $userobj
	 * @param string $candidate the username to check
	 * @param bool $update Flag indicating a user-update is in progress
	 * @return bool
	 */
	private function UsernameAvailable(User $userobj, string $candidate, bool $update) : bool
	{
		$db = Lone::get('Db');
		$acct = $this->PrepareUserhash(trim($candidate));
		$id = ($update) ? $userobj->id : 0;
		$query = 'SELECT user_id FROM '.CMS_DB_PREFIX.'users WHERE account=? AND user_id!=?';
		$dbr = $db->getOne($query, [$acct, $id]);
		return $dbr == false;
	}

	/**
	 * Check validity of a posited username and/or password for the specified user
	 * @since 3.0
	 *
	 * @param User $userobj object with (at least) the properties to be used in checking
	 * @param mixed $username intended-username or null to skip check
	 * @param mixed $password intended-password or null to skip check
	 * @param bool $update Optional flag indicating a user-update
	 *  (as opposed to addition) is under way Default false
	 * @return string html error message or empty if no problem detected
	 */
	public function CredentialsCheck(User $userobj, $username, $password, bool $update = false) : string
	{
		$msg = ''; //feedback err msg holder (html)
		Events::SendEvent('Core', 'CheckUserData', [
			'user' => $userobj,
			'username' => $username,
			'password' => $password,
			'update' => $update,
			'report' => &$msg,
		]);
		if ($username && !$this->UsernameAvailable($userobj, $username, $update)) {
			if ($msg) { $msg .= '<br>'; }
			$msg .= _la('errorusernameexists', $username);
		}
		return $msg;
	}

	/* *
	 * Report whether the specified username/account is unused, hence available
	 * If available and a user id is specified, that account's name will be
	 * updated now, to reduce the race-risk.
	 * @since 3.0
	 *
	 * @param string $username
	 * @param int $updateby id of user who wants the name, or 0 to ignore
	 * @return bool indicating $username is not already taken
	 * /
	public function ReserveUsername(string $username, int $updateby = 0) : bool
	{
		$db = Lone::get('Db');
		$tval = trim($username);
		$acct = $this->PrepareUserhash($tval);
		$salt = $this->DefaultKey();
		$cname = $db->addQ($this->Distort($tval, $salt));
		$pref = CMS_DB_PREFIX;
		if ($updateby == 0) {
			$query = "SELECT 1 FROM {$pref}users WHERE account=?";
			$dbr = $db->getOne($query, [$acct]);
			return $dbr == false;
		} elseif ($updateby < 0) {
// TODO support interim reservation for a new user, pending other stuff
		} else {
			$query = <<<EOS
UPDATE {$pref}users SET account=?,username=? WHERE user_id=?
AND NOT EXISTS (SELECT 1 FROM {$pref}users T WHERE T.account=? AND T.user_id!=?)
EOS;
			$db->execute($query, [$acct, $cname, $updateby, $acct, $updateby]);
			return $db->affected_rows() == 1; TODO useless - check errno() == 0
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
	public function GetRecoveryData(string $username = '', int $uid = -1) : ?User
	{
		$db = Lone::get('Db');
		$query = 'SELECT user_id FROM '.CMS_DB_PREFIX.'users WHERE ';
		if ($username) {
			$query .= 'account=?';
			$acct = $this->PrepareUserhash(trim($username));
			$args = [$acct];
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
		return null;
	}

	/**
	 * Record updated password for the specified user
	 * @ignore
	 * @param Connction $db
	 * @param int $uid
	 * @param string $password
	 */
	private function trigger($db, $uid, $password) : void
	{
		$hash = $this->PreparePassword($password);
		$query = 'UPDATE '.CMS_DB_PREFIX.'users SET password = ? WHERE user_id = ?';
		$db->execute($query, [$hash, $uid]);
	}

	/**
	 * Send lost-password recovery email to a specified admin user
	 * @since 3.0 a 2-hr timeout applies
	 *
	 * @param object $userobj user data
	 * @return bool result from the attempt to send the message
	 */
	public function SendRecoveryEmail(User $userobj) : bool
	{
		$to = trim($userobj->firstname.' '.$userobj->lastname.' <'.$userobj->email.'>');
		if ($to[0] == '<') {
			$to = $userobj->email;
		}
		$name = AppParams::get('sitename', 'CMSMS Site');
		$subject = _la('lostpwemailsubject', $name);
		//TODO hash something certainly-unique without user ->id e.g. db->genID()
		$token = base_convert(bin2hex(random_bytes(14).$userobj->id), 16, 36); // 23+ chars URL-safe random alphanum
		$url = Lone::get('Config')['admin_url'].'/login.php?repass='.$token;
		$message = _la('lostpwemail', $name, $userobj->username, $url);
		if (Utils::send_email($to, $subject, $message)) {
			$this->RecordToken($userobj->id, $token, time() + 7200);
			return true;
		}
		return false;
	}

	/**
	 * Send replace-password email to a specified admin user
	 * @since 3.0 a 48-hr timeout applies
	 *
	 * @param object $userobj user data
	 * @param object $mod current module-object
	 * @return bool result from the attempt to send the message
	 */
	public function SendReplacementEmail(User $userobj) : bool
	{
		$to = trim($userobj->firstname.' '.$userobj->lastname.' <'.$userobj->email.'>');
		if ($to[0] == '<') {
			$to = $userobj->email;
		}
		$name = AppParams::get('sitename', 'CMSMS Site');
		$subject = _la('replacepwemailsubject', $name);
		//TODO hash something certainly-unique without user ->id e.g. db->genID()
		$token = base_convert(bin2hex(random_bytes(14).$userobj->id), 16, 36); // 23+ chars URL-safe random alphanum
		$url = Lone::get('Config')['admin_url'].'/login.php?onepass='.$token;
		$message = _la('replacepwemail', $name, $userobj->username, $url);
		if (Utils::send_email($to, $subject, $message)) {
			$this->RecordToken($userobj->id, $token, time() + 172800);
			return true;
		}
		return false;
	}

	/**
	 * Get the default password for crypting user properties
	 * @since 3.0
	 * @ignore
	 *
	 * @return string 40 hexa bytes or empty
	 */
	public function DefaultKey() : string
	{
		$result = '';
		$fp = cms_join_path(CMS_ASSETS_PATH, 'configs', 'master.dat');
		if (is_file($fp)) {
			$result = trim(file_get_contents($fp));
		}
		return $result;
	}

	/**
	 * Encrypt the provided user-property
	 * @since 3.0
	 *
	 * @param string $value property value to be processed. May be empty
	 * @param string $salt default-password extender, unused if $pw is supplied
	 * @param string $pw optional specific password
	 * @return string maybe empty
	 */
	public function Distort(string $value, string $salt, string $pw = '') : string
	{
		if ($value || is_numeric($value)) {
			if (!$pw) {
				$mpw = $this->DefaultKey();
				if ($mpw) {
					$pw = hash_pbkdf2('tiger128,3', $mpw, $salt, 500);
				} else {
					throw new RuntimeException('Missing master password');
				}
			}
			return Crypto::encrypt_string($value, $pw);
		}
		return '';
	}

	/**
	 * Decrypt the provided value
	 * @since 3.0
	 *
	 * @param string $value property value to be processed. May be empty.
	 * @param string $salt default-password extender, unused if $pw is supplied
	 * @param string $pw optional
	 * @return string maybe empty
	 */
	public function Restore(string $value, string $salt, string $pw = '') : string
	{
		if ($value) {
			if (!$pw) {
				$mpw = $this->DefaultKey();
				if ($mpw) {
					$pw = hash_pbkdf2('tiger128,3', $mpw, $salt, 500);
				} else {
					throw new RuntimeException('Missing master password');
				}
			}
			return Crypto::decrypt_string($value, $pw);
		}
		return $value;
	}

	/**
	 * Convenience method to decrypt the provided value(s) using the
	 * default methodology for user properties
	 * @since 3.0
	 *
	 * @param mixed $value string | string[]
	 * @return mixed string | string[] some or may be empty
	 */
	public function PlainValue(/*mixed */$value)// : mixed
	{
		if (is_array($value)) {
			$salt = $this->DefaultKey();
			foreach ($value as &$one) {
				$one = $this->Restore($one, $salt);
			}
			unset($one);
			return $value;
		}
		return $this->Restore($value, $this->DefaultKey());
	}
} //class
//backward-compatibility shiv
\class_alias(UserOperations::class, 'UserOperations', false);
