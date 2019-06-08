<?php
#Class for handling module file-templates as a resource
#Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks Ted Kulp and all other contributors from the CMSMS Development Team.
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

namespace CMSMS\internal;

use CMSMS\ModuleOperations;
use Smarty_Resource_Custom;
use const CMS_ASSETS_PATH;
use function cms_error;
use function cms_join_path;

/**
 * A simple class to handle a module file template.
 *
 * @ignore
 * @internal
 * @package CMS
 * @since 1.11
 */
class module_file_template_resource extends Smarty_Resource_Custom
{
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

        $module_name = trim($parts[0]);
        $module = ModuleOperations::get_instance()->get_module_instance($module_name); //loaded modules only
        if( $module ) {
            $module_path = $module->GetModulePath();
            $filename = trim($parts[1]);
            $files = [];
//            $files[] = cms_join_path($module_path,'custom','templates',$filename);
            $files[] = cms_join_path(CMS_ASSETS_PATH,'module_custom',$module_name,'templates',$filename);
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
