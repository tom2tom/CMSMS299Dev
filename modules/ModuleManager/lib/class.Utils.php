<?php
/*
ModuleManager class: Utils
Copyright (C) 2011-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\CommunicationException;
//use CMSMS\HttpRequest;
use CMSMS\Lone;
use CMSMS\Utils as AppUtils;
use ModuleManager\CachedRequest;
use ModuleManager\ModuleRepClient;
use RuntimeException;
use const CMS_VERSION;
use function cms_join_path;

final class Utils
{
    /**
     * @ignore
     */
    private function __construct() {}
    #[\ReturnTypeWillChange]
    private function __clone() {}// : void {}

    /**
     * Return information about installed modules
     *
     * @param bool $include_inactive Whether to also report inactive modules. Default false
     * @param bool $as_hash Whether returned array keys are respective module-names. Default false
     * @return array
     * [0] = true always
     * [1] = array of arrays
     */
    public static function get_installed_modules(bool $include_inactive = FALSE,bool $as_hash = FALSE)
    {
        $modops = Lone::get('ModuleOperations');
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

    /**
     * Custom sort method
     * @internal
     *
     * @param mixed $e1 object | array
     * @param mixed $e2 object | array
     * @return int
     */
    private static function uasort_cmp_details($e1,$e2) : int
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
        return version_compare($v2,$v1);
    }

    /**
     * Reconcile installed and in-forge modules (the latter from
     *  ModuleRepClient::get_repository_modules())
     * @param array $xmldetails Reference to array of forge data for a
     * series of modules e.g. all named like A*, each member an array like
     *  'name' => 'AceSyntax'
     *  'filename' => 'AceSyntax-1.0.1.xml'
     *  'md5sum' => '53358ac108bf203ccc10bc5dd1147cd5'
     *  'version' => '1.0.1'
     *  'mincmsversion' => '2.1'
     *  'description' => 'AceSyntax is a syntax highlighter module using <strong>Ace</strong> a standalone code editor written in JavaScript.'	string
     *  'date' => '2019-11-09 10:39:59'
     *  'size' => '20132483'
     *  'downloads' => 0
     * @param array $installdetails Reference to array of installed-modules
     * data each member an array like
     *  'name' => 'AdminSearch'
     *  'description' => ''
     *  'version' => '1.2'
     *  'active' => true
     * @param bool $newest Optional flag whether to retrieve the latest available version
     * @return array maybe empty
     */
    public static function build_module_data(array &$xmldetails,array &$installdetails,bool $newest = TRUE)
    {
        if( !is_array($xmldetails) ) return [];
        // sort
        uasort($xmldetails,'ModuleManager\Utils::uasort_cmp_details');

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
                    $res = version_compare($det1['version'],$det2['version']);
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
        uasort($results,'ModuleManager\Utils::uasort_cmp_details');
        return $results;
    }

    /**
     * Report whether the md5sum signature of the supplied file is valid
     *
     * @param string $filename
     * @param int $size byte-size of download chunks
     * @param string $md5sum Optional expected checksum of the retrieved file
     * @return string downloaded xmlfile name if test is passed
     * @throws CommunicationException or RuntimeException
     */
    public static function get_module_xml(string $filename,int $size,string $md5sum = '') : string
    {
        $mod = AppUtils::get_module('ModuleManager');
        $xml_filename = ModuleRepClient::get_repository_xml($filename,$size);
        if( !$xml_filename ) throw new CommunicationException($mod->Lang('error_downloadxml',$filename));

        if( !$md5sum ) { $md5sum = ModuleRepClient::get_module_md5($filename); }
        $dl_md5 = md5_file($xml_filename);

        if( $md5sum != $dl_md5 ) {
            @unlink($xml_filename);
            throw new RuntimeException($mod->Lang('error_checksum',[$md5sum,$dl_md5]));
        }
        return $xml_filename;
    }

    /**
     * Report whether the Forge backend connection is usable
     *
     * @staticvar bool $ok
     * @return boolean
     */
    public static function is_connection_ok() : bool
    {
        // static properties here >> Lone property|ies ?
        static $ok = -1;
        if( $ok != -1 ) { return $ok; }

        list($res,$data) = ModuleRepClient::get_repository_version(); //TODO
        if( $res ) {
            $mod = AppUtils::get_module('ModuleManager');
            $ok = (version_compare($data,$mod::MIN_FORGE_VERSION) >= 0);
            return $ok;
        }
        else {
            $ok = FALSE;
            return FALSE;
        }

        $mod = AppUtils::get_module('ModuleManager');
        $url = $mod->GetPreference('module_repository');
        if( $url ) {
            $url .= '/version';
/*          $req = new HttpRequest();
            $req->setTimeout(5);
            $res = $req->execute($url,'','POST');
            $stat = $req->getStatus();
*/
            $req = new CachedRequest($url);
//          $req->setTimeout(10); use default
            $req->execute($url);
            if( $req->getStatus() == 200 ) {
                $result = $req->getResult();
                if( $result ) {
                    $data = json_decode($result,TRUE);
                    if( $data && version_compare($data,$mod::MIN_FORGE_VERSION) >= 0 ) {
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
     * Get a status-descriptor corresponding to the supplied $date
     *
     * @param string $date supplied by Forge, formatted like Y-m-d G:i:s
     * @return string maybe empty
     */
    public static function get_status(string $date) : string
    {
        $ts = strtotime($date);
        $limit = strtotime('-2 years');
        if( $ts <= $limit ) return 'stale';
        $limit = strtotime('-18 months');
        if( $ts <= $limit ) return 'warn';
        $limit = strtotime('-1 month');
        if( $ts >= $limit ) return 'new';
        return '';
    }

    /**
     * Set template-vars for various image tags
     *
     * @param $template Smarty_Internal_Template object
     */
    public static function get_images($template)
    {
        $mod = AppUtils::get_module('ModuleManager');
        $base = cms_join_path($mod->GetModulePath(),'images').DIRECTORY_SEPARATOR;
        $themeObject = AppUtils::get_theme_object();

        //[0] = file basename [1] = alt, tip suffix, variable-name suffix
        foreach( [
            ['bundled','bundled'],
            ['deprecated','deprecated'],
            ['new','new'],
//            ['noforge','noforge'],
            ['puzzle','missingdeps'],
            ['stagnant','stagnant'],
            ['stale','stale'],
            ['stale','staleupgrade'],
            ['star','upgradeable'],
            ['star2','freshupgrade'],
            ['warn','warning'],
        ] as &$one ) {
            $path = $base.$one[0];
            $title = $mod->Lang('title_'.$one[1]);
            $img = $themeObject->DisplayImage($path,$one[1],0,0,'statusicon',['title'=>$title]);
            $template->assign($one[1].'_img',$img);
        }
        unset($one);
    }
} // class
