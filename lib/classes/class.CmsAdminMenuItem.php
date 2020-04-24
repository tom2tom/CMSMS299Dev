<?php
# Class to provide menu items in the CMSMS admin navigation
# Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# BUT withOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

/* for future use
namespace CMSMS;
use cms_utils;
use CMSModule;
*/
/**
 * Base module class.
 *
 * Modules should extend this class as appropriate, to provide their functionality.
 *
 * @package		CMS
 * @since		2.0
 * @license     GPL
 * @author      Robert Campbell <calguy1000@cmsmadesimple.org>
 * @see         CMSModule::GetAdminSection() FOO
 * @property string $module The module that hosts the destination action
 * @property string $section The admin section (from CMSModule::GetAdminSection)
 * @property string $title The title of the menu item
 * @property string $action The module action
 * @property string $url The actual URL for the menu item link.  If this is not specified, it is created from the module and action.
 * @property string $icon The URL to the icon to associate with this action
 * @property int $priority Priority for the menu item (minimum of 2)
 */
final class CmsAdminMenuItem
{
    /**
     * @ignore
     * 'system' is for internal use only.
     */
    const ITEMKEYS = ['module','section','name','title','description','action','url','icon','priority','system'];

    /**
     * @ignore
     */
    private $_data = [];


    /**
     * @ignore
     */
    public function __get($k)
    {
        if( !in_array($k,self::ITEMKEYS) ) throw new CmsException('Invalid key: '.$k.' for '.self::class.' object');
        switch( $k ) {
        case 'url':
            if( isset($this->_data[$k]) && $this->_data[$k] ) return $this->_data[$k];
            // url can be dynamically generated... maybe
            if( $this->module && $this->action ) {
                $mod = cms_utils::get_module($this->module);
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
        if( !in_array($k,self::ITEMKEYS) ) throw new CmsException('Invalid key: '.$k.' for '.self::class.' object');
        $this->_data[$k] = $v;
    }

    /**
     * @ignore
     */
    public function __isset($k)
    {
        if( !in_array($k,self::ITEMKEYS) ) throw new CmsException('Invalid key: '.$k.' for '.self::class.' object');
        return isset($this->_data[$k]);
    }

    /**
     * @ignore
     */
    public function __unset($k)
    {
        if( !in_array($k,self::ITEMKEYS) ) throw new CmsException('Invalid key: '.$k.' for '.self::class.' object');
        throw new CmsException('Cannot unset data from a CmsAdminMenuItem object');
    }

    /**
     * Return all recorded data for this item
     * @since 2.3
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
        foreach (self::ITEMKEYS as $ok) {
			switch ($ok) {
				case 'name':
				case 'description':
				case 'icon':
				case 'system':
				case 'priority':
				case 'url':
					break 2;  // we don't care whether these are set
				default:
					if (!isset($this->_data[$ok])) return false;
			}
        }
        return true;
    }

    /**
     * A convenience method to build a standard admin menu item from module methods.
     *
     * @internal
     * @param CMSModule $mod
     * @param since 2.3 Optional action name, default 'defaultadmin'
     * @return mixed CmsAdminMenuItem-object or null
     */
    public static function from_module(CMSModule $mod, $action = 'defaultadmin')
    {
        $obj = null;
        if( $mod->HasAdmin() ) {
            $obj = new static();
            $obj->module = $mod->GetName();
            $obj->section = $mod->GetAdminSection();
            $obj->title = $mod->GetFriendlyName();
            $obj->description = $mod->GetAdminDescription();
            $obj->priority = 50;
            $obj->action = $action;
            $obj->url = $mod->create_url('m1_',$action);
        }
        return $obj;
    }
} // class
