<?php
# ModuleManager class: Utils
# Copyright (C) 2011-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace ModuleManager;

use CmsCommunicationException;
use CmsInvalidDataException;
use CMSMS\ModuleOperations;
use CMSMS\Utils as AppUtils;
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
    public static function get_installed_modules($include_inactive = FALSE, $as_hash = FALSE)
    {
        $modops = ModuleOperations::get_instance();
        $module_list = $modops->GetInstalledModules($include_inactive);

        $results = [];
        foreach( $module_list as $module_name ) {
            $inst = $modops->get_module_instance($module_name);
            if( !$inst ) continue;

            $details = [];
            $details['name'] = $inst->GetName();
            $details['description'] = $inst->GetDescription();
            $details['version'] = $inst->GetVersion();
            $details['active'] = $modops->IsModuleActive($module_name);

            if( $as_hash ) {
                $results[$module_name] = $details;
            }
            else {
                $results[] = $details;
            }
        }
        return [true,$results];
    }

    private static function uasort_cmp_details( $e1, $e2 )
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
        if( $r < 0 ) {
            return -1;
        }
        elseif( $r > 0 ) {
            return 1;
        }
        return version_compare( $v2, $v1 );
    }

    /**
     *
     * @param type $xmldetails
     * @param type $installdetails
     * @param type $newest
     * @return mixed array|null
     */
    public static function build_module_data( &$xmldetails, &$installdetails, $newest = true )
    {
        if( !is_array($xmldetails) ) return;

        // sort
        uasort( $xmldetails, 'ModuleManager\\Utils::uasort_cmp_details' );

        $mod = AppUtils::get_module('ModuleManager');

        //
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
                    $res = version_compare( $det1['version'], $det2['version'] );
                    if( $res == 1 ) {
                        $det1['status'] = 'upgrade';
                    }
                    else if( $res == 0 ) {
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
        uasort( $results, 'ModuleManager\\Utils::uasort_cmp_details' );
        return $results;
    }

    /**
     *
     * @param type $filename
     * @param type $size
     * @param type $md5sum
     * @return string
     * @throws CmsCommunicationException
     * @throws CmsInvalidDataException
     */
    public static function get_module_xml($filename,$size,$md5sum = null)
    {
        $mod = AppUtils::get_module('ModuleManager');
        $xml_filename = modulerep_client::get_repository_xml($filename,$size);
        if( !$xml_filename ) throw new CmsCommunicationException($mod->Lang('error_downloadxml',$filename));

        if( !$md5sum ) $md5sum = modulerep_client::get_module_md5($filename);
        $dl_md5 = md5_file($xml_filename);

        if( $md5sum != $dl_md5 ) {
            @unlink($xml_filename);
            throw new CmsInvalidDataException($mod->Lang('error_checksum',[$server_md5,$dl_md5]));
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
        // static properties here >> StaticProperties class ?
        static $ok = -1;
        if( $ok != -1 ) return $ok;

        $mod = AppUtils::get_module('ModuleManager');
        $url = $mod->GetPreference('module_repository');
        if( $url ) {
            $url .= '/version';
            $req = new cached_request($url);
            $req->setTimeout(10);
            $req->execute($url);
            if( $req->getStatus() == 200 ) {
                $tmp = $req->getResult();
                if( empty($tmp) ) {
                    $req->clearCache();
                    $ok = FALSE;
                    return FALSE;
                }

                $data = json_decode($req->getResult(),true);
                if( version_compare($data,MINIMUM_REPOSITORY_VERSION) >= 0 ) {
                    $ok = TRUE;
                    return TRUE;
                }
            }
            else {
                $req->clearCache();
            }
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
