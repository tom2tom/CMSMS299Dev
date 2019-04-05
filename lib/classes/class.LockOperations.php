<?php
# Class of utilities for interacting with locks
# Copyright (C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use CmsApp;
use CMSMS\CmsNoLockException;
use CMSMS\Lock;
use const CMS_DB_PREFIX;
use function get_userid;

/**
 * A class of static utilities for interacting with locks.
 *
 * @package CMS
 * @since 2.0
 */
final class LockOperations
{
	/**
	 * Touch any lock of the specified type, and id that matches the currently logged in UID
	 *
	 * @param int $lock_id The lock identifier
	 * @param string $type The type of object being locked
	 * @param int $oid The object identifier
	 * @return int The expiry timestamp of the lock.
	 */
	public static function touch($lock_id,$type,$oid)
	{
		$uid = get_userid(FALSE);
		$lock = Lock::load_by_id($lock_id,$type,$oid,$uid);
		$lock->save();
		return $lock['expires'];
	}

	/**
	 * Delete any lock of the specified type, and id that matches the currently logged in UID
	 *
	 * @param int $lock_id The lock identifier
	 * @param string $type The type of object being locked
	 * @param int $oid The object identifier
	 */
	public static function delete($lock_id,$type,$oid)
	{
		self::unlock($lock_id,$type,$oid);
	}

	/**
	 * Delete any lock of the specified type, and id that matches the currently logged in UID
	 *
	 * @param int $lock_id The lock identifier
	 * @param string $type The type of object being locked
	 * @param int $oid The object identifier
	 */
	public static function unlock($lock_id,$type,$oid)
	{
		if( $lock_id ) {
			$uid = get_userid(FALSE);
			$lock = Lock::load_by_id($lock_id,$type,$oid);
			$lock->delete();
		}
	}

	/**
	 * test for any lock of the specified type, and id
	 *
	 * @param string $type The type of object being locked
	 * @param int $oid The object identifier
	 * @return bool
	 */
	public static function is_locked($type,$oid)
	{
		try {
			$lock = Lock::load($type,$oid);
			sleep(1); // wait for potential asynhronous requests to complete.
			$lock = Lock::load($type,$oid);
			return $lock['id'];
		}
		catch( CmsNoLockException $e ) {
			return FALSE;
		}
	}

	/**
	 * Remove some or all expired locks.
	 *
	 * @param mixed $limit unix timestamp | null Delete locks older than this. Default current time.
	 * @param string $type The type of locks to delete.  If not specified any type can be deleted.
	 */
	private static function delete_expired($limit = null,$type = null)
	{
		if( !$limit ) $limit == time();
		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.Lock::LOCK_TABLE.' WHERE expires < ?';
		$parms = [$limit];
		if( $type ) {
			$query .= ' AND type = ?';
			$parms[] = $type;
		}
		$dbr = $db->Execute($query,$parms);
	}

	/**
	 * Get all locks of a specific type
	 *
	 * @param string $type The lock type
	 */
	public static function get_locks($type)
	{
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.Lock::LOCK_TABLE.' WHERE type = ?';
		$tmp = $db->GetArray($query,[$type]);
		if( !$tmp ) return;

		$locks = [];
		foreach( $tmp as $row ) {
			$obj = Lock::from_row($row);
			$locks[] = $obj;
		}
		return $locks;
	}

	/**
	 * Delete all the locks for the current user
	 *
	 * @param string $type An optional type name.
	 */
	public static function delete_for_user($type = null)
	{
		$uid = get_userid(FALSE);
		$db = CmsApp::get_instance()->GetDb();
		$parms = [$uid];
		$query = 'DELETE FROM '.CMS_DB_PREFIX.Lock::LOCK_TABLE.' WHERE uid = ?';
		if( $type ) {
			$query .= ' AND type = ?';
			$parms[] = trim($type);
		}
		$db->Execute($query,$parms);
	}
} // class
