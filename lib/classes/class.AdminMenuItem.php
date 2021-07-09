<?php
/*
Class to provide menu items in the CMSMS admin navigation
Copyright (C) 2010-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
     'name', //shortish module-unique identifier/alias for e.g. CSS classes
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
        // back-compatibility workaround
        if (empty($this->_data['name']) && !empty($this->_data['action'])) {
            $this->_data['name'] = $this->get_name($this->_data['action']);
        }
        return $this->_data;
    }

    /**
     * Return an object-name derived from $from
     * @since 2.99
     *
     * @param string $from
     * @return string
     */
    public function get_name(string $from) : string
    {
        if ($from == 'defaultadmin') { return 'default'; }
        $s = strtr($from, ['-' => '']);
        if (($p = strpos($s, '_')) !== false) {
            $p++;
        } else {
            $p = 0;
        }
        return substr($s, $p, 6);
    }

    /**
     * Test if the object is valid
     * @return bool
     */
    public function valid()
    {
        // back-compatibility workaround
        if (empty($this->_data['name']) && !empty($this->_data['action'])) {
            $this->_data['name'] = $this->get_name($this->_data['action']);
        }
        $must = array_diff(self::ITEMKEYS, [
            'description',
            'icon',
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
            $obj->name = $obj->get_name($action);
            $obj->priority = 50;
            $obj->section = $mod->GetAdminSection();
            $obj->title = $mod->GetFriendlyName();
            $obj->url = $mod->create_url('m1_',$action);
            return $obj;
        }
    }
} // class
