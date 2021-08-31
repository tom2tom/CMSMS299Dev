<?php
/*
ModuleManager class: Utils
Copyright (C) 2011-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

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
use CMSMS\SingleItem;
use CMSMS\Utils as AppUtils;
use ModuleManager\CachedRequest;
use ModuleManager\ModuleRepClient;
use RuntimeException;
use const CMS_VERSION;
use const MINIMUM_REPOSITORY_VERSION;
use function cms_join_path;

final class Utils
{
    /**
     * @ignore
     */
    private function __construct() {}
    private function __clone() {}

    /**
     *
     * @param bool $include_inactive Whether to also report inactive modules. Default false
     * @param bool $as_hash Whether returned array keys are respective module-names. Default false
     * @return array
     */
    public static function get_installed_modules($include_inactive = false, $as_hash = false)
    {
        $modops = SingleItem::ModuleOperations();
        $module_list = $modops->GetInstalledModules($include_inactive); // available | all

        $results = [];
        foreach( $module_list as $modname ) {
            $mod = $modops->get_module_instance($modname);
            if( !$mod ) continue;

            $details = [];
            $details['name'] = $mod->GetName();
            $details['description'] = $mod->GetDescription();
            $details['version'] = $mod->GetVersion();
            $details['active'] = $modops->IsModuleActive($modname);

            if( $as_hash ) {
                $results[$modname] = $details;
            }
            else {
                $results[] = $details;
            }
        }
        return [true,$results];
    }

    private static function uasort_cmp_details($e1, $e2)
    {
        if( is_object($e1) ) {
            $n1 = $e1->name;
            $v1 = $e1->version;
        }
        else {
            $n1 = $e1['name'];
            $v1 = $e1['version'];
        }
        if( is_object($e2) ) {
            $n2 = $e2->name;
            $v2 = $e2->version;
        }
        else {
            $n2 = $e2['name'];
            $v2 = $e2['version'];
        }

        $r = strcasecmp($n1,$n2);
        if( $r !== 0 ) {
            return $r <=> 0;
        }
        return version_compare($v2, $v1);
    }

    /**
     *
     * @param type $xmldetails
     * @param type $installdetails
     * @param type $newest
     * @return mixed array|null
     */
    public static function build_module_data(&$xmldetails, &$installdetails, $newest = true)
    {
        if( !is_array($xmldetails) ) return;

        // sort
        uasort($xmldetails, 'ModuleManager\Utils::uasort_cmp_details');

        $mod = AppUtils::get_module('ModuleManager');

        // Process the xmldetails, and only keep the latest version
        // of each (according to a preference)
        //
        // Note: should be redundant with 1.2, but kept in here for
        // a while just in case..
        if( $newest && $mod->GetPreference('onlynewest',1) == 1 ) {
            $thexmldetails = [];
            $prev = '';
            foreach( $xmldetails as $det ) {
                if( is_array($prev) && $prev['name'] == $det['name'] ) continue;

                $prev = $det;
                $thexmldetails[] = $det;
            }
            $xmldetails = $thexmldetails;
        }

        $results = [];
        foreach( $xmldetails as $det1 ) {
            $found = 0;
            foreach( $installdetails as $det2 ) {
                if( $det1['name'] == $det2['name'] ) {
                    $found = 1;
                    // if the version of the xml file is greater than that of the
                    // installed module, we have an upgrade
                    $res = version_compare($det1['version'], $det2['version']);
                    if( $res == 1 ) {
                        $det1['status'] = 'upgrade';
                    }
                    elseif( $res == 0 ) {
                        $det1['status'] = 'uptodate';
                    }
                    else {
                        $det1['status'] = 'newerversion';
                    }

                    $results[] = $det1;
                    break;
                }
            }
            if( $found == 0 ) {
                // we don't have this module installed
                $det1['status'] = 'notinstalled';
                $results[] = $det1;
            }
        }

        //
        // Do a third loop
        // and check min and max cms version
        //
        $results2 = [];
        foreach( $results as $oneresult ) {
            if( (!empty($oneresult['maxcmsversion']) && version_compare(CMS_VERSION,$oneresult['maxcmsversion']) > 0) ||
                (!empty($oneresult['mincmsversion']) && version_compare(CMS_VERSION,$oneresult['mincmsversion']) < 0) ) {
                $oneresult['status'] = 'incompatible';
            }
            $results2[] = $oneresult;
        }
        $results = $results2;

        // now we have everything
        // let's try sorting it
        uasort($results, 'ModuleManager\Utils::uasort_cmp_details');
        return $results;
    }

    /**
     *
     * @param type $filename
     * @param type $size
     * @param type $md5sum
     * @return string
     * @throws CommunicationException or RuntimeException
     */
    public static function get_module_xml($filename,$size,$md5sum = null)
    {
        $mod = AppUtils::get_module('ModuleManager');
        $xml_filename = ModuleRepClient::get_repository_xml($filename,$size);
        if( !$xml_filename ) throw new CommunicationException($mod->Lang('error_downloadxml',$filename));

        if( !$md5sum ) $md5sum = ModuleRepClient::get_module_md5($filename);
        $dl_md5 = md5_file($xml_filename);

        if( $md5sum != $dl_md5 ) {
            @unlink($xml_filename);
            throw new RuntimeException($mod->Lang('error_checksum',[$server_md5,$dl_md5]));
        }

        return $xml_filename;
    }

    /**
     *
     * @staticvar bool $ok
     * @return boolean
     */
    public static function is_connection_ok()
    {
        // static properties here >> SingleItem property|ies ?
        static $ok = -1;
        if( $ok != -1 ) return $ok;

        $mod = AppUtils::get_module('ModuleManager');
        $url = $mod->GetPreference('module_repository');
        if( $url ) {
            $url .= '/version';
            $req = new CachedRequest($url);
//          $req->setTimeout(10); use default
            $req->execute($url);
            if( $req->getStatus() == 200 ) {
                $result = $req->getResult();
                if( $result ) {
                    $data = json_decode($result,true);
                    if( $data && version_compare($data,MINIMUM_REPOSITORY_VERSION) >= 0 ) {
                        $ok = TRUE;
                        return TRUE;
                    }
                }
            }
            $req->clearCache();
        }
        $ok = FALSE;
        return FALSE;
    }

    /**
     *
     * @param string $date
     * @return mixed string|null
     */
    public static function get_status($date)
    {
        $ts = strtotime($date);
        $stale_ts = strtotime('-2 years');
        $warn_ts = strtotime('-18 months');
        $new_ts = strtotime('-1 month');
        if( $ts <= $stale_ts ) return 'stale';
        if( $ts <= $warn_ts ) return 'warn';
        if( $ts >= $new_ts ) return 'new';
    }

    /**
     * set smarty vars for various image tags
     */
    public static function get_images($template)
    {
        $mod = AppUtils::get_module('ModuleManager');
        $base = cms_join_path($mod->GetModulePath(),'images').DIRECTORY_SEPARATOR;
        $themeObject = AppUtils::get_theme_object();

        foreach ([
            ['error','stale'],
            ['puzzle','missingdeps'],
            ['warn','warning'],
            ['new','new'],
            ['star','star'],
            ['system','system'],
            ['deprecated','deprecated'],
        ] as &$one) {
            $path = $base.$one[0];
            $title = $mod->Lang('title_'.$one[1]);
            $img = $themeObject->DisplayImage($path, $one[1], '20', '20', null, ['title'=>$title]);
            $template->assign($one[1].'_img',$img);
        }
        unset ($one);
    }
} // class
