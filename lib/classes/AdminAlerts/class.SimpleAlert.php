<?php
/*
Alert class that uses pre-defined values
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp, Rovert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\AdminAlerts;

use CMSMS\SingleItem;
use InvalidArgumentException;

/**
 * The SimpleAlert class is a type of alert that allows the developer to create alerts with pre-defined titles, messages, icons, and permissions.
 *
 * @since 2.2
 * @package CMS
 * @license GPL
 * @prop string[] $perms An array of permission names.  The logged in user must have at least one of these permissions to see the alert.
 * @prop string $icon The complete URL to an icon to associate with this alert
 * @prop string $msg The message to display.  Note: Since alerts are stored in the database, and can be created asynchronously you cannot rely on language strings for the message or title when using this class.
 */
class SimpleAlert extends Alert
{
    /**
     * @ignore
     */
    private $_perms = [];

    /**
     * @ignore
     */
    private $_icon;

    /**
     * @ignore
     */
    private $_title;

    /**
     * @ignore
     */
    private $_msg;

    /**
     * Constructor
     *
     * @param string[] $perms An array of permission names.  Or null.
     * @throws InvalidArgumentException
     */
    public function __construct($perms = null)
    {
        if( $perms && !is_array($perms) ) InvalidArgumentException('perms must be an array of permission name strings');
        $this->_perms = $perms;
        parent::__construct();
    }

    /**
     * The magic __get method.
     *
     * Get a property from this object, or from the base class.
     *
     * @param string $key
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'perms':
            return $this->_perms;
        case 'icon':
            return $this->_icon;
        case 'title':
            return $this->_title;
        case 'msg':
            return $this->_msg;
        default:
            return parent::__get($key);
        }
    }

    /**
     * The magic __set method.
     *
     * Set a property for this object, or for the base Alert class.
     *
     * @param string $key
     * @param mixed $val
     */
    public function __set($key,$val)
    {
        switch( $key ) {
        case 'icon':
            $this->_icon = trim($val);
            break;
        case 'title':
            $this->_title = trim($val);
            break;
        case 'msg':
            $this->_msg = trim($val);
            break;
        case 'perms':
            if( !$val || !is_array($val) ) throw new InvalidArgumentException('perms must be an array of permission name strings');
            $tmp = [];
            foreach( $val as $one ) {
                $one = trim($one);
                if( !$one ) continue;
                if( !in_array($one,$tmp) ) $tmp[] = $one;
            }
            if( !$tmp ) throw new InvalidArgumentException('perms must be an array of permission name strings');
            $this->_perms = $tmp;
            break;

        default:
            return parent::__set($key,$val);
        }
    }

    /**
     * Givent he admin_uid, check if the specified uid has at least one of the permissions specified in the perms array.
     *
     * @param int $admin_uid
     * @return bool;
     */
    protected function is_for($admin_uid)
    {
        if( !$this->_perms ) return FALSE;
        $admin_uid = (int) $admin_uid;
        $userops = SingleItem::UserOperations();
        $perms = $this->_perms;
        if( !is_array($this->_perms) ) $perms = [$this->_perms];
        foreach( $perms as $permname ) {
            if( $userops->CheckPermission($admin_uid,$permname) ) return TRUE;
        }
        return FALSE;
    }

    /**
     * Return the alert title.
     *
     * @return string
     */
    public function get_title()
    {
        return $this->_title;
    }

    /**
     * Return the alert message
     *
     * @return string
     */
    public function get_message()
    {
        return $this->_msg;
    }

    /**
     * Return the alert icon URL (if any)
     *
     * @return string
     */
    public function get_icon()
    {
        return $this->_icon;
    }

} // end of class