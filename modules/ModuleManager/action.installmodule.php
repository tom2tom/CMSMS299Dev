<?php
/*
Module Manager action: install module
Copyright (C) 2008-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of ModuleManager, an addon module for
CMS Made Simple to allow browsing remotely stored modules, viewing
information about them, and downloading or upgrading

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

use CMSMS\Crypto;
use CMSMS\DataException;
use CMSMS\Lone;
use ModuleManager\ModuleInfo;
use ModuleManager\ModuleRepClient;
use ModuleManager\Operations;
use ModuleManager\Utils;
use ModuleNoDataException;
use function CMSMS\log_error;

if( empty($this) || !($this instanceof ModuleManager) ) { exit; }
if( empty($gCms) ) { exit; }
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('modules');

if( isset($params['cancel']) ) {
    $this->SetInfo($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
}

try {
    $module_name = $params['name'] ?? '';
    $module_version = $params['version'] ?? '';
    $module_filename = $params['filename'] ?? '';
    $module_size = (int)($params['size'] ?? 0);
    if( !isset($params['doinstall']) ) {
        if( !$module_name || $module_version === '' || !$module_filename || $module_size < 100 ) {
            throw new DataException($this->Lang('error_missingparams'));
        }
    }

    if( isset($params['submit']) ) {
        // phase one... organize and download
        set_time_limit(9999);
        if( !empty($params['modlist']) ) {
            $modlist = unserialize(base64_decode($params['modlist']));
            if( !$modlist || !is_array($modlist) ) throw new DataException($this->Lang('error_missingparams'));

            // cache all of the xml files first... make sure we can download everything, and that it gets cached.
            foreach( $modlist as $key => $rec ) {
                if( $rec['action'] != 'i' && $rec['action'] != 'u' ) continue;
                if( !isset($rec['filename']) ) throw new DataException($this->Lang('error_missingparams'));
                if( !isset($rec['size']) ) throw new DataException($this->Lang('error_missingparams'));
                $filename = Utils::get_module_xml($rec['filename'],$rec['size']);
            }

            // expand all of the xml files.
            $ops = new Operations($this);
            foreach( $modlist as $key => &$rec ) {
                if( $rec['action'] != 'i' && $rec['action'] != 'u' ) continue;
                $xml_filename = Utils::get_module_xml($rec['filename'],$rec['size'],$rec['md5sum']??'');
                $rec['tmpfile'] = $xml_filename;
                $ops->expand_xml_package($xml_filename,true); //may throw ...
            }

            // now put this data into the session and redirect for the install part
            $key = '_'.Crypto::hash_string(__FILE__,true);
            $_SESSION[$key] = $modlist;
            $this->Redirect($id,'installmodule',$returnid,['doinstall'=>$key]);
        }
    }

    if( isset($params['doinstall']) ) {
        $key = trim($params['doinstall']);
        if( !isset($_SESSION[$key]) ) throw new LogicException('No doinstall data found in the session');

        set_time_limit(999);
        $modlist = $_SESSION[$key];
        if( !$modlist || !is_array($modlist) ) throw new LogicException('Invalid modlist data found in session');
        unset($_SESSION[$key]);

        // install/upgrade the modules that need to be installed or upgraded.
        $ops = Lone::get('ModuleOperations');
        foreach( $modlist as $name => $rec ) {
            switch( $rec['action'] ) {
            case 'i': // install
                $res = $ops->InstallModule($name);
                break;
            case 'u': // upgrade
                $res = $ops->UpgradeModule($name,$rec['version']);
                break;
            case 'a': // activate
                $res = $ops->ActivateModule($name);
                $res = [ $res ];
                break;
            }

            if( !is_array($res) || !$res[0] ) {
                log_error('Problem installing, upgrading or activating module',$name);
                debug_buffer('ERROR: problem installing/upgrading/activating '.$name);
                debug_buffer($rec,'action record');
                debug_buffer($res,'error info');
                throw new Exception($res[1] ?? 'Error processing module '.$name);
            }
        }

        // done, rest will be done when the module is loaded.
        $this->RedirectToAdminTab();
    }

    // recursive function to resolve dependencies given a module name and a module version
    $mod = $this;
    $resolve_deps = function($module_name,$module_version,$uselatest,$depth = 0) use (&$resolve_deps,&$mod): array {

        $array_to_hash = function(array $in,/*mixed */$key): array {
            $out = [];
            $idx = 0;
            foreach( $in as $rec ) {
                if( isset($rec[$key]) ) {
                    $out[$rec[$key]] = $rec;
                } else {
                    $out[$idx++] = $rec;
                }
            }
            return $out;
        };

        $extract_member = function(array $in,/*mixed */$key): array {
            $out = [];
            foreach( $in as $rec ) {
                if( isset($rec[$key]) ) $out[] = $rec[$key];
            }
            if( $out ) {
                $out = array_unique($out);
            }
            return $out;
        };

        [$res,$deps] = ModuleRepClient::get_module_dependencies($module_name,$module_version); // might throw
        if( $deps ) {
            $deps = $array_to_hash($deps,'name');
            $dep_module_names = $extract_member($deps,'name');
            $update_latest_deps = function(array $indeps,array $latest) use (&$mod): array {
                $out = [];
                foreach( $indeps as $name => $onedep ) {
                    if( isset($latest[$name]) ) {
                        $out[$name] = $latest[$name];
                    } else {
                        // module not in forge - might be an 'installer-bundled' module
                        if( !Lone::get('ModuleOperations')->IsBundledModule($name) ) {
                            // otherwise it's a problem
                            throw new Exception($mod->Lang('error_dependencynotfound2',$name,$onedep['version']));
                        }
                        $out[$name] = $onedep;
                    }
                }
                return $out;
            };

            if( $uselatest ) {
                // we want the latest of all of the dependencies.
                $latest = ModuleRepClient::get_modulelatest($dep_module_names);
                if( !$latest ) throw new Exception($this->Lang('error_dependencynotfound'));
                $latest = $array_to_hash($latest,'name');
                $deps = $update_latest_deps($deps,$latest);
            } else {
                $info = ModuleRepClient::get_multiple_moduleinfo($deps);
                $info = $array_to_hash($info,'name');
                $deps = $update_latest_deps($deps,$info);
            }

            foreach( $deps as $row ) {
                // now see if these dependencies, have dependencies.
                $child_deps = $resolve_deps($row['name'],$row['version'],$uselatest,$depth + 1);

                // grab the latest version of any duplicates
                if( $child_deps ) {
                    foreach( $child_deps as $child_name => $child_row ) {
                        if( !isset($deps[$child_name]) ) {
                            $deps[$child_name] = $child_row;
                        } else {
                            if( version_compare($deps[$child_name]['version'],$child_row['version']) < 0 ) $deps[$child_name] = $child_row;
                        }
                    }
                }
            }
        }

        return $deps;
    };

    /* algorithm
    given a desired module name, module version, and whether we want latest deps versions
    get module dependencies/prerequisites for the desired module version
    if we want latest versions of those
      get latest version info for all dependencies
      get module dependencies again as they may have changed
      merge results
    else
      get module info for all dependencies
    */
    // recursively (depth first) get the dependencies for the module+version we specified.
    $uselatest = (int)$this->GetPreference('latestdepends',1);
    $alldeps = $resolve_deps($module_name,$module_version,$uselatest);

    // get information for all dependencies, and make sure that they are all there.
    if( $alldeps ) {
        $res = [];
        try {
            if( $this->GetPreference('latestdepends',1) ) {
                // get the latest version of dependency (but not necessarily of the module we're installing)
                $res = ModuleRepClient::get_modulelatest(array_keys($alldeps));
                $new_deps = [];
            }
            else {
                // get the info for all dependencies
                $res = ModuleRepClient::get_multiple_moduleinfo($alldeps);
            }
        }
        catch (ModuleNoDataException $e) {
            // at least one of the dependencies could not be found on the server.
            // may be a system module... or if not, throw an exception
            log_error('At least one dependent module is not available from the forge',$module_name);
		    $this->ShowErrors($e->GetMessage());
        }

        foreach( $alldeps as $name => $row ) {
            $fnd = FALSE;
            if( $res ) {
                foreach( $res as $rec ) {
                    if( $rec['name'] != $name ) continue;
                    if( version_compare($row['version'],$rec['version']) <= 0 ) {
                        $fnd = TRUE;
                        $alldeps[$name] = $rec;
                        break;
                    }
                }
            }
        }
    }

    // add our current item into alldeps.
    $alldeps[$module_name] = ['name'=>$module_name,'version'=>$module_version,'filename'=>$module_filename,'size'=>$module_size];

    // remove items that are already installed (where installed version is greater or equal)
    // and create actions as to what we're going to do.
    if( $alldeps ) {
        $allmoduleinfo = ModuleInfo::get_all_module_info(FALSE); //from 'modules' cache if possible
        foreach( $alldeps as $name => &$rec ) {
            $rec['has_custom'] = FALSE;
            if( isset($allmoduleinfo[$name]) ) $rec['has_custom'] = ($allmoduleinfo[$name]['has_custom']) ? TRUE : FALSE;
            if( !isset($allmoduleinfo[$name]) ) {
                // install
                $rec['action']='i';
            }
            elseif( version_compare($allmoduleinfo[$name]['version'],$rec['version']) < 0 ) {
                // upgrade
                $rec['action']='u';
            }
            elseif( !$allmoduleinfo[$name]['active'] ) {
                // activate
                $rec['action']='a';
            }
            else {
                // already installed, do nothing.
                unset($alldeps[$name]);
            }
        }
    }

    // test to make sure we have the required info for each record.
    foreach( $alldeps as $mname => &$rec ) {
        if( $rec['action'] == 'a' ) continue; // if just activating we don't have to worry.
        if( !isset($rec['filename']) ) throw new DataException($this->Lang('error_missingmoduleinfo',$mname));
        if( !isset($rec['version']) ) throw new DataException($this->Lang('error_missingmoduleinfo',$mname));
        if( !isset($rec['size']) ) throw new DataException($this->Lang('error_missingmoduleinfo',$mname.' '.$rec['version']));
    }

    // here, if alldeps is empty... we have nothing to do.
    if( !$alldeps ) {
        $this->SetError($this->Lang('err_nothingtodo'));
        $this->RedirectToAdminTab();
    }

    $tpl = $smarty->createTemplate($this->GetTemplateResource('installinfo.tpl')); //,null,null,$smarty);

    $tpl->assign('return_url',$this->create_action_url($id,'defaultadmin',['__activetab'=>'modules']));
    $parms = ['name'=>$module_name,'version'=>$module_version,'filename'=>$module_filename,'size'=>$module_size];
    $tpl->assign('form_start',$this->CreateFormStart($id, 'installmodule', $returnid, 'post', '', FALSE, '', $parms).
       $this->CreateInputHidden($id,'modlist',base64_encode(serialize($alldeps))))
     ->assign('formend',$this->CreateFormEnd())
     ->assign('module_name',$module_name)
     ->assign('module_version',$module_version);
    $tmp = array_keys($alldeps);
    $n = count($tmp) - 1;
    $key = $tmp[$n];
    $action = $alldeps[$key]['action'];
    $tpl->assign('is_upgrade',($action == 'u')?1:0)
     ->assign('dependencies',$alldeps);

    $tpl->display();
}
catch (Throwable $t) {
    $this->SetError($t->GetMessage());
    $this->RedirectToAdminTab();
}
