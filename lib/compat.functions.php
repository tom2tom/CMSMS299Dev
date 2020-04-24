<?php
#Backward compatibility support
#Copyright (C) 2011-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

/**
 * Miscellaneous support functions
 *
 * @package CMS
 * @license GPL
 */
if( !function_exists('gzopen') ) {
    /**
     * Wrapper for gzopen in case it does not exist.
     * Some installs of PHP (after PHP 5.3 use a different zlib library, and therefore gzopen is not defined.
     * This method works past that.
     *
     * @since 2.0
     * @ignore
     */
    function gzopen( $filename , $mode , $use_include_path = 0 ) {
        return gzopen64($filename, $mode, $use_include_path);
    }
}

/**
 * Smarty 2 API
 */
//include_once __DIR__.DIRECTORY_SEPARATOR.'smarty'.DIRECTORY_SEPARATOR.'SmartyBC.class.php';
//class_alias('SmartyBC', $TODOalias, false); some sort of 'merge' needed

/**
 * Return the currently configured database prefix.
 * @deprecated since 2.3 Use constant CMS_DB_PREFIX instead
 *
 * @since 0.4
 * @return string
 */
function cms_db_prefix() : string
{
    return CMS_DB_PREFIX;
}
