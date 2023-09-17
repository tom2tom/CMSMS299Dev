<?php
/*
Class for handling file-stored module templates as a resource
Copyright (C) 2012-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\Lone;
use function CMSMS\log_error;
use function CMSMS\log_warning;
use function CMSMS\sanitizeVal;

/**
 * Class for handling file-stored module templates as a resource.
 *
 * @package CMS
 * @internal
 * @ignore
 * @since 1.11
 */
class Smarty_Resource_module_file_tpl extends Smarty_Resource_Custom
{
    // static properties here >> Lone ?
    /**
     * @var array intra-request cache of used templates, each member like
     *  name => [ 'content' => whatever, 'modified' => non-0 timestamo ]
     * @ignore
     */
    private static $loaded = [];

    /**
     * populate Source Object with meta data from Resource, and work
     * around Smarty's filepath-setting limitation
     *
     * @param Smarty_Template_Source   $source    source object
     * @param Smarty_Internal_Template $_template template object
     */
    public function populate(Smarty_Template_Source $source, Smarty_Internal_Template $_template = null)
    {
        parent::populate($source, $_template);
        if ($source->exists) {
            $source->filepath = $source->type . ':' . sanitizeVal(strtr($source->name, [';'=>'_']), CMSSAN_FILE);
        }
    }

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
        // not cms_module_path($modname), module might be present but uninstalled
        $mod = Lone::get('ModuleOperations')->get_module_instance($modname);
        if( $mod ) {
            $module_path = $mod->GetModulePath();
            $filename = trim($parts[1]);
            $files = [];
            $files[] = cms_join_path(CMS_ASSETS_PATH,'module_custom',$modname,'templates',$filename);
            $files[] = cms_join_path($module_path,'templates',$filename);

            foreach( $files as $one ) {
                if( is_file($one) ) {
                    $content = @file_get_contents($one);
                    if( $content ) {
                        $source = $content;
                        $mtime = @filemtime($one);
                        self::$loaded[$name] = ['content'=>$content,'modified'=>$mtime];
                        return;
                    }
                    log_warning('Empty template',$one);
                }
            }
        }
        log_error('Missing template',$name);
    }
} // class
