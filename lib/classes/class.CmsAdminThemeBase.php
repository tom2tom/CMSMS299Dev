<?php
#Base class for CMS admin themes
#Copyright (C) 2010-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

/* for future use
namespace CMSMS;
use cms_admin_tabs;
use cms_cache_handler;
use cms_config;
use cms_url;
use cms_userprefs;
use cms_utils;
use CMSMS\App as CmsApp;
use const CMS_ADMIN_PATH;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function audit;
use function check_permission;
use function cleanArray;
use function cleanValue;
use function cms_join_path;
use function cms_module_places;
use function endswith;
use function get_site_preference;
use function get_userid;
use function lang;
use function startswith;
use ArrayTreeIterator;
use RecursiveArrayTreeIterator;
use RecursiveIteratorIterator;
*/
use CMSMS\AdminUtils, CMSMS\ArrayTree, CMSMS\HookManager;

/**
 * This is an abstract base class for building CMSMS admin themes.
 * This is also a singleton object.
 *
 * @package CMS
 * @license GPL
 * @since   1.11
 * @author  Robert Campbell
 * @property-read string $themeName Return the theme name
 * @property-read int $userid Return the current logged in userid (deprecated)
 * @property-read string $title The current page title
 * @property-read string $subtitle The current page subtitle
 */
abstract class CmsAdminThemeBase
{
    /**
     * @ignore
     */
    private static $_instance = null;

    /**
     * @ignore
     */
    private $_perms;

    /**
     * @ignore
     */
    private $_menuTree = [];

    /**
     * @ignore
     */
    private $_activePath = [];

    /**
     * @ignore
     */
    private $_notifications;

    /**
     * Feedback-message-string accumulators
     * @ignore
     */
    protected $_errors = [];
    protected $_warnings = [];
    protected $_successes = [];
    protected $_infos = [];

    /**
     * @ignore
     */
    private $_breadcrumbs = [];

    /**
     * @ignore
     */
    private $_imageLink;

    /**
     * @ignore
     */
    private $_script;

    /**
     * @ignore
     */
    private $_url;

    /**
     * @ignore
     */
    private $_query;

    /**
     * @ignore
     */
    private $_data;

    /**
     * @ignore
     */
    private $_action_module;

    // meta information

    /**
     * @ignore
     */
    private $_modules;

    /* *
     * @ignore
     */
//    private $_active_item;

    /* *
     * @ignore
     */
//    private $_activetab;

    /**
     * @ignore
     */
    private $_title;

    /**
     * @ignore
     */
    private $_subtitle;

    /**
     * Cache for content to be included in the page header
     * @ignore
     */
    private $_headtext;

    /**
     * Cache for content to be included in the page footer
     * @ignore
     */
    private $_foottext;

    /**
     * Use small-size icons (named like *-small.ext) if available
     * @ignore
     */
    private $_smallicons = false;

    /**
     * @ignore
     */
    private $_valid_sections = ['view','content','layout','files','usersgroups','extensions','services','ecommerce','siteadmin','myprefs'];

    /**
     * @ignore
     * @since 2.3
     */
//    private $_primary_content;

    /**
     * @ignore
     */
    protected function __construct()
    {
        if( is_object(self::$_instance) ) throw new CmsLogicExceptin('Only one instance of a theme object is permitted');

        $this->_url = $_SERVER['SCRIPT_NAME'];
        $this->_query = $_SERVER['QUERY_STRING']??'';
        if( $this->_query == '' && isset($_POST['mact']) ) {
            $tmp = explode(',',$_POST['mact']);
            $this->_query = 'module='.$tmp[0];
        }
        //if ($this->_query == '' && isset($_POST['module']) && $_POST['module']) $this->_query = 'module='.$_POST['module'];
        if (strpos( $this->_url, '/' ) === false) {
            $this->_script = $this->_url;
        } else {
            $toam_tmp = explode('/',$this->_url);
            $toam_tmp2 = array_pop($toam_tmp);
            $this->_script = $toam_tmp2;
        }

        $this->UnParkNotices();

        HookManager::add_hook('AdminHeaderSetup', [$this, 'AdminHeaderSetup']);
        HookManager::add_hook('AdminBottomSetup', [$this, 'AdminBottomSetup']);
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'themeName':
            $class = get_class($this);
            if( endswith($class,'Theme') ) $class = substr($class,0,strlen($class)-5);
            return $class;
        case 'userid':
            return get_userid();
        case 'title':
            return $this->_title;
        case 'subtitle':
            return $this->_subtitle;
        case 'root_url':
            $config = cms_config::get_instance();
            return $config['admin_url']."/themes/".$this->themeName;
        }
    }

    /**
     * Helper for constructing js data
     * @ignore
     * @since 2.3
     * @param array $strings
     * @return mixed string or false
     */
    private function merger(array $strings)
    {
        if ($strings) {
            if (count($strings) > 1) {
                foreach ($strings as &$one) {
                    if ($one) {
                        $one = json_encode($one);
                    }
                }
                unset($one);
                return '['.implode(',',array_filter($strings)).']';
            } else {
                return json_encode(reset($strings));
            }
        }
        return false;
    }

    /**
     * Hook function to populate runtime js variables
     * This will probably be subclassed for specific themes, to also do extra setup
     * @since 2.3
     * @param array $vars to be populated with members like key=>value
     * @param array $add_list to be populated with ...
     * @param array $exclude_list to be populated with ...
     * @return array updated values of each of the supplied arguments
     */
/*    public function JsSetup(array $vars, array $add_list, array $exclude_list) : array
    {
        $msgs = [
            'errornotices' => $this->merger($this->_errors),
            'warnnotices' => $this->merger($this->_warnings),
            'successnotices' => $this->merger($this->_successes),
            'infonotices' => $this->merger($this->_infos),
        ];
        $vars += array_filter($msgs);

//        $add_list['toast'] = CMS_SCRIPTS_URL.'/jquery.toast.js';

        return [$vars, $add_list, $exclude_list];
    }
*/
    /**
     * Hook functions to populate page content at runtime
     * These will normally be subclassed for specific themes, and such methods
     * should call here (their parent) as well as their own specific setup
     * @since 2.3
     * @param array $vars to be populated with data for js vars, like varname=>varvalue
     * @param array $add_list to be populated with string(s) for includables
     * @return mixed scalar or array, same number and type as the supplied argument(s)
     */
    public function AdminHeaderSetup(array $vars, array $add_list) : array
    {
        $msgs = [
            'errornotices' => $this->merger($this->_errors),
            'warnnotices' => $this->merger($this->_warnings),
            'successnotices' => $this->merger($this->_successes),
            'infonotices' => $this->merger($this->_infos),
        ];
        $vars += array_filter($msgs);
        return [$vars, $add_list];
    }

    public function AdminBottomSetup(array $add_list) : array
    {
        return $add_list;
    }

    /**
     * FixSpaces
     * This method converts spaces into a non-breaking space HTML entity.
     * It's used for making menus that work nicely
     *
     * @param str string to have its spaces converted
     * @ignore
     */
    private function _FixSpaces(string $str) : string
    {
/* RUBBISH - UTF-8 whitespace is ASCII-compatible
        $tmp = preg_replace('/\s+/u','&nbsp;',$str); // PREG UTF8
        if(!empty($tmp)) return $tmp;
        else return preg_replace('/\s+/',"&nbsp;",$str); // bad UTF8
*/
        return preg_replace('/\s+/','&nbsp;',$str);
    }

    /**
     * @ignore
     * @param mixed $url string or null
     * @return mixed string or null
     */
    private function _fix_url_userkey($url)
    {
        if( strpos($url,CMS_SECURE_PARAM_NAME) !== FALSE ) {
            $from = '/'.CMS_SECURE_PARAM_NAME.'=[a-zA-Z0-9]{16,19}/i';
            $to = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
            return preg_replace($from,$to,$url);
        }
        elseif( startswith($url,CMS_ROOT_URL) || !startswith($url,'http') ) {
            $prefix = ( strpos($url,'?') !== FALSE ) ? '&amp;' : '?';
            return $url.$prefix.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
        }
        return $url;
    }

    /**
     * _get_user_module_info
     * Given the currently logged in user, this will read cache information representing info for all available modules
     * for that particular user.   If cache information is not available, then modules will be loaded and the information
     * will be gleaned from the module for that user.
     *
     * @since 1.10
     * @access private
     * @ignore
     * @author calguy1000
     * @return array
     */
    private function _get_user_module_info() : array
    {
        $uid = get_userid(false);
/*DEBUG        if (($data = cms_cache_handler::get_instance()->get('themeinfo'.$uid))) {
            $data = base64_decode($data);
            $data = @unserialize($data);
        }
*/$data = false;
        if (!$data) {
            // data doesn't exist, gotta build it
            $usermoduleinfo = [];
            $modops = ModuleOperations::get_instance();
            $allmodules = $modops->GetInstalledModules();
            foreach ($allmodules as $modname) {
                $modinst = $modops->get_module_instance($modname);
                if (is_object($modinst) && $modinst->HasAdmin()) {
                    $recs = $modinst->GetAdminMenuItems();
                    if ($recs) {
                        $sys = $modops->IsSystemModule($modname);
                        $suffix = 1;
                        foreach ($recs as &$one) {
                            if (!$one->valid()) continue;
                            $key = $modname.$suffix++;
                            $one->name = $key;
                            $url = (!empty($one->url)) ? $one->url :
                                $modinst->create_url('m1_', $one->action);
                            if (($p = strpos($url, 'moduleinterface.php')) === false) {
                                $one->url = $url;
                            } else {
                                $one->url = substr($url, $p);
                            }
                            $one->system = $sys;
                            $usermoduleinfo[$key] = $one;
                        }
                        unset($one);
                    }
                }
            }
            // cache the array, even if empty
            $data = $usermoduleinfo;
            $tmp = serialize($usermoduleinfo);
            cms_cache_handler::get_instance()->set('themeinfo'.$uid,base64_encode($tmp));
        }

        return $data;
    }

    /**
     * _SetModuleAdminInterfaces
     *
     * This function sets up data structures to place modules in the proper Admin sections
     * for display on section pages and menus.
     *
     * @since 1.10
     * @access private
     * @ignore
     */
    private function _SetModuleAdminInterfaces() : void
    {
        if ($this->_modules) {
            return; //once is enough
        }

        // get the info from the cache
        $usermoduleinfo = $this->_get_user_module_info();
        // is there any module with an admin interface?
        if (is_array($usermoduleinfo)) {
            //TODO prefer .svg if present
            $appends = [
                ['images','icon.svg'],
                ['icons','icon.svg'],
                ['images','icon.png'],
                ['icons','icon.png'],
                ['images','icon.gif'],
                ['icons','icon.gif'],
            ];
            $smallappends = [
                ['images','icon.svg'],
                ['icons','icon.svg'],
                ['images','icon-small.png'],
                ['icons','icon-small.png'],
                ['images','icon-small.gif'],
                ['icons','icon-small.gif'],
            ];

            foreach ($usermoduleinfo as $key => $obj) {
                if (empty($obj->section)) $obj->section = 'extensions';
                // fix up the session key stuff
                $obj->url = $this->_fix_url_userkey($obj->url);
                if (empty($obj->icon)) {
                    // find the 'best' icon
                    $modname = $obj->module;
                    $dirs = cms_module_places($modname);
                    foreach ($dirs as $base) {
                        if ($this->_smallicons) {
                            foreach ($smallappends as $one) {
                                $path = cms_join_path($base, ...$one);
                                if (is_file($path)) {
                                    $obj->icon = AdminUtils::path_to_url($path);
                                    break 2;
                                }
                            }
                        }
                        foreach ($appends as $one) {
                            $path = cms_join_path($base, ...$one);
                            if (is_file($path)) {
                                $obj->icon = AdminUtils::path_to_url($path);
                                break 2;
                            }
                        }
                    }
                }
                $this->_modules[] = $obj;
            }
        } else {
            // put mention into the admin log
            audit(get_userid(false),'Admin Theme','No module information found for user');
        }
    }

    /**
     * SetAggregatePermissions
     *
     * This function gathers disparate permissions to come up with the visibility of
     * various admin sections, e.g., if there is any content-related operation for
     * which a user has permissions, the aggregate content permission is granted, so
     * that menu item is visible.
     *
     * @access private
     * @ignore
     */
    private function _SetAggregatePermissions(bool $force = false) : void
    {
        if( is_array($this->_perms) && !$force ) return;

        $this->_perms = [];

        // content section TODO individual
        $this->_perms['contentPerms'] =
            check_permission($this->userid, 'Manage All Content') |
            check_permission($this->userid, 'Modify Any Page') |
            check_permission($this->userid, 'Add Pages') |
            check_permission($this->userid, 'Remove Pages') |
            check_permission($this->userid, 'Reorder Content');

        // layout TODO individual
        $this->_perms['layoutPerms'] =
            check_permission($this->userid, 'Manage Designs') |
            check_permission($this->userid, 'Manage Stylesheets') |
            check_permission($this->userid, 'Add Templates') |
            check_permission($this->userid, 'Modify Templates');

        // file
        $this->_perms['filePerms'] = check_permission($this->userid, 'Modify Files');

        // user-defined plugins (2.3+)
        $this->_perms['plugPerms'] = check_permission($this->userid, 'Modify UserTags');

        // user/group
        $this->_perms['userPerms'] = check_permission($this->userid, 'Manage Users');
        $this->_perms['groupPerms'] = check_permission($this->userid, 'Manage Groups');
        $this->_perms['usersGroupsPerms'] = $this->_perms['userPerms'] |
            $this->_perms['groupPerms'];

        // admin
        $this->_perms['sitePrefPerms'] = check_permission($this->userid, 'Modify Site Preferences');
        $this->_perms['adminPerms'] = $this->_perms['sitePrefPerms'];
        $this->_perms['siteAdminPerms'] = $this->_perms['sitePrefPerms'] |
            $this->_perms['adminPerms'];

        // extensions
        $this->_perms['codeBlockPerms'] = check_permission($this->userid, 'Modify User-defined Tags');
        $this->_perms['modulePerms'] = check_permission($this->userid, 'Modify Modules');
        $this->_perms['eventPerms'] = check_permission($this->userid, 'Modify Events');
        $this->_perms['taghelpPerms'] = check_permission($this->userid, 'View Tag Help');
        $this->_perms['extensionsPerms'] = $this->_perms['codeBlockPerms'] |
            $this->_perms['modulePerms'] | $this->_perms['eventPerms'] | $this->_perms['taghelpPerms'];

        // myprefs
        $this->_perms['myaccount'] = check_permission($this->userid,'Manage My Account');
        $this->_perms['mysettings'] = check_permission($this->userid,'Manage My Settings');
        $this->_perms['bookmarks'] = check_permission($this->userid,'Manage My Bookmarks');
        $this->_perms['myprefPerms'] = $this->_perms['myaccount'] |
                $this->_perms['mysettings'] | $this->_perms['bookmarks'];
    }

    /**
     * @ignore
     * @return 2-member array,
     *  [0] = admin-relative url derived from the request
     *  [1] = assoc. array of request variables
     */
    private function _parse_request() : array
    {
        $config = cms_config::get_instance();
        $url_ob = new cms_url($config['admin_url']);
        $urlroot = $url_ob->get_path();

        $url_ob = new cms_url($_SERVER['REQUEST_URI']);
        // if mact is available via post and not via get, we fake it
        //  so that comparisons can get the mact from the query
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mact']) && !isset($_GET['mact'])) {
            $value = cleanValue(rawurldecode($_POST['mact'])); //direct use N/A
            $url_ob->set_queryvar('mact', $value);
            $url_ob = new cms_url((string)$url_ob);
        }
        $urlparms = [];
        parse_str($url_ob->get_query(), $urlparms);

        $urlpath = $url_ob->get_path();
        $text = substr($urlpath, strlen($urlroot) + 1);
        if (!$text) {
            $text = 'index.php';
        }
        $first = true;
        foreach ($urlparms as $key => $value) {
            if ($first) {
                $text .= '?';
                $first = false;
            } else {
                $text .= '&amp;';
            }
            // cleanup
            if ($key == CMS_SECURE_PARAM_NAME) {
                $value = $_SESSION[CMS_USER_KEY];
                $urlparms[$key] = $value;
            }
            $text .= rawurlencode($key).'='.rawurlencode($value);
        }
        return [$text, $urlparms];
    }

    /**
     * Set the page title.
     * This is used in the admin to set the title for the page, and for the visible page header.
     * Note: if no title is specified, the theme will try to calculate one automatically.
     *
     * @since 2.0
     * @param string $str The page title.
     */
    public function SetTitle($str)
    {
        if( $str == '' ) $str = null;
        $this->_title = $str;
    }

    /**
     * Set the page subtitle.
     * This is used in the admin to set the title for the page, and for the visible page header.
     * Note: if no title is specified, the theme will try to calculate one automatically.
     *
     * @since 2.0
     * @param string $str The page subtitle.
     */
    public function SetSubTitle($str)
    {
        if( $str == '' ) $str = null;
        $this->_subtitle = $str;
    }

    /**
     * HasPerm
     *
     * @ignore
     * Check if the user has one of the aggregate permissions
     *
     * @param string $permission the permission to check.
     * @return bool
     */
    protected function HasPerm($permission)
    {
        $this->_SetAggregatePermissions();
        return !empty($this->_perms[$permission]);
    }

    /**
     * populate_tree
     *
     * @ignore
     * Generate admin menu data
     * @since 2.3
     */
    protected function populate_tree()
    {
        $urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

        $items = [

        ['name'=>'root','parent'=>null,
        'show_in_menu'=>false],

        ['name'=>'main','parent'=>'root',
        'url'=>'index.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('home')),
        'description'=>'', //lang('viewdescription'),
        'priority'=>1,
        'show_in_menu'=>true],

        ['name'=>'content','parent'=>'root',
        'url'=>'index.php'.$urlext.'&section=content',
        'title'=>$this->_FixSpaces(lang('content')),
        'description'=>lang('contentdescription'),
        'priority'=>2,
        'show_in_menu'=>$this->HasPerm('contentPerms')],

        ['name'=>'layout','parent'=>'root',
        'url'=>'index.php'.$urlext.'&section=layout',
        'title'=>$this->_FixSpaces(lang('layout')),
        'description'=>lang('layoutdescription'),
        'priority'=>3,
        'show_in_menu'=>$this->HasPerm('layoutPerms')],

        ['name'=>'files','parent'=>'root',
        'url'=>'index.php'.$urlext.'&section=files',
        'title'=>$this->_FixSpaces(lang('files')),
        'description'=>lang('filesdescription'),
        'priority'=>4,
        'show_in_menu'=>$this->HasPerm('filePerms')],

        ['name'=>'usersgroups','parent'=>'root',
        'url'=>'index.php'.$urlext.'&section=usersgroups',
        'title'=>$this->_FixSpaces(lang('usersgroups')),
        'description'=>lang('usersgroupsdescription'),
        'priority'=>5,
        'show_in_menu'=>$this->HasPerm('usersGroupsPerms')],

        ['name'=>'extensions','parent'=>'root',
        'url'=>'index.php'.$urlext.'&section=extensions',
        'title'=>$this->_FixSpaces(lang('extensions')),
        'description'=>lang('extensionsdescription'),
        'priority'=>6,
        'show_in_menu'=>$this->HasPerm('extensionsPerms')],

        ['name'=>'services','parent'=>'root',
        'url'=>'index.php'.$urlext.'&section=services',
        'title'=>$this->_FixSpaces(lang('services')),
        'description'=>lang('servicesdescription'),
        'priority'=>7,
        'show_in_menu'=>true],

        ['name'=>'siteadmin','parent'=>'root',
        'url'=>'index.php'.$urlext.'&section=siteadmin',
        'title'=>$this->_FixSpaces(lang('admin')),
        'description'=>lang('admindescription'),
        'priority'=>8,
        'show_in_menu'=>$this->HasPerm('siteAdminPerms')],

        ['name'=>'myprefs','parent'=>'root',
        'url'=>'index.php'.$urlext.'&section=myprefs',
        'title'=>$this->_FixSpaces(lang('myprefs')),
        'description'=>lang('myprefsdescription'),
        'priority'=>9,
        'show_in_menu'=>$this->HasPerm('myprefPerms')],

        ['name'=>'logout','parent'=>'root',
        'url'=>'logout.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('logout')),
        'description'=>'',
        'final'=>true, //force keep
        'priority'=>10,
        'show_in_menu'=>true],

        ];

        // ~~~~~~~~~~ main menu items ~~~~~~~~~~
/* this is essentially a duplication
        $items[] = ['name'=>'home','parent'=>'main',
        'url'=>'index.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('home')),
        'description'=>'',
        'priority'=>1,
        'show_in_menu'=>false];
*/
        $items[] = ['name'=>'site','parent'=>'main',
        'url'=>CMS_ROOT_URL.'/index.php',
        'title'=>$this->_FixSpaces(lang('viewsite')),
        'type'=>'external',
        'description'=>'',
        'priority'=>2,
        'show_in_menu'=>true,
        'target'=>'_blank'];

        // ~~~~~~~~~~ services menu items ~~~~~~~~~~

        $items[] = ['name'=>'ecommerce','parent'=>'services',
        'url'=>'index.php'.$urlext.'&section=ecommerce',
        'title'=>$this->_FixSpaces(lang('ecommerce')),
        'description'=>lang('ecommerce_desc'),
        'show_in_menu'=>true]; //TODO relevant perm

        // ~~~~~~~~~~ user/groups menu items ~~~~~~~~~~

        $items[] = ['name'=>'listusers','parent'=>'usersgroups',
        'url'=>'listusers.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('currentusers')),
        'description'=>lang('usersdescription'),
        'priority'=>1,
        'show_in_menu'=>$this->HasPerm('userPerms')];

        $items[] = ['name'=>'adduser','parent'=>'usersgroups',
        'url'=>'adduser.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('adduser')),
        'description'=>'',
        'priority'=>1,
        'show_in_menu'=>false]; //??

        $items[] = ['name'=>'edituser','parent'=>'usersgroups',
        'url'=>'edituser.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('edituser')),
        'description'=>'',
        'priority'=>1,
        'show_in_menu'=>false]; //??

        $items[] = ['name'=>'listgroups','parent'=>'usersgroups',
        'url'=>'listgroups.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('currentgroups')),
        'description'=>lang('groupsdescription'),
        'priority'=>2,
        'show_in_menu'=>$this->HasPerm('groupPerms')];

        $items[] = ['name'=>'addgroup','parent'=>'usersgroups',
        'url'=>'addgroup.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('addgroup')),
        'description'=>'',
        'priority'=>2,
        'show_in_menu'=>false]; //??

        $items[] = ['name'=>'editgroup','parent'=>'usersgroups',
        'url'=>'editgroup.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('editgroup')),
        'description'=>'',
        'priority'=>2,
        'show_in_menu'=>false]; //??

        $items[] = ['name'=>'groupmembers','parent'=>'usersgroups',
        'url'=>'changegroupassign.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('groupassignments')),
        'description'=>lang('groupassignmentdescription'),
        'priority'=>3,
        'show_in_menu'=>$this->HasPerm('groupPerms')];

        $items[] = ['name'=>'groupperms','parent'=>'usersgroups',
        'url'=>'changegroupperm.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('grouppermissions')),
        'description'=>lang('grouppermsdescription'),
        'priority'=>3,
        'show_in_menu'=>$this->HasPerm('groupPerms')];

        // ~~~~~~~~~~ extensions menu items ~~~~~~~~~~

        $items[] = ['name'=>'tags','parent'=>'extensions',
        'url'=>'listtags.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('tags')),
        'description'=>lang('tagdescription'),
        'show_in_menu'=>$this->HasPerm('taghelpPerms')];
        $items[] = ['name'=>'eventhandlers','parent'=>'extensions',
        'url'=>'eventhandlers.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('eventhandlers')),
        'description'=>lang('eventhandlerdescription'),
        'show_in_menu'=>$this->HasPerm('eventPerms')];
        $items[] = ['name'=>'editeventhandler','parent'=>'eventhandlers',
        'url'=>'editevent.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('editeventhandler')),
        'description'=>lang('editeventhandlerdescription'),
        'show_in_menu'=>false]; //??

        // ~~~~~~~~~~ admin menu items ~~~~~~~~~~

        $items[] = ['name'=>'siteprefs','parent'=>'siteadmin',
        'url'=>'siteprefs.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('globalconfig')),
        'description'=>lang('preferencesdescription'),
        'priority'=>1,
        'show_in_menu'=>$this->HasPerm('sitePrefPerms')];
        $items[] = ['name'=>'systeminfo','parent'=>'siteadmin',
        'url' => 'systeminfo.php'.$urlext,
        'title' => $this->_FixSpaces(lang('systeminfo')),
        'description' => lang('systeminfodescription'),
        'priority'=>2,
        'show_in_menu' => $this->HasPerm('adminPerms')];
        $items[] = ['name'=>'systemmaintenance','parent'=>'siteadmin',
        'url' => 'systemmaintenance.php'.$urlext,
        'title' => $this->_FixSpaces(lang('systemmaintenance')),
        'description' => lang('systemmaintenancedescription'),
        'priority'=>3,
        'show_in_menu' => $this->HasPerm('adminPerms')];
        $items[] = ['name'=>'checksum','parent'=>'siteadmin',
        'url' => 'checksum.php'.$urlext,
        'title' => $this->_FixSpaces(lang('system_verification')),
        'description' => lang('checksumdescription'),
        'priority'=>4,
        'show_in_menu' => $this->HasPerm('adminPerms')];

        // ~~~~~~~~~~ myprefs menu items ~~~~~~~~~~

        $items[] = ['name'=>'myaccount','parent'=>'myprefs',
        'url'=>'myaccount.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('myaccount')),
        'description'=>lang('myaccountdescription'),
        'show_in_menu'=>$this->_perms['myaccount']];
        $items[] = ['name'=>'mysettngs','parent'=>'myprefs',
        'url'=>'mysettings.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('mysettings')),
        'description'=>lang('mysettingsdescription'),
        'show_in_menu'=>$this->_perms['mysettings']];
        $items[] = ['name'=>'mybookmarks','parent'=>'myprefs',
        'url'=>'listbookmarks.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('mybookmarks')),
        'description'=>lang('mybookmarksdescription'),
        'show_in_menu'=>$this->_perms['bookmarks']];
/*      $items[] = ['name'=>'addbookmark','parent'=>'myprefs',
        'url'=>'addbookmark.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('addbookmark')),
        'description'=>''),
        'show_in_menu'=>false];
        $items[] = ['name'=>'editbookmark','parent'=>'myprefs',
        'url'=>'editbookmark.php'.$urlext,
        'title'=>$this->_FixSpaces(lang('editbookmark')),
        'description'=>''),
        'show_in_menu'=>false];
*/
        // append the user's module-related items, if any
        $this->_SetModuleAdminInterfaces();
        foreach ($this->_modules as $key => $obj) {
            $item = ['parent' => null] + $obj->get_all();
            $item['parent'] = (!empty($item['section'])) ? $item['section'] : 'extensions';
            unset($item['section']);
            $item['title'] = $this->_FixSpaces($item['title']);
            $item['show_in_menu'] = true;
            $items[] = $item;
        }

        $tree = ArrayTree::load_array($items);

        $iter = new \RecursiveArrayTreeIterator(
                new \ArrayTreeIterator($tree),
                \RecursiveIteratorIterator::SELF_FIRST | \RecursiveArrayTreeIterator::NONLEAVES_ONLY
                );
        foreach ($iter as $key => $value) {
/* TODO e.g. add/remove properties,
 remove those without children (unless 'forcekeep' => true)
*/
            if (!empty($value['children'])) {
                $node = ArrayTree::node_get_data($tree, $value['path'], '*');
                uasort($node['children'], function($a,$b) use ($value) {
                    $pa = $a['priority'] ?? 999;
                    $pb = $b['priority'] ?? 999;
                    $c = $pa <=> $pb;
                    if ($c != 0) {
                        return $c;
                    }
                    return strnatcmp($a['title'],$b['title']); //TODO mb_cmp if available
                });
                $ret = ArrayTree::node_set_data($tree, $value['path'], 'children', $node['children']);
            } else {
$adbg = $value;
                $depth = $iter->getDepth();
                if ($depth < 2 && empty($value['final'])) { //c.f. upstream $maxdepth
$X = 1;
//                    $iter->offsetUnset(X); //TODO relevant offset
                }
            }
        }

        $this->_menuTree = $tree;
    }

    /**
     * Populate the admin navigation tree (if not done before), and return some or all of it
     * (This might be called direct from a template.)
     *
     * @since 1.11
     * @param mixed $parent    Optional name of the wanted root node, or
     *  null for actual root node. The formerly used -1 is also recognised
     *  as an indicator of the root node.
     * @param int   $maxdepth  Optional no. of sub-root levels to be displayed
     *  for $parent. < 1 indicates no maximum depth. Default 2
     * $param mixed $usepath   Since 2.3 Optional treepath for the selected item.
     *  Array, or ':'-separated string, of node names (commencing with 'root'),
     *  or (boolean) true in which case a path is derived from the current request,
     *  or false to skip selection-processing. Default true
     * @param int   $alldepth  Optional no. of sub-root levels to be displayed
     *  for tree-paths other than $parent. < 1 indicates no limit. Default 2
     * @param bool  $striproot Since 2.3 Optional flag whether to omit the tree root-node
     *  from the returned array Default (backward compatible) true
     * @return array  Nested menu nodes.  Each node's 'children' member represents the nesting
     */
    public function get_navigation_tree($parent = null, $maxdepth = 2, $usepath = true, $alldepth = 2, $striproot = true)
    {
        if (!$this->_menuTree) {
            $this->populate_tree();
        }
        $tree = $this->_menuTree;

        if ($parent == -1) {
            $parent = null;
        }
        if ($parent) {
            $path = ArrayTree::find($tree, 'name', $parent);
            if ($path) {
                $tree = ArrayTree::node_get_data($tree, $path, '*');
            }
        } else {
            $alldepth = $maxdepth;
        }
        if ($maxdepth > 0 || $alldepth > 0) {
            //get a subset of $tree
            //TODO $tree = func($tree)
        }

        if ($usepath) {
            if (is_string($usepath)) {
                $this->_activePath = ArrayTree::process_path($usepath);
            } else {
                list($req_url, $req_vars) = $this->_parse_request();
                $this->_activePath = ArrayTree::find($tree, 'url', $req_url);
            }

            ArrayTree::path_set_data($tree, $this->_activePath, 'selected', true);
            $this->_title = ArrayTree::node_get_data($tree, $this->_activePath, 'title');
            $this->_subtitle = ArrayTree::node_get_data($tree, $this->_activePath, 'description');
//          $this->_breadcrumbs(); on-demand only?
        } else {
            $this->_activePath = [];
        }

        if ($striproot) {
            if ($parent) {
                return $tree['children']; //TODO bad logic want whole tree
            } else {
                return reset($tree)['children'];
            }
        }
        return $tree;
    }

    /**
     * Set the current action module
     *
     * @since 2.0
     * @param string $module_name the module name.
     */
    public function set_action_module($module_name)
    {
        if( !$module_name ) return;
        $this->_action_module = $module_name;
    }

    /**
     * Determine the module name (if any) associated with the current request.
     *
     * @since 2.0
     * @access protected
     * @return string the module name for the current request, if any.
     */
    protected function get_action_module()
    {
        if( $this->_action_module ) return $this->_action_module;
        // todo: if this is empty, get it from the mact in the request.
    }

    /**
     * Get the help URL for a module.
     *
     * @since 2.0
     * @access protected
     * @param string $module_name
     * @return mixed url-string or null
     */
    protected function get_module_help_url($module_name = null)
    {
        if( !$module_name ) $module_name = $this->get_action_module();
        if( !$module_name ) return;
        //TODO some core method c.f. \CMSMS\AdminUtils::get_generic_url()
        $modman = cms_utils::get_module('ModuleManager');
        if( is_object($modman) ) {
            return $modman->create_url('m1_','defaultadmin','',['modulehelp'=>$module_name]);
        }
    }

    /**
     * A function to return the name (key) of a menu item given its title
     * returns the first match.
     *
     * @access protected
     * @param string $title The title to search for
     * @return string The matching key, or null
     */
    protected function find_menuitem_by_title($title)
    {
        $path = ArrayTree::find($this->menuTree, 'title', $title);
        if ($path) {
            return ArrayTree::node_get_data($this->menuTree, $path, 'name');
        }
    }

    /**
     * Return the list of bookmarks
     *
     * @param bool $pure if False the shortcuts for adding and managing bookmarks are added to the list.
     * @return array Array of Bookmark objects
     */
    public function get_bookmarks($pure = FALSE)
    {
        $bookops = CmsApp::get_instance()->GetBookmarkOperations();
        $marks = array_reverse($bookops->LoadBookmarks($this->userid));

        if( !$pure ) {
            $urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
            $mark= new Bookmark();
            $mark->title = lang('addbookmark');
            $mark->url = 'makebookmark.php'.$urlext.'&amp;title='.urlencode($this->_title);
            $marks[] = $mark;

            $mark = new Bookmark();
            $mark->title = lang('mybookmarks');
            $mark->url = 'listbookmarks.php'.$urlext;
            $marks[] = $mark;
        }
        return $marks;
    }

    /**
     * Return list of breadcrumbs
     *
     * @return array Array of menu nodes representing the breadcrumb trail.
     */
    public function get_breadcrumbs()
    {
        if (!$this->_breadcrumbs) {
            $this->_breadcrumbs = [];
            $urls = ArrayTree::path_get_data($this->_menuTree, $this->_activePath, 'url');
            $titles = ArrayTree::path_get_data($this->_menuTree, $this->_activePath, 'title');
            foreach ($urls as $key => $value) {
                $this->_breadcrumbs[] = [
                    'url' => $value,
                    'title' => $titles[$key],
                ];
            }
        }
        return $this->_breadcrumbs;
    }

    /**
     * Return the title of the active item.
     * Used for module actions
     *
     * @return string
     */
    public function get_active_title()
    {
        return ArrayTree::node_get_data($this->_menuTree, $this->_activePath, 'title');
    }

    public function get_active_icon()
    {
        return ArrayTree::node_get_data($this->_menuTree, $this->_activePath, 'icon');
    }

    /**
     * Cache some data
     *
     * @param string $key
     * @param mixed $value
     * @returns void
     */
    public function set_value($key,$value)
    {
        if( is_null($value) && is_array($this->_data) && isset($this->_data[$key]) ) {
            unset($this->_data[$key]);
            return;
        }
        if( $value ) {
            if( !is_array($this->_data) ) $this->_data = array();
            $this->_data[$key] = $value;
        }
    }

    /**
     * Return cached data
     *
     * @param string $key
     * @returns void
     */
    public function get_value($key)
    {
        if( is_array($this->_data) && isset($this->_data[$key]) ) return $this->_data[$key];
    }

    /**
     * HasDisplayableChildren
     * This method returns a boolean, based upon whether the section in question
     * has displayable children.
     *
     * @deprecated
     * @param string $section section to test
     * @return bool
     */
    public function HasDisplayableChildren($section)
    {
/* TODO array-tree interrogation
        $displayableChildren=false;
        foreach($this->_menuItems[$section]['children'] as $one) {
            $thisItem = $this->_menuItems[$one];
            if ($thisItem['show_in_menu']) {
                $displayableChildren = true;
                break;
            }
        }
        return $displayableChildren;
*/
        return false;
    }

    /**
     * DisplayImage
     * Displays the themed version of $imageName (if it exists), preferring type
     *  (in order): .svg, .png, .gif, .jpg, .jpeg
     * @param string $imageName name of image file, may have a 'images' (i.e.
     *  theme-images-dir) relative-path, may omit the extension/type suffix
     * @param string $alt Optional alternate identifier for the created image element, may also be used for its title
     * @param int $width Optional image-width (ignored for svg)
     * @param int $height Optional image-height (ignored for svg)
     * @param string $class Optional class
     * @param array $attrs Since 2.3 Optional array with any or all attributes for the image tag
     * @return string
     */
    public function DisplayImage($imageName, $alt = '', $width = '', $height = '', $class = null, $attrs = [])
    {
        if (!is_array($this->_imageLink)) {
            $this->_imageLink = [];
        }

        if (!isset($this->_imageLink[$imageName])) {
            $detail = preg_split('~\\/~',$imageName);
            $fn = array_pop($detail);
            $p = strrpos($fn,'.');
            if ($p !== false) {
                $fn = substr($fn, 0, $p+1);
            } else {
                $fn .= '.';
            }
            if ($detail) {
                $rel = implode(DIRECTORY_SEPARATOR,$detail).DIRECTORY_SEPARATOR;
            } else {
                $rel = '';
            }

            $base = $path = cms_join_path(CMS_ADMIN_PATH,'themes',$this->themeName,'images',$rel); //has trailing separator
            foreach (['svg','png','gif','jpg','jpeg'] as $type) {
                $path = $base.$fn.$type;
                if (file_exists($path)) {
                    //admin-relative URL will do
                    $path = substr($path, strlen(CMS_ADMIN_PATH) + 1);
                    $this->_imageLink[$imageName] = AdminUtils::path_to_url($path);
                    break;
                } else {
                    $path = '';
                }
            }
            if (!$path) {
//                $this->_imageLink[$imageName] = 'themes/'.$this->themeName.'/images/'.$imageName; //DEBUG
                $this->_imageLink[$imageName] = 'themes/assets/images/space.png';
            }
        }
        $path = $this->_imageLink[$imageName];

        $extras = array_merge(['width'=>$width, 'height'=>$height, 'class'=>$class, 'alt'=>$alt, 'title'=>''], $attrs);
        if (!$extras['title']) {
            if ($extras['alt']) {
                $extras['title'] = $extras['alt'];
            } else {
                $extras['title'] = pathinfo($path, PATHINFO_FILENAME);
            }
        }
        if (!$extras['alt']) {
            $p = strrpos($path,'/');
            $extras['alt'] = substr($path, $p+1);
        }

        $p = strrpos($path,'.');
        $type = substr($path,$p+1);
        if ($type == 'svg') {
            // see https://css-tricks.com/using-svg
            $alt = str_replace('svg','png',$path);
            $res = '<img src="'.$path.'" onerror="this.onerror=null;this.src=\''.$alt.'\';"';
        } else {
            $res = '<img src="'.$path.'"';
        }

        foreach( $extras as $key => $value ) {
            if ($value !== '' || $key == 'title') {
                $res .= " $key=\"$value\"";
            }
        }
        $res .= ' />';
        return $res;
    }

    /**
     * Cache error-message(s) to be shown in a dialog during the current request.
     *
     * @param mixed $errors The error message(s), string|strings array
     * @param string $get_var An optional $_GET variable name. Such variable
     *  contains a lang key for an error string, or an array of such keys.
     *  If specified, $errors is ignored.
     * @deprecated since 2.3 Use RecordNotice instead
     * @return empty string (in case something thinks it's worth echoing)
     */
    public function ShowErrors($errors, $get_var = null)
    {
        $this->PrepareStrings($this->_errors, $errors, '', $get_var);
        return '';
    }

    /**
     * Cache success-message(s) to be shown in a dialog during the current request.
     *
     * @param mixed $message The message(s), string|strings array
     * @param string $get_var An optional $_GET variable name. Such variable
     *  contains a lang key for an error string, or an array of such keys.
     *  If specified, $message is ignored.
     * @deprecated since 2.3 Use RecordNotice instead
     * @return empty string (in case something thinks it's worth echoing)
     */
    public function ShowMessage($message, $get_var = null)
    {
        $this->PrepareStrings($this->_successes, $message, '', $get_var);
        return '';
    }

    /**
     * Cache message string(s) to be shown in a dialog during the current request.
     *
     * @internal
     * @param array store The relevant string-accumulator
     * @param mixed $message The error message(s), string|strings array
     * @param string $title  Title for the message(s), may be empty
     * @param mixed $get_var A $_GET variable name, or null. If specified,
     *  such variable is expected to contain a lang key for an error string,
     *  or an array of such keys. If non-null, $message is ignored.
     * @since 2.3
     */
    protected function PrepareStrings(array &$store, $message, string $title, $get_var = null) : void
    {
        if ($get_var && !empty($_GET[$get_var])) {
            if (is_array($_GET[$get_var])) {
                cleanArray($_GET[$get_var]);
                foreach ($_GET[$get_var] as $one) {
                    if ($one) {
                        $store[] = lang($one);
                    }
                }
            } else {
                $store[] = lang(cleanValue($_GET[$get_var]));
            }
        } elseif ($title) {
            $store[$title] = $message;
        } else {
            if (is_array($message)) {
                $store = array_merge($store, $message);
            } else {
                $store[] = $message;
            }
        }
    }

    /**
     * Cache message(s) to be shown in a notification-dialog DURING THE NEXT REQUEST
     *
     * @since 2.3
     * @param string $type Message-type indicator 'error','warn','success' or 'info'
     * @param mixed $message The error message(s), string|strings array
     * @param string $title Optional title for the message(s)
     * @param bool $cache Optional flag, whether to setup for display during the next request (instead of the current one)
     * @param mixed $get_var Optional $_GET variable name. Such variable
     *  is expected to contain a lang key for an error string, or an
     *  array of such keys. If specified, $message is ignored.
     */
    public function ParkNotice(string $type, $message, string $title, $get_var = null) : void
    {
        $from = 'cmsmsg_'.$type;
        if (isset($_SESSION[$from])) {
            $val = cleanValue($_SESSION[$from]);
            if ($val) {
                $store = json_decode(base64_decode($val), true);
            } else {
                $store = [];
            }
        } else {
            $store = [];
        }
        $this->PrepareStrings($store, $message, $title, $get_var);
        $_SESSION[$from] = base64_encode(json_encode($store));
    }

    /**
     * Helper to retrieve message(s) from $_SESSION and set them up for display
     * @ignore
     * @since 2.3
     */
    protected function retrieve_message($type, $into)
    {
        $from = 'cmsmsg_'.$type;
        if (isset($_SESSION[$from])) {
            $val = cleanValue($_SESSION[$from]);
            if ($val) {
                $message = json_decode(base64_decode($val), true);
                $this->PrepareStrings($into, $message, '');
            }
            unset($_SESSION[$from]);
        }
    }

    /**
     * Cache message(s) that were logged during a prior request, to be shown in a notification-dialog
     *
     * @since 2.3
     */
    protected function UnParkNotices($type = null) : void
    {
/* TOAST DEBUGGING
        $this->_infos = ['dummy 1st line','This is some cool stuff that you\'ll want to remember'];
        $this->_successes = ['1234 5678 91011 All good to go, <b>great!</b>'];
        $this->_warnings = ['WOOPS!','this is just enough wider','and higher','as you can see'];
        $this->_errors = ['OOPS!'];
*/
        switch ($type) {
            case 'error':
                $this->retrieve_message($type, $this->_errors);
                return;
            case 'warn':
                $this->retrieve_message($type, $this->_warnings);
                return;
            case 'success':
                $this->retrieve_message($type, $this->_successes);
                return;
            case 'info':
                $this->retrieve_message($type, $this->_infos);
                return;
            default:
                // otherwise, everything
                $this->retrieve_message('error', $this->_errors);
                $this->retrieve_message('warn', $this->_warnings);
                $this->retrieve_message('success', $this->_successes);
                $this->retrieve_message('info', $this->_infos);
        }
    }

    /**
     * Cache message(s) to be shown in a notification-dialog
     *
     * @since 2.3
     * @param string $type Message-type indicator 'error','warn','success' or 'info'
     * @param mixed $message The error message(s), string|strings array
     * @param string $title Optional title for the message(s)
     * @param bool $defer Optional flag, whether to setup for display during the next request (instead of the current one)
     * @param mixed $get_var Optional $_GET variable name. Such variable
     *  is expected to contain a lang key for an error string, or an
     *  array of such keys. If specified, $message is ignored.
     */
    public function RecordNotice(string $type, $message, string $title= '', bool $defer = false, $get_var = null) : void
    {
        if (!$defer) {
            switch ($type) {
                case 'error':
                    $into =& $this->_errors;
                    break;
                case 'warn':
                    $into =& $this->_warnings;
                    break;
                case 'success':
                    $into =& $this->_successes;
                    break;
//              case 'info':
                default:
                    $into =& $this->_infos;
                    break;
            }
            $this->PrepareStrings($into, $message, $title, $get_var);
        } else {
            switch ($type) {
                case 'error':
                case 'warn':
                case 'success':
                case 'info':
                    break;
                default:
                    $type = 'info';
                    break;
            }
            $this->ParkNotice($type, $message, $title, $get_var);
        }
    }

    /**
     * Abstract method for preparing (not displaying) the header for a page
     * displaying module action output
     *
     * @abstract
     * @deprecated since 2.3 not used
     * @param string $title_name The text to show in the header.  This will be
     *  passed through lang() if module_help_type is FALSE.
     * @param array  $extra_lang_params Extra parameters to pass to lang() along with $title_name.
     *   Ignored if module_help_type is not FALSE
     * @param string $link_text Text to show in the module help link (depends on the module_help_type param)
     * @param mixed  $module_help_type Flag for how to display module help types.
     *  Recognized values are TRUE to display a simple link, FALSE for no help, and 'both' for both types?? of links
     */
    abstract public function ShowHeader($title_name,$extra_lang_params = [],$link_text = null,$module_help_type = FALSE);

    /**
     * Return the name of the default admin theme.
     *
     * @returns string
     */
    public static function GetDefaultTheme()
    {
        $tmp = self::GetAvailableThemes();
        if( $tmp ) {
            $logintheme = get_site_preference('logintheme');
            if( $logintheme && in_array($logintheme,$tmp) ) return $logintheme;
            return $tmp[0];
        }
        return '';
    }

    /**
     * Retrieve a list of the available admin themes.
     *
     * @param bool $fullpath since 2.3 Optional flag. Default false.
     *  If true, array values are theme-class filepaths. Othersie theme names.
     * @return array A theme-name-sorted hash of theme names or themefile-path strings
     */
    public static function GetAvailableThemes($fullpath = false)
    {
        $res = [];
        $files = glob(cms_join_path(CMS_ADMIN_PATH,'themes','*','*Theme.php'),GLOB_NOESCAPE);
        if( $files ) {
            foreach( $files as $one ) {
                if( is_readable( $one )) {
                    $name = basename($one,'Theme.php');
                    $res[$name] = ($fullpath) ? $one : $name;
                }
            }
        }
        return $res;
    }

    /**
     * Retrieve the global admin theme object.
     * This method will create the admin theme object if has not yet been created.
     * It will read the CMSMS preferences and cross reference with available themes.
     *
     * @param string $name optional theme name.
     * @return CmsAdminThemeBase Reference to the initialized admin theme.
     */
    public static function GetThemeObject($name = null)
    {
        if( is_object(self::$_instance) ) return self::$_instance;

        if( !$name ) $name = cms_userprefs::get_for_user(get_userid(FALSE),'admintheme',self::GetDefaultTheme());
        if( class_exists($name) ) {
            self::$_instance = new $name;
        }
        else {
            $gCms = CmsApp::get_instance();
            $themeObjName = $name."Theme";
            $fn = CMS_ADMIN_PATH."/themes/$name/{$themeObjName}.php";
            if( file_exists($fn) ) {
                include_once($fn);
                self::$_instance = new $themeObjName($gCms,get_userid(FALSE),$name);
            }
            else {
                // theme not found... use default
                $name = self::GetDefaultTheme();
                $themeObjName = $name."Theme";
                $fn = CMS_ADMIN_PATH."/themes/$name/{$themeObjName}.php";
                if( file_exists($fn) ) {
                    include_once($fn);
                    self::$_instance = new $themeObjName($gCms,get_userid(FALSE),$name);
                }
                else {
                    // still not found
                    $res = null;
                    return $res;
                }
            }
        }
        return self::$_instance;
    }

    /**
     * Add a notification for display in the theme.
     *
     * @param CmsAdminThemeNotification $notification A reference to the new notification
     */
    public function add_notification(CmsAdminThemeNotification &$notification)
    {
        if( !is_array($this->_notifications) ) $this->_notifications = array();
        $this->_notifications[] = $notification;
    }

    /**
     * Add a notification for display in the theme.
     * This is simply a compatibility wrapper around the add_notification method.
     *
     * @deprecated
     * @param int $priority priority level between 1 and 3
     * @param string $module The module name.
     * @param string $html The contents of the notification
     */
    public function AddNotification($priority,$module,$html)
    {
      $notification = new CmsAdminThemeNotification;
      $notification->priority = max(1,min(3,$priority));
      $notification->module = $module;
      $notification->html = $html;
      $this->add_notification($notification);
    }

    /**
     * Retrieve the current list of notifications.
     *
     * @return Array Array of CmsAdminThemeNotification objects
     */
    public function get_notifications()
    {
        return $this->_notifications;
    }

    /**
     * Return an array of admin pages, suitable for use in a dropdown.
     *
     * @internal
     * @since 1.12
     * @param bool $none A flag indicating whether 'none' should be the first option.
     * @return array The keys of the array are langified strings to display to the user.  The values are URLS.
     */
    public function GetAdminPages($none = true)
    {
        $opts = [];
        if( $none ) $opts[ucfirst(lang('none'))] = '';

        $depth = 0;
/*
        $menuItems = $this->get_admin_navigation();
        foreach( $menuItems as $sectionKey=>$menuItem ) {
            if( $menuItem['parent'] != -1 ) continue; // only parent pages
            if( !$menuItem['show_in_menu'] || strlen($menuItem['url']) < 1 ) continue; // only visible stuff

            $opts[$menuItem['title']] = AdminUtils::get_generic_url($menuItem['url']);

            if( is_array($menuItem['children']) && count($menuItem['children']) ) {
                foreach( $menuItem['children'] as $one ) {
                    if( $one == 'home' || $one == 'logout' || $one == 'viewsite') {
                        continue;
                    }

                    $menuChild = $menuItems[$one];
                    if( !$menuChild['show_in_menu'] || strlen($menuChild['url']) < 1 ) {
                        continue;
                    }

                    //$opts['&nbsp;&nbsp;'.$menuChild['title']] = cms_htmlentities($menuChild['url']);
                    $url = $menuChild['url'];
                    $url = AdminUtils::get_generic_url($url);
                    $opts['&nbsp;&nbsp;'.$menuChild['title']] = $url;
                }
            }
        }
*/
        //TODO iterwalk, pages: top-level & direct children? shown, with-url
        return $opts;
    }

    /**
     * Return a select list of the pages in the system for use in
     * various admin pages.
     *
     * @internal
     * @param string $name - The html name of the select box
     * @param string $selected - If a matching id is found in the list, that item
     *                           is marked as selected.
     * @return string The select list of pages
     */
    public function GetAdminPageDropdown($name,$selected,$id = null)
    {
        $opts = $this->GetAdminPages();
        $attrs = array('name'=>trim((string)$name));
        if( $id ) $attrs['id'] = trim((string)$id);
        $output = '<select ';
        foreach( $attrs as $key => $value ) {
            $output .= ' '.$key.'='.$value;
        }
        $output .= '>';

        foreach( $opts as $key => $value ) {
            if( $value == $selected ) {
                $output .= sprintf("<option selected=\"selected\" value=\"%s\">%s</option>\n",
                                   $value,$key);
            }
            else {
                $output .= sprintf("<option value=\"%s\">%s</option>\n",
                                   $value,$key);
            }
        }
        $output .= '</select>'."\n";
        return $output;
    }

    /**
     *  BackUrl
     *  "Back" Url - link to the next-to-last item in the breadcrumbs
     *  for the back button.
     */
    public function BackUrl()
    {
        $this->get_breadcrumbs(); //ensure data are populated
        $count = $this->_breadcrumbs ? count($this->_breadcrumbs) - 2 : -1;
        if ($count > -1) {
            $url = $this->_breadcrumbs[$count]['url'];
            return $url;
        }
        // rely on base href to redirect back to the admin home page
        $urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
        return 'index.php'.$urlext;
    }

    /**
     * Accumulate content to be inserted in the head section of the output
     *
     * The CMSMS core code calls this method to add text and javascript to output in the head section required for various functionality.
     *
     * @param string $txt The text to add to the head section.
     * @param bool   $after Since 2.3 Optional flag whether to append (instead of prepend) default true
     * @since 2.2
     * @author Robert Campbell
     */
    public function add_headtext($txt, $after = true)
    {
        $txt = trim($txt);
        if( $txt ) {
            if( $after ) { $this->_headtext .= "\n".$txt; }
            else { $this->_headtext = $txt."\n".$this->_headtext; }
        }
    }

    /**
     * Get text that needs to be inserted into the head section of the output.
     *
     * This method is typically called by the admin theme itself to get the text to render.
     *
     * @return string
     * @since 2.2
     * @author Robert Campbell
     */
    public function get_headtext()
    {
        return $this->_headtext;
    }

    /**
     * Accumulate content to be inserted at the bottom of the output, immediately before the </body> tag.
     *
     * @param string $txt The text to add to the end of the output.
     * @param bool   $after Since 2.3 Optional flag whether to append (instead of prepend) default true
     * @since 2.2
     * @author Robert Campbell
     */
    public function add_footertext($txt, $after = true)
    {
        $txt = trim($txt);
        if( $txt ) {
            if( $after ) { $this->_foottext .= "\n".$txt; }
            else { $this->_foottext = $txt."\n".$this->_foottext; }
        }
    }

    /**
     * Get text that needs to be inserted into the bottom of the output.
     *
     * This method is typically called by the admin theme itself to get the text to render.
     *
     * @return string
     * @since 2.2
     * @author Robert Campbell
     */
    public function get_footertext()
    {
        return $this->_foottext;
    }

    /**
     * An abstract function to output generic content which precedes the specific
     *  output of a module-action or admin operation. Themes may ignore this,
     *  and instead deal with such content during postprocess(). Might be useful
     *  for backward-compatibility.
     * @abstract
     */
    public function do_header() {}

    /**
     * An abstract function to output generic content which follows the specific
     *  output of a module-action or admin operation. Themes may ignore this,
     *  and instead deal with such content during postprocess(). Might be useful
     *  for backward-compatibility.
     */
    public function do_footer() {}

    /**
     * An abstract function to output the content of the menu-root (home) page
     * or a menu-section. e.g. a dashboard
     *
     * @abstract
     * @param string $section_name A menu-section name, typically empty to work
     * with the whole menu.
     */
    abstract public function do_toppage($section_name);

/* TODO DISCUSS WITH ROBERT ... BETTER SOME OTHER WAY ?
    @since 2.3

    abstract public function do_authenticated_page();

    abstract public function do_loginpage( string $pageid = null );

    /**
     * Set the HTML for the primary content for the page.
     *
     * @see do_minimal()
     * /
    public function set_content( string $content )
    {
        $this->_primary_content = $content;
    }

    /**
     * Get the HTML for the primary content of the page.
     * /
    public function get_content()
    {
        return $this->_primary_content;
    }

    /**
     * An abstract method to output a minimal HTML page (typically for login and other operations that do not require navigations)
     * /
    abstract public function do_minimal();
*/
    /**
     * Display and process a login form
     * Since 2.3 this is an optional supplement to the login module
     *
     * @param  array $params
     */

    /**
     * An abstract function for processing the generated content.
     * Called only via footer.php. Many admin themes will do most of their work
     * in this method (e.g. passing the content through a smarty template)
     *
     * @param string $html The page content generated by a module action or admin operation
     * @return string  Modified content (or maybe null upon error?)
     */
    abstract public function postprocess($html);

    /**
     * ------------------------------------------------------------------
     * Tab Functions
     * ------------------------------------------------------------------
     */

    /**
     * Output a string suitable for staring tab headers
     * i.e. echo $this->StartTabHeaders();
     *
     * @final
     * @deprecated since 2.3. Instead use cms_admin_tabs::start_tab_headers()
     * @return string
     */
    final public function StartTabHeaders() : string
    {
        return cms_admin_tabs::start_tab_headers();
    }

    /**
     * Set a specific tab header.
     * i.e:  echo $this->SetTabHeader('preferences',$this->Lang('preferences'));
     *
     * @final
     * @param string $tabid The tab id
     * @param string $title The tab title
     * @param bool $active Flag indicating whether this tab is active
     * @deprecated since 2.3 Use cms_admin_tabs::set_tab_header()
     * @return string
     */
    final public function SetTabHeader(string $tabid,string $title,bool $active=false) : string
    {
        return cms_admin_tabs::set_tab_header($tabid,$title,$active);
    }

    /**
     * Output a string to stop the output of headers and close the necessary XHTML div.
     *
     * @final
     * @deprecated since 2.3 Use cms_admin_tabs::end_tab_headers()
     * @return string
     */
    final public function EndTabHeaders() : string
    {
        return cms_admin_tabs::end_tab_headers();
    }

    /**
     * Output a string to indicate the start of XHTML areas for tabs.
     *
     * @final
     * @deprecated since 2.3 Use cms_admin_tabs::start_tab_content()
     * @return string
     */
    final public function StartTabContent() : string
    {
        return cms_admin_tabs::start_tab_content();
    }

    /**
     * Output a string to indicate the end of XHTML areas for tabs.
     *
     * @final
     * @deprecated since 2.3 Use cms_admin_tabs::end_tab_content()
     * @return string
     */
    final public function EndTabContent() : string
    {
        return cms_admin_tabs::end_tab_content();
    }

    /**
     * Output a string to indicate the start of the output for a specific tab
     *
     * @final
     * @param string $tabid The tabid (see SetTabHeader)
     * @param array $params Parameters unused since 2.3
     * @deprecated since 2.3 Use cms_admin_tabs::start_tab()
     * @return string
     */
    final public function StartTab(string $tabid) : string
    {
        return cms_admin_tabs::start_tab($tabid);
    }

    /**
     * Output a string to indicate the end of the output for a specific tab.
     *
     * @final
     * @deprecated since 2.3 Use cms_admin_tabs::end_tab()
     * @return string
     */
    final public function EndTab() : string
    {
        return cms_admin_tabs::end_tab();
    }
} // class

