<?php
/*
Class to provide menu items in the CMSMS admin navigation
Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace CMSMS;

use CmsException;
use CMSModule;
use CMSMS\Utils;

/**
 * @since 2.99
 * @since 2.0 as global-namespace CmsAdminMenuItem
 */
final class AdminMenuItem
{
    /**
     * @ignore
     */
    private const ITEMKEYS = [
     'action',
     'description', //optional
     'icon', //optional
     'module',
     'name', //optional
     'priority', //optional
     'section',
     'system', //internal use only
     'title',
     'url',
    ];

    /**
     * @ignore
     */
    private $_data = [];

    /**
     * @ignore
     */
    public function __get($k)
    {
        if( !in_array($k,self::ITEMKEYS) ) throw new Exception('Invalid key: '.$k.' for '.self::class.' object');
        switch( $k ) {
        case 'url':
            if( isset($this->_data[$k]) && $this->_data[$k] ) return $this->_data[$k];
            // url can be dynamically generated... maybe
            if( $this->module && $this->action ) {
                $mod = Utils::get_module($this->module);
                if( $mod ) {
                    $url = $mod->create_url('m1_',$this->action);
                    return $url;
                }
            }
            break;

        default:
            if( isset($this->_data[$k]) ) return $this->_data[$k];
        }
    }


    /**
     * @ignore
     */
    public function __set($k,$v)
    {
        if( !in_array($k,self::ITEMKEYS) ) throw new Exception('Invalid key: '.$k.' for '.self::class.' object');
        $this->_data[$k] = $v;
    }

    /**
     * @ignore
     */
    public function __isset($k)
    {
        if( !in_array($k,self::ITEMKEYS) ) throw new Exception('Invalid key: '.$k.' for '.self::class.' object');
        return isset($this->_data[$k]);
    }

    /**
     * @ignore
     */
    public function __unset($k)
    {
        if( !in_array($k,self::ITEMKEYS) ) throw new Exception('Invalid key: '.$k.' for '.self::class.' object');
        throw new Exception('Cannot unset data from a AdminMenuItem object');
    }

    /**
     * Return all recorded data for this item
     * @since 2.99
     * @return associative array
     */
    public function get_all() : array
    {
        return $this->_data;
    }

    /**
     * Test if the object is valid
     * @return bool
     */
    public function valid()
    {
        $must = array_diff(self::ITEMKEYS, [
            'description',
            'icon',
            'name',
            'priority',
            'system',
        ]);
        foreach ($must as $key) {
            if (!isset($this->_data[$key])) return false;
        }
        return true;
    }

    /**
     * A convenience method to build a standard admin menu item from module methods.
     *
     * @internal
     * @param CMSModule | IResource $mod
     * @param since 2.99 Optional action name, default 'defaultadmin'
     * @return mixed AdminMenuItem-object or null
     */
    public static function from_module($mod, $action = 'defaultadmin')
    {
        if( $mod->HasAdmin() ) {
            $obj = new self();
            $obj->action = $action;
            $obj->description = $mod->GetAdminDescription();
            $obj->module = $mod->GetName();
            $obj->priority = 50;
            $obj->section = $mod->GetAdminSection();
            $obj->title = $mod->GetFriendlyName();
            $obj->url = $mod->create_url('m1_',$action);
            return $obj;
        }
    }
} // class
