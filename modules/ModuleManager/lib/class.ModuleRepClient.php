<?php
/*
ModuleManager class: engagement with CMSMS modules repository/forge
Copyright (C) 2011-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace ModuleManager;

use CMSMS\CommunicationException;
use CMSMS\Crypto;
use CMSMS\DataException;
use CMSMS\HttpRequest;
use CMSMS\SingleItem;
use CMSMS\Utils;
use Exception;
use ModuleManager\CachedRequest;
use ModuleManager\ModuleInfo;
use const CMS_VERSION;
use const TMP_CACHE_LOCATION;

class ModuleManagerException extends Exception {}
class ModuleNoDataException extends ModuleManagerException {}
class ModuleNotFoundException extends ModuleManagerException {}

final class ModuleRepClient
{
    // static properties here >> SingleItem property|ies ?
    private static $_latest_installed_modules;

    private function __construct() {}

    /**
     *
     * @return array 2 members
     * [0] = bool indicating success
     * [1] = mixed result | error message string
     */
    public static function get_repository_version()
    {
        $mod = Utils::get_module('ModuleManager');
        $url = $mod->GetPreference('module_repository');
        if( !$url ) return [FALSE,$mod->Lang('error_norepositoryurl')];
        $url .= '/version';

        $req = new CachedRequest();
        $req->execute($url);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status != 200 || $result == '' ) return [FALSE,$mod->Lang('error_request_problem')];

        $data = json_decode($result,TRUE);
        return [TRUE,$data];
    }

    /**
     * Given an array of hashes with name/version members return module info for all matches.
     * maximum of 25 rows, and no guarantee that there will be results for each request.
     */
    public static function get_multiple_moduleinfo($input)
    {
        $mod = Utils::get_module('ModuleManager');
        if( !$input || !is_array($input) ) throw new RuntimeException($mod->Lang('error_missingparam'));

        $out = [];
        foreach( $input as $key => $data ) {
            if( is_array($data) && isset($data['name']) && isset($data['version']) && $data['name'] && $data['version'] ) {
                $out[] = ['name'=>$data['name'],'version'=>$data['version']];
            }
            elseif( is_string($key) && (int)$key == 0 ) {
                $out[] = ['name'=>$key,'version'=>$data];
            }
            else {
                throw new DataException($mod->Lang('error_missingparam'));
            }
        }
        if( !$out ) throw new DataException($mod->Lang('error_missingparam'));

        $url = $mod->GetPreference('module_repository');
        if( !$url ) return [FALSE,$mod->Lang('error_norepositoryurl')];
        $url .= '/multimoduleinfo';
        $data = ['data'=>json_encode($out)];

        $req = new CachedRequest();
        $req->execute($url,$data);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 400 ) {
            return;
        }
        elseif( $status != 200 || $result == '' ) {
            throw new CommunicationException($mod->Lang('error_request_problem'));
        }

        return json_decode($result,TRUE);
    }

    /**
     *
     * @param string $prefix
     * @param int $newest optional bool-equivalent? default 1
     * @param bool $exact optional flag default false
     * @return array 2 members
     * [0] = bool indicating success
     * [1] = mixed retrieved data | error message string
     */
    public static function get_repository_modules($prefix = '',$newest = 1,$exact = FALSE)
    {
        $mod = Utils::get_module('ModuleManager');
        $url = $mod->GetPreference('module_repository');
        if( !$url ) return [FALSE,$mod->Lang('error_norepositoryurl')];
        $url .= '/moduledetailsgetall';

        $data = ['newest'=>$newest];
        if( $prefix ) $data['prefix'] = ltrim($prefix);
        if( $exact ) $data['exact'] = 1;
        $data['clientcmsversion'] = CMS_VERSION;

        $req = new CachedRequest();
        $req->execute($url,$data);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 400 ) {
            return [TRUE,[]];
        }
        elseif( $status != 200 || $result == '' ) {
            return [FALSE,$mod->Lang('error_request_problem')];
        }

        $data = json_decode($result,TRUE);
        return [TRUE,$data];
    }

    /**
     *
     * @param string $modname
     * @param string $module_version
     * @return array maybe empty
     * @throws DataException or CommunicationException
     */
    public static function get_module_dependencies($modname,$module_version = '')
    {
        $mod = Utils::get_module('ModuleManager');
        if( !$modname ) throw new DataException($mod->Lang('error_missingparams'));
        $url = $mod->GetPreference('module_repository');
        if( $url == '' ) throw new DataException($mod->Lang('error_norepositoryurl'));
        $url .= '/moduledependencies';

        $parms = ['name'=>$modname];
        if( $module_version ) $parms['version'] = $module_version;
        $req = new CachedRequest();
        $req->execute($url,$parms);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 400 ) {
            // no dependencies found
            return [];
        }
        elseif( $status != 200 || $result == '' ) {
            throw new CommunicationException($mod->Lang('error_request_problem'));
        }
        return json_decode($result,TRUE);
    }

    /**
     *
     * @param string $xmlfile name of module XML-source
     * @return array maybe empty
     * @throws DataException or CommunicationException
     */
    public static function get_module_depends($xmlfile)
    {
        $mod = Utils::get_module('ModuleManager');
        if( !$xmlfile ) throw new DataException($mod->Lang('error_nofilename'));
        $url = $mod->GetPreference('module_repository');
        if( $url == '' ) throw new DataException($mod->Lang('error_norepositoryurl'));
        $url .= '/moduledepends';

        $req = new CachedRequest();
        $req->execute($url,['name'=>$xmlfile]);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 400 ) return [];
        if( $status != 200 || $result == '' ) {
            throw new CommunicationException($mod->Lang('error_request_problem'));
        }
        return json_decode($result,TRUE);
    }

    /**
     *
     * @param string $xmlfile name of TBA
     * @param int $size size of download-chunks
     * @return false | string
     */
    public static function get_repository_xml($xmlfile, $size = -1)
    {
        if( !$xmlfile ) return FALSE;

        // this is manually cached.
        $tmpname = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'modmgr_'.Crypto::hash_string(__DIR__.$xmlfile).'.dat';
        $mod = Utils::get_module('ModuleManager');
        if( !file_exists($tmpname) || $mod->GetPreference('disable_caching',0) || (time() - filemtime($tmpname)) > 7200 ) {
            @unlink($tmpname);

            // must download
            $orig_chunksize = $mod->GetPreference('dl_chunksize',256);
            $chunksize = $orig_chunksize * 1024;
            $url = $mod->GetPreference('module_repository');
            if( $url == '' ) return FALSE;

            if( $size <= $chunksize ) {
                // downloading the whole file at one shot.
                $url .= '/modulexml';
                $req = new HttpRequest();
                $req->execute($url,'','POST',['name'=>$xmlfile]);
                $status = $req->GetStatus();
                $result = $req->GetResult();
                if( $status != 200 || $result == '' ) {
                    $req->clear();
                    return FALSE;
                }
                $fh = fopen($tmpname,'w');
                fwrite($fh,$result);
                fclose($fh);
                $req->clear();
                return $tmpname;
            }

            // download in chunks
            $url .= '/modulegetpart';
            $nchunks = (int)ceil($size / $chunksize);
            $req = new HttpRequest();
            for( $i = 0; $i < $nchunks; $i++ ) {
                $req->execute($url,'','POST', ['name'=>$xmlfile,'partnum'=>$i,'sizekb'=>$orig_chunksize]);
                $status = $req->GetStatus();
                $result = $req->GetResult();
                if( $status != 200 || $result == '' ) {
                    unlink($tmpname);
                    $req->clear();
                    return FALSE;
                }

                $fh = fopen($tmpname,'a');
                fwrite($fh,base64_decode($result));
                fclose($fh);
                $req->clear();
            }
        }

        return $tmpname;
    }

    /**
     *
     * @param string $xmlfile name of TBA
     * @return type
     * @throws DataException
     * @throws CommunicationException
     */
    public static function get_module_md5($xmlfile)
    {
        $mod = Utils::get_module('ModuleManager');
        if( !$xmlfile ) {
            throw new DataException($mod->Lang('error_nofilename'));
        }
        $url = $mod->GetPreference('module_repository');
        if( $url == '' ) {
            throw new DataException($mod->Lang('error_norepositoryurl'));
        }
        $url .= '/modulemd5sum';

        $req = new CachedRequest();
        $req->execute($url,['name'=>$xmlfile]);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status != 200 || $result == '' ) {
            throw new CommunicationException($mod->Lang('error_request_problem'));
        }
        return json_decode($result,TRUE);
    }

    /**
     *
     * @param type $term
     * @param int $advanced
     * @return array 2 members
     * [0] = bool indicating success
     * [1] = mixed data | null | error message string
     */
    public static function search($term,$advanced)
    {
        $mod = Utils::get_module('ModuleManager');
        $url = $mod->GetPreference('module_repository');
        if( $url == '' ) return [FALSE,$mod->Lang('error_norepositoryurl')];
        $url .= '/modulesearch';

        $filter = [
         'term' => $term,
         'advanced' => (int)$advanced,
         'newest' => 1,
         'sortby' => 'score',
        ];
        $qparms = [
         'filter' => $filter,
         'clientcmsversion' => CMS_VERSION,
        ];

        $req = new CachedRequest();
        $req->execute($url,['json'=>json_encode($qparms)]);
        $status = $req->getStatus();
        $result = $req->getResult();
        if( $status == 200 && $result == ''  ) return [TRUE,NULL]; // no results.
        if( $status != 200 || $result == '' ) return [FALSE,$mod->Lang('error_request_problem')];

        $data = json_decode($result,TRUE);
        return [TRUE,$data];
    }

    /**
     * Return the latest info about all specified modules
     * on success returns associative array of info about modules
     * on error throws an exception.
     *
     * @param string[] $modules Array of module-names whose info is wanted
     * @return array
     */
    public static function get_modulelatest($modules)
    {
        $mod = Utils::get_module('ModuleManager');
        if( !$modules || !is_array($modules) ) {
            throw new DataException($mod->Lang('error_missingparam'));
        }
        $url = $mod->GetPreference('module_repository');
        if( !$url ) {
            throw new DataException($mod->Lang('error_norepositoryurl'));
        }
        $qparms = [];
        $qparms['names'] =  implode(',',$modules);
        $qparms['newest'] = '1';
        $qparms['clientcmsversion'] = CMS_VERSION;
        $url .= '/upgradelistgetall';
        $req = new CachedRequest();
        $req->execute($url,$qparms);
        $status = $req->getStatus();
        if( $status != 200 ) {
            throw new CommunicationException($mod->Lang('error_request_problem'));
        }
        $result = $req->getResult();
        if( $status == 400 || !$result ) {
            throw new ModuleNoDataException();
        }

        $data = json_decode($result,TRUE);
        if( !$data || !is_array($data) ) {
            throw new Exception($mod->Lang('error_nomatchingmodules'));
        }
        return $data;
    }

    /**
     * Return the latest info about installed modules.
     * on success returns associative array of info about modules
     * on error throw exception.
     *
     * @return array
     */
    public static function get_allmoduleversions()
    {
        if( self::$_latest_installed_modules  === NULL ) {
            $availmodules = SingleItem::ModuleOperations()->GetInstalledModules();
            self::$_latest_installed_modules = self::get_modulelatest($availmodules);
        }
        return self::$_latest_installed_modules;
    }

    /**
     * Return info about installed modules that have newer versions available
     *
     * @return mixed (false on error, null or associative array on success
     */
    public static function get_newmoduleversions()
    {
        $versions = self::get_allmoduleversions();
        if( !is_array($versions) ) return FALSE;
        if( count($versions) == 2 && $versions[0] === FALSE ) return FALSE;

        $out = [];
        foreach( $versions as $row ) {
            $info = ModuleInfo::get_module_info( $row['name'] );
            if( version_compare($row['version'],$info['version']) > 0 ) {
                $data = [];
                $out[$row['name']] = $row;
            }
        }
        if( $out ) return $out;
    }

    /**
     * Return info about named module versions.
     *
     * @param string $modname
     * @return mixed false | null | array
     */
    public static function get_upgrade_module_info($modname)
    {
        $versions = self::get_allmoduleversions();
        if( !is_array($versions) ) return FALSE;
        if( count($versions) == 2 && $versions[0] === FALSE ) return FALSE;

        foreach( $versions as $row ) {
            if( $row['name'] == $modname ) return $row;
        }
    }
} // class
