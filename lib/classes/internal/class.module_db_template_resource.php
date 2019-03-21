<?php
#Classes to handle module templates.
#Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS\internal;

use CmsApp;
use CMSMS\ModuleOperations;
use CMSMS\TemplateOperations;
use Exception;
use Smarty_Resource_Custom;
use const CMS_ASSETS_PATH;
use const CMS_DB_PREFIX;
use function cms_join_path;
use function debug_buffer;

/**
 * A class to handle a module database template, with fallback to a generic template of the same name.
 *
 * @ignore
 * @internal
 * @since 1.11
 * @package CMS
 */
class module_db_template_resource extends Smarty_Resource_Custom
{
    /**
     * @param string $name ';'-separated like 'modulename;templatename'
     * @param string &$source retrieved template content
     * @param int    &$mtime retrieved template modification timestamp
     */
    protected function fetch($name,&$source,&$mtime)
    {
        debug_buffer('','CMSModuleDbTemplateResource start'.$name);
        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT content,modified FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE originator=? AND name=?';
        $parts = explode(';',$name);
        $row = $db->GetRow($query, $parts);
        if( $row ) {
            $source = $row['content'];
            $mtime = (int) $row['modified'];
        }
        else {
            // fallback to the layout stuff.
            try {
                $obj = TemplateOperations::load_template($parts[1]);
                $source = $obj->get_content();
                $mtime = $obj->get_modified();
            }
            catch( Exception $e ) {
                // nothing here.
            }
        }
        debug_buffer('','CMSModuleDbTemplateResource end'.$name);
    }
} // class


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
     * @param string $name ';'-separated like 'modulename;filename'
     * @param mixed  $source store for retrieved template content
     * @param int    $mtime store for retrieved template modification timestamp
     */
    protected function fetch($name,&$source,&$mtime)
    {
        $source = null;
        $mtime = 0;
        $parts = explode(';',$name);
        if( count($parts) != 2 ) return;

        $module_name = trim($parts[0]);
        $filename = trim($parts[1]);
        $module = ModuleOperations::get_instance()->get_module_instance($module_name); //loaded modules only
        $module_path = $module->GetModulePath();
        $files = [];
        $files[] = cms_join_path($module_path,'custom','templates',$filename);
        $files[] = cms_join_path(CMS_ASSETS_PATH,'module_custom',$module_name,'templates',$filename);
        $files[] = cms_join_path($module_path,'templates',$filename);

        foreach( $files as $one ) {
            if( is_file($one) ) {
                $source = @file_get_contents($one);
                $mtime = @filemtime($one);
                break;
            }
        }
    }
} // class
