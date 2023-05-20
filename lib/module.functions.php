<?php
/*
Module-related methods available for every request
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

/**
 * Module-directories lister. Checks for directories existence, including $modname if provided.
 * @since 3.0
 *
 * @param string $modname Optional name of a module
 * @param bool $all Optional flag whether to list all module-places even
 *  if a corresponding directory doesn't exist. Ignored if $modname is
 *  not empty. Default false.
 * @return array of absolute filepaths, no trailing separators, or maybe empty.
 *  Main/normal-location first, then 'core'-location, then 'non-core'-location.
 */
function cms_module_places(string $modname = '', bool $all = false) : array
{
    $dirlist = [];
    $path = cms_join_path(CMS_ROOT_PATH, 'modules');
    if ($modname) {
        $path .= DIRECTORY_SEPARATOR . $modname;
    }
    if (is_dir($path)) {
        $dirlist[] = $path;
//    } elseif ($all && !$modname) {
//        $dirlist[] = ''; // deep trouble coming !!
    }
/*  $path = cms_join_path(CMS_ROOT_PATH, 'lib', 'modules');
    if ($modname) {
        $path .= DIRECTORY_SEPARATOR . $modname;
    }
    if (is_dir($path)) {
        $dirlist[] = $path;
//    } elseif ($all && !$modname) {
//        $dirlist[] = ''; // indicate this place N/A
    }
*/
    $path = cms_join_path(CMS_ASSETS_PATH, 'modules');
    if ($modname) {
        $path .= DIRECTORY_SEPARATOR . $modname;
    }
    if (is_dir($path)) {
        $dirlist[] = $path;
//    } elseif ($all && !$modname) {
//        $dirlist[] = ''; // indicate this place N/A
    }
    return $dirlist;
}

/**
 * Module-file locator which doesn't need the module to be loaded.
 *
 * @since 3.0
 * @param string $modname name of the module.
 * @param bool $folder Optional flag whether to return filepath of folder containing the module Default false
 * @return string filepath of module class, or its parent folder (maybe empty)
 */
function cms_module_path(string $modname, bool $folder = false) : string
{
    $p = DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
    // default place
    $path = CMS_ROOT_PATH.$p;
    if (is_file($path)) {
        return ($folder) ? dirname($path) : $path;
    }
    // so-called 'core-modules' place'
    $path = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.$p;
    if (is_file($path)) {
        return ($folder) ? dirname($path) : $path;
    }
    // so-called 'non-core-modules' place'
    $path = CMS_ASSETS_PATH.$p;
    if (is_file($path)) {
        return ($folder) ? dirname($path) : $path;
    }
    return '';
}
