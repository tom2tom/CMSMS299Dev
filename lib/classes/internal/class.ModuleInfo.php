<?php
/*
Class to process module information
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
namespace CMSMS\internal;

use ArrayAccess;
use CMSMS\LogicException;
use CMSMS\Lone;
use const CMS_ASSETS_PATH;
use const CMS_VERSION;
use function cms_join_path;
use function cms_module_path;
use function is_directory_writable;

class ModuleInfo implements ArrayAccess
{
    protected const MIPROPS = [
     'about',
     'author',
     'authoremail',
     'changelog',
     'depends', // prerequisite module(s)
     'description',
     'dir', // module topmost-folder
     'has_custom',
     'has_meta',
     'help',
//   'lazyloadadmin',
//   'lazyloadfrontend',
     'mincmsversion',
     'name',
     'notavailable',
     'root_writable', // topmost-folder is writable
     'system_module',
     'ver_compatible',
     'version',
     'writable', // topmost-folder and all its contents are writable
    ];

    /**
     * @ignore
     */
    protected $midata = [];

    #[\ReturnTypeWillChange]
    public function __construct($modname,$can_load = true)
    {
        $arr = $this->_read_from_module_cache($modname);
        if( $arr ) {
            $arr2 = $this->_check_modulecustom($modname);
            $this->_setData(array_merge($arr2, $arr));
        } else {
            $arr['name'] = $modname;
            $this->_setData($arr);
//            $this->midata['notavailable'] = true;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)// : mixed
    {
        switch( $key ) {
//      case 'about':
//          return;
        case 'dir':
            return Lone::get('ModuleOperations')->get_module_path((string)$this->midata['name']);
        case 'writable':
            $dir = $this['dir'];
            if( $dir && is_dir($dir) ) {
                return is_directory_writable($dir);
            }
            return false;
        case 'root_writable':
            $dir = $this['dir'];
            return ($dir && is_writable($dir));
        case 'system_module':
            return Lone::get('ModuleOperations')->IsSystemModule((string)$this->midata['name']);
        case 'ver_compatible':
            return version_compare((string)$this['mincmsversion'],CMS_VERSION,'<=');
        default:
            if( in_array($key,self::MIPROPS) ) {
                return $this->midata[$key] ?? null;
            }
            throw new LogicException('CMSEX_INVALIDMEMBER',null,$key);
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($key,$value)// : void
    {
        switch( $key ) {
            case 'about':
            case 'dir':
            case 'has_custom':
            case 'root_writable':
            case 'ver_compatible':
            case 'writable':
                throw new LogicException('CMSEX_INVALIDMEMBERSET',$key);
            default:
                if( in_array($key,self::MIPROPS) ) {
                    $this->midata[$key] = $value;
                }
                throw new LogicException('CMSEX_INVALIDMEMBER',$key);
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($key)// : bool
    {
        if( !in_array($key,self::MIPROPS) ) {
            throw new LogicException('CMSEX_INVALIDMEMBER',null,$key);
        }
        return isset($this->midata[$key]) || in_array($key, // some props are generated, not stored
        ['dir',
         'root_writable',
         'system_module',
         'ver_compatible',
         'writable',
        ]);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key)// : void
    {
    }

    /**
     * @ignore
     * @param string $modname name of module whose props are wanted
     * @returns string
     */
    private function _get_module_file(string $modname) : string
    {
        return cms_module_path($modname);
    }

    /**
     * Set class properties. Any not in self::MIPROPS are ignored
     * @ignore
     * @param array $in properties-hash
     */
    private function _setData(array $in)
    {
        foreach( $in as $key => $value ) {
            if( in_array($key, self::MIPROPS) ) $this->midata[$key] = $value;
        }
    }

    /**
     * @ignore
     * @returns array ['has_custom'=>bool]
     */
    private function _check_modulecustom(string $modname) : array
    {
        $fn = cms_module_path($modname,true); //don't care about installation status
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

        $path = cms_join_path(CMS_ASSETS_PATH,'module_custom',$modname,'');
        $files = glob($path.'templates'.DIRECTORY_SEPARATOR.'*.tpl',GLOB_NOSORT);
        if( !$files ) {
            $files = glob($path.'lang'.DIRECTORY_SEPARATOR.'??_??.php',GLOB_NOSORT);
        }
        $has = count($files) > 0;
        return ['has_custom' => $has];
    }

    /**
     * Get data about a module, from cache supplemented by possibly-uninstalled present module
     * @ignore
     * @param string $modname name of module whose props are wanted
     * @returns array maybe empty
     */
    private function _read_from_module_cache(string $modname)
    {
        $data = Lone::get('LoadedData')->get('modules');
        if( $data ) {
            if( isset($data[$modname]) ) {
                if( /*$data[$modname]['status'] != 'installed' ||*/ !$data[$modname]['active'] ) {
                    return [];
                }
                if( isset($data[$modname][self::MIPROPS[1]]) ) { // anything not in raw table data
                    return $data[$modname];
                }
                $mod = Lone::get('ModuleOperations')->get_module_instance($modname);
                if( is_object($mod) ) {
                    unset($data[$modname]['version']); // we will use the version reported by the module
                    $data[$modname] += ['name' => $modname];

                    $arr = [];
                    $arr['description'] = $mod->GetDescription();
                    if( !$arr['description'] ) { $arr['description'] = $mod->GetAdminDescription(); }
                    $arr['version'] = $mod->GetVersion(); //might be different from database version
                    $arr['depends'] = $mod->GetDependencies();
                    $arr['mincmsversion'] = $mod->MinimumCMSVersion();
                    $arr['author'] = $mod->GetAuthor();
                    $arr['authoremail'] = $mod->GetAuthorEmail();
//                  $arr['lazyloadadmin'] = $mod->LazyLoadAdmin();
//                  $arr['lazyloadfrontend'] = $mod->LazyLoadFrontend();
                    $arr['help'] = $mod->GetHelp();
                    $arr['changelog'] = $mod->GetChangelog();

                    $data[$modname] += $arr;
//                  Lone::get('LoadedData')->set('modules', $data); BAD if available-module is not installed!
                    return $data[$modname];
                }
            }
        }
        return [];
    }
} // class

/*    private function _remove_module_meta(string  $modname )
    {
        $fn = $this->_get_module_meta_file( $modname );
        if( is_file($fn) && is_writable($fn) ) unlink($fn);
    }
*/
    /* return array maybe empty */
/*    private function _read_from_module_meta(string $modname)
    {
        $dir = Lone::get('ModuleOperations')->get_module_path($modname);
        $fn = $this->_get_module_meta_file($modname);
        if( !is_file($fn) ) return [];
        $inidata = @parse_ini_file($fn,true);
        if( !$inidata ) return [];
        if( !isset($inidata['module']) ) return [];

        $data = $inidata['module'];
        $arr = [];
        $arr['name'] = trim($data['name'] ?? $modname);
        $arr['version'] = trim($data['version'] ?? '0.0.1');
        $arr['description'] = trim($data['description'] ?? '');
        $arr['author'] = trim($data['author'] ?? lang('notspecified'));
        $arr['authoremail'] = trim($data['authoremail'] ?? lang('notspecified'));
        $arr['mincmsversion'] = trim($data['mincmsversion'] ?? CMS_VERSION);
        $arr['lazyloadadmin'] = cms_to_bool($data['lazyloadadmin'] ?? false);
        $arr['lazyloadfrontend'] = cms_to_bool($data['lazyloadfrontend'] ?? false);

        if( isset($inidata['depends']) ) {
            $arr['depends'] = $inidata['depends'];
        }

        foreach( [
            'changelog.inc',
            'changelog.htm',
            'doc'.DIRECTORY_SEPARATOR.'changelog.inc',
            'doc'.DIRECTORY_SEPARATOR.'changelog.htm',
        ] as $one ) {
            $path = cms_join_path($dir,$one);
            if( is_file($path) ) {
                $arr['changelog'] = file_get_contents($path);
                break;
            }
        }

        foreach( [
            'modhelp.inc',
            'modhelp.htm',
            'help.inc',
            'help.htm',
            'doc'.DIRECTORY_SEPARATOR.'modhelp.inc',
            'doc'.DIRECTORY_SEPARATOR.'modhelp.htm',
            'doc'.DIRECTORY_SEPARATOR.'help.inc',
            'doc'.DIRECTORY_SEPARATOR.'help.htm',
        ] as $one ) {
            $path = cms_join_path($dir,$one);
            if( is_file($path) ) {
                $arr['help'] = file_get_contents($path);
                break;
            }
        }
        if( !isset($arr['help']) ) {
        //TODO $mod = ; $arr['help'] = $mod->GetHelp();
        }

        $arr['has_meta'] = true;
        return $arr;
    }

    /* return array maybe empty * /
/*
    private function _read_from_module(string $modname)
    {
        $mod = Lone::get('ModuleOperations')->get_module_instance($modname, '', true);
        if( !is_object($mod) ) {
            // if the module is not installed, try to interrogate it anyway
            $path = cms_module_path($modname);
            if ($path) {
                include $path;
                $class_name = '\\'.$modname; //modules in global namespace
                $mod = new $class_name();
            }
        }
        if( is_object($mod) ) {
            $arr = [];
            $arr['name'] = $mod->GetName();
            $arr['description'] = $mod->GetDescription();
            if( !$arr['description'] ) $arr['description'] = $mod->GetAdminDescription();
            $arr['version'] = $mod->GetVersion();
            $arr['depends'] = $mod->GetDependencies();
            $arr['mincmsversion'] = $mod->MinimumCMSVersion();
            $arr['author'] = $mod->GetAuthor();
            $arr['authoremail'] = $mod->GetAuthorEmail();
//            $arr['lazyloadadmin'] = $mod->LazyLoadAdmin();
//            $arr['lazyloadfrontend'] = $mod->LazyLoadFrontend();
            $arr['help'] = $mod->GetHelp();
            $arr['changelog'] = $mod->GetChangelog();
            return $arr;
        }
        return [];
    }
*/
/*  private function _get_module_meta_file( string $modname ) : string
    {
        $path = cms_module_path($modname);
        if( $path ) {
            return str_replace($modname.'.module.php','moduleinfo.ini',$path);
        }
        return '';
    }
*/
