<?php
/*
Class for handling module file-templates as a resource
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppSingle;

/**
 * A class to handle a module file template.
 *
 * @ignore
 * @internal
 * @package CMS
 * @since 1.11
 */
class Smarty_Resource_module_file_tpl extends Smarty_Resource_Custom //Smarty_Internal_Resource_File
{
    // static properties here >> StaticProperties class ?
    /**
     * @ignore
     */
    private static $loaded = [];

    /**
     * @param string $name    template identifier like 'modulename;filename'
     * @param mixed  &$source store for retrieved template content, if any
     * @param int    &$mtime  store for retrieved template modification timestamp
     */
    protected function fetch($name,&$source,&$mtime)
    {
        $mtime = false;
        $parts = explode(';',$name);
        if( count($parts) != 2 ) return;

        if( isset(self::$loaded[$name]) ) {
            $source = self::$loaded[$name]['content'];
            $mtime = self::$loaded[$name]['modified'];
            return;
        }

        $modname = trim($parts[0]);
        $modinst = AppSingle::ModuleOperations()->get_module_instance($modname); //loaded modules only
        if( $modinst ) {
            $module_path = $modinst->GetModulePath();
            $filename = trim($parts[1]);
            $files = [];
//            $files[] = cms_join_path($module_path,'custom','templates',$filename);
            $files[] = cms_join_path(CMS_ASSETS_PATH,'module_custom',$modname,'templates',$filename);
            $files[] = cms_join_path($module_path,'templates',$filename);

            foreach( $files as $one ) {
                if( is_file($one) ) {
                    $source = @file_get_contents($one);
                    $mtime = @filemtime($one);
                    self::$loaded[$name] = ['content'=>$source,'modified'=>$mtime];
                    return;
                }
            }
        }
        cms_error('Missing template: '.$name);
    }
} // class
