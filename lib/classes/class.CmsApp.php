<?php
#Singleton class for accessing system variables
#Copyright (C) 2010-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

/* for future use
namespace CMSMS {
*/

use CMSMS\BookmarkOperations;
use CMSMS\ContentOperations;
use CMSMS\contenttypes\ErrorPage;
use CMSMS\Database\Connection;
use CMSMS\GroupOperations;
use CMSMS\internal\global_cache;
use CMSMS\internal\Smarty;
use CMSMS\ModuleOperations;
use CMSMS\ScriptOperations;
use CMSMS\StylesOperations;
use CMSMS\UserOperations;
use CMSMS\UserPluginOperations;
use CMSMS\UserTagOperations;

/**
 * Singleton class that contains various functions and properties representing
 * the application.
 *
 * @final
 * @package CMS
 * @license GPL
 * @since 0.5
 */
final class CmsApp
{
    /**
     * A bitflag constant indicating that the request is for a frontend page
     * @since 2.3
     */
    const STATE_FRONT_PAGE = 1;

    /**
     * A bitflag constant indicating that the request is for a page in the CMSMS admin console
     */
    const STATE_ADMIN_PAGE = 2;

    /**
     * A bitflag constant indicating that the request is for an admin login
     */
    const STATE_LOGIN_PAGE = 4;

    /**
     * A bitflag constant indicating that the request is taking place during the installation process
     */
    const STATE_INSTALL = 0x80;

    /**
     * A bitflag constant indicating that the request is for a stylesheet
     */
    const STATE_STYLESHEET = 0x100;

    /**
     * A bitflag constant indicating that we are currently parsing page templates
     * UNUSED
     */
    const STATE_PARSE_TEMPLATE = 0x200;

    /**
     * @ignore
     */
    const STATELIST = [
        self::STATE_ADMIN_PAGE,
        self::STATE_FRONT_PAGE,
        self::STATE_STYLESHEET,
        self::STATE_INSTALL,
        self::STATE_PARSE_TEMPLATE,
        self::STATE_LOGIN_PAGE
    ];

    /**
     * @ignore
     * @since 2.3
     * @deprecated since 2.3
     */
    const STRINGSTATES = [
        'admin_request' => self::STATE_ADMIN_PAGE,
        'install_request' => self::STATE_INSTALL,
        'login_request' => self::STATE_LOGIN_PAGE,
        'parse_page_template' => self::STATE_PARSE_TEMPLATE,
        'stylesheet_request' => self::STATE_STYLESHEET,
    ];

    /**
     * @ignore
     */
    private static $_instance = null;

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
     * Array of current states.
     * @ignore
     */
    private $_states;

    /**
     * Connection object - handle|connection to the site database
     * @ignore
     */
    private $db;

    /**
     * @ignore
     */
    private $smarty = null;

    /**
     * cms_content_tree object with nested descendent-objects
     * @ignore
     */
    private $hrinstance = null;

    /**
     * Internal error array - So functions/modules can store up debug info and spit it all out at once
     * @ignore
     */
    private $errors = [];

    /**
     * @ignore
     */
    private $scriptsmerger = null;

    /**
     * @ignore
     */
    private $stylesmerger = null;

    /**
     * @ignore
     * This cache must be set externally, after autoloading is available
     */
    public $jobmgrinstance = null;

    /**
     * Constructor
     * @ignore
     */
    private function __construct()
    {
        register_shutdown_function([$this, 'dbshutdown']);
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
            return cms_config::get_instance();
            break;
        }
    }

    /**
     * Retrieve the single app instance.
     *
     * @since 1.10
     */
    public static function get_instance() : self
    {
        if( !self::$_instance  ) self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * Retrieve the installed schema version.
     *
     * @since 2.0
     */
    public function get_installed_schema_version() : string
    {
        if( self::test_state(self::STATE_INSTALL) ) {
            $db = $this->GetDb();
            $query = 'SELECT version FROM '.CMS_DB_PREFIX.'version';
            return $db->GetOne($query);
        }
        return CMS_SCHEMA_VERSION;
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
     * Ideally this method can be called from within a module action that is called
     * from within the default content block when content_processing is set to 2
     * (the default) in the config.php file
     *
     * @return void
     * @since 2.3
     */
    public function disable_template_processing()
    {
        $this->_showtemplate = false;
    }

    /**
     * Get the flag indicating whether or not template processing is allowed.
     *
     * @return bool
     * @since 2.3
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
     * @param mixed $content one of the content classes, CMSMS or CMSContentManager namespace
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
     * @return mixed content-object (CMSMS or CMSContentManager namespace) or null
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
     * Get a list of all installed and available modules
     *
     * This method will return an array of module names that are installed, loaded and ready for use.
     * Suitable for iteration with GetModuleInstance
     *
     * @see CmsApp::GetModuleInstance()
     * @since 1.9
     * @return string[]
     */
    public function GetAvailableModules()
    {
        return ModuleOperations::get_instance()->get_available_modules();
    }

    /**
     * Get a reference to an installed module instance.
     *
     * This method will return a reference to the module object specified if it is installed, and available.
     * Optionally, a version check can be performed to test if the version of the requeted module matches
     * that specified.
     *
     * @since 1.9
     * @param string $module_name The module name.
     * @param mixed  $version (optional) string|float version number for a check. Default ''
     * @return mixed CMSModule Reference to the module object, or null.
     * @deprecated
     */
    public function GetModuleInstance(string $module_name,$version = '')
    {
        return ModuleOperations::get_instance()->get_module_instance($module_name,$version);
    }

    /**
     * Set the database connection object.
     *
     * @internal
     * @ignore
     * @param Connection $conn
     */
    public function _setDb(Connection $conn)
    {
        $this->db = $conn;
    }

    /**
    * Get a handle to the database.
    *
    * @return mixed Connection object or null
    */
    public function GetDb()
    {
        if (isset($this->db)) return $this->db;

        $config = cms_config::get_instance();
        $this->db = new Connection($config);
        //deprecated since 2.3 at most: make old stuff available
        require_once cms_join_path(__DIR__, 'Database', 'class.compatibility.php');
        return $this->db;
    }

    /**
     * Get the database prefix.
     * @deprecated since 2.3 Instead, use const CMS_DB_PREFIX
     *
     * @return string
     */
    public function GetDbPrefix() : string
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('parameter','CMS_DB_PREFIX'));
        return CMS_DB_PREFIX;
    }

    /**
    * Get a handle to the global CMS config.
    * That object contains global paths and settings that do not belong in the database.
    * @see cms_config
    *
    * @return cms_config The configuration object.
    */
    public function GetConfig() : cms_config
    {
        return cms_config::get_instance();
    }

    /**
    * Get a handle to the module operations instance.
    * If it does not yet exist, this method will instantiate it.
    * @see ModuleOperations
    *
    * @return ModuleOperations handle to the ModuleOperations object
    */
    public function GetModuleOperations() : ModuleOperations
    {
        return ModuleOperations::get_instance();
    }

    /**
     * Get a handle to the user-plugin operations object.
     * @since 2.3
     * @see UserPluginOperations
     *
     * @return UserPluginOperations
     */
    public function GetUserPluginOperations() : UserPluginOperations
    {
        return UserPluginOperations::get_instance();
    }

    /**
    * Get a handle to the user operations object.
    * @see UserOperations
    *
    * @return UserOperations handle to the UserOperations object
    */
    public function GetUserOperations() : UserOperations
    {
        return UserOperations::get_instance();
    }

    /**
    * Get a handle to the content operations object.
    * @see ContentOperations
    *
    * @return ContentOperations handle to the ContentOperations object
    */
    public function GetContentOperations() : ContentOperations
    {
        return ContentOperations::get_instance();
    }

    /**
    * Get a handle to the bookmark operations object.
    * @see BookmarkOperations
    * @deprecated since 2.3 get the object directly
    *
    * @return BookmarkOperations handle to the BookmarkOperations object, useful only in the admin
    */
    public function GetBookmarkOperations() : BookmarkOperations
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\BookmarkOperations'));
        return new BookmarkOperations();
    }

    /**
    * Get a handle to the group operations object.
    * @see GroupOperations
    *
    * @return GroupOperations handle to the GroupOperations instance
    */
    public function GetGroupOperations() : GroupOperations
    {
        return GroupOperations::get_instance();
    }

    /**
    * Get a handle to the UDT operations object (which is for back-compatibility only).
    * @see UserTagOperations
    * @deprecated - since 2.3 UserTagOperations has been superseded by UserPluginOperations
    *
    * @return UserTagOperations handle to the UserTagOperations object
    */
    public function GetUserTagOperations() : UserTagOperations
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\UserPluginOperations'));
        return UserTagOperations::get_instance();
    }

    /**
    * Get a handle to the CMS Smarty object.
    * @see Smarty
    * @link http://www.smarty.net/manual/en/
    *
    * @return mixed Smarty handle to the CMSMS Smarty object, or null
    */
    public function GetSmarty()
    {
        global $CMS_INSTALL_PAGE;
        if( !isset($CMS_INSTALL_PAGE) ) {
            // we don't load the main Smarty class during installation
            if( is_null($this->smarty) ) {
                $this->smarty = new Smarty();
            }
            return $this->smarty;
        }
    }

    /**
    * Get a handle to the cached pages-hierarchy manager.
    * @see content_tree
    *
    * @return object
    */
    public function GetHierarchyManager()
    {
        if( is_null($this->_hrinstance) ) {
            $this->_hrinstance = global_cache::get('content_tree');
        }
        return $this->_hrinstance;
    }

    /**
     * Get a handle to the scripts combiner
     * @since 2.3
     */
    public function GetScriptManager() : ScriptOperations
    {
        if( is_null($this->scriptsmerger) ) $this->scriptsmerger = new ScriptOperations();
        return $this->scriptsmerger;
    }

    /**
     * Get a handle to the styles combiner
     * @since 2.3
     */
    public function GetStylesManager() : StylesOperations
    {
        if( is_null($this->stylesmerger) ) $this->stylesmerger = new StylesOperations();
        return $this->stylesmerger;
    }

    /**
     * Get the async-jobs manager module
     * @since 2.3
     * @return mixed CMSModule object|null
     */
    public function GetJobManager()
    {
        return $this->jobmgrinstance;
    }

    /**
    * Disconnect from the database.
    *
    * @internal
    * @ignore
    * @access private
    */
    public function dbshutdown()
    {
        if (isset($this->db) && $this->db->IsConnected()) {
            $this->db->Close();
        }
    }

    /**
     * Remove files from the website file-cache directories.
     * @deprecated since 2.3 Now does nothing.
     * This functionality has been relocated, and surrounded with
     * appropriate security.
     *
     * @internal
     * @ignore
     * @param $age_days Optional file-modification threshold (days), 0 to whatever. Default 0 hence 'now'.
     */
    public function clear_cached_files(int $age_days = 0)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('Method does nothing'));
    }

    /**
     * Accumulate all known states from global variables.
     * @todo transition to CmsApp::set_states()
     * @since 1.11.2
     * @ignore
     */
    private function _capture_states()
    {
        global $CMS_ADMIN_PAGE, $CMS_INSTALL_PAGE, $CMS_LOGIN_PAGE, $CMS_STYLESHEET;

        $tmp = 0;
        if( isset($CMS_LOGIN_PAGE) ) $tmp += self::STATE_LOGIN_PAGE; // files also set STATE_ADMIN_PAGE
        if( isset($CMS_ADMIN_PAGE) ) $tmp += self::STATE_ADMIN_PAGE;
        if( isset($CMS_INSTALL_PAGE) ) $tmp += self::STATE_INSTALL;
        if( $tmp == 0 ) $tmp = self::STATE_FRONT_PAGE;

        if( isset($CMS_STYLESHEET) ) $tmp += self::STATE_STYLESHEET; // the cms_stylesheet plugin is running
//      if (?) $tmp += self::STATE_PARSE_TEMPLATE;
        $this->set_states($tmp);
    }

    /**
     * [Un]set a global variable reflecting $flag and $value.
     * Effectively the inverse of _capture_states()
     * @since 2.3
     * @deprecated since 2.3
     * @ignore
     */
    private function _set_state_var(int $flag, bool $value = true)
    {
        switch( $flag ) {
            case self::STATE_ADMIN_PAGE:
				$name = 'CMS_ADMIN_PAGE';
				break;
            case self::STATE_STYLESHEET:
				$name = 'CMS_STYLESHEET';
				break;
            case self::STATE_INSTALL:
				$name = 'CMS_INSTALL_PAGE';
				break;
            case self::STATE_LOGIN_PAGE:
				$name = 'CMS_LOGIN_PAGE';
				break;
//          case self::STATE_PARSE_TEMPLATE: $name = ??; break;
            case self::STATE_FRONT_PAGE:
				unset($CMS_ADMIN_PAGE, $CMS_INSTALL_PAGE, $CMS_LOGIN_PAGE, $CMS_STYLESHEET);
			default:
				return;
        }

        global $CMS_ADMIN_PAGE, $CMS_INSTALL_PAGE, $CMS_LOGIN_PAGE, $CMS_STYLESHEET;
		if( $value ) {
			$$name = 1;
		}
		else {
			unset($$name);
		}
    }

    /**
     * Set the list of current states.
     *
     * @ignore
     * @internal
     * @since 2.3
     * @param int $states State bit-flag(s), OR'd class constant(s).
     */
    public function set_states(int $states)
    {
        $tmp = [];
        foreach( self::STATELIST as $flag ) {
            if( $states & $flag ) {
                $tmp[$flag] = $flag;
                $this->_set_state_var($flag); //compatibility
            }
        }
        $this->_states = $tmp;
    }

    /**
     * Get a list of all current states.
     *
     * @since 1.11.2
     * @author Robert Campbell
     * @return array  State constants (int's)
     */
    public function get_states() : array
    {
        $this->_capture_states();
        return array_keys($this->_states);
    }

    /**
     * Report whether the specified state matches the current application state.
     * @since 1.11.2
     * @author Robert Campbell
     *
     * @param mixed $state int | deprecated string State identifier, a class constant 
     * @return bool
     * @throws CmsInvalidDataException if invalid identifier is provided
     */
    public function test_state($state) : bool
    {
        if( is_string($state) ) {
            $state = self::STRINGSTATES[$state] ?? (int)$state; //deprecated since 2.3
        }
        if( !in_array($state,self::STATELIST) ) throw new CmsInvalidDataException($state.' is not a recognized CMSMS state');
        $this->_capture_states();
        return isset($this->_states[$state]);
    }

    /**
     * Report whether one or more of the specified state(s) is current.
     *
     * @ignore
     * @internal
     * @since 2.3
     * @param int $states State bit-flag(s), OR'd class constant(s)
     * @return bool
     */
    public function test_any_state(int $states) : bool
    {
        $this->_capture_states();
        $tmp = array_sum($this->_states);
        return ($tmp & $states) > 0;
    }

    /**
     * Report whether all the specified state(s) are current.
     *
     * @ignore
     * @internal
     * @since 2.3
     * @param int $states State bit-flag(s), OR'd class constant(s)
     * @return bool
     */
    public function test_all_states(int $states) : bool
    {
        $this->_capture_states();
        $tmp = array_sum($this->_states);
        return ($tmp & $states) == $states;
    }

    /**
     * Add a state to the list of current states.
     *
     * @ignore
     * @internal
     * @since 1.11.2
     * @author Robert Campbell
     * @param mixed $state int | deprecated string The state, a class constant
     * @throws CmsInvalidDataException if an invalid state is provided.
     */
    public function add_state($state)
    {
        if( is_string($state) ) {
            $state = self::STRINGSTATES[$state] ?? (int)$state; //deprecated since 2.3
        }
        if( !in_array($state,self::STATELIST) ) throw new CmsInvalidDataException($state.' is an invalid CMSMS state');
        $this->_capture_states();
        $this->_states[$state] = $state;
        $this->_set_state_var($state); //compatibility
    }

    /**
     * Remove a state from the list of current states.
     *
     * @ignore
     * @internal
     * @since 1.11.2
     * @author Robert Campbell
     *
     * @param mixed $state int | deprecated string The state, a class constant
     * @return bool indicating success
     * @throws CmsInvalidDataException if an invalid state is provided.
     */
    public function remove_state($state) : bool
    {
        if( is_string($state) ) {
            $state = self::STRINGSTATES[$state] ?? (int)$state; //deprecated since 2.3
        }
        if( !in_array($state,self::STATELIST) ) throw new CmsInvalidDataException($state.' is an invalid CMSMS state');
        $this->_capture_states();
        if( isset($this->_states[$state]) ) {
            unset($this->_states[$state]);
            $this->_set_state_var($state, 0); //compatibility
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Report whether the current request was executed via the CLI.
     *
     * @since 2.2.9
     * @author Robert Campbell
     * @return bool
     */
    public function is_cli() : bool
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * Report whether the current request is a frontend request.
     *
     * @since 1.11.2
     * @author Robert Campbell
     * @return bool
     */
    public function is_frontend_request() : bool
    {
        return $this->test_state(self::STATE_FRONT_PAGE);
    }

    /** Report whether the current request was over HTTPS.
     *
     * @since 1.11.12
     * @author Robert Campbell
     * @return bool
     */
    public function is_https_request() : bool
    {
        return !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off';
    }
}

//} //namespace

//namespace {

/**
 * Return the global CmsApp object.
 *
 * @since 1.7
 * @return CmsApp
 * @see CmsApp::get_instance()
 */
function cmsms() : CmsApp
{
    return CmsApp::get_instance();
}

//} //namespace
