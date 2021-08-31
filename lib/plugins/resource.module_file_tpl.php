<?php
/*
Class for handling file-stored module templates as a resource
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

use CMSMS\SingleItem;

/**
 * Class for handling file-stored module templates as a resource.
 *
 * @package CMS
 * @internal
 * @ignore
 * @since 1.11
 */
class Smarty_Resource_module_file_tpl extends Smarty_Resource_Custom //OR Smarty_Internal_Resource_File
{
    // static properties here >> SingleItem ?
    /**
     * @ignore
     */
    private static $loaded = [];

    /**
     * @param string $name    template identifier like 'modulename;filename'
     * @param mixed  &$source store for retrieved template content, if any
     * @param int    &$mtime  store for retrieved template modification timestamp, if $source is set
     */
    protected function fetch($name,&$source,&$mtime)
    {
        if( isset(self::$loaded[$name]) ) {
            $source = self::$loaded[$name]['content'];
            $mtime = self::$loaded[$name]['modified'];
            return;
        }
        $parts = explode(';',$name);
        if( count($parts) != 2 ) {
            return;
        }
        $modname = trim($parts[0]);
        $mod = SingleItem::ModuleOperations()->get_module_instance($modname); //loaded modules only
        if( $mod ) {
            $module_path = $mod->GetModulePath();
            $filename = trim($parts[1]);
            $files = [];
            $files[] = cms_join_path(CMS_ASSETS_PATH,'module_custom',$modname,'templates',$filename);
            $files[] = cms_join_path($module_path,'templates',$filename);

            foreach( $files as $one ) {
                if( is_file($one) ) {
                    $content = @file_get_contents($one);
                    if( !$content ) {
                        cms_warning('Empty template: '.$one);
                        continue;
                    }
                    $source = $content;
                    $mtime = @filemtime($one);
                    self::$loaded[$name] = ['content'=>$content,'modified'=>$mtime];
                    return;
                }
            }
        }
        cms_error('Missing template: '.$name);
    }
} // class
