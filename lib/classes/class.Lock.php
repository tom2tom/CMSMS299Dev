<?php
/*
Class for lock functionality plus related exceptions
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

use ArrayAccess;
use CMSMS\AppParams;
use CMSMS\DataException;
use CMSMS\DeprecationNotice;
use CMSMS\LockOperations;
use CMSMS\NoLockException;
use const CMS_DEPREC;
use function cms_to_stamp;
use function get_userid;
use function lang;

/**
 * A simple class representing a lock on a logical object in CMSMS.
 *
 * @package CMS
 * @since 2.0
 * @param-read int $id
 * @param string $type
 * @param int $oid
 * @param int $userid
 * @param-read int $create_date (db datetime)
 * @param-read int $modified_date (db datetime)
 * @param-read int $created (*NIX timestamp corresponding to create_date)
 * @param-read int $lifetime (minutes)
 * @param-read int $expires (*NIX timestamp)
 */
final class Lock implements ArrayAccess
{
// @ignore
//    const KEYS = ['id','type','oid','uid','create_date','modified_date','lifetime','expires'];

    /**
     * @ignore
     */
    private $_data = [];

    /**
     * @ignore
     */
    private $_dirty = false;

    /**
     * Constructor
     *
     * Varargs for this method are:
     * absent,
     * or an assoc. array of some/all instance properties,
     * or (pre-3.0 API) 2 or 3 individual un-named properties:
     * string Locked-object type
     * int    Locked-object numeric identifier
     * int    Optional interval (in minutes) during which the lock may not
     *  be stolen. If not specified, the system default value will be used.
     * @throws LogicException or DataException
     */
    public function __construct(...$params)
    {
        switch (count($params)) {
            case 2:
                $params = array_combine(['type', 'oid'], $params);
                $this->set_properties($params);
                return;
            case 3:
                $params = array_combine(['type', 'oid', 'lifetime'], $params);
                $this->set_properties($params);
            // no break here
            case 0:
                return;
            case 1:
                if (is_array($params[0])) {
                    $this->set_properties($params[0]);
                    return;
                }
            // no break here
            default:
                throw new LogicException('Invalid lock parameters');
        }
    }

    /**
     * @ignore
     * @param string $key
     * @return mixed
     * @throws LogicException
     */
    public function OffsetGet($key)
    {
        switch ($key) {
        case 'type':
        case 'oid':
        case 'uid':
            return $this->_data[$key];
        case 'created':  // deprecated since 3.0
            return cms_to_stamp($this->_data['create_date']);
        case 'id':
        case 'create_date': // deprecated since 3.0
        case 'modified_date': // deprecated since 3.0
        case 'lifetime': // deprecated since 3.0
        case 'expires':
            if (isset($this->_data[$key])) {
                return $this->_data[$key];
            }
            throw new LogicException(lang('missingparams'));
        }
    }

    /**
     * @ignore
     * @param string $key
     * @param mixed $value
     * @throws LogicException
     */
    public function OffsetSet($key, $value)// : void
    {
        switch ($key) {
        case 'modified_date': // deprecated since 3.0
            $this->_data[$key] = trim($value);
            break;
        case 'lifetime': // deprecated since 3.0
            $this->_data[$key] = max(1, (int)$value);
            break;
        case 'expires':
            $this->_data[$key] = max(0, (int)$value);
            break;
        case 'id':
        case 'uid':
        case 'oid':
            // can't reset these
            if (isset($this->_data['id']) && $this->_data['id'] != 0) {
                throw new LogicException('CMSEX_INVALIDMEMBER', null, $key); // TODO something better re repeitition
            }
            $this->_data[$key] = (int)$value;
            break;
        case 'type':
        case 'create_date': // deprecated since 3.0
            // can't reset these
            if (isset($this->_data['id']) && $this->_data['id'] != 0) {
                throw new LogicException('CMSEX_INVALIDMEMBER', null, $key);
            }
            $this->_data[$key] = trim($value);
            break;
        default:
            throw new LogicException('CMSEX_INVALIDMEMBER', null, $key);
        }
        $this->_dirty = true;
    }

    /**
     * @ignore
     * @param string $key
     * @return mixed
     */
    public function OffsetExists($key)// : bool
    {
        if ($key != 'created') {
            return isset($this->_data[$key]);
        }
        return isset($this->_data['create_date']);
    }

    /**
     * @ignore
     * @param string $key
     */
    public function OffsetUnset($key)// : void
    {
        // do nothing
    }

    /**
     * Set some or all properties of this lock
     * @since 3.0
     * @throws LogicException or DataException
     */
    public function set_properties(array $params)
    {
        $val = $params['type'] ?? null;
        if ($val !== null) {
            $val = trim($val);
            if ($val !== '') {
                $params['type'] = $val;
            } else {
                throw new DataException('CMSEX_L003');
            }
        } else {
            unset($params['type']);
        }

        $val = $params['oid'] ?? 0;
        $params['oid'] = max(0, (int)$val);
        $val = isset($params['uid']) ? (int)$params['uid'] : 0;
        $params['uid'] = ($val > 0) ? $val : get_userid(false);

        $val = $params['lifetime'] ?? 0;
        if ($val <= 0) {
            $val = AppParams::get('lock_timeout', 60);
        }
        $t = max(1, (int)$val);
        $params['lifetime'] = $t; // deprecated since 3.0

        $val = $params['expires'] ?? 0;
        if ($val == 0) { // null ok
            $val = $t * 60 + time();
        }
        $params['expires'] = $val;

        // id must be set last
        $val = $params['id'] ?? 0;
        unset($params['id']);

        foreach ($params as $key => $value) {
            $this->OffsetSet($key, $value);
        }

        $this->OffsetSet('id', max(0, (int)$val));
    }

    /**
     * Report whether this lock object has expired
     *
     * @return bool
     */
    public function expired()
    {
        if (empty($this->_data['expires'])) {
            return false;
        }
        return $this->_data['expires'] <= time();
    }

    /**
     * Save this lock object, if needed
     *
     * @throws SQLException
     */
    public function save()
    {
        if ($this->_dirty) {
            $id = LockOperations::save($this->_data);
            if (empty($this->_data['id'])) {
                $this->_data['id'] = $id;
            }
            $this->_dirty = false;
        }
    }

    /**
     * Remove this lock from the database
     *
     * @throws LogicException or LockOwnerException
     */
    public function delete()
    {
        if (LockOperations::delete_real($this->_data)) {
            $this->_data['id'] = 0;
            $this->_dirty = true;
        }
    }

    //~~~~~~~~~~ METHODS EXPORTED TO THE OPERATIONS CLASS ~~~~~~~~~

    /**
     * Create a lock object from a database row
     * @deprecated since 3.0 Instead use LockOperations::from_row()
     * @internal
     *
     * @param array $row An array representing a database lock
     * @return Lock
     */
    public static function from_row($row)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'new Lock($row)'));
        return new self($row);
    }

    /**
     * Load a lock object matching the supplied parameters
     * @deprecated since 3.0 Instead use LockOperations::load_by_id()
     *
     * @param int $lock_id
     * @param string $type  The lock type (type of object being locked)
     * @param int $oid  The numeric id of the locked object
     * @param int $userid  Optional lock-holder identifier
     * @return Lock
     * @throws NoLockException
     */
    public static function load_by_id($lock_id, $type, $oid, $userid = null)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'LockOperations::load_by_id'));
        return LockOperations::load_by_id($lock_id, $type, $oid, $userid);
    }

    /**
     * Load a lock object matching the supplied parameters
     * @deprecated since 3.0 Instead use LockOperations::load()
     *
     * @param string $type  The lock type (type of object being locked)
     * @param int $oid  The numeric id of the locked object
     * @param int $userid  Optional lock-holder identifier
     * @return Lock
     * @throws NoLockException
     */
    public static function load($type, $oid, $userid = null)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'LockOperations::load'));
        return LockOperations::load($type, $oid, $userid);
    }
} // class
