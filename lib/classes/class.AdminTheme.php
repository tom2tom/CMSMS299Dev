<?php
/*
Base class for CMSMS admin themes
Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use ArrayTreeIterator;
use CmsLogicException;
use CMSMS\AdminAlerts\Alert;
use CMSMS\AdminTabs;
use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\ArrayTree;
use CMSMS\Bookmark;
use CMSMS\BookmarkOperations;
use CMSMS\FormUtils;
use CMSMS\HookOperations;
use CMSMS\internal\AdminNotification;
use CMSMS\ModuleOperations;
use CMSMS\RequestParameters;
use CMSMS\SystemCache;
use CMSMS\Url;
use CMSMS\UserParams;
use CMSMS\Utils;
use RecursiveArrayTreeIterator;
use RecursiveIteratorIterator;
use Throwable;
use const CMS_ADMIN_PATH;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function add_page_foottext;
use function add_page_headtext;
use function audit;
use function check_permission;
use function cleanArray;
use function cleanValue;
use function cms_join_path;
use function cms_module_places;
use function cms_path_to_url;
use function endswith;
use function get_page_foottext;
use function get_page_headtext;
use function get_secure_param;
use function get_userid;
use function lang;
use function startswith;

/**
 * Base class for CMSMS admin themes.
 * The theme-object in use, derived from this, will be a singleton.
 *
 * @package CMS
 * @license GPL
 * @since 2.9
 * @since 1.11 as global-namespace CmsAdminAdminTheme
 * @author  Robert Campbell
 * @property-read string $themeName Return the theme name
 * @property-read int $userid Return the current logged in userid (deprecated)
 * @property-read string $title The current page title
 * @property-read string $subtitle The current page subtitle
 */
abstract class AdminTheme
{
    /* *
     * @ignore
     */
    //const VALIDSECTIONS = ['view','content','layout','files','usersgroups','extensions','services','ecommerce','siteadmin','myprefs'];

    /**
     * @var AdminTheme sub-class instance
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
     * Notice-string accumulators
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

    /* *
     * Cache for content to be included in the page header
     * @ignore
     */
//    private $_headtext;

    /* *
     * Cache for content to be included in the page footer
     * @ignore
     */
//    private $_foottext;

    /**
     * Cache for the entire content of a page
     * @ignore
     * @since 2.3
     */
    private $_primary_content;

    /**
     * Whether this theme uses fontimages (.i files)
     * @ignore
     */
    protected $_fontimages = null;

    /**
     * Use small-size icons (named like *-small.ext) if available
     * @ignore
     */
    protected $_smallicons = false;

    /**
     * Init for all specific-theme sub-classes
     * @ignore
     */
    protected function __construct()
    {
        if (is_object(self::$_instance)) {
            throw new CmsLogicException('Only one instance of a theme object is permitted');
        }

        $this->_url = $_SERVER['SCRIPT_NAME'];
        $this->_query = $_SERVER['QUERY_STRING'] ?? '';
        if (!$this->_query) {
            $module = RequestParameters::get_request_values('module');
            if ($module) {
                $this->_query = 'module='.$module;
            }
        }
        if (strpos($this->_url, '/') === false) {
            $this->_script = $this->_url;
        } else {
            $tmp = explode('/',$this->_url);
            $this->_script = end($tmp);
        }

        if ($this->_fontimages === null) {
            $path = cms_join_path(CMS_ADMIN_PATH,'themes',$this->themeName,'images','icons','system','*.i');
            $items = glob($path,GLOB_NOSORT);
            $this->_fontimages = ($items != false);
        }

        $this->UnParkNotices();

        HookOperations::add_hook('AdminHeaderSetup', [$this, 'AdminHeaderSetup']);
        HookOperations::add_hook('AdminBottomSetup', [$this, 'AdminBottomSetup']);
        // generate name on demand by FormUtils::create_menu()
        HookOperations::add_hook('ThemeMenuCssClass', [$this, 'MenuCssClassname']);
    }

    /**
     * @ignore
     */
    private function __clone() {}

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch( $key) {
        case 'themeName':
            $o = strlen(__NAMESPACE__) + 1; //separator-offset
            $class = get_class($this);
            if (endswith($class,'Theme')) { $class = substr($class,$o,-5); }
            else { $class = substr($class,$o); }
            return $class;
        case 'userid':
            return get_userid();
        case 'title':
            return $this->_title;
        case 'subtitle':
            return $this->_subtitle;
        case 'root_url':
            return AppSingle::Config()['admin_url'].'/themes/'.$this->themeName;
        }
    }

    /**
     * Get the singleton admin-theme object (a sub-class of this class)
     * per the specified name or else the current user's recorded preference
     * or else the system default.
     * This method [re]creates the theme object if appropriate.
     * NOTE the hierarchy of theme-classes prevents the theme singleton
     * from being populated and cached in App|AppSingle like most other
     * singletons.
     *
     * @param mixed string|null $name Optional theme name.
     * @return mixed AdminTheme admin theme object | null
     */
    public static function get_instance($name = '')
    {
        if (is_object(self::$_instance)) {
            if ($name == '' || $name = self::$_instance->themeName) {
                return self::$_instance;
            }
            self::$_instance = null; // prevent exception when recreated
        }

        if (!$name) {
            $userid = get_userid(FALSE);
            if ($userid !== NULL) {
                $name = UserParams::get_for_user($userid,'admintheme');
            }
            if (!$name) $name = self::GetDefaultTheme();
        }
        $themeObjName = 'CMSMS\\'.$name;
        if (class_exists($themeObjName)) {
            self::$_instance = new $themeObjName();
        }
        else {
            $fn = cms_join_path(CMS_ADMIN_PATH,'themes',$name,$name.'Theme.php');
            if (is_file($fn)) {
                include_once $fn;
                $themeObjName = 'CMSMS\\'.$name.'Theme';
                self::$_instance = new $themeObjName();
            }
            else {
                // theme not found... use default
                $name = self::GetDefaultTheme();
                $fn = cms_join_path(CMS_ADMIN_PATH,'themes',$name,$name.'Theme.php');
                if (is_file($fn)) {
                    include_once $fn;
                    $themeObjName = 'CMSMS\\'.$name.'Theme';
                    self::$_instance = new $themeObjName();
                }
                else {
                    // oops, still not found
                    return null;
                }
            }
        }
        return self::$_instance;
    }

    /**
     * This is an alias for get_instance().
     * @deprecated since 2.9 instead use AdminTheme::get_instance($name)
     *
     * @param mixed string|null $name Optional theme name.
     * @return mixed AdminTheme sub-class object | null
     */
    public static function GetThemeObject($name = '')
    {
        return self::get_instance($name);
    }

    /**
     * Helper for constructing js data
     * @ignore
     * @since 2.9
     * @param array $strings
     * @return mixed string or false
     */
    private function merger(array $strings)
    {
        if ($strings) {
            if (count($strings) > 1) {
                foreach( $strings as &$one) {
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
     * Hook function to populate page content at runtime
     * This will normally be sub-classed by specific themes, and such methods
     * should call here (their parent) as well as their own specific setup
     * @since 2.9
     * @return 2-member array (not typed to support back-compatible themes)
     * [0] = array of data for js vars, members like varname=>varvalue
     * [1] = array of [x]html string(s) which the browser will interpret
     *  as files to fetch and process - css and/or js, mainly
     */
    public function AdminHeaderSetup()
    {
        $msgs = [
            'errornotices' => $this->merger($this->_errors),
            'warnnotices' => $this->merger($this->_warnings),
            'successnotices' => $this->merger($this->_successes),
            'infonotices' => $this->merger($this->_infos),
        ];
        $vars = array_filter($msgs);
        return [$vars, []];
    }

    /**
     * Hook function to populate page content at runtime
     * Normally sub-classed
     *
     * @return array
     */
    public function AdminBottomSetup() : array
    {
        return [];
    }

    /**
     * Convert spaces into a non-breaking space HTML entity.
     * To make menu-item labels look nicer
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
        if (strpos($url,CMS_SECURE_PARAM_NAME) !== FALSE) {
            $from = '/'.CMS_SECURE_PARAM_NAME.'=[a-zA-Z0-9]{16,19}/i';
            $to = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
            return preg_replace($from,$to,$url);
        }
        elseif (startswith($url,CMS_ROOT_URL) || !startswith($url,'http')) {
            //TODO generally support the websocket protocol 'wss' : 'ws'
            $prefix = ( strpos($url,'?') !== FALSE) ? '&' : '?';
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
// TODO also clear cache group 'module_menus' after change of group membership or permission
        $data = SystemCache::get_instance()->get('themeinfo'.$uid, 'module_menus');
        $data = false;  //DEBUG
        if (!$data) {
            // data doesn't exist, gotta build it
            $usermoduleinfo = [];
            $modops = ModuleOperations::get_instance();
            $allmodules = $modops->GetInstalledModules();
            foreach( $allmodules as $modname) {
                $modinst = $modops->get_module_instance($modname);
                if (is_object($modinst) && $modinst->HasAdmin()) {
                    $recs = $modinst->GetAdminMenuItems();
                    if ($recs) {
                        $sys = $modops->IsSystemModule($modname);
                        $suffix = 1;
                        foreach( $recs as &$one) {
                            if (!$one->valid()) continue;
                            $key = $modname.$suffix++;
                            $one->name = $key;
                            if (empty($one->url)) {
                                $one->url = $modinst->create_url('m1_', $one->action);
                            }
                            $one->system = $sys;
                            $usermoduleinfo[$key] = $one;
                        }
                        unset($one);
                    }
                }
            }
            // cache the array, even if empty
            SystemCache::get_instance()->set('themeinfo'.$uid, $usermoduleinfo, 'module_menus');
            $data = $usermoduleinfo;
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
    private function _SetModuleAdminInterfaces()
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

            foreach( $usermoduleinfo as $obj) {
                if (empty($obj->section)) {
                     $obj->section = 'extensions';
/* PRESERVE ORIGINAL APPROACH
                } elseif ($obj->section == 'content') {
                    //hack pending non-core module updates by developers
                    if ($obj->module != 'CMSContentManager') {
                        $obj->section = 'services';
                    }
*/
                }
                // fix up the session key stuff
                $obj->url = $this->_fix_url_userkey($obj->url);
                if (!isset($obj->icon)) {
                    // find the 'best' icon
                    $modname = $obj->module;
                    $dirs = cms_module_places($modname);
                    foreach( $dirs as $base) {
                        if ($this->_smallicons) {
                            foreach( $smallappends as $one) {
                                $path = cms_join_path($base, ...$one);
                                if (is_file($path)) {
                                    $obj->icon = cms_path_to_url($path);
                                    break 2;
                                }
                            }
                        }
                        foreach( $appends as $one) {
                            $path = cms_join_path($base, ...$one);
                            if (is_file($path)) {
                                $obj->icon = cms_path_to_url($path);
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
    private function _SetAggregatePermissions(bool $force = false)
    {
        if (is_array($this->_perms) && !$force) return;

        $this->_perms = [];

        // content section TODO individual
        $this->_perms['contentPerms'] =
            check_permission($this->userid, 'Manage All Content') |
            check_permission($this->userid, 'Modify Any Page') |
            check_permission($this->userid, 'Add Pages') |
            check_permission($this->userid, 'Remove Pages') |
            check_permission($this->userid, 'Reorder Content');

        $this->_perms['templatePerms'] =
            check_permission($this->userid, 'Add Templates') |
            check_permission($this->userid, 'Modify Templates');

        $this->_perms['stylePerms'] =
            check_permission($this->userid, 'Manage Stylesheets');

        $this->_perms['layoutPerms'] =
            check_permission($this->userid, 'Manage Designs') |
            $this->_perms['stylePerms'] |
            $this->_perms['templatePerms'];

        // file
        $this->_perms['filePerms'] = check_permission($this->userid, 'Modify Files');

        // UDT/user-plugin files (2.9+)
        $this->_perms['plugPerms'] = check_permission($this->userid, 'Manage Simple Plugins');

        // myprefs
        $this->_perms['myaccount'] = check_permission($this->userid,'Manage My Account');
        $this->_perms['mysettings'] = check_permission($this->userid,'Manage My Settings');
        $this->_perms['mybookmarks'] = check_permission($this->userid,'Manage My Bookmarks');
        $this->_perms['myprefPerms'] = $this->_perms['myaccount'] |
            $this->_perms['mysettings'] | $this->_perms['mybookmarks'];

        // user/group
        $this->_perms['userPerms'] = check_permission($this->userid, 'Manage Users');
        $this->_perms['groupPerms'] = check_permission($this->userid, 'Manage Groups');
        $this->_perms['usersGroupsPerms'] = $this->_perms['userPerms'] |
            $this->_perms['groupPerms'] | $this->_perms['myprefPerms'];

        // admin
        $this->_perms['sitePrefPerms'] = check_permission($this->userid, 'Modify Site Preferences');
        $this->_perms['adminPerms'] = $this->_perms['sitePrefPerms'];
        $this->_perms['siteAdminPerms'] = $this->_perms['sitePrefPerms'] |
            $this->_perms['adminPerms'];

        // extensions
        $this->_perms['codeBlockPerms'] = check_permission($this->userid, 'Modify User-defined Tags');
        $this->_perms['modulePerms'] = check_permission($this->userid, 'Modify Modules');
        $config = AppSingle::Config();
        $this->_perms['eventPerms'] = $config['develop_mode'] && check_permission($this->userid, 'Modify Events');
        $this->_perms['taghelpPerms'] = check_permission($this->userid, 'View Tag Help');
        $this->_perms['usertagPerms'] = $this->_perms['taghelpPerms'] |
            check_permission($this->userid, 'Manage Simple Plugins');
        $this->_perms['extensionsPerms'] = $this->_perms['codeBlockPerms'] |
            $this->_perms['modulePerms'] | $this->_perms['eventPerms'] |
            $this->_perms['taghelpPerms'];
    }

    /**
     * @ignore
     * @todo export this for general use
     * @return 2-member array,
     *  [0] = admin-root-relative URL including get-parameters derived from request parameters
     *  [1] = assoc. array of request parameters
     */
    private function _parse_request() : array
    {
        $parms = RequestParameters::get_action_params();
        if ($parms) {
            // construct a mact-parameter in case something wants to use that
            $module = $parms['module'] ?? '';
            $action = $parms['action'] ?? '';
            if ($module && $action) {
                $inline = $parms['inline'] ?? 0;
                $id = $parms['id'] ?? '';
                $parms['mact'] = "$module,$id,$action,$inline";
            } else {
                $id = '';
            }
        } else {
            $parms = [];
            $id = '';
        }
        $parms += RequestParameters::get_general_params($id);

        $config = AppSingle::Config();
        $url_ob = new Url($config['admin_url']);
        $urlroot = $url_ob->get_path();

        $url_ob = new Url($_SERVER['REQUEST_URI']);
        $urlpath = $url_ob->get_path();
        $text = substr($urlpath, strlen($urlroot) + 1);
        if (!$text) {
            $text = 'menu.php';
        }
        $text .= '?'.RequestParameters::create_action_params($parms);
        return [$text, $parms];
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
        if ($str == '') $str = null;
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
        if ($str == '') $str = null;
        $this->_subtitle = $str;
    }

    /**
     * Check whether the user has one of the aggregate permissions
     * @ignore
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
     * Generate complete admin menu array-tree from PHP definition
     * @ignore
     * @since 2.9
     */
    protected function populate_tree()
    {
        $urlext = get_secure_param();
        $items = [];
        require_once cms_join_path(CMS_ADMIN_PATH, 'configs', 'method.adminmenu.php');

        foreach( $menucontent as $item) {
            $val = ( !empty($item['url'])) ? $item['url'] : (($item['parent']!= null) ? 'menu.php' : '');
            if ($val) { //not the root node
                if (!(strpos($val, '://') > 0 || strpos($val, '//') === 0)) {
                    $val .= $urlext;
                }
                if (!empty($item['urlparm'])) {
                    $val .= $item['urlparm'];
                    unset($item['urlparm']);
                }
                $item['url'] = $val;

                $val = $item['labelkey'] ?? ''; // should always be present, except for root node
                if ($val) {
                    $item['title'] = $this->_FixSpaces(lang($item['labelkey']));
                    unset($item['labelkey']);
                }

                $val = $item['description'] ?? '';
                if (!$val) {
                    $val = $item['descriptionkey'] ?? '';
                    if ($val) {
                        $val = lang($val);
                        unset($item['descriptionkey']);
                    }
                }
                $item['description'] = $val;

                $val = $item['priority'] ?? 1;
                $item['priority'] = (int)$val;
                $item['final'] = !empty($item['final']);

                $val = $item['show'] ?? true;
                unset($item['show']);
                if (!is_bool($val)) {
                    $val = $this->HasPerm($val);
                }
                $item['show_in_menu'] = $val;
            }
            else {
                $item['show_in_menu'] = false;
            }
            $items[] = $item;
        }
        unset($menucontent);

        // merge module-related items, if any
        $this->_SetModuleAdminInterfaces();
        foreach( $this->_modules as $key => $obj) {
            $item = ['parent' => null] + $obj->get_all(); //may include 'icon' (a file url or false)
            $item['parent'] = (!empty($item['section'])) ? $item['section'] : 'extensions';
            unset($item['section']);
            $item['title'] = $this->_FixSpaces($item['title']);
            $item['final'] = true;
            $item['show_in_menu'] = true;
            $items[] = $item;
        }

        $tree = ArrayTree::load_array($items);
//        $col = new Collator(TODO);
        $iter = new RecursiveArrayTreeIterator(
                new ArrayTreeIterator($tree),
                RecursiveIteratorIterator::SELF_FIRST | RecursiveArrayTreeIterator::NONLEAVES_ONLY
               );
        foreach( $iter as $key => $value) {
            if (!empty($value['children'])) {
                $node = ArrayTree::node_get_data($tree, $value['path'], '*');
                uasort($node['children'], function($a,$b) use ($value) { //use $col
                    $pa = $a['priority'] ?? 999;
                    $pb = $b['priority'] ?? 999;
                    $c = $pa <=> $pb;
                    if ($c != 0) {
                        return $c;
                    }
                    return strnatcmp($a['title'],$b['title']); //TODO return $col->compare($a['title'],$b['title']);
                });
                $ret = ArrayTree::node_set_data($tree, $value['path'], 'children', $node['children']);
//            } else {
//              $adbg = $value;
            }
        }

        $this->_menuTree = $tree;
    }

    /**
     * Populate the admin navigation tree (if not done before), and
     * return some or all of it.
     * This might be called directly from a template.
     *
     * @since 1.11
     * @param mixed $parent    Optional name of the wanted root section/node,
     *  or null for actual root node. The formerly-used -1 is also
     *  recognized as an indicator of the root node. Default null
     * @param int   $maxdepth  Optional no. of sub-root levels to be
     *  displayed for $parent. < 1 indicates no maximum depth. Default 3
     * $param mixed $usepath   Since 2.9 Optional treepath for the selected item.
     *  Array, or ':'-separated string, of node names (commencing with 'root'),
     *  or (boolean) true in which case a path is derived from the current request,
     *  or false to skip selection-processing. Default true
     * @param int   $alldepth  Optional no. of sub-root levels to be displayed
     *  for tree-paths other than $parent. < 1 indicates no limit. Default 2
     * @param bool  $striproot Since 2.9 Optional flag whether to omit the
     *  tree root-node from the returned array. Default true (backward compatible)
     * @return array  Nested menu nodes.  Each node's 'children' member represents the nesting
     */
    public function get_navigation_tree($parent = null, $maxdepth = 3, $usepath = true, $alldepth = 2, $striproot = true)
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
        }
        $dmax = max($maxdepth, $alldepth + 1);

        $iter = new RecursiveArrayTreeIterator(
                new ArrayTreeIterator($tree),
                RecursiveIteratorIterator::CHILD_FIRST
               );
        foreach( $iter as $value) {
            $depth = $iter->getDepth();
            if ($depth == 0) continue;
            if (empty($value['children'])) {
                if (empty($value['final']) && isset($value['path'])) {
                    ArrayTree::drop_node($tree, $value['path']); // this doesn't affect the iterator, and so it may return to this node
                    continue;
                }
            }
            if ($maxdepth > 0 || $alldepth > 0) {
                $limit = ($depth > 0) ? $dmax : 9999;
                if (!empty($value['children'])) {
                    if (isset($value['path'])) {
                        $chks = max(0, $limit - $depth);
                        $ret = ArrayTree::get_descend_data($value['children'], 'final', $chks);
                        if (!$ret || !array_filter($ret)) {
                            if (1) { //TODO KEEP if ANY child at depth $limit
                                ArrayTree::drop_node($tree, $value['path']);
                                continue;
                            }
                        }
                    }
                }
                if ($depth > $limit) {
                    if (isset($value['path'])) {
                        ArrayTree::drop_node($tree, $value['path']);
                        continue;
                    }
                }
            } elseif (!empty($value['children'])) {
                if (isset($value['path'])) {
                    $ret = ArrayTree::get_descend_data($value['children'], 'final');
                    if (!$ret || !array_filter($ret)) {
                        ArrayTree::drop_node($tree, $value['path']);
                        continue;
                    }
                }
            }
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
        if (!$module_name) return;
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
        if ($this->_action_module) return $this->_action_module;
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
        if (!$module_name) $module_name = $this->get_action_module();
        if (!$module_name) return;
        //TODO some core method c.f. \CMSMS\AdminUtils::get_generic_url()
        $modman = Utils::get_module('ModuleManager');
        if (is_object($modman)) {
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
        $bookops = new BookmarkOperations();
        $marks = array_reverse($bookops->LoadBookmarks($this->userid));

        if (!$pure) {
            $urlext = get_secure_param();
            $mark= new Bookmark();
            $mark->title = lang('addbookmark');
            $mark->url = 'makebookmark.php'.$urlext.'&title='.urlencode($this->_title);
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
            foreach( $urls as $key => $value) {
                $this->_breadcrumbs[] = [
                    'url' => $value,
                    'title' => $titles[$key],
                ];
            }
        }
        return $this->_breadcrumbs;
    }

    /**
     * Return a specified property of the active menu-item, if such exists.
     *
     * @param string $key identifier
     * @return string
     */
    public function get_active(string $key)
    {
        if ($this->_menuTree && $this->_activePath) {
            return ArrayTree::node_get_data($this->_menuTree, $this->_activePath, $key);
        }
    }

    /**
     * Return the title of the active item.
     * Used for module actions
     *
     * @return string
     */
    public function get_active_title()
    {
        return $this->get_active('title');
    }

    /* *
     * Return the icon of the active item.
     *
     * @return string
     */
/*    public function get_active_icon()
    {
        return $this->get_active('icon');
    }
*/
    /**
     * Cache some data
     *
     * @param string $key
     * @param mixed $value
     * @returns void
     */
    public function set_value($key,$value)
    {
        if (is_null($value) && is_array($this->_data) && isset($this->_data[$key])) {
            unset($this->_data[$key]);
            return;
        }
        if ($value) {
            if (!is_array($this->_data)) $this->_data = [];
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
        if (is_array($this->_data) && isset($this->_data[$key])) return $this->_data[$key];
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
        foreach( $this->_menuItems[$section]['children'] as $one) {
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
     * Generate xhtml tag to display the themed version of $image (if it exists),
     *  preferring image-file extension/type (in order):
     *  .i(if used in this theme), .svg, .png, .gif, .jpg, .jpeg
     *  As a convenience, this can also process specific images that are not
     *  included in the current theme.
     * @param string $image Image file identifier, a theme-images-dir (i.e. 'images')
     *  relative-filepath, or an absolute filepath. It may omit extension (type)
     * @param string $alt Optional alternate identifier for the created
     *  image element (NOT also used for its default title)
     * @param int $width Optional image-width (ignored for svg)
     * @param int $height Optional image-height (ignored for svg)
     * @param string $class Optional class. For .i (iconimages), class "fontimage" is always prepended
     * @param array $attrs Since 2.9 Optional array with any or all attributes for the image/span tag
     * @return string
     */
    public function DisplayImage($image, $alt = '', $width = 0, $height = 0, $class = '', $attrs = [])
    {
        if (!is_array($this->_imageLink)) {
            $this->_imageLink = [];
        }

        if (!isset($this->_imageLink[$image])) {
            if (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~',$image)) { //not absolute
                $detail = preg_split('~\\/~',$image);
                $fn = array_pop($detail);
                if ($detail) {
                    $rel = implode(DIRECTORY_SEPARATOR,$detail).DIRECTORY_SEPARATOR;
                } else {
                    $rel = '';
                }
                $base = cms_join_path(CMS_ADMIN_PATH,'themes',$this->themeName,'images',$rel); //has trailing separator
            } else {
                $fn = basename($image);
                $base = dirname($image).DIRECTORY_SEPARATOR;
                $rel = false;
            }
            $p = strrpos($fn,'.');
            if ($p !== false) {
                $fn = substr($fn,0,$p+1);
            } else {
                $fn .= '.';
            }

            $exts = ['i','svg','png','gif','jpg','jpeg'];
            if (!$this->_fontimages) {
                unset($exts[0]);
            }
            foreach( $exts as $type) {
                $path = $base.$fn.$type;
                if (is_file($path)) {
                    if ($type != 'i') {
                        if ($rel !== false) {
                            //admin-relative URL will do
                            $path = substr($path, strlen(CMS_ADMIN_PATH) + 1);
                        }
                        $this->_imageLink[$image] = cms_path_to_url($path);
                    } else {
                        $this->_imageLink[$image] = $path;
                    }
                    break;
                } else {
                    $path = '';
                }
            }
            if (!$path) {
                $this->_imageLink[$image] = 'themes/assets/images/space.png';
            }
        }

        $path = $this->_imageLink[$image];
        $p = strrpos($path,'.');
        $type = substr($path,$p+1);

        if ($type == 'i') {
            $props = parse_ini_file($path, false, INI_SCANNER_TYPED);
            if ($props) {
                foreach( $props as $key => $value) {
                    if (isset($attrs[$key])) {
                        if (is_numeric($value) || is_bool($value)) {
                            continue; //supplied attrib prevails
                        } elseif (is_string($value)) {
                            $attrs[$key] = $value.' '.$attrs[$key];
                        }
                    } else {
                        $attrs[$key] = $value;
                    }
                }
            }
            if (isset($attrs['class'])) {
                $attrs['class'] .= ' '.trim($class.' fontimage');
            } else {
                $attrs['class'] = trim($class.' fontimage');
            }
        }

        $extras = array_merge(['width'=>$width, 'height'=>$height, 'class'=>$class, 'alt'=>$alt, 'title'=>''], $attrs);
        if (!$extras['alt']) {
            if ($extras['title']) {
                $extras['alt'] = $extras['title'];
            } else {
                $p = strrpos($path,'/');
                $extras['alt'] = substr($path, $p+1);
            }
        }

        switch ($type) {
          case 'svg':
            $extras['class'] = trim('svgicon '.$extras['class']);
            // see https://css-tricks.com/using-svg
            $alt = str_replace('svg','png',$path);
            $res = '<img src="'.$path.'" onerror="this.onerror=null;this.src=\''.$alt.'\';"';
            break;
          case 'i':
            $res = '<i';
            break;
          default:
            $res = '<img src="'.$path.'"';
            break;
        }

        foreach( $extras as $key => $value) {
            if ($value !== '' || $key == 'title') {
                $res .= " $key=\"$value\"";
            }
        }
        if ($type != 'i') {
            $res .= ' />';
        } else {
            $res .= '></i>';
        }
        return $res;
    }

    /**
     * Cache error-message(s) to be shown in a dialog during the current request.
     * @deprecated since 2.9 Use RecordNotice() instead
     *
     * @param mixed $errors The error message(s), string|strings array
     * @param string $get_var An optional $_GET variable name. Such variable
     *  contains a lang key for an error string, or an array of such keys.
     *  If specified, $errors is ignored.
     * @return empty string (in case something thinks it's worth echoing)
     */
    public function ShowErrors($errors, $get_var = null)
    {
        $this->PrepareStrings($this->_errors, $errors, '', $get_var);
        return '';
    }

    /**
     * Cache success-message(s) to be shown in a dialog during the current request.
     * @deprecated since 2.9 Use RecordNotice() instead
     *
     * @param mixed $message The message(s), string|strings array
     * @param string $get_var An optional $_GET variable name. Such variable
     *  contains a lang key for an error string, or an array of such keys.
     *  If specified, $message is ignored.
     * @return empty string (in case something thinks it's worth echoing)
     */
    public function ShowMessage($message, $get_var = null)
    {
        $this->PrepareStrings($this->_successes, $message, '', $get_var);
        return '';
    }

    /**
     * Cache message string(s) to be shown in a dialog during the current request.
     * @since 2.9
     * @internal
     *
     * @param array store The relevant string-accumulator
     * @param mixed $message The error message(s), string|strings array
     * @param string $title  Title for the message(s), may be empty
     * @param mixed $get_var A $_GET variable name, or null. If specified,
     *  such variable is expected to contain a lang key for an error string,
     *  or an array of such keys. If non-null, $message is ignored.
     */
    protected function PrepareStrings(array &$store, $message, string $title, $get_var = null)
    {
        if ($get_var && !empty($_GET[$get_var])) {
            if (is_array($_GET[$get_var])) {
                cleanArray($_GET[$get_var]);
                foreach( $_GET[$get_var] as $one) {
                    if ($one) {
                        $store[] = lang($one);
                    }
                }
            } else {
                $store[] = lang(cleanValue($_GET[$get_var]));
            }
        } elseif ($title) {
            if (isset($store[$title])) {
                //TODO merge
            } else {
                $store[$title] = $message;
            }
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
     * @since 2.9
     *
     * @param string $type Message-type indicator 'error','warn','success' or 'info'
     * @param mixed $message The error message(s), string|strings array
     * @param string $title Optional title for the message(s)
     * @param bool $cache Optional flag, whether to setup for display during the next request (instead of the current one)
     * @param mixed $get_var Optional $_GET variable name. Such variable
     *  is expected to contain a lang key for an error string, or an
     *  array of such keys. If specified, $message is ignored.
     */
    public function ParkNotice(string $type, $message, string $title = '', $get_var = null)
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
     * @since 2.9
     */
    protected function retrieve_message($type, &$into)
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
     * Retrieve message(s) that were logged during a prior request, to be shown in a notification-dialog
     *
     * @since 2.9
     */
    protected function UnParkNotices($type = null)
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
     * @since 2.9
     * @param string $type Message-type indicator 'error','warn','success' or 'info'
     * @param mixed $message The error message(s), string|strings array
     * @param string $title Optional title for the message(s)
     * @param bool $defer Optional flag, whether to setup for display during the next request (instead of the current one)
     * @param mixed $get_var Optional $_GET variable name. Such variable
     *  is expected to contain a lang key for an error string, or an
     *  array of such keys. If specified, $message is ignored.
     */
    public function RecordNotice(string $type, $message, string $title = '', bool $defer = false, $get_var = null)
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
     * Cache page-related data for later use. This might be called by modules,
     * but (from 2.9) is not used by any admin operation.
     *
     * @param string $title_name        Displayable content, or a lang key, for the page-title to be displayed
     *     Assumed to be a key, and passed through lang(), if $module_help_type is FALSE.
     * @param array  $extra_lang_params Optional extra string(s) to be supplied (with $title_name) to lang()
     *     Ignored if $module_help_type is not FALSE
     * @param string $link_text         Optional text to show in a module-help link (if $module_help_type is 'both')
     * @param mixed  $module_help_type  Optional flag for type(s) of module help link display.
     *  Recognized values are FALSE for no link, TRUE to display an icon-link, and 'both' for icon- and text-links
     */
    public function ShowHeader($title_name, $extra_lang_params = [], $link_text = '', $module_help_type = false)
    {
        if ($title_name) {
            $this->set_value('pagetitle', $title_name);
            if ($extra_lang_params) {
                $this->set_value('extra_lang_params', $extra_lang_params);
            }
        }

        $this->set_value('module_help_type', $module_help_type);
        if ($module_help_type) {
            // set the module help url TODO supply this TO the theme
            $this->set_value('module_help_url', $this->get_module_help_url());
        }

        // are we processing a module action?
        // TODO maybe cache this in $this->_modname ??
        if (isset($_REQUEST['module'])) {
            $module = cleanValue($_REQUEST['module']); //TODO cleanString()
        } else {
            $module = RequestParameters::get_request_values('module'); //maybe null
        }

        if ($module) {
            $tag = AdminUtils::get_module_icon($module, ['alt'=>$module, 'class'=>'module-icon']);
        } else {
            $tag = ''; //TODO get icon for admin operation
            //$tag = $this->get_active_icon());
        }
        $this->set_value('pageicon', $tag);
/* TODO figure this out ... are breadcrumbs ever relevant in this context?
        $bc = $this->get_breadcrumbs();
        if ($bc) {
            $n = count($bc);
            for ($i = 0; $i < $n; ++$i) {
                $rec = $bc[$i];
                $title = $rec['title'];
                if ($module_help_type && $i + 1 == $n) {
                    $module_name = $module;
                    $module_name = preg_replace('/([A-Z])/', "_$1", $module_name);
                    $module_name = preg_replace('/_([A-Z])_/', "$1", $module_name);
                    if ($module_name[0] == '_') {
                        $module_name = substr($module_name, 1);
                    }
                } else {
                    if (($p = strrchr($title, ':')) !== false) {
                        $title = substr($title, 0, $p);
                    }
                    // find the key of the item with this title.
//unused            $title_key = $this->find_menuitem_by_title($title);
                }
            } // for loop
            $this->set_value('page_crumbs', $TODO);
        }
*/
    }

    /**
     * Return the name of the default admin theme.
     *
     * @return string, maybe empty
     */
    public static function GetDefaultTheme()
    {
        $tmp = self::GetAvailableThemes();
        if ($tmp) {
            $name = AppParams::get('logintheme');
            if ($name && in_array($name,$tmp)) return $name;
            return reset($tmp);
        }
        return '';
    }

    /**
     * Retrieve a list of the available admin themes.
     *
     * @param bool $fullpath since 2.9 Optional flag. Default false.
     *  If true, array values are theme-class filepaths. Otherwise theme names.
     * @return array A theme-name-sorted hash of theme names or theme filepath strings
     */
    public static function GetAvailableThemes($fullpath = false)
    {
        $res = [];
        $files = glob(cms_join_path(CMS_ADMIN_PATH,'themes','*','*Theme.php'),GLOB_NOESCAPE);
        if ($files) {
            foreach( $files as $one) {
                if (is_readable($one)) {
                    $name = basename($one,'Theme.php');
                    $res[$name] = ($fullpath) ? $one : $name;
                }
            }
        }
        return $res;
    }

    /**
     * Record a notification for display in the theme.
     * @deprecated since 2.9 instead use RecordNotice()
     *
     * @param AdminNotification $notification A reference to the new notification
     */
    public function add_notification(AdminNotification &$notification)
    {
/*      if (!is_array($this->_notifications)) $this->_notifications = [];
        $this->_notifications[] = $notification;
*/
    }

    /**
     * Record a notification for display in the theme.
     * This is a wrapper around the add_notification method.
     * @deprecated since 2.9 instead use RecordNotice()
     *
     * @param int $priority priority level between 1 and 3
     * @param string $module The module name.
     * @param string $html The contents of the notification
     */
    public function AddNotification($priority,$module,$html)
    {
/*    $notification = new AdminNotification();
      $notification->priority = max(1,min(3,$priority));
      $notification->module = $module;
      $notification->html = $html;
      $this->add_notification($notification);
*/
    }

    /**
     * Retrieve the current list of notifications.
     * @deprecated since 2.9 instead use PrepareStrings()
     *
     * @return array of AdminNotification objects
     */
    public function get_notifications()
    {
//        return $this->_notifications;
    }

    /**
     * Retrieve current alerts (e.g. for display in page shortcuts toolbar)
     * @since 2.9 (pre-2.9, themes handled this individually)
     *
     * @return mixed array | null
     */
    public function get_my_alerts()
    {
        return Alert::load_my_alerts();
    }

    /**
     * Return an array of admin pages, suitable for use in a dropdown.
     *
     * @internal
     * @since 1.12
     * @param bool $none Optional flag indicating whether 'none' should be the first option. Default true
     * @return array Keys are langified page-titles, values are respective URLs.
     */
    public function GetAdminPages($none = true)
    {
        $opts = [];
        if ($none) {
            $opts[lang('default')] = '';
        }

        $nodes = $this->get_navigation_tree(null, 2);
/* TODO iterwalk, pages: top-level & direct children? shown, with-url
        $iter = new RecursiveArrayTreeIterator(
                new ArrayTreeIterator($nodes),
                RecursiveIteratorIterator::SELF_FIRST | RecursiveArrayTreeIterator::NONLEAVES_ONLY
               );
        foreach( $iter as $key => $value) {
        }
*/
        foreach( $nodes as $name=>$node) {
            if (!$node['show_in_menu'] || empty($node['url'])) {
                continue; // only visible stuff
            }
            if ($name == 'main' || $name == 'logout') {
                continue; // no irrelevant choices
            }
            try {
                $opts[$node['title']] = AdminUtils::get_generic_url($node['url']);
            } catch (Throwable $t) {
                continue;
            }

            if ($node['children']) {
                foreach( $node['children'] as $childname=>$one) {
                    if ($name == 'home' || $name == 'logout' || $name == 'viewsite') {
                        continue;
                    }
                    if (!$one['show_in_menu'] || empty($one['url'])) {
                        continue;
                    }
                    try {
                        $opts['&nbsp;&nbsp;'.$one['title']] = AdminUtils::get_generic_url($one['url']);
                    } catch (Throwable $t) {
                        continue;
                    }
                }
            }
        }

        return $opts;
    }

    /**
     * Return a select list of the pages in the system, for use in various admin pages.
     *
     * @internal
     * @param string $name - The html name of the select box
     * @param string $selected - If a matching page identifier is found in the list,
     *     that option will be marked as selected.
     * @param mixed  $id -  Optional html id of the select box. Default null
     * @return string The select list of pages
     */
    public function GetAdminPageDropdown($name,$selected,$id = null)
    {
        $opts = $this->GetAdminPages();
        if ($opts) {
            $parms = ['type'=>'drop','name'=>trim((string)$name),
                'options'=>$opts,'selectedvalue'=>$selected];
            if ($id) {
                $parms['id'] = trim((string)$id);
            }
            return FormUtils::create_select($parms);
        }
        return '';
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
        $urlext = get_secure_param();
        return 'menu.php'.$urlext;
    }

    /**
     * Accumulate content to be inserted in the head section of the output
     *
     * The CMSMS core code calls this method to add text and javascript to output in the head section required for various functionality.
     *
     * @param string $txt The text to add to the head section.
     * @param bool   $after Since 2.9 Optional flag whether to append (instead of prepend) default true
     * @since 2.2
     * @deprecated since 2.9 use add_page_headtext()
     * @author Robert Campbell
     */
    public function add_headtext($txt, $after = true)
    {
/*        $txt = trim($txt);
        if ($txt) {
            if ($after) { $this->_headtext .= "\n".$txt; }
            else { $this->_headtext = $txt."\n".$this->_headtext; }
        }
*/
        add_page_headtext($txt, $after);
    }

    /**
     * Get text that needs to be inserted into the head section of the output.
     *
     * This method is typically called by the admin theme itself to get the text to render.
     *
     * @return mixed string | null
     * @since 2.2
     * @deprecated since 2.9 use get_page_headtext()
     * @author Robert Campbell
     */
    public function get_headtext()
    {
//        return $this->_headtext;
        return get_page_headtext();
    }

    /**
     * Accumulate content to be inserted at the bottom of the output, immediately before the </body> tag.
     *
     * @param string $txt The text to add to the end of the output.
     * @param bool   $after Since 2.9 Optional flag whether to append (instead of prepend) default true
     * @since 2.2
     * @deprecated since 2.9 use add_page_foottext()
     * @author Robert Campbell
     */
    public function add_footertext($txt, $after = true)
    {
/*        $txt = trim($txt);
        if ($txt) {
            if ($after) { $this->_foottext .= "\n".$txt; }
            else { $this->_foottext = $txt."\n".$this->_foottext; }
        }
*/
        add_page_foottext($txt, $after);
    }

    /**
     * Get text that needs to be inserted into the bottom of the output.
     *
     * This method is typically called by the admin theme itself to get the text to render.
     *
     * @return string | null
     * @since 2.2
     * @deprecated since 2.9 use get_page_foottext()
     * @author Robert Campbell
     */
    public function get_footertext()
    {
//        return $this->_foottext;
        return get_page_foottext();
    }

    /**
     * Output generic content which precedes the specific output of a module-action
     *  or admin operation. Themes may ignore this, and instead deal with such
     *  content during postprocess(). Might be useful for backward-compatibility.
     * @abstract
     */
    public function do_header() {}

    /**
     * Output generic content which follows the specific output of a module-action
     *  or admin operation. Themes may ignore this, and instead deal with such
     *  content during postprocess(). Might be useful for backward-compatibility.
     * @abstract
     */
    public function do_footer() {}

    /**
     * Output the content of the menu-root (home) page or a menu-section. e.g. a dashboard
     *
     * @abstract
     * @param string $section_name A menu-section name, typically empty to work
     * with the whole menu.
     */
    abstract public function do_toppage($section_name);

    /**
     * Cache the entire content of a 'minimal' page.
     * CHECKME can the subsequent processing of such content be a security risk?
     * Hence maybe some sanitize here?
     *
     * @since 2.9
     * @param string $content the entire displayable content
     * @see AdminTheme::get_content(), AdminTheme::do_minimal()
     */
    public function set_content(string $content)
    {
        $this->_primary_content = $content;
    }

    /**
     * Retrieve the cached content of a 'minimal' page
     *
     * @since 2.9
     * @see AdminTheme::set_content(), AdminTheme::do_minimal()
     */
    public function get_content() : string
    {
        return $this->_primary_content;
    }

    /**
     * Return the content of a self-managed admin page i.e. without the usual processing
     * of header, footer, page-title, menu.
     * @abstract
     * @since 2.9
     *
     * @param mixed $bodyid string|null  Optional id for page body-element. Default null.
     *
     * @return html string | null if smarty->fetch() fails
     */
    public function do_minimal($bodyid = null) {}

    /**
     * Return the content of an authenticated page
     * @abstract
     * @since 2.9
     *
     * @return html string
     */
    public function do_authenticated_page() : string {}

    /**
     * Return the content of a login page.
     * @abstract
     * @since 2.9
     * @see AdminTheme::do_minimal()
     *
     * @param mixed $bodyid string | null  Optional id for page body-element. Default null.
     * @return html string | null if smarty->fetch() fails
     */
    public function do_loginpage($bodyid = null) {}

    /**
     * Display and process a login form
     * @abstract
     *
     * @param  mixed $params Optional array. Default null.
     * @since 2.9, relevant parameters are supplied by a login module.
     * However some themes aspire to backward-compatibility, so $params
     *  remains as an option.
     * @see AdminTheme::do_loginpage()
     */
    public function do_login($params = null) {}

    /**
     * Post-process the generated content of the current page.
     * Called only via footer.php. Admin themes might do some of their work
     * in this method (e.g. passing the content through a smarty template)
     * @abstract
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
     * Return page content representing the start of tab headers
     * e.g. echo $this->StartTabHeaders();
     * This infills related page-elements which are not explicitly created.
     *
     * @final
     * @deprecated since 2.9. Instead use CMSMS\AdminTabs::start_tab_headers()
     * @return string
     */
    final public function StartTabHeaders() : string
    {
        return AdminTabs::start_tab_headers();
    }

    /**
     * Return page content representing a specific tab header
     * e.g.  echo $this->SetTabHeader('preferences',$this->Lang('preferences'));
     * This infills related page-elements which are not explicitly created.
     *
     * @final
     * @param string $tabid The tab id
     * @param string $title The tab title
     * @param bool $active Optional flag indicating whether this tab is active, default false
     * @deprecated since 2.9 Use CMSMS\AdminTabs::set_tab_header()
     * @return string
     */
    final public function SetTabHeader(string $tabid, string $title, bool $active = false) : string
    {
        return AdminTabs::set_tab_header($tabid,$title,$active);
    }

    /**
     * Return page content representing the end of tab headers.
     * This infills related page-elements which are not explicitly created.
     *
     * @final
     * @deprecated since 2.9 Use CMSMS\AdminTabs::end_tab_headers()
     * @return string
     */
    final public function EndTabHeaders() : string
    {
        return AdminTabs::end_tab_headers();
    }

    /**
     * Return page content representing the start of XHTML areas for tabs.
     * This infills related page-elements which are not explicitly created.
     *
     * @final
     * @deprecated since 2.9 Use CMSMS\AdminTabs::start_tab_content()
     * @return string
     */
    final public function StartTabContent() : string
    {
        return AdminTabs::start_tab_content();
    }

    /**
     * Return page content representing the end of XHTML areas for tabs.
     * This infills related page-elements which are not explicitly created.
     *
     * @final
     * @deprecated since 2.9 Use CMSMS\AdminTabs::end_tab_content()
     * @return string
     */
    final public function EndTabContent() : string
    {
        return AdminTabs::end_tab_content();
    }

    /**
     * Return page content representing the start of a specific tab.
     * This infills related page-elements which are not explicitly created.
     *
     * @final
     * @param string $tabid The tabid (see SetTabHeader)
     * @deprecated since 2.9 Use CMSMS\AdminTabs::start_tab()
     * @return string
     */
    final public function StartTab(string $tabid) : string
    {
        return AdminTabs::start_tab($tabid);
    }

    /**
     * Return page content representing the end of a specific tab.
     * This infills related page-elements which are not explicitly created.
     *
     * @final
     * @deprecated since 2.9 Use CMSMS\AdminTabs::end_tab()
     * @return string
     */
    final public function EndTab() : string
    {
        return AdminTabs::end_tab();
    }
} // class
