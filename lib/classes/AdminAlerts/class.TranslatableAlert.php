<?php
/*
A simple alert class.
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that License, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\AdminAlerts;

use CMSMS\SingleItem;
use CMSMS\Utils;
use InvalidArgumentException;
use function lang;

/**
 * A simple alert class that provides for translatable messages and titles.
 *
 * This class will use the module that is associated with the alert to translate the key.  If the module name is empty, or the special value 'core' then the global 'lang' function will
 * be used to read translations from the admin lang file.
 *
 * @since 2.2
 * @package CMS
 * @license GPL
 * @prop string[] $perms An array of permission names.  The logged in user must have at least one of these permissions to see the alert.
 * @prop string $icon The complete URL to an icon to associate with this alert
 * @prop string $titlekey The language key (relative to the module) for the alert title.
 * @prop string $msgkey The language key (relative to the module) for the alert message.
 * @prop mixed  $msgargs Either an array of arguments to pass to the language function or a single string or value.
 * @see SecurityCheckTask
 */
class TranslatableAlert extends Alert
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
    private $_titlekey;

    /**
     * @ignore
     */
    private $_msgkey;

    /**
     * @ignore
     */
    private $_msgargs;

    /**
     * Constructor
     *
     * @param mixed $perms A single permission name, or an An array of permission names, or null.
     * @throws InvalidArgumentException
     */
    public function __construct($perms = null)
    {
        if( $perms ) {
            if( is_string($perms) ) $perms = [ $perms ];
            if( !$perms ) throw new InvalidArgumentException('perms must be an array of permission name strings');
        }
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
        case 'titlekey':
            return $this->_titlekey;
        case 'msgkey':
            return $this->_msgkey;
        case 'msgargs':
            return $this->_msgargs;
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
     * @throws InvalidArgumentException
     */
    public function __set($key,$val)
    {
        switch( $key ) {
        case 'icon':
            $this->_icon = trim($val);
            break;
        case 'titlekey':
            $this->_titlekey = trim($val);
            break;
        case 'msgkey':
            $this->_msgkey = trim($val);
            break;
        case 'msgargs':
            if( !is_array( $val ) ) $val = [ $val ];
            $this->_msgargs = $val; // accept string or array...
            break;
        case 'perms':
            if( !$val ) throw new InvalidArgumentException('perms must be an array of permission name strings');
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
     * Givent he title key, translate the key into a displayable string.
     *
     * @return string
     */
    public function get_title()
    {
        $modname = $this->module;
        if( !$modname || strtolower($modname) == 'core' ) {
            return lang($this->_titlekey);
        }
        $mod = Utils::get_module($modname);
        if( $mod ) return $mod->Lang($this->_titlekey);
    }

    /**
     * Given the message key and the message args (if any) translate the key and arguments into a displayable striing.
     *
     * @return string
     */
    public function get_message()
    {
        $modname = $this->module;
        $args = [ $this->_msgkey ];
        if( $this->_msgargs ) $args = array_merge( $args, $this->_msgargs );
        if( !$modname || strtolower($modname) == 'core' ) {
            return lang(...$args);
        }
        $mod = Utils::get_module($modname);
        if( $mod ) return $mod->Lang(...$args);
    }

    /**
     * @ignore
     */
    public function get_icon()
    {
        return $this->_icon;
    }

} // end of class
