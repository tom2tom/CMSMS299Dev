<?php
#class to process module information
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use ArrayAccess;
use CmsLogicException;
use ModuleOperations;
use const CMS_ASSETS_PATH;
use const CMS_VERSION;
use function cms_join_path;
use function cms_module_path;
use function cms_to_bool;
use function get_parameter_value;
use function is_directory_writable;
use function lang;

class module_info implements ArrayAccess
{
    const PROPNAMES = [
    'about',
    'author',
    'authoremail',
    'changelog',
    'depends',
    'description',
    'dir',
    'has_custom',
    'has_meta',
    'help',
    'is_system_module',
    'lazyloadadmin',
    'lazyloadfrontend',
    'mincmsversion',
    'name',
    'notavailable',
    'root_writable',
    'ver_compatible',
    'version',
    'writable',
    ];

    private $_data = [];

    public function __construct($module_name,$can_load = true)
    {
        $fn1 = $this->_get_module_meta_file( $module_name );
        $if1 = is_file($fn1);
        $ft1 = ( $if1 ) ? filemtime($fn1) : 0 ;
        $fn2 = $this->_get_module_file( $module_name );
        $if2 = is_file($fn2);
        $ft2 = ( $if2) ? filemtime($fn2) : 0;
        if( ($ft2 > $ft1 && $can_load) || ($if2 && !$if1) ) {
            // module file is newer or the only choice
            $arr = $this->_read_from_module($module_name);
        } elseif( $if1 && $if2 ) {
            // moduleinfo.ini file is newer
            $arr = $this->_read_from_module_meta($module_name);
        } else {
            $arr = null;
        }
        if( $arr ) {
            $arr2 = $this->_check_modulecustom($module_name);
            $this->_setData( array_merge($arr2, $arr ));
        } else {
            $arr['name'] = $module_name;
            $this->_setData( $arr );
            $this->_data['notavailable'] = true;
        }
    }

    public function OffsetGet($key)
    {
        if( !in_array($key,self::PROPNAMES) ) throw new CmsLogicException('CMSEX_INVALIDMEMBER',null,$key);
        switch( $key ) {
        case 'about':
            break;

        case 'ver_compatible':
            return version_compare($this['mincmsversion'],CMS_VERSION,'<=');

        case 'dir':
            return (new ModuleOperations())->get_module_path( $this->_data['name'] );

        case 'writable':
            $dir = $this['dir'];
            if( !$dir || !is_dir( $dir ) ) return false;
            return is_directory_writable($this['dir']);

        case 'root_writable':
            // TODO move this into ModuleManager\module_info
            return is_writable($this['dir']);

        case 'is_system_module':
            return (new ModuleOperations())->IsSystemModule( $this->_data['name'] );

        default:
            return $this->_data[$key] ?? null;
        }
    }

    public function OffsetSet($key,$value)
    {
        if( !in_array($key,self::PROPNAMES) ) throw new CmsLogicException('CMSEX_INVALIDMEMBER',$key);
        if( $key == 'about' ) throw new CmsLogicException('CMSEX_INVALIDMEMBERSET',$key);
        if( $key == 'ver_compatible' ) throw new CmsLogicException('CMSEX_INVALIDMEMBERSET',$key);
        if( $key == 'dir' ) throw new CmsLogicException('CMSEX_INVALIDMEMBERSET',$key);
        if( $key == 'writable' ) throw new CmsLogicException('CMSEX_INVALIDMEMBERSET',$key);
        if( $key == 'root_writable' ) throw new CmsLogicException('CMSEX_INVALIDMEMBERSET',$key);
        if( $key == 'has_custom' ) throw new CmsLogicException('CMSEX_INVALIDMEMBERSET',$key);
        $this->_data[$key] = $value;
    }

    public function OffsetExists($key)
    {
        if( !in_array($key,self::PROPNAMES) ) throw new CmsLogicException('CMSEX_INVALIDMEMBER',null,$key);
        return isset($this->_data[$key]);
    }

    public function OffsetUnset($key)
    {
    }

    private function _get_module_meta_file( string $module_name ) : string
    {
        $path = cms_module_path($module_name);
        if( $path ) {
            return str_replace($module_name.'.module.php','moduleinfo.ini',$path);
        }
        return '';
    }

    private function _get_module_file( string $module_name ) : string
    {
        return cms_module_path($module_name);
    }

    private function _setData( array $in )
    {
        foreach( $in as $key => $value ) {
            if( in_array( $key, self::PROPNAMES ) ) $this->_data[$key] = $value;
        }
    }

    private function _check_modulecustom(string $module_name) : array
    {
        $fn = cms_module_path($module_name,true); //don't care about installation status
        if( $fn ) {
            $path = cms_join_path($fn,'custom','templates','*.tpl');
            $files = glob($path,GLOB_NOSORT);
            if( !$files ) {
                $path = cms_join_path($fn,'custom','lang','??_??.php');
                $files = glob($path,GLOB_NOSORT);
            }
            if( $files ) {
                return ['has_custom' => true];
            }
//        } else {
			//TODO exception?
        }

        $path = cms_join_path(CMS_ASSETS_PATH,'module_custom',$module_name,'');
        $files = glob($path.'templates'.DIRECTORY_SEPARATOR.'*.tpl',GLOB_NOSORT);
        if( !$files ) {
            $files = glob($path.'lang'.DIRECTORY_SEPARATOR.'??_??.php',GLOB_NOSORT);
        }
        $has = count($files) > 0;
        return ['has_custom' => $has];
    }

    private function _remove_module_meta(string  $module_name )
    {
        $fn = $this->_get_module_meta_file( $module_name );
        if( is_file($fn) && is_writable($fn) ) unlink($fn);
    }

    /* return mixed array or null */
    private function _read_from_module_meta(string $module_name)
    {
        $dir = (new ModuleOperations())->get_module_path( $module_name );
        $fn = $this->_get_module_meta_file( $module_name );
        if( !is_file($fn) ) return;
        $inidata = @parse_ini_file($fn,TRUE);
        if( $inidata === FALSE || count($inidata) == 0 ) return;
        if( !isset($inidata['module']) ) return;

        $data = $inidata['module'];
        $arr = [];
        $arr['name'] = isset($data['name'])?trim($data['name']):$module_name;
        $arr['version'] = isset($data['version'])?trim($data['version']):'0.0.1';
        $arr['description'] = isset($data['description'])?trim($data['description']):'';
        $arr['author'] = trim(get_parameter_value($data,'author',lang('notspecified')));
        $arr['authoremail'] = trim(get_parameter_value($data,'authoremail',lang('notspecified')));
        $arr['mincmsversion'] = isset($data['mincmsversion'])?trim($data['mincmsversion']):CMS_VERSION;
        $arr['lazyloadadmin'] = cms_to_bool(get_parameter_value($data,'lazyloadadmin',FALSE));
        $arr['lazyloadfrontend'] = cms_to_bool(get_parameter_value($data,'lazyloadfrontend',FALSE));

        if( isset($inidata['depends']) ) $arr['depends'] = $inidata['depends'];

        $fn = cms_join_path($dir,'changelog.inc');
        if( is_file($fn) ) $arr['changelog'] = file_get_contents($fn);
        $fn = cms_join_path($dir,'doc/changelog.inc');
        if( is_file($fn) ) $arr['changelog'] = file_get_contents($fn);

        $fn = cms_join_path($dir,'help.inc');
        if( is_file($fn) ) $arr['help'] = file_get_contents($fn);
        $fn = cms_join_path($dir,'doc/help.inc');
        if( is_file($fn) ) $arr['help'] = file_get_contents($fn);

        $arr['has_meta'] = TRUE;
        return $arr;
    }

    /* return mixed array or null */
    private function _read_from_module(string $module_name)
    {
        $mod = (new ModuleOperations())->get_module_instance($module_name,'',TRUE);
        if( !is_object($mod) ) {
            // if the module is not installed, try to interrogate it anyway
            $path = cms_module_path($module_name);
            if ($path) {
                include $path;
                $module_name = '\\'.$module_name; //modules in global namespace
                $mod = new $module_name();
            }
        }
        if( is_object($mod) ) {
            $arr = [];
            $arr['name'] = $mod->GetName();
            $arr['description'] = $mod->GetDescription();
            if( $arr['description'] == '' ) $arr['description'] = $mod->GetAdminDescription();
            $arr['version'] = $mod->GetVersion();
            $arr['depends'] = $mod->GetDependencies();
            $arr['mincmsversion'] = $mod->MinimumCMSVersion();
            $arr['author'] = $mod->GetAuthor();
            $arr['authoremail'] = $mod->GetAuthorEmail();
            $arr['lazyloadadmin'] = $mod->LazyLoadAdmin();
            $arr['lazyloadfrontend'] = $mod->LazyLoadFrontend();
            $arr['help'] = $mod->GetHelp();
            $arr['changelog'] = $mod->GetChangelog();
            return $arr;
        }
    }

} // class
