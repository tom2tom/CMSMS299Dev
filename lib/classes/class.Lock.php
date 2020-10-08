<?php
# Class for lock functionality plus related exceptions
# Copyright (C) 2014-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS {

use ArrayAccess;
use CmsException;
use CmsInvalidDataException;
use CmsNoLockException;
use CmsLogicException;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Lock;
use const CMS_DB_PREFIX;
use function cms_notice;
use function cms_to_stamp;
use function cms_warning;
use function get_userid;

/**
 * A simple class representing a lock on a logical object in CMSMS.
 *
 * @package CMS
 * @since 2.0
 * @param-read int $id
 * @param string $type
 * @param int $oid
 * @param int $uid
 * @param-read int $create_date  (db datetime)
 * @param-read int $modified_date (db datetime)
 * @param-read int created (unix timestamp corresponding to create_date)
 * @param-read int $lifetime (minutes)
 * @param-read int $expires  (unix timestamp)
 */
final class Lock implements ArrayAccess
{
    /**
     * @ignore
     */
    const LOCK_TABLE = 'locks';

    /* *
     * @ignore
     */
//    const KEYS = ['id','type','oid','uid','create_date','modified_date','lifetime','expires'];

    /**
     * @ignore
     */
    private $_data = [];

    /**
     * @ignore
     */
    private $_dirty = FALSE;

    /**
     * Constructor
     *
     * @param string $type Locked-object type
     * @param int    $oid Locked-object numeric identifier
     * @param int    $lifetime Optional interval (in minutes) during which the
     *  lock may not be stolen. If not specified, the system default value will be used.
     */
    public function __construct($type,$oid,$lifetime = null)
    {
        $type = trim($type);
        if( $type == '' ) throw new CmsInvalidDataException('CMSEX_L003');
        $oid = trim($oid);

        $this->_data['type'] = $type;
        $this->_data['oid'] = $oid;
        $this->_data['uid'] = get_userid(FALSE);
        if( $lifetime == null ) $lifetime = AppParams::get('lock_timeout',60);
        $t = max(1,(int)$lifetime);
        $this->_data['lifetime'] = $t; // deprecated since 2.3
        $this->_data['expires'] = $t * 60 + time();
        $this->_dirty = TRUE;
    }

    /**
     * @ignore
     */
    public function OffsetGet($key)
    {
        switch( $key ) {
        case 'type':
        case 'oid':
        case 'uid':
            return $this->_data[$key];
        case 'created':  // deprecated since 2.3
            return cms_to_stamp($this->_data['create_date']);
        case 'id':
        case 'create_date': // deprecated since 2.3
        case 'modified_date': // deprecated since 2.3
        case 'lifetime': // deprecated since 2.3
        case 'expires':
            if( isset($this->_data[$key]) ) return $this->_data[$key];
            throw new CmsLogicException('CMSEX_L004');
        }
    }

    /**
     * @ignore
     */
    public function OffsetSet($key,$value)
    {
        switch( $key ) {
        case 'modified_date': // deprecated since 2.3
            $this->_data[$key] = trim($value);
            $this->_dirty = TRUE;
            break;
        case 'lifetime': // deprecated since 2.3
            $this->_data[$key] = max(1,(int)$value);
            $this->_dirty = TRUE;
            break;
        case 'expires':
            $this->_data[$key] = max(0,(int)$value);
            $this->_dirty = TRUE;
            break;
        case 'uid':
        case 'id':
            // can't reset this one
            if( isset($this->_data['id']) ) throw new CmsInvalidDataException('CMSEX_G001');
            $this->_data[$key] = (int)$value;
            $this->_dirty = TRUE;
            break;
        case 'type':
        case 'oid':
        case 'create_date': // deprecated since 2.3
            // or this one
            if( isset($this->_data['id']) ) throw new CmsInvalidDataException('CMSEX_G001');
            $this->_data[$key] = trim($value);
            $this->_dirty = TRUE;
            break;
        }
    }

    /**
     * @ignore
     */
    public function OffsetExists($key)
    {
        if( $key != 'created' ) {
            return isset($this->_data[$key]);
        }
        return isset($this->_data['create_date']);
    }

    /**
     * @ignore
     */
    public function OffsetUnset($key)
    {
        // do nothing.
    }

    /**
     * Test if the current lock object has expired
     *
     * @return bool
     */
    public function expired()
    {
        if( !isset($this->_data['expires']) ) return FALSE;
        return $this->_data['expires'] < time();
    }

    /**
     * Save the current lock object
     *
     * @throws CmsSqlErrorException
     */
    public function save()
    {
        if( !$this->_dirty ) return;

        $db = AppSingle::Db();
        $dbr = null;
        $this->_data['expires'] = time() + $this->_data['lifetime'] * 60;
        if( !isset($this->_data['id']) ) {
            // insert
            //TODO DT fields for created, modified
            //,created,modified
            $query = 'INSERT INTO '.CMS_DB_PREFIX.self::LOCK_TABLE.'
(type,oid,uid,lifetime,expires)
VALUES (?,?,?,?,?)'; //,?,?
            $dbr = $db->Execute($query,[$this->_data['type'], $this->_data['oid'], $this->_data['uid'],
                                        $this->_data['lifetime'], $this->_data['expires']]); //time(), time(),
            $this->_data['id'] = $db->Insert_ID();
        }
        else {
            // update
            //TODO DT field for modified
            //, modified = ?
            $query = 'UPDATE '.CMS_DB_PREFIX.self::LOCK_TABLE.'
SET lifetime = ?, expires = ?
WHERE type = ? AND oid = ? AND uid = ? AND id = ?';
            $dbr = $db->Execute($query,[$this->_data['lifetime'],$this->_data['expires'],//time(),
                                        $this->_data['type'],$this->_data['oid'],$this->_data['uid'],$this->_data['id']]);
        }
        if( !$dbr ) throw new CmsSqlErrorException('CMSEX_SQL001',null,$db->ErrorMsg());
        $this->_dirty = FALSE;
    }

    /**
     * Create a lock object from a database row
     *
     * @internal
     * @param array $row An array representing a database lock
     * @return Lock
     */
    public static function from_row($row)
    {
        $obj = new Lock($row['type'],$row['oid'],$row['lifetime']);
        foreach( $row as $key => $val ) {
            $obj->_data[$key] = $val;
        }
        $obj->_dirty = TRUE;
        return $obj;
    }


    /**
     * Delete the current lock from the database.
     *
     * @throws CmsLogicException
     * @throws CmsLockOwnerException
     */
    public function delete()
    {
        if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) throw new CmsLogicException('CMSEX_L002');

        $uid = get_userid(FALSE);
        if( !$this->expired() && $uid != $this->_data['uid'] ) {
            cms_warning('Attempt to delete a non expired lock owned by user '.$uid);
            throw new CmsLockOwnerException('CMSEX_L001');
        }

        if( $uid != $this->_data['uid'] ) {
            cms_notice(sprintf('Lock %s (%s/%d) owned by uid %s deleted by non owner',
                                         $this->_data['id'],$this->_data['type'],$this->_data['oid'],$this->_data['uid']));
        }

        $db = AppSingle::Db();
        $query = 'DELETE FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE id = ?';
        $db->Execute($query,[$this->_data['id']]);
        unset($this->_data['id']);
        $this->_dirty = TRUE;
    }

    /**
     * Load a lock object matching the supplied parameters.
     *
     * @param int $lock_id
     * @param string $type  The lock type (type of object being locked)
     * @param int $oid  The numeric id of the locked object
     * @param int $uid  Optional lock-holder identifier
     * @return Lock
     * @throws CmsNoLockException
     */
    public static function load_by_id($lock_id,$type,$oid,$uid = null)
    {
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE id = ? AND type = ? AND oid = ?';
        $db = AppSingle::Db();
        $parms = [$lock_id,$type,$oid];
        if( $uid > 0 ) {
            $query .= ' AND uid = ?';
            $parms[] = $uid;
        }
        $row = $db->GetRow($query,$parms);
        if( $row ) return self::from_row($row);
        throw new CmsNoLockException('CMSEX_L005','',[$lock_id,$type,$oid,$uid]);
    }

    /**
     * Load a lock object matching the supplied parameters.
     *
     * @param string $type  The lock type (type of object being locked)
     * @param int $oid  The numeric id of the locked object
     * @param int $uid  Optional lock-holder identifier
     * @return Lock
     * @throws CmsNoLockException
     */
    public static function load($type,$oid,$uid = null)
    {
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.self::LOCK_TABLE.' WHERE type = ? AND oid = ?';
        $db = AppSingle::Db();
        $parms = [$type,$oid];
        if( $uid > 0 ) {
            $query .= ' AND uid = ?';
            $parms[] = $uid;
        }
        $row = $db->GetRow($query,$parms);
        if( $row ) return self::from_row($row);
        throw new CmsNoLockException('CMSEX_L005','',[$type,$uid,$uid]);
    }
} // class

} //namespace

namespace {

/**
 * An exception indicating an error creating a lock
 *
 * @package CMS
 * @since 2.0
 */
class CmsLockException extends CmsException {}

/**
 * An exception indicating a uid mismatch wrt a lock (person operating on the lock is not the owner)
 *
 * @package CMS
 * @since 2.0
 */
class CmsLockOwnerException extends CmsLockException {}

/**
 * An exception indicating an error removing a lock
 *
 * @package CMS
 * @since 2.0
 */
class CmsUnLockException extends CmsLockException {}

/**
 * An exception indicating an error loading or finding a lock
 *
 * @package CMS
 * @since 2.0
 */
class CmsNoLockException extends CmsLockException {}

} // global namspace
