<?php
# ModuleManager class: ..
# Copyright (C) 2017-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace ModuleManager;

use CMSMS\internal\extended_module_info;
use CMSMS\ModuleOperations;
use Exception;
use ModuleManager\modulerep_client;
use function debug_display;

class module_info extends extended_module_info //was ModuleManagerModuleInfo
{
    const MMKEYS = [
     'can_install',
     'can_uninstall',
     'can_upgrade',
     'deprecated',
     'e_status',
     'missing_deps',
     'needs_upgrade',
    ];
    const DEPRECATED = ['CMSMailer','MenuManager'];
    private static $_minfo;
    private $_mmdata = [];

    public function __construct($module_name,$can_load = TRUE,$can_check_forge = TRUE)
    {
        parent::__construct($module_name,$can_load);

        // add in some info that only module manager can tell us.
        // like: is there a newer version available
        // extended status (db version newer then file version) / needs upgrade
        if( $this['version'] && $this['installed_version'] ) {
            $tmp = version_compare($this['installed_version'],$this['version']);
            if( $this['installed'] && $tmp < 0 ) {
                $this['e_status'] = 'need_upgrade';
            }
            else if( $tmp > 0 ) {
                $this['e_status'] = 'db_newer';
            }
            else if( $can_check_forge ) {
                try {
                    $rep_info = modulerep_client::get_upgrade_module_info($module_name);
                    if( is_array($rep_info) ) {
                        if( ($res = version_compare($this['version'],$rep_info['version'])) < 0 ) $this['e_status'] = 'newer_available';
                    }
                }
                catch( Exception $e ) {
                    // nothing here.
                }
            }
        }

    }

    private function _get_missing_dependencies()
    {
        $depends = $this['depends'];
        if( $depends ) {
            $out = [];
            foreach( $depends as $name => $ver ) {
                $rec = self::get_module_info($name);
                if( !is_object($rec) ) {
                    // problem getting module info for it.
                    $out[$name] = $ver;
                    continue;
                }
                if( !$rec['installed'] || !$rec['active'] ) {
                    // dependent is not installed (or not active) so its missing
                    $out[$name] = $ver;
                    continue;
                }
                if( $rec['needs_upgrade'] ) {
                    // dependent needs upgrading, so it's a missing dependency
                    $out[$name] = $ver;
                    continue;
                }
                if( version_compare($rec['version'],$ver) >= 0 ) continue;
                $out[$name] = $ver;
            } // foreach
            if( $out ) return $out;
        }
    }

    private function _check_dependencies()
    {
        // check if all module dependants are installed and are of sufficient version.
        $missing = $this->_get_missing_dependencies();
        if( $missing ) return FALSE;
        return TRUE;
    }

    public function OffsetGet($key)
    {
        if( !in_array($key,self::MMKEYS) ) return parent::OffsetGet($key);
        if( isset($this->_mmdata[$key]) ) return $this->_mmdata[$key];

        if( $key == 'can_install' ) {
            // can we install this module
            if( $this['installed'] ) return FALSE;
            if( !$this['ver_compatible'] ) return FALSE;
            return $this->_check_dependencies();
        }


        if( $key == 'can_upgrade' ) {
            // see if we can upgrade this module
            return $this->_check_dependencies();
        }

        if( $key == 'needs_upgrade' || $key == 'need_upgrade' ) {
            // test if this module needs an upgrade
            // this only checks with the data we have, does not calculate an extended status.
            if( !$this['version'] || !$this['installed_version'] ) {
                // if it's not installed, it doesn't need an upgrade
                return FALSE;
            }
            $tmp = version_compare($this['installed_version'],$this['version']);
            if( $tmp < 0 ) return TRUE;
            // The e_status field checks the repository... and tells if the db version is newer.
            return FALSE;
        }

        if( $key == 'can_uninstall' ) {
            // check if this module can be uninstalled
            if( !$this['installed'] ) return FALSE;

            // check for installed modules that are dependent upon this one
            $name = $this['name'];
            if( $name == 'ModuleManager' || $name == 'CoreAdminLogin' ) return FALSE;

            foreach( self::$_minfo as $mname => $minfo ) {
                if( $minfo['dependants'] ) {
                    if( in_array($name,$minfo['dependants']) ) return FALSE;
                }

            }
            return TRUE;
        }

        if( $key == 'missing_deps' ) {
            // test if this module is missing dependencies
            $out = $this->_get_missing_dependencies();
            return $out;
        }

        if( $key == 'deprecated' ) {
            // test if this module is deprecated
            if( in_array($this['name'],self::DEPRECATED) ) return TRUE;
            return FALSE;
        }
    }

    public function OffsetSet($key,$value)
    {
        if( !in_array($key,self::MMKEYS) ) parent::OffsetSet($key,$value);
        if( $key != 'e_status' && $key != 'deprecated' ) return; // dynamic
        $this->_mmdata[$key] = $value;
    }

    public function OffsetExists($key)
    {
        if( !in_array($key,self::MMKEYS) ) return parent::OffsetExists($key);
        if( $key != 'e_status' && $key != 'deprecated' ) return; // dynamic
        return isset($this->_mmdata[$key]);
    }

    public static function get_all_module_info($can_check_forge = TRUE)
    {
        if( is_array(self::$_minfo) ) return self::$_minfo;

        $allknownmodules = (new ModuleOperations())->FindAllModules();

        // first pass...
        $out = [];
        foreach( $allknownmodules as $module_name ) {
            try {
                $info = new module_info($module_name,TRUE,$can_check_forge);
                $out[$module_name] = $info;
            }
            catch( Exception $e ) {
                debug_display($e->GetMessage(),$module_name);
            }
        }

        self::$_minfo = $out;
        return self::$_minfo;
    }

    public static function &get_module_info($module)
    {
        $tmp = self::get_all_module_info();
        if( isset($tmp[$module]) ) return $tmp[$module];

        $out = null;
        return $out;
    }

} // class
