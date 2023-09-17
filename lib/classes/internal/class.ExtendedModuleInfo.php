<?php
/*
Class to process extended module information
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
namespace CMSMS\internal;

use CMSMS\internal\ModuleInfo;
use CMSMS\LogicException;
use CMSMS\Lone;

class ExtendedModuleInfo extends ModuleInfo
{
    protected const EMPROPS = [
     'active', //c.f. parent::notavailable
     'admin_only',
//   'allow_admin_lazyload', //c.f. parent::lazyloadadmin
//   'allow_fe_lazyload', //c.f. parent::lazyloadfrontend
     'can_deactivate',
     'dependents', //names of modules that need this module c.f. parent::depends i.e. prerequisites
     'installed',
     'installed_version',
     'missingdeps',
     'status' //'installed' or absent
    ];

    protected $emdata = [];

    public function __construct($module_name,$can_load = false)
    {
        parent::__construct($module_name,$can_load);

        $ops = Lone::get('ModuleOperations');
        $minfo = $ops->GetInstalledModuleInfo();
        if( isset($minfo[$module_name]) ) {
            $this->emdata['active'] = (int)$minfo[$module_name]['active'];
            $this->emdata['admin_only'] = (int)($minfo[$module_name]['admin_only'] ?? 0);
//          $this->emdata['allow_fe_lazyload'] = (int)$minfo[$module_name]['allow_fe_lazyload'];
//          $this->emdata['allow_admin_lazyload'] = (int)$minfo[$module_name]['allow_admin_lazyload'];
            $this->emdata['can_deactivate'] = !in_array($module_name,['ConsoleAuth','ModuleManager']); // etc?
            $this->emdata['dependents'] = $minfo[$module_name]['dependents'] ?? [];
            $this->emdata['installed'] = 1;
            $this->emdata['installed_version'] = $minfo[$module_name]['version'];
            $this->emdata['status'] = 'installed';
        }
        else {
            $this->emdata['installed'] = 0;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetGet(/*mixed */$key)//: mixed
    {
        if( !in_array($key,self::EMPROPS) ) { return parent::OffsetGet($key); }
        if( isset($this->emdata[$key]) ) { return $this->emdata[$key]; }
        if( $key == 'missingdeps' ) {
            $out = [];
            $deps = $this['depends'];
            if( $empty($deps) ) {
                foreach( $deps as $mname => $mversion ) {
                    $minfo = new self($mname);
                    if( !$minfo['installed'] || version_compare($minfo['version'],$mversion) < 0 ) {
                        $out[$mname] = $mversion;
                    }
                }
                unset($minfo); // help the garbage collector
                $minfo = null;
            }
            return $out;
        }
        return null;
    }

    public function offsetSet(/*mixed */$key,$value): void
    {
        if( !in_array($key,self::EMPROPS) ) {
            parent::OffsetSet($key,$value);
        }
        switch( $key ) {
            case 'can_deactivate':
            case 'missingdeps':
                throw new LogicException('CMSEX_INVALIDMEMBERSET',null,$key);
            default:
                $this->emdata[$key] = $value;
        }
    }

    public function offsetExists(/*mixed */$key): bool
    {
        if( !in_array($key,self::EMPROPS) ) {
            return parent::OffsetExists($key);
        }
        return isset($this->emdata[$key]) || in_array($key, // some props are generated, not stored
            ['missingdeps']);
    }

    public function offsetUnset(/*mixed */$key): void
    {
        //nothing here
    }
}
