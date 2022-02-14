<?php
/*
Class of utilities for interacting with locks
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
use CMSMS\Lock;
use CMSMS\NoLockException;
use CMSMS\SingleItem;
use CMSMS\SQLException;
use LogicException;
use const CMS_DB_PREFIX;
use function CMSMS\log_notice;
use function CMSMS\log_warning;
use function get_userid;
use function lang;

/**
 * A class of static utilities for interacting with locks
 *
 * @package CMS
 * @since 2.0
 */
final class LockOperations
{
    /**
     * @ignore
     */
    const LOCK_TABLE = 'locks';

    /**
     * Load a lock object matching the supplied parameters
     * @since 2.99 (formerly a Lock-class method)
     *
     * @param string $type  Locked-object type
     * @param int $oid  Locked-object identifier
     * @param int $userid  Optional lock-holder identifier
     * @return Lock
     * @throws DataException or NoLockException
     */
    public static function load(string $type, int $oid, $userid = null)
    {
        $db = SingleItem::Db();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE type = ? AND oid = ?';
        $parms = [$type,$oid];
        if( $userid > 0 ) {
            $query .= ' AND uid = ?';
            $parms[] = (int)$userid;
        }
        $query .= ' ORDER BY create_date DESC LIMIT 1';
        $row = $db->getRow($query,$parms); // too bad if error created multiple
        if( $row ) {
            return new Lock($row);
        }
        throw new NoLockException('CMSEX_L005','',[$type,$userid,$userid]);
    }

    /**
     * Load a lock object matching the supplied parameters
     * @since 2.99 (formerly a Lock-class method)
     *
     * @param int $lock_id Lock identifier
     * @param string $type Locked-object type
     * @param int $oid Locked-object identifier
     * @param mixed $userid int|null Optional lock-holder identifier
     * @return Lock
     * @throws DataException or NoLockException
     */
    public static function load_by_id(int $lock_id, string $type, int $oid, $userid = null)
    {
        $db = SingleItem::Db();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE id=? AND type=? AND oid=?';
        $parms = [$lock_id,$type,$oid];
        if( $userid > 0 ) {
            $query .= ' AND uid = ?';
            $parms[] = (int)$userid;
        }
        $query .= ' ORDER BY create_date DESC LIMIT 1';
        $row = $db->getRow($query,$parms);
        if( $row ) {
            return new Lock($row);
        }
        throw new NoLockException('CMSEX_L005','',[$lock_id,$type,$oid,$userid]);
    }

    /**
     * Touch any lock of the specified type and held by the current user
     *
     * @param int $lock_id The lock identifier
     * @param string $type Locked-object type
     * @param int $oid Locked-object identifier
     * @return int The expiry timestamp of the lock
     * @throws DataException or NoLockException
     */
    public static function touch(int $lock_id, string $type, int $oid) : int
    {
        $userid = get_userid(false);
        $lock = self::load_by_id($lock_id,$type,$oid,$userid);
        $lock->save(); // self::save($lock) doesn't affect lock's dirty-flag
        return $lock['expires'];
    }

    /**
     * Delete any lock matching the supplied parameters
     *
     * @param int $lock_id  The lock identifier
     * @param string $type  Locked-object type
     * @param int $oid  Locked-object identifier
     * @throws DataException or NoLockException
     */
    public static function unlock(int $lock_id, string $type, int $oid)
    {
        if( $lock_id ) {
            $lock = self::load_by_id($lock_id,$type,$oid);
            $lock->delete();// self::delete_real($lock) doesn't affect lock's dirty-flag
        }
    }

    /**
     * Report whether any lock having the specified type and identifier
     * exists. This checks several times, for up to 1 second, in case
     * there's something asynchronous going on.
     *
     * @param string $type Locked-object type
     * @param int $oid Locked-object identifier
     * @return int lock id > 0 (truthy) if lock exists, or 0 (falsy) if not so
     */
    public static function is_locked(string $type, int $oid) : int
    {
        $db = SingleItem::Db();
        $query = 'SELECT id FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE type = ? AND oid = ?';
        for( $i = 0; $i < 4; $i++ ) {
            $dbr = $db->getOne($query,[$type,$oid]);
            if( $dbr ) {
                return (int)$dbr;
            }
            usleep(250000);
        }
        return 0;
    }

    /**
     * Get all locks, or all of a specific type
     *
     * @param string $type Optional locked-object type. Default '' (hence any type)
     * @param bool   $by_state Since 2.99 Optional flag indicating result
     *  format. If true, return array of data including
     *  [type],object_id,user_id,status = 1(stealable) or -1(not stealable)
     *  If false, return lock objects. Default false.
     * @return array, maybe empty
     */
    public static function get_locks(string $type = '', bool $by_state = false) : array
    {
        $locks = [];
        $db = SingleItem::Db();
        if( $by_state ) {
            $query = 'SELECT type,oid AS object_id,uid AS user_id,expires AS status FROM '.CMS_DB_PREFIX.self::LOCK_TABLE;
            if( $type ) $query .= ' WHERE type = ?';
            $dbr = $db->getArray($query,[$type]);
            if( $dbr ) {
                $now = time();
                foreach( $dbr as $row ) {
                    if( $type ) { unset($row['type']); }
                    $row['status'] = ( $row['status'] < $now ) ? 1 : -1;
                    $locks[] = $row;
                }
            }
        }
        else {
            $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::LOCK_TABLE;
            if( $type ) $query .= ' WHERE type = ?';
            $dbr = $db->getArray($query,[$type]);
            if( $dbr ) {
                foreach( $dbr as $row ) {
                    $obj = new Lock($row);
                    $locks[] = $obj;
                }
            }
        }
        return $locks;
    }

    /**
     * Report whether a lock (if any) having the specified type
     * and identifier may be stolen by the current user
     * @since 2.99
     *
     * @param string $type Locked-object type
     * @param int $oid Locked-object identifier
     * @return bool
     * @throws NoLockException
     */
    public static function is_stealable(string $type, int $oid) : bool
    {
        $db = SingleItem::Db();
        $query = 'SELECT uid,expires FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE type = ? AND oid = ?';
        $row = $db->getRow($query,[$type,$oid]);
        if( $row ) {
            if( $row['expires'] <= time() ) { return true; }
            $userid = get_userid(false);
            return ($row['uid'] == $userid
              || $userid == 1
              || SingleItem::UserOperations()->UserInGroup($userid, 1));
        }
        return false;
//        throw new NoLockException('CMSEX_L005','',[$type,$userid,$userid]); TODO params
    }

    /**
     * Save a lock
     * @since 2.99
     *
     * @param mixed $a Lock object or assoc. array of lock-properties
     * @return int id of processed lock
     * @throws LogicException or SQLException
     */
    public static function save($a) : int
    {
        if( $a instanceof Lock ) {
            $props = [
                'id' => $a['id'],
                'type' => $a['type'],
                'oid' => $a['oid'],
                'uid' => $a['uid'],
                'lifetime' => ($a['lifetime'] ?? null),
                'expires' => ($a['expires'] ?? null),
            ];
        } elseif( is_array($a) ) {
            $props = $a;
        } else {
            throw new LogicException('Unknown lock parameters');
        }
        if( empty($props['expires']) ) {
            if( empty($props['lifetime']) ) {
                $props['lifetime'] = AppParams::get('lock_timeout', 60);
            }
            $props['expires'] = time() + $props['lifetime'] * 60;
        }

        $db = SingleItem::Db();
        if( empty($props['id']) ) {
            // insert
            $query = 'INSERT INTO '.CMS_DB_PREFIX.self::LOCK_TABLE.'
(type,oid,uid,expires)
VALUES (?,?,?,?)';
            $dbr = $db->execute($query,
                [$props['type'], $props['oid'], $props['uid'], $props['expires']]);
            $res = ($dbr!= false);
            $props['id'] = $db->Insert_ID();
        } else {
            // update
            $query = 'UPDATE '.CMS_DB_PREFIX.self::LOCK_TABLE.' SET expires=? WHERE id=?';
            $dbr = $db->execute($query, [$props['expires'], $props['id']]);
            $res = $db->affected_rows() > 0;
        }
        if( $res ) {
            return $props['id'];
        }
        throw new SQLException('CMSEX_SQL001', null, $db->errorMsg());
    }

    /**
     * Expunge lock data from the database
     * @since 2.99
     *
     * @param mixed $a Lock object or assoc. array of lock-properties
     * @return bool indicating success
     * @throws LogicException or SQLException
     */
    public static function delete_real($a) : bool
    {
        if( $a instanceof Lock ) {
            $props = [
                'id' => $a['id'],
                'type' => $a['type'],
                'oid' => $a['oid'],
                'uid' => $a['uid'],
                'lifetime' => ($a['lifetime'] ?? null),
                'expires' => ($a['expires'] ?? null),
            ];
        } elseif( is_array($a) ) {
            $props = $a;
        } else {
            throw new LogicException('Unknown lock parameters');
        }

        if (!isset($props['id']) || $props['id'] < 1) {
            throw new LogicException(lang('CMSEX_L002'));
        }
        $userid = get_userid(false);
        if( $userid != $props['uid'] ) {
            if( $TODO->expired() ) { // TODO
                log_notice(sprintf('Lock %s (%s/%d) owned by uid %s deleted by non owner',
                    $props['id'], $props['type'], $props['oid'], $props['uid']));
            } else {
                log_warning('Attempt to delete a non expired lock owned by user '.$userid);
                throw new LockOwnerException('CMSEX_L001');
            }
        }
        $db = SingleItem::Db();
        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE id = ?';
        $dbr = $db->execute($query, [$props['id']]);
        return $dbr != false;
    }

    /**
     * Delete any lock matching the supplied parameters
     * This is an alias of LockOperations::unlock()
     *
     * @param int $lock_id Lock identifier
     * @param string $type Locked-object type
     * @param int $oid Locked-object identifier
     */
    public static function delete(int $lock_id, string $type, int $oid)
    {
        self::unlock($lock_id,$type,$oid);
    }

    /**
     * Delete all expired locks, or all of them having the specified type
     *
     * @param mixed $limit UNIX UTC timestamp | null Delete locks which expire
     * before this. Default null (hence current time).
     * @param string $type Optional lock type. Default '' (hence any type).
     */
    public static function delete_expired($limit = 0, string $type = '')
    {
        if( !$limit ) { $limit == time(); }
        $db = SingleItem::Db();
        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE expires < ?';
        $parms = [$limit];
        if( $type ) {
            $query .= ' AND type = ?';
            $parms[] = trim($type);
        }
        $db->execute($query,$parms);
    }

    /**
     * Delete locks held by the current user
     *
     * @param string $type An optional type name.
     */
    public static function delete_for_user(string $type = '')
    {
        $db = SingleItem::Db();
        $userid = get_userid(false);
        $parms = [$userid];
        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE uid = ?';
        if( $type ) {
            $query .= ' AND type = ?';
            $parms[] = trim($type);
        }
        $db->execute($query,$parms);
    }

    /**
     * Delete locks held by the specified user
     * @since 2.99
     *
     * @param int $userid  User id
     * @param string $type An optional type name.
     * @param int $oid  An optional object-id, ignored unless $type is provided
     */
    public static function delete_for_nameduser (int $userid, string $type = '', int $oid = 0)
    {
        $db = SingleItem::Db();
        if( !$db ) return; //during shutdown, connection gone ?
        $parms = [$userid];
        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE uid = ?';
        if( $type ) {
            $query .= ' AND type = ?';
            $parms[] = trim($type);
            if( $oid ) {
                $query .= ' AND oid = ?';
                $parms[] = (int)$oid;
            }
        }
        $db->execute($query,$parms);
    }
} // class
