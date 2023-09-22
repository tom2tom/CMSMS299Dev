<?php
/*
Class to process extra-extended module information
Copyright (C) 2017-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace ModuleManager;

use CMSMS\internal\ExtendedModuleInfo;
use CMSMS\internal\ModuleInfo as TopInfo;
use CMSMS\Lone;
use ModuleManager\ModuleRepClient;
use Throwable;
use function debug_display;

class ModuleInfo extends ExtendedModuleInfo // and thence CMSMS\internal\ModuleInfo
{
    private const MMPROPS = [
     'can_install',
     'can_uninstall',
     'can_upgrade',
     'deprecated',
     'e_status', // reflects a repository-check and/or db-check
     'missing_deps',
     'needs_upgrade', // aka 'need_upgrade' ?
//   'notinforge', // not (standalone at least) in the repository
     'stagnant', // released > 2 yrs ago, no upgrade
     'stale_upgrade', // latest upgrade released > 2 yrs ago
     'fresh_upgrade', // latest upgrade released <= 2 yrs ago
    ];

    private const DEPRECATED = ['MenuManager'];

    // static properties >> Lone props?
    private static $minfo;

    private static $allmodules;

    private static $load_stack = []; // circularity blocker

    protected $mmdata = []; // class properties

    /**
     * Constructor
     * @param string $modname
     * @param bool $can_load
     * @param bool $can_check_forge
     */
    public function __construct(string $modname, bool $can_load = true, bool $can_check_forge = true)
    {
        parent::__construct($modname,$can_load);

        if( $this['version'] && $this['installed_version'] ) {
            $tmp = version_compare($this['installed_version'],$this['version']);
            if( $tmp < 0  && $this['installed'] && $this['can_upgrade']) {
                $this['e_status'] = 'need_upgrade';
            }
            elseif( $tmp > 0 ) {
                $this['e_status'] = 'db_newer';
            }
            elseif( $can_check_forge ) {
                try {
                    $rep_info = ModuleRepClient::get_upgrade_module_info($modname);
                    if( $rep_info ) {
                        if( ($res = version_compare($this['version'],$rep_info['version'])) < 0 ) {
                            $this['e_status'] = 'newer_available';
                        }
                    }
                }
                catch( Throwable $t ) {
                    // nothing here
                }
            }
        }
    }

    /**
     * Get the name(s) of missing prerequisite module(s) of this one
     * @ignore
     * @return array maybe empty
     */
    private function _get_missing_dependencies()
    {
        $depends = $this['depends'];
        if( $depends ) {
            $out = [];
            foreach( $depends as $name => $ver ) {
                $rec = self::get_module_info($name);
                if( !is_object($rec) ) {
                    // problem getting module info for it
                    $out[$name] = $ver;
                    continue;
                }
                if( !$rec['installed'] || !$rec['active'] ) {
                    // dependent is not installed (or not active) so its missing
                    $out[$name] = $ver;
                    continue;
                }
                if( $rec['needs_upgrade'] ) {
                    // dependent needs upgrading, so it's a missing dependency
                    $out[$name] = $ver;
                    continue;
                }
                if( version_compare($rec['version'],$ver) >= 0 ) continue;
                $out[$name] = $ver;
            } // foreach
            return $out;
        }
        return [];
    }

    /**
     * Report whether all of this module's dependencies are installed
     *  and are of sufficient version
     * @ignore
     * @return bool
     */
    private function _check_dependencies(): bool
    {
        $missing = $this->_get_missing_dependencies();
        return !$missing;
    }

    /**
     *
     * @param bool $can_check_forge Default true
     * @return array
     */
    public static function get_all_module_info($can_check_forge = true)
    {
        if( is_array(self::$minfo) ) {
            return self::$minfo;
        }

        if( self::$allmodules === null ) {
            self::$allmodules = Lone::get('ModuleOperations')->FindAllModules();
        }

        $out = [];
        foreach( self::$allmodules as $modname ) {
            if( isset(self::$load_stack[$modname]) ) {
                continue; // no recursion
            }
            self::$load_stack[$modname] = 1;
            try {
                $info = new self($modname,true,$can_check_forge); // TODO something without props $minfo etc
                $out[$modname] = $info;
            }
            catch( Throwable $t ) {
                debug_display("'$modname' :: ".$t->GetMessage());
            }
            unset(self::$load_stack[$modname]);
        }
        self::$minfo = $out;
        return self::$minfo;
    }

    /**
     *
     * @param string $modname
     * @return array maybe empty
     */
    public static function get_module_info($modname)
    {
        $tmp = self::get_all_module_info();
        return $tmp[$modname] ?? [];
    }

    /**
     * Get some or all recorded module-properties
     * @since 3.0
     *
     * @param varargs array or series of property names, or nothing
     * @returns associative array
     */
    public function select_module_properties(...$wanted): array
    {
        if( $wanted ) {
            if( count($wanted) == 1 && is_array($wanted[0]) ) {
                $outkeys = array_flip($wanted[0]);
            }
            else {
                $outkeys = array_flip($wanted);
            }
        }
        else {
            $outkeys = [];
        }

        $out = array_merge_recursive($this->midata,$this->emdata,$this->mmdata);
        if ($outkeys) {
            $out = array_intersect_key($out,$outkeys);
        }

        foreach( self::MMPROPS as $key ) {
            if( !isset($out[$key]) && isset($outkeys[$key]) ) {
               try { $out[$key] = $this->OffsetGet($key); } catch (Throwable $t) {}
            }
        }
        // infill from parent
        foreach( parent::EMPROPS as $key ) {
            if( !isset($out[$key]) && isset($outkeys[$key]) ) {
                try { $out[$key] = parent::OffsetGet($key); } catch (Throwable $t) {}
            }
        }
        // infill from grandparent
        foreach( TopInfo::MIPROPS as $key ) {
            if( !isset($out[$key]) && isset($outkeys[$key]) ) {
                try { $out[$key] = TopInfo::OffsetGet($key); } catch (Throwable $t) {}
            }
        }
        return $out;
    }

    // ArrayInterface methods

    #[\ReturnTypeWillChange]
    public function offsetGet(/*mixed */$key)//: mixed
    {
        if( !in_array($key,self::MMPROPS) ) return parent::OffsetGet($key);
        if( isset($this->mmdata[$key]) ) return $this->mmdata[$key];

        switch( $key ) {
          case 'can_install':
            // can we install this module
            if( $this['installed'] ) return false;
            if( !$this['ver_compatible'] ) return false;
            return $this->_check_dependencies();
          case 'can_upgrade':
            // can we upgrade this module
            return $this->_check_dependencies();
          case 'needs_upgrade':
          case 'need_upgrade':
            // does this module need an upgrade
            // this only checks the data we have, does not calculate an extended status
            if( !$this['version'] || !$this['installed_version'] ) {
                // if not installed, it doesn't need an upgrade
                return false;
            }
            $tmp = version_compare($this['installed_version'],$this['version']);
            return ($tmp < 0);
          case 'can_uninstall':
            // can this module be uninstalled
            if( !$this['installed'] ) {
                return false;
            }
            $modname = $this['name'];
            // check for installed modules that are dependent upon this one
            foreach( self::$minfo as $minfo ) {
                if( $minfo['depends'] ) {
                    if( isset($minfo['depends'][$modname]) ) {
                        return false;
                    }
                }
            }
//            return parent::OffsetGet($key);
            return !in_array($modname,['ConsoleAuth','ModuleManager']);
          case 'missing_deps':
              // is any dependency missing
              return $this->_get_missing_dependencies();
          case 'deprecated':
              // is this module deprecated
              return in_array($this['name'],self::DEPRECATED);
/*        case 'notinforge':
              if( !$this['version'] || !$this['installed_version'] || $this['bundled'] ) {
                  return false; // ignore
              }
              $data = ModuleRepClient::get_repository_modules($this['name'],false,true); dunt work properly for v.old releases !
              return !$data[0] || !$data[1];
*/
          case 'stagnant':
/*
if ($this['name'] == 'MBVFaq') { // example: in-forge, very old
    $here = 1;
}
if ($this['name'] == 'News') { // example: in-forge, unbundled, recent
    $here = 1;
}
*/
              if( !$this['version'] || !$this['installed_version'] || $this['bundled'] ) {
                  return false; // ignore
              }
              //OR $rep_info = ModuleRepClient::get_repository_modules($this['name'],false,true);
              // then check [0][1] contents which is supposed to be the latest release BUT dunt work properly!
              if( !isset($rep_info) ) { $rep_info = ModuleRepClient::get_upgrade_module_info($this['name']); }
              if( !$rep_info ) {
                  return false; //TODO check ANY in-forge version? release-date of installed version in forge? support & check installation-date ?
              }
              $its = strtotime('now'); // TODO release-date of installed version | support & check installation-date
              if( !isset($stale_ts) ) { $stale_ts = strtotime('-2 years'); }
              return ($its < $stale_ts) && version_compare($rep_info['version'],$this['version'] <= 0);
          case 'stale_upgrade':
          case 'fresh_upgrade':
              if( !$this['version'] || !$this['installed_version'] || $this['bundled'] ) {
                  return false; // ignore
              }
              if( !isset($rep_info) ) { $rep_info = ModuleRepClient::get_upgrade_module_info($this['name']); }
              if( !$rep_info ) {
                  return false; // TODO N/A == no upgrade available ??
              }
              if (version_compare($rep_info['version'],$this['version'] <= 0)) {
                  return false; // no upgrade available
              }
              if( !isset($ts) ) { $ts = strtotime($rep_info['date']); }
              if( !isset($stale_ts) ) { $stale_ts = strtotime('-2 years'); }
              return ($key == 'stale_upgrade') ? ($ts < $stale_ts) : ($ts >= $stale_ts);
        } // switch
        return null;
    }

    public function offsetSet(/*mixed */$key,$value): void
    {
        if( !in_array($key,self::MMPROPS) ) parent::OffsetSet($key,$value);
        // only this may be set directly
        if( $key == 'e_status' ) {
            $this->mmdata[$key] = $value;
        }
    }

    public function offsetExists(/*mixed */$key): bool
    {
        if( !in_array($key,self::MMPROPS) ) {
            return parent::OffsetExists($key);
        }
        return isset($this->mmdata[$key]) || in_array($key,
            ['can_install', // some props are generated, never stored
             'can_uninstall',
             'can_upgrade',
             'deprecated',
             'missing_deps',
             'needs_upgrade',
             'need_upgrade',
//           'notinforge',
             'fresh_upgrade',
             'stale_upgrade',
             'stagnant']);
    }

    public function offsetUnset(/*mixed */$key): void
    {
    }
}