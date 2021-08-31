<?php
/*
Singleton class for accessing intra-request system properties
Copyright (C) 2010-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS {

use CMSMS\AdminUtils;
use CMSMS\AppConfig;
use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\AutoCookieOperations;
use CMSMS\BookmarkOperations;
use CMSMS\ContentOperations;
use CMSMS\ContentTree;
use CMSMS\contenttypes\ErrorPage;
use CMSMS\Database\Connection;
use CMSMS\DeprecationNotice;
use CMSMS\GroupOperations;
use CMSMS\internal\Smarty;
use CMSMS\ModuleOperations;
use CMSMS\ScriptsMerger;
use CMSMS\SingleItem;
use CMSMS\UserOperations;
use CMSMS\UserTagOperations;
use RuntimeException;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CMS_SCHEMA_VERSION;
use function cms_join_path;

/**
 * Singleton class for accessing intra-request system properties and classes.
 *
 * @final
 * @package CMS
 * @license GPL
 * @since 2.99
 * @since 0.5 as global-namespace CmsApp etc
 */
final class App
{
    /**
     * A bitflag constant indicating that the request is for a page in the CMSMS admin console
     * @deprecated since 2.99 use AppState::ADMIN_PAGE
     */
    const STATE_ADMIN_PAGE = 2;

    /**
     * A bitflag constant indicating that the request is for an admin login
     * @deprecated since 2.99 use AppState::LOGIN_PAGE
     */
    const STATE_LOGIN_PAGE = 4;

//    const STATE_ASYNC_JOB = 0x40; from 2.99

    /**
     * A bitflag constant indicating that the request is taking place during the installation process
     * @deprecated since 2.99 use AppState::INSTALL
     */
    const STATE_INSTALL = 0x80;

    /**
     * A bitflag constant indicating that the request is for a stylesheet
     * @deprecated since 2.99 use AppState::STYLESHEET
     */
    const STATE_STYLESHEET = 0x100;

    /**
     * A bitflag constant indicating that we are currently parsing page templates
     * @deprecated since 2.99 use AppState::PARSE_TEMPLATE
     */
    const STATE_PARSE_TEMPLATE = 0x200;

    /**
     * @var object Singleton instance of this class
     * @ignore
     */
    private static $_instance = null;

    // TODO cache all of these as SingleItem properties
    /**
     * @ignore
     */
    private $_current_content;

    /**
     * @ignore
     */
    private $_content_type = '';

    /**
     * @ignore
     */
    private $_showtemplate = true;

    /**
     * @var singleton Connection object - handle|connection to the site database
     * @ignore
     */
    private $db;

    /**
     * @var singleton CMSMS\internal\Smarty object
     * @ignore
     */
    private $smarty;

    /* *
     * @var singleton module-object
     * @ignore
     * This cache must be set externally, after autoloading is available
     */
//    public $jobmgrinstance = null;

    /**
     * Cache for other properties
     * @since 2.99
     * @ignore
     */
    private $data = [];

    /**
     * @var array Error-messages
     * So functions/modules can store up debug info and spit it all out at once
     * @ignore
     */
    private $errors = [];

    /**
     * @var array Callable methods to be called during shutdown
     * @ignore
     */
    private $shutfuncs = [];

    //TODO another batch of callables for end-of-session cleanup, cached in $_SESSION etc

    /**
     * @ignore
     * @private to prevent direct creation (even by SingleItem class)
     */
    private function __construct()
    {
        $this->add_shutdown(500, [$this, 'dbshutdown']);
        register_shutdown_function([$this, 'run_shutters']);
    }

    /**
     * @ignore
     */
    private function __clone() {}

    /**
     * @ignore
     */
    public function __get(string $key)
    {
        switch($key) {
        case 'config':
            return SingleItem::Config();
        case 'get':
        case 'instance':
            return self::get_instance();
        default:
            return $this->data[$key] ?? null;
        }
    }

    /**
     * @ignore
     */
    public function __set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Retrieve the singleton instance of another class.
     * @ignore
     * @throws RuntimeException if $name is not recognized
     */
    public function __call(string $name, $args)
    {
        return SingleItem::$name(...$args);
    }

    /**
     * Retrieve the singleton instance of this class.
     * This method is used during request-setup, when caching via the
     * SingleItem class might not yet be possible. Later, use
     * CMSMS\SingleItem::[Cms]App() instead of this method, to get the
     * (same) singleton.
     *
     * @since 1.10
     */
    public static function get_instance() : self
    {
        if( !self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Retrieve the value of an internal variable.
     * @since 1.9
     *
     * @param string The variable name to get
     * @return mixed The value of the internal variable, or null.
     */
    public function get_variable(string $key)
    {
        return $this->__get($key);
    }

    /**
     * Set the value of an internal variable
     * @since 1.9
     *
     * @param string The variable name to set
     * @param mixed  The value
     */
    public function set_variable($key,$value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Retrieve the installed schema version.
     *
     * @since 2.0
     */
    public function get_installed_schema_version() : int
    {
        $val = AppParams::get('cms_schema_version');
        if( AppState::test(AppState::INSTALL) ) {
            return (int)$val; //most-recently cached value (if any)
        }
        if (!$val && defined('CMS_SCHEMA_VERSION')) { //undefined during installation
            $val = CMS_SCHEMA_VERSION;
        }
        if (!$val) {
            $val = $CMS_SCHEMA_VERSION ?? 0; // no force-load here, might not be installed
        }
        return (int)$val; // maybe 0
    }

    /**
     * Report whether the installed tables-schema is up-to-date.
     *
     * @since 2.99
     *
     * @return bool;
     */
    public function schema_is_current() : bool
    {
        global $CMS_SCHEMA_VERSION; // what we're supposed to have
        if (!isset($CMS_SCHEMA_VERSION)) {
            require dirname(__DIR__).DIRECTORY_SEPARATOR.'version.php'; // get it [again?]
        }
        $current = $this->get_installed_schema_version(); // what we think we do have
        return version_compare($current, $CMS_SCHEMA_VERSION) == 0;
    }

    /**
     * Retrieve the list of errors
     *
     * @ignore
     * @since 1.9
     * @internal
     * @access private.
     * return array
     */
    public function get_errors() : array
    {
        return $this->errors;
    }

    /**
     * Add an error to the list
     *
     * @ignore
     * @since 1.9
     * @internal
     * @access private
     * @param string The error message.
     */
    public function add_error(string $str)
    {
        if( !is_array($this->errors) ) $this->errors = [];
        $this->errors[] = $str;
    }

    /**
     * Retrieve the request content type (for frontend requests)
     *
     * If no content type is explicity set, text/html is assumed.
     *
     * @since 2.0
     */
    public function get_content_type() : string
    {
        if( $this->_content_type ) return $this->_content_type;
        return 'text/html';
    }

    /**
     * Set the request content type to a valid mime type.
     *
     * @param string $mime_type
     * @since 2.0
     */
    public function set_content_type(string $mime_type = null)
    {
        if( $mime_type ) $this->_content_type = $mime_type;
        else $this->_content_type = '';
    }

    /**
     * Disable the processing of the page template.
     * This function controls whether the page template will be processed at all.
     * It must be called early enough in the content generation process.
     *
     * Ideally this method can be called from within a module action that is
     * called from within the default content block when content_processing is
     * set to 2 (the default) in the config.php file
     *
     * @return void
     * @since 2.99
     */
    public function disable_template_processing()
    {
        $this->_showtemplate = false;
    }

    /**
     * [Un]set the flag indicating whether to process the (optional) template
     * currently pending.
     * This method can be called from anywhere, to temporarily toggle smarty processing
     *
     * @since 2.99
     * @param bool $state optional default true
     */
    public function do_template_processing(bool $state = true)
    {
        $this->_showtemplate = $state;
    }

    /**
     * Get the flag indicating whether or not template processing is allowed.
     *
     * @return bool
     * @since 2.99
     */
    public function template_processing_allowed() : bool
    {
        return $this->_showtemplate;
    }

    /**
     * Set the current-page content object, if not already done
     *
     * @since 2.0
     * @internal
     * @ignore
     * @param mixed $content one of the content classes, CMSMS or ContentManager namespace
     */
    public function set_content_object($content)
    {
        if( !$this->_current_content || $content instanceof ErrorPage ) {
            $this->_current_content = $content;
        }
    }

    /**
     * Get the current-page content object
     *
     * @since 2.0
     * @return mixed content-object (CMSMS or ContentManager namespace) or null
     */
    public function get_content_object()
    {
        return $this->_current_content ?? null;
    }

    /**
     * Get the ID of the current page
     *
     * @since 2.0
     */
    public function get_content_id()
    {
        $obj = $this->get_content_object();
        if( is_object($obj) ) return $obj->Id();
    }

    /**
    * Get a handle to the module operations instance.
    * @see ModuleOperations
    * @since 2.99 CMSMS\SingleItem::ModuleOperations() may be used instead
    *
    * @return ModuleOperations handle to the ModuleOperations object
    */
    public function GetModuleOperations() : ModuleOperations
    {
        return SingleItem::ModuleOperations();
    }

    /**
     * Get a list of all installed and available modules
     *
     * This method will return an array of module names that are installed, loaded and ready for use.
     * Suitable for iteration with GetModuleInstance
     *
     * @see App::GetModuleInstance()
     * @since 1.9
     * @return string[]
     */
    public function GetAvailableModules()
    {
        $obj = $this->GetModuleOperations();
        return $obj->get_available_modules();
    }

    /**
     * Get an installed-module instance.
     *
     * This method will return an instance of the specified module class, if it
     * is installed and available. Optionally, a check can be performed to test
     * whether the version of the requested module matches the one specified.
     *
     * @since 1.9
     * @param string $modname The module name.
     * @param mixed  $version (optional) string|float version number for a check. Default ''
     * @return mixed CMSModule sub-class object | null.
     * @deprecated
     */
    public function GetModuleInstance(string $modname,$version = '')
    {
        $ops = $this->GetModuleOperations();
        return $ops->get_module_instance($modname,$version);
    }

    /**
     * Set the database connection object.
     * For use when the installer is running, when the db connection
     * cannot be created via self::GetDb(). That expects the global
     * $config to already be populated with connection parameters.
     *
     * @internal
     * @ignore
     * @param Connection $conn
     */
    public function _setDb(Connection $conn)
    {
        if( !AppState::test(AppState::INSTALL) ) {
            throw new RuntimeException('Invalid use of '.__METHOD__);
        }
        $this->db = $conn;
    }

    /**
    * Get a handle to the database.
    * @see SingleItem::Db()
    *
    * @return mixed Connection object or null
    */
    public function GetDb()
    {
        if (isset($this->db)) return $this->db;

        $config = SingleItem::Config();
        $this->db = new Connection($config);
        //deprecated since 2.99 (at most): make old stuff available
        require_once cms_join_path(__DIR__, 'Database', 'class.compatibility.php');
        return $this->db;
    }

    /**
     * Get the database prefix.
     * @deprecated since 2.99 Instead, use constant CMS_DB_PREFIX
     *
     * @return string
     */
    public function GetDbPrefix() : string
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('parameter', 'CMS_DB_PREFIX'));
        return CMS_DB_PREFIX;
    }

    /**
    * Get a handle to the CMS config instance.
    * That object contains global paths and settings that do not belong in the database.
    * @see AppConfig
    *
    * @return AppConfig The configuration object
    */
    public function GetConfig() : AppConfig
    {
        return SingleItem::Config();
    }

    /**
    * Get a handle to the user operations instance.
    * @see UserOperations
    * @since 2.99 CMSMS\SingleItem::UserOperations() may be used instead
    *
    * @return UserOperations handle to the UserOperations object
    */
    public function GetUserOperations() : UserOperations
    {
        return SingleItem::UserOperations();
    }

    /**
    * Get a handle to the content operations instance.
    * @see ContentOperations
    * @since 2.99 CMSMS\SingleItem::ContentOperations() may be used instead
    *
    * @return ContentOperations handle to the ContentOperations object
    */
    public function GetContentOperations() : ContentOperations
    {
        return SingleItem::ContentOperations();
    }

    /**
    * Get a bookmark-operations instance.
    * @see BookmarkOperations
    *
    * @return BookmarkOperations
    */
    public function GetBookmarkOperations() : BookmarkOperations
    {
        return new BookmarkOperations();
    }

    /**
    * Get a handle to the group operations instance.
    * @see GroupOperations
    * @since 2.99 CMSMS\SingleItem::GroupOperations() may be used instead
    *
    * @return GroupOperations handle to the GroupOperations instance
    */
    public function GetGroupOperations() : GroupOperations
    {
        return SingleItem::GroupOperations();
    }

    /**
    * Get a handle to the user-plugin operations instance, for interacting with UDT's
    * @see UserTagOperations
    * @since 2.99 CMSMS\SingleItem::UserTagOperations() may be used instead
    *
    * @return the UserTagOperations singleton
    */
    public function GetUserTagOperations() : UserTagOperations
    {
        return SingleItem::UserTagOperations();
    }

    /**
    * Get a handle to the Smarty instance, except during install/upgrade/refresh.
    * @see Smarty
    * @see SingleItem::Smarty()
    * @link http://www.smarty.net/manual/en/
    *
    * @return mixed CMSMS\internal\Smarty object | null
    */
    public function GetSmarty()
    {
        if( !AppState::test(AppState::INSTALL) ) {
            // we don't load the main Smarty class during installation
            if( empty($this->smarty) ) {
                $this->smarty = new Smarty();
            }
            return $this->smarty;
        }
    }

    /**
    * Get a handle to the cached pages-hierarchy manager.
    * @see ContentTree
    *
    * @return object ContentTree, with nested descendant-objects
    */
    public function GetHierarchyManager() : ContentTree
    {
        $hm = SingleItem::get('HierarchyManager');
        if( !$hm ) {
            $hm = SingleItem::LoadedData()->get('content_tree');
            SingleItem::set('HierarchyManager', $hm);
        }
        return $hm;
    }

    /**
     * Get the intra-request shared scripts-combiner object.
     * @since 2.99
     *
     * @return object ScriptsMerger
     */
    public function GetScriptsManager() : ScriptsMerger
    {
        $sm = SingleItem::get('ScriptsMerger');
        if( !$sm ) {
            $sm = new ScriptsMerger();
            SingleItem::set('ScriptsMerger', $sm);
        }
        return $sm;
    }

    /**
     * Get the intra-request shared styles-combiner object.
     * @since 2.99
     *
     * @return object StylesMerger
     */
    public function GetStylesManager() : StylesMerger
    {
        $sm = SingleItem::get('StylesMerger');
        if( !$sm ) {
            $sm = new StylesMerger();
            SingleItem::set('StylesMerger', $sm);
        }
        return $sm;
    }

    /**
     * Get a cookie-manager instance.
     * @since 2.99
     *
     * @return AutoCookieOperations
     */
    public function GetCookieManager() : AutoCookieOperations
    {
        return new AutoCookieOperations($this);
    }

    /**
     * Get this site's unique identifier
     * @since 2.99
     *
     * @return 32-byte english-alphanum string
     */
    public function GetSiteUUID()
    {
        return SingleItem::get('site_uuid');
    }

    /**
     * Shutdown-function: process all recorded methods
     * @ignore
     * @internal
     * @since 2.99
     * @todo export this to elsewhere e.g. populate via hooklist
     */
    public function run_shutters()
    {
        usort($this->shutfuncs, function($a,$b) {
            return $a[0] <=> $b[0];
        });
        foreach( $this->shutfuncs as $row ) {
            if( is_callable($row[1]) ) {
                if( $row[2] ) $row[1](...$row[2]);
                else $row[1]();
            }
        }
    }

    /**
     * Queue a shutdown-function
     * @since 2.99
     * @param int $priority 1(high)..big int(low). Default 1.
     * @param callable $shutter
     * @param(s) variable no. of arguments to supply to $shutter
     */
    public function add_shutdown(int $priority = 1, $shutter = null, ...$args)
    {
        $this->shutfuncs[] = [$priority, $shutter, $args];
    }

    /**
    * A shutdown function: disconnect from the database.
    *
    * @internal
    * @ignore
    */
    public function dbshutdown()
    {
        if (isset($this->db) && $this->db->IsConnected()) {
            $this->db->Close();
        }
        $this->db = null; //no restarting during shutdown
    }

    /* TODO
     * End-of-session-function: process all recorded methods
     * Maybe elsewhere
     * @ignore
     * @internal
     * @since 2.99
     */

    /**
     * Remove files from the website file-cache directories.
     * @deprecated since 2.99 Instead use AdminUtils::clear_cached_files()
     *
     * @internal
     * @ignore
     * @param $age_days Optional file-modification threshold (days), 0 to whatever. Default 0 hence 'now'.
     */
    public function clear_cached_files(int $age_days = 0)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\AdminUtils::clear_cached_files'));
        AdminUtils::clear_cached_files($age_days);
    }

    /**
     * Get a list of all current states.
     *
     * @since 1.11.2
     * @deprecated since 2.99 instead use CMSMS\AppState::get()
     * @return array  State constants (int's)
     */
    public function get_states() : array
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\AppState::get'));
        return AppState::get();
    }

    /**
     * Report whether the specified state matches the current application state.
     * @since 1.11.2
     * @deprecated since 2.99 instead use CMSMS\AppState::test()
     *
     * @param mixed $state int | deprecated string State identifier, a class constant
     * @return bool
     * @throws UnexpectedValueException if invalid identifier is provided
     */
    public function test_state($state) : bool
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\AppState::test'));
        return AppState::test($state);
    }

    /**
     * Add a state to the list of current states.
     *
     * @ignore
     * @internal
     * @since 1.11.2
     * @deprecated since 2.99 instead use CMSMS\AppState::add()
     * @param mixed $state int | deprecated string The state, a class constant
     * @throws UnexpectedValueException if an invalid state is provided.
     */
    public function add_state($state)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\AppState::add'));
        AppState::add($state);
    }

    /**
     * Remove a state from the list of current states.
     *
     * @ignore
     * @internal
     * @since 1.11.2
     * @deprecated since 2.99 instead use CMSMS\AppState::remove()
     *
     * @param mixed $state int | deprecated string The state, a class constant
     * @return bool indicating success
     * @throws UnexpectedValueException if an invalid state is provided.
     */
    public function remove_state($state) : bool
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\AppState::remove'));
        AppState::remove($state);
    }

    /* * MAYBE IN FUTURE
     * Report whether the current request was executed via the CLI.
     *
     * @since 2.99
     * @return bool
     */
/*    public function is_cli() : bool
    {
        return PHP_SAPI == 'cli';
    }
*/
    /**
     * Report whether the current request is a frontend request.
     *
     * @since 1.11.2
     * @return bool
     */
    public function is_frontend_request() : bool
    {
        return AppState::test(AppState::FRONT_PAGE);
    }

    /** Report whether the current request was over HTTPS.
     *
     * @since 1.11.12
     * @return bool
     */
    public function is_https_request() : bool
    {
        return !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off';
    }
}

} //namespace

namespace {

use CMSMS\App;
use CMSMS\SingleItem;

/**
 * Return the App singleton object.
 *
 * @since 1.7
 * @return App
 * @see App::get_instance()
 */
function cmsms() : App
{
    return SingleItem::App();
}

/**
 * Check whether the supplied identifier matches the site UUID
 * This is a security function e.g. in module actions: <pre>if (!checkuuid($uuid)) exit;</pre>
 * @since 2.99
 * @param mixed $uuid identifier to be checked
 * @return bool indicating success
 */
function checkuuid($uuid) : bool
{
    return hash_equals(SingleItem::App()->GetSiteUUID(), $uuid.'');
}

} // global namespace
