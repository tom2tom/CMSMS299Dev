<?php
/*
Module: DesignManager - A CMSMS addon module to provide template management.
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace DesignManager;

use CMSMS\LockOperations;
use CMSMS\Utils as AppUtils;

final class utils
{
//    public function __construct() {}

    public static function locking_enabled()
    {
        $mod = AppUtils::get_module('DesignManager');
        $timeout = $mod->GetPreference('lock_timeout', 60);
        return $timeout > 0;
    }

    public static function get_template_locks()
    {
		// static properties here >> SingleItem property|ies ?
        static $_locks = null;
        static $_locks_loaded = FALSE;
        if( !$locks_loaded ) {
            $_locks_loaded = TRUE;
            $tmp = LockOperations::get_locks('template');
            if( $tmp ) {
                $_locks = [];
                foreach( $tmp as $lock_obj ) {
                    $_locks[$lock_obj['oid']] = $lock_obj;
                }
            }
        }
        return $_locks;
    }

    public static function get_css_locks()
    {
		//NOTE static properties here
        static $_locks = null;
        static $_locks_loaded = FALSE;
        if( !$locks_loaded ) {
            $_locks_loaded = TRUE;
            $tmp = LockOperations::get_locks('stylesheet');
            if( $tmp ) {
                $_locks = [];
                foreach( $tmp as $lock_obj ) {
                    $_locks[$lock_obj['oid']] = $lock_obj;
                }
            }
        }
        return $_locks;
    }

} // class
