<?php
# Base class for all CMSMS modules
# Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# BUT withOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSContentManager\BulkOperations;
use CMSMS\AdminMenuItem;
use CMSMS\AdminTabs;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\ContentType;
use CMSMS\ContentTypeOperations;
use CMSMS\contenttypes\ContentBase;
use CMSMS\DeprecationNotice;
use CMSMS\Events;
use CMSMS\FormUtils;
use CMSMS\HookOperations;
use CMSMS\internal\ModulePluginOperations;
use CMSMS\internal\Smarty;
use CMSMS\LangOperations;
use CMSMS\ModuleOperations;
use CMSMS\Permission;
use CMSMS\RouteOperations;
use CMSMS\SimpleTagOperations;
use CMSMS\Utils;

/**
 * Base module class.
 *
 * All modules should inherit and extend this class with their functionality.
 *
 * @since       0.9
 * @version     2.3
 * @package     CMS
 */
abstract class CMSModule
{
    /**
     * ------------------------------------------------------------------
     * Initialization functions and parameters
     * ------------------------------------------------------------------
     */

    /**
     * A hash of the parameters passed in to the module action
     *
     * @access private
     * @ignore
     */
    private $params = [];

    /**
     * @access private
     * @ignore
     */
    private $modinstall = false;

    /**
     * @access private
     * @ignore
     */
    private $modtemplates = false;

    /**
     * @access private
     * @ignore
     */
    private $modredirect = false;

    /**
     * @access private
     * @ignore
     */
    private $modurl = false;

    /**
     * @access private
     * @ignore
     */
    private $modmisc = false;

    /**
     * @access private
     * @ignore
     */
    private $param_map = [];

    /**
     * @var Smarty_Internal_Template object | null
     * @access private
     * @ignore
     */
    private $_action_tpl = null;

    /**
     * ------------------------------------------------------------------
     * Magic methods
     * ------------------------------------------------------------------
     */

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        global $CMS_FORCELOAD; //TODO set where ?

        if( !empty($CMS_FORCELOAD)) return;
        if( AppSingle::App()->is_cli() ) return;

        if( AppSingle::App()->is_frontend_request() ) {
            //generic parameters always accepted
            $this->SetParameterType('action',CLEAN_STRING);
            $this->SetParameterType('assign',CLEAN_STRING);
            $this->SetParameterType(CMS_JOB_KEY,CLEAN_INT);
            $this->SetParameterType('id',CLEAN_STRING);
            $this->SetParameterType('inline',CLEAN_INT);
            $this->SetParameterType('lang',CLEAN_STRING);
            $this->SetParameterType('module',CLEAN_STRING);
            $this->SetParameterType('returnid',CLEAN_INT);
            $this->SetParameterType('showtemplate',CLEAN_STRING); //deprecated since 2.9, use CMS_JOB_KEY
        }
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'cms':
            return AppSingle::App();

        case 'config':
            return AppSingle::Config();

        case 'db':
            return AppSingle::Db();
        }

        return null;
    }

    /**
     * @since 2.0
     *
     * @ignore
     */
    public function __call($name, $args)
    {
        if (strncmp($name, 'Create', 6) == 0) {
            //maybe it's a now-removed form-element call
            // static properties here >> StaticProperties class ?
            static $flect = null;

            if ($flect === null) {
                $flect = new ReflectionClass('CMSMS\\FormTags');
            }
            try {
                $md = $flect->getMethod($name);
            } catch (ReflectionException $e) {
                return false;
            }

            $parms = [];
            foreach ($md->getParameters() as $i => $one) {
                $val = $args[$i] ?? (($one->isOptional()) ? $one->getDefaultValue() : '!oOpS!');
                $parms[$one->getName()] = $val;
            }
            return FormUtils::create($this, $name, $parms);
        }
        return false;
    }

    /**
     * ------------------------------------------------------------------
     * Load internals.
     * ------------------------------------------------------------------
     */

    /**
     * Private
     *
     * @ignore
     */
    private function _loadTemplateMethods()
    {
        if (!$this->modtemplates) {
            require_once cms_join_path(__DIR__, 'module_support', 'modtemplates.inc.php');
            $this->modtemplates = true;
        }
    }

    /* *
     * Private
     *
     * @ignore
     */
/*REDUNDANT    private function _loadFormMethods()
    {
        if (!$this->modform) {
            require_once cms_join_path(__DIR__, 'module_support', 'modform.inc.php');
            $this->modform = true;
        }
    }
*/
    /**
     * Private
     *
     * @ignore
     */
    private function _loadRedirectMethods()
    {
        if (!$this->modredirect) {
            require_once cms_join_path(__DIR__, 'module_support', 'modredirect.inc.php');
            $this->modredirect = true;
        }
    }

    /**
     * Private
     *
     * @ignore
     */
    private function _loadUrlMethods()
    {
        if (!$this->modurl) {
            require_once cms_join_path(__DIR__, 'module_support', 'modurl.inc.php');
            $this->modurl = true;
        }
    }

    /**
     * Private
     *
     * @ignore
     */
    private function _loadMiscMethods()
    {
        if (!$this->modmisc) {
            require_once cms_join_path(__DIR__, 'module_support', 'modmisc.inc.php');
            $this->modmisc = true;
        }
    }

    /**
     * ------------------------------------------------------------------
     * Plugin Functions.
     * ------------------------------------------------------------------
     */

    /**
     * Callback function for module plugins.
     * This is used to run a module-action initiated in a page or template.
     *
     * This function cannot be overridden
     *
     * @final
     * @internal
     * @param array $params
     * @param type $template
     * @return mixed module call output.
     */
    final public static function function_plugin(array $params, $template)
    {
        if( empty($params['module']) ) {
            $class = static::class;
            if( $class != CMSModule::class ) {
                $params['module'] = $class;
            }
        }
        return ModulePluginOperations::call_plugin_module($params, $template);
    }

    /**
     * Register a smarty plugin associated with the module.
     * This method records the plugin in the plugins database table,
     *  and should be used only when a module is installed or upgraded.
     * @see https://www.smarty.net/docs/en/api.register.plugin.tpl
     * @author calguy1000
     * @since 1.11
     *
     * @param string  $name The plugin name
     * @param string  $type The plugin type (function,compiler,block, etc)
     * @param callable $callback The plugin processor,
     *  (since 2.9) an actual callable or
     *  a string identifying a static function, like 'class::name' or just 'name' (if the module-class is implied)
     * @param bool    $cachable UNUSED since 2.9 (always cachable) Optional flag whether this function is cachable. Default true.
     * @param int     $usage Optional bit-flag(s) for frontend and/or backend availability.
     *   Default 0, hence ModulePluginOperations::AVAIL_FRONTEND
     *   0=front, 1=front, 2=back, 3=both
     * @return bool, or not at all
     * @throws Exception
     */
    public function RegisterSmartyPlugin($name, $type, $callback, $cachable = true, $usage = 0)
    {
        if( !$name || !$type || !$callback ) {
            throw new Exception('Invalid data passed to RegisterSmartyPlugin');
        }
        // validate $type
        switch( $type ) {
            case 'function':
            case 'modifier':
            case 'block':
            case 'prefilter':
            case 'postfilter':
            case 'outputfilter':
            case 'compiler':
            case 'resource':
            case 'insert':
            break;
            default:
            throw new Exception('Invalid data passed to RegisterSmartyPlugin');
        }

        $modname = $this->GetName();
        if( is_string($callback) ) {
            if( strpos($callback,'::') !== false ) {
                $callable = $callback;
            } else {
                $callable = $modname.'::'.$callback;
            }
        } elseif( is_callable($callback) ) {
            $callable = $callback;
        } else {
            throw new Exception('Invalid callable passed to RegisterSmartyPlugin');
        }
        return ModulePluginOperations::add($modname, $name, $type, $callable, $cachable, $usage);
    }

    /**
     * Unregister smarty plugin(s) by name or current module.
     * This method removes any matching rows from the database, and
     *  should only be used during module uninstallation or upgrade.
     *
     * @author calguy1000
     * @since 1.11
     * @param string $name Optional plugin name. Defaule '', which implies
     *  all plugins registered for this module.
     */
    public function RemoveSmartyPlugin($name = '')
    {
        if( $name == '' ) {
            ModulePluginOperations::remove_by_module($this->GetName());
        } else {
            ModulePluginOperations::remove_by_name($name);
        }
    }

    /**
     * Register the module as a smarty 'function' plugin.
     * This method should be called during module installation or upgrade,
     *  or from the module's constructor or Initialize*() method.
     *
     * @final
     * @param bool $static Optional flag whether to record this registration
     *  in the database. Default false. If true, the module is not immediately
     *  registered with Smarty i.e. for use during module installation/upgrade.
     *  Ignored since 2.9. Automatic true|false per install/upgrade | not.
     * @param mixed bool|null $cachable Optional indicator whether the plugin's
     *  (frontend) output is cachable by Smarty. Default false.
     *  Deprecated since 2.9 (Make it always be cachable, with override in page|template)
     * @return bool
     */
    final public function RegisterModulePlugin(bool $static = false, $cachable = false) : bool
    {
        $name = $this->GetName();
        if( $this->modinstall ) {
            // record in database
            return ModulePluginOperations::add($name, $name, 'function', $name.'::function_plugin', $cachable);
        }

        if( !AppState::test_state(AppState::STATE_INSTALL) ) {
            // record in cache, for this request
            return ModulePluginOperations::add_dynamic($name, $name, 'function', $name.'::function_plugin', $cachable);
        }
        return false;
    }

    /**
     * Report whether the output generated by a tag representing this module can be cached by smarty.
     *
     * @final
     * @since 1.11
     * @deprecated since 2.9 frontend output should default to cachable, subject to in-template smarty overrides
     * @author Robert Campbell
     *
     * @return bool
     */
    final public function can_cache_output() : bool
    {
        if( AppState::test_any_state(AppState::STATE_ADMIN_PAGE | AppState::STATE_INSTALL | AppState::STATE_STYLESHEET) ) {
            return false;
        }
        return $this->AllowSmartyCaching();
    }

    /**
     * Report whether the output generated (during a frontend request) by a tag
     * representing this module can be cached by smarty
     * @since 1.11
     * @deprecated since 2.9
     *
     * @author Robert Campbell
     *
     * @return bool
     */
    public function AllowSmartyCaching()
    {
        return (int)AppParams::get('smarty_cachelife',-1) != 0;
    }

    /**
     * ------------------------------------------------------------------
     * Basic Functions.  Name and Version MUST be overridden.
     * ------------------------------------------------------------------
     */

    /**
     * Returns a sufficient about page for a module
     *
     * @abstract
     * @return string The about page HTML text.
     */
    public function GetAbout()
    {
        $this->_loadMiscMethods();
        return call_user_func('CMSMS\\module_support\\'.__FUNCTION__, $this);
    }

    /**
     * Returns a sufficient help page for a module
     *
     * @final
     * @return string The help page HTML text.
     */
    final public function GetHelpPage() : string
    {
        $this->_loadMiscMethods();
        return call_user_func('CMSMS\\module_support\\'.__FUNCTION__, $this);
    }

    /**
     * Returns the name of the module
     *
     * @abstract
     * @return string The name of the module.
     */
    public function GetName()
    {
        $tmp = get_class($this);
        return basename(str_replace(['\\','/'],[DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR],$tmp));
    }

    /**
     * Returns the full path to the module directory.
     *
     * @final
     * @return string The full path to the module directory.
     */
    final public function GetModulePath() : string
    {
        return ModuleOperations::get_instance()->get_module_path($this->GetName());
    }

    /**
     * Returns the URL path to the module directory.
     *
     * @final
     * @param bool $use_ssl Optional generate an URL using HTTPS path Unused since 2.9
     * @return string The full path to the module directory.
     */
    final public function GetModuleURLPath(bool $use_ssl = false) : string
    {
        return cms_path_to_url($this->GetModulePath());
    }

    /**
     * Returns a translatable name of the module.  For modules whose name can
     * probably be translated into another language (like News)
     *
     * @abstract
     * @return string
     */
    public function GetFriendlyName()
    {
        return $this->GetName();
    }

    /**
     * Returns the version of the module
     *
     * @abstract
     * @return string
     */
    abstract public function GetVersion();

    /**
     * Returns the minimum version necessary to run this version of the module.
     *
     * @abstract
     * @return string
     */
    public function MinimumCMSVersion()
    {
        return CMS_VERSION;
    }

    /**
     * Returns the help for the module
     *
     * @abstract
     * @return string Help HTML Text.
     */
    public function GetHelp()
    {
        return '';
    }

    /**
     * Returns XHTML that needs to go between the <head> tags when this module is called from an admin side action.
     *
     * This method is called by the admin theme when executing an action for a specific module.
     *
     * @return string XHTML text
     */
    public function GetHeaderHTML()
    {
        return '';
    }

    /**
     * Record the entire content of an admin page. This is intended for
     * 'minimal' pages which can bypass normal page-content generation.
     *
     * @since 2.9
     * @param string $text the complete [X]HTML
     */
    public function AdminPageContent(string $text)
    {
        if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) {
            $text = trim($text);
            $obj = Utils::get_theme_object();
            if( $text && $obj ) $obj->set_content($text);
        }
        elseif( AppSingle::App()->JOBTYPE > 0 ) {
            echo $text;
        }
    }

    /**
     * Use this method to prevent the admin interface from outputting header, footer,
     * theme, etc, so your module can output files directly to the administrator.
     * Do this by returning true.
     *
     * @param  array $request The input $_REQUEST[].
     *  This can be used to test whether or not admin output should be suppressed.
     * @return bool
     */
    public function SuppressAdminOutput(&$request)
    {
        return false;
    }

    /**
     * Register an intra-request route to use for pretty-URL parsing
     *
     * @final
     * @param string $pattern Route string, regular expression or exact string
     * @param array $defaults Optional associative array containing defaults
     * @param bool  $is_exact Since 2.9 Optional flag whether $pattern is strict|literal. Default false
     *  for parameters that might not be included in the url
     */
    final public function RegisterRoute(string $pattern, array $defaults = [], bool $is_exact = FALSE)
    {
        $route = new CmsRoute($pattern,$this->GetName(),$defaults,$is_exact);
        RouteOperations::add($route);
    }

    /**
     * Register all static (database-recorded) routes for this module.
     *
     * @abstract
     * @since 1.11
     * @since 2.9 this is a deprecated alias for CreateStaticRoutes()
     * @author Robert Campbell
     */
    public function CreateRoutes()
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CreateStaticRoutes'));
        $this->CreateStaticRoutes();
    }

    /**
     * Register all static (database-recorded) routes for this module.
     * This method should be called rarely e.g. during module installation
     * or upgrade or after module-settings change.
     *
     * @abstract
     * @since 2.9
     * @since 1.11 as CreateRoutes()
     * @author Robert Campbell
     */
    public function CreateStaticRoutes() {}

    /**
     * This is a transitional mechanism to speed up module processing.
     * Returns true/false indicating whether the module registers route(s) during
     * construction/intialization.  Defaults to true.
     * @see CMSModule::RegisterRoute() and CreateStaticRoutes()
     * @since 2.9
     * @deprecated since 2.9 Instead CMSModule::HasCapability() should report CMSMS\CoreCapabilities::ROUTE_MODULE
     *
     * @abstract
     * @return bool
     */
    public function IsRoutableModule()
    {
        return true;
    }

    /**
     * Returns a list of parameters and their help strings in a hash.
     * This is generally used internally.
     * Not to be confused with the GetParameters() class
     *
     * @final
     * @internal
     * @return array
     */
    final public function GetParameters() : array
    {
        if( count($this->params) == 0 ) $this->InitializeAdmin(); // quick hack to load parameters if they are not already loaded.
        return $this->params;
    }

    /**
     * Sanitize the action-parameters in the provided data array.
     * This method is called to deal with action-parameters incoming from the
     * frontend. It uses the map created by the module's SetParameterType() method.
     *
     * @internal
     * @access private
     * @param string $modulename Module name
     * @param array  $data The data to process
     * @param array  $map  A map of registered parameter names and corresponding types
     * @param bool   $allow_unknown A flag indicating whether unknown keys in $data
     *  are acceptable. Default false.
     * @param bool   $clean_keys A flag indicating whether $data keys should be
     *  treated as strings and cleaned. Default true.
     * @return array
     */
    private function _cleanParamHash(string $modulename, array $data, array $map, bool $allow_unknown = false, bool $clean_keys = true) : array
    {
        $mappedcount = 0;
        $result = [];
        foreach( $data as $key => $value ) {
            $mapped = false;
            $paramtype = '';
            if( is_array($map) ) {
                if( isset($map[$key]) ) {
                    $paramtype = $map[$key];
                }
                else {
                    // Key not found in the map
                    // see if one matches via regular expressions
                    foreach( $map as $mk => $mv ) {
                        if(strstr($mk,CLEAN_REGEXP) === false) continue;

                        // mk is a regular expression
                        $ss = substr($mk,strlen(CLEAN_REGEXP));
                        if( $ss !== false ) {
                            if( preg_match($ss, $key) ) {
                                // it matches, we now know what type to use
                                $paramtype = $mv;
                                break;
                            }
                        }
                    }
                } // else

                if( $paramtype != '' ) {
                    ++$mappedcount;
                    $mapped = true;
                    switch( $paramtype ) {
                    case 'CLEAN_INT':
                        $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                        break;
                    case 'CLEAN_FLOAT':
                        $value = filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT);
                        break;
                    case 'CLEAN_NONE':
                        // pass through without cleaning.
                        break;
                    case 'CLEAN_STRING':
                        $value = filter_var($value, FILTER_SANITIZE_STRING);
                        break;
                    case 'CLEAN_FILE':
                        $value = realpath($value);
                        if ($value === false
                         || strpos($value, CMS_ROOT_PATH) !== 0) {
                            $value = CLEANED_FILENAME;
                        }
                        break;
                    default:
                        if (is_string($value) && $value !== '') {
                            $value = filter_var($value, FILTER_SANITIZE_STRING,
                                    FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
                        }
                        break;
                    } // switch
                } // if $paramtype
            }

            if( $allow_unknown && !$mapped ) {
                // we didn't clean this yet
                // but we're allowing unknown stuff so we'll just clean it.
                $mappedcount++;
                $mapped = true;
                if (is_string($value) && $value !== '') {
                    $value = filter_var($val, FILTER_SANITIZE_STRING,
                            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
                }
            }

            if ($clean_keys) {
                $key = filter_var($key, FILTER_SANITIZE_STRING,
                        FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
            }

            if( !$mapped && !$allow_unknown ) {
                trigger_error('Parameter '.$key.' is not known by module '.$modulename.' dropped',E_USER_WARNING);
                continue;
            }
            $result[$key]=$value;
        }
        return $result;
    }

    /*
     * Note: In past versions of CMSMS, SetParameters() was used for both admin and
     * frontend requests to register routes, create parameters, register a module plugin, etc.
     * As of version 1.10 this method was deprecated, replaced by InitializeFrontend()
     * and InitializeAdmin(). This method was scheduled for removal in version 1.11,
     * and as of 2.3, is gone.
     */

    /**
     * Called for module frontend actions only.
     * This method should be overridden to create routes, set handled parameters,
     * and perform other initialization tasks that need to be done for any
     * frontend action.
     *
     * @abstract
     * @see CMSModule::SetParameterType()
     * @see CMSModule::RegisterRoute()
     * @see CMSModule::RegisterModulePlugin()
     */
    protected function InitializeFrontend()
    {
    }

    /**
     * Called for module admin actions only.
     * This method should be overridden to create routes, set handled parameters,
     * and perform other initialization tasks that need to be done for any
     * backend action.
     *
     * @abstract
     * @see CMSModule::CreateParameter()
     */
    protected function InitializeAdmin()
    {
    }

    /*
     * A method to indicate that the system should drop and optionally
     * generate an error about unknown parameters on frontend actions.
     *
     * This functionality was removed in 2.2.4 ? Unknown parameters are always restricted.
     *
     * @see CMSModule::SetParameterType()
     * @see CMSModule::CreateParameter()
     * @final
     * @param bool $flag Indicating whether unknown params should be restricted.
     */
    final public function RestrictUnknownParams(bool $flag = true)
    {
    }

    /**
     * Indicate the name and type of acceptable parameter(s) for frontend actions.
     *
     * possible values for type are:
     * CLEAN_INT,CLEAN_FLOAT,CLEAN_NONE,CLEAN_STRING,CLEAN_REGEXP,CLEAN_FILE
     *
     * e.g. $this->SetParameterType('numarticles',CLEAN_INT);
     *
     * @see CMSModule::CreateParameter()
     * @see CMSModule::SetParameters()
     * @final
     * @param mixed $param string Parameter name or (since 2.9) [name => type] array
     * @param string $type  Parameter type
     */
    final public function SetParameterType($param, string $type)
    {
        if( is_array($param) ) {
            foreach( $param as $name => $type ) {
                switch( $type ) {
                case CLEAN_INT:
                case CLEAN_FLOAT:
                case CLEAN_NONE:
                case CLEAN_STRING:
                case CLEAN_REGEXP:
                case CLEAN_FILE:
                    $this->param_map[trim($name)] = $type;
                    break;
                default:
                    trigger_error('Attempt to set invalid parameter type');
                    break;
                }
            }
            return;
        }

        switch( $type ) {
        case CLEAN_INT:
        case CLEAN_FLOAT:
        case CLEAN_NONE:
        case CLEAN_STRING:
        case CLEAN_REGEXP:
        case CLEAN_FILE:
            $this->param_map[trim($param)] = $type;
            break;
        default:
            trigger_error('Attempt to set invalid parameter type');
            break;
        }
    }

    /**
     * Create a parameter and its documentation for display in the module help.
     *
     * e.g. $this->CreateParameter('numarticles',100000,$this->Lang('help_numarticles'),true);
     *
     * @see CMSModule::SetParameters()
     * @see CMSModule::SetParameterType()
     * @final
     * @param string $param Parameter name
     * @param mixed  $defaultval Optional default parameter value string | null
     * @param string $helpstring Optional help string
     * @param bool   $optional  Optional flag indicating whether this parameter is optional or required. Default true
     */
    final public function CreateParameter(string $param, $defaultval = '', string $helpstring = '', bool $optional = true)
    {
        array_push($this->params,
        ['name' => $param,
         'default' => $defaultval,
         'help' => $helpstring,
         'optional' => $optional]
        );
    }

    /**
     * Returns a short description of the module
     *
     * @abstract
     * @return string
     */
    public function GetDescription()
    {
        return '';
    }

    /**
     * Returns a description of what the admin link does.
     *
     * @abstract
     * @return string
     */
    public function GetAdminDescription()
    {
        return '';
    }

    /**
     * Returns whether this module should only be loaded for admin purposes
     *
     * @abstract
     * @return bool
     */
    public function IsAdminOnly()
    {
        return false;
    }

    /**
     * Returns the changelog for the module
     *
     * @return string HTML text of the changelog.
     */
    public function GetChangeLog()
    {
        return '';
    }

    /**
     * Returns the name of the author
     *
     * @abstract
     * @return string The name of the author.
     */
    public function GetAuthor()
    {
        return '';
    }

    /**
     * Returns the email address of the author
     *
     * @abstract
     * @return string The email address of the author.
     */
    public function GetAuthorEmail()
    {
        return '';
    }

    /**
     * ------------------------------------------------------------------
     * Reference functions
     * ------------------------------------------------------------------
     */

    /**
     * Returns the cms->config object as a reference
     *
     * @final
     * @return array The config hash.
     * @deprecated Use CMSMS\AppSingle::Config()
     */
    final public function GetConfig()
    {
        return AppSingle::Config();
    }

    /**
     * Returns the cms->db object as a reference
     *
     * @final
     * @return Database object
     * @deprecated Use CMSMS\AppSingle::Db()
     */
    final public function GetDb()
    {
        return AppSingle::Db();
    }

    /**
     * ------------------------------------------------------------------
     * Content Block Related Functions
     * ------------------------------------------------------------------
     */

    /**
     * Get an input field for a module generated content block type.
     *
     * This method is called from the content edit form when a {content_module} tag is encountered.
     *
     * This method can be overridden if the module is providing content
     * block types to the CMSMS content objects.
     *
     * @abstract
     * @since 2.0
     * @param string $blockName Content block name
     * @param mixed  $value     Content block value
     * @param array  $params    Associative array containing content block parameters
     * @param bool   $adding    Flag indicating whether the content editor is in create mode (adding) vs. edit mode.
     * @param ContentBase $content_obj The content object being edited.
     * @return mixed Either an array with two elements (prompt, and xhtml element) or a string containing only the xhtml input element.
     */
    public function GetContentBlockFieldInput($blockName, $value, $params, $adding, ContentBase $content_obj)
    {
        return false;
    }

    /**
     * Return a value for a module generated content block type.
     *
     * This mehod is called from a {content_module} tag, when the content edit form is being edited.
     *
     * Given input parameters (i.e: via _POST or _REQUEST), this method
     * will extract a value for the given content block information.
     *
     * This method can be overridden if the module is providing content
     * block types to the CMSMS content objects.
     *
     * @abstract
     * @since 2.0
     * @param string $blockName Content block name
     * @param array  $blockParams Content block parameters
     * @param array  $inputParams input parameters
     * @param ContentBase $content_obj The content object being edited.
     * @return mixed|false The content block value if possible.
     */
    public function GetContentBlockFieldValue($blockName, $blockParams, $inputParams, ContentBase $content_obj)
    {
        return false;
    }

    /**
     * Validate the value for a module generated content block type.
     *
     * This method is called from a {content_module} tag, when the content edit form is being validated.
     *
     * This method can be overridden if the module is providing content
     * block types to the CMSMS content objects.
     *
     * @abstract
     * @since 2.0
     * @param string $blockName Content block name
     * @param mixed  $value     Content block value
     * @param arrray $blockparams Content block parameters.
     * @param contentBase $content_obj The content object that is currently being edited.
     * @return string An error message if the value is invalid, empty otherwise.
     */
    public function ValidateContentBlockFieldValue($blockName, $value, $blockparams, ContentBase $content_obj)
    {
        return '';
    }

    /**
     * Render the value of a module content block on the frontend of the website.
     * This gives modules the opportunity to render data stored in content blocks differently.
     *
     * @abstract
     * @since 2.2
     * @param string $blockName Content block name
     * @param string $value     Content block value as stored in the database
     * @param array  $blockparams Content block parameters
     * @param ContentBase $content_obj The content object that is currently being displayed
     * @return string
     */
    public function RenderContentBlockField($blockName, $value, $blockparams, ContentBase $content_obj)
    {
        return $value;
    }

    /**
     * Register a bulk content action, for use in a content list
     *
     * @final
     * @param string $label A label for the action
     * @param string $action A module action name.
     */
    final public function RegisterBulkContentFunction(string $label, string $action)
    {
        try {
            BulkOperations::register_function($label,$action,$this->GetName());
        }
        catch (Throwable $t) {
           //nothing here
        }
    }

    /**
     * ------------------------------------------------------------------
     * Content Type Related Functions
     * ------------------------------------------------------------------
     */

    /**
     * Register a custom content-type.
     * This should be called from the module's constructor or from relevant
     * Initialize method(s).
     * The module must report CMSMS\CoreCapabilities::CONTENT_TYPES or else
     * this will probably never be called.
     *
     * @since 0.9 (but missing from 2.0-2.2)
     *
     * @param string $name Name of the content type
     * @param string $locator Fully-qualified class, or if the class cannot be
     *  autoloaded, the filesystem path of the file defining the content type
     * @param mixed $friendlyname Optional public|friendly name of the content type. Default ''
     * @param string $editorlocator since 2.9 Optional fully-qualified class, or
     * if the class cannot be autoloaded, the filesystem path of the file defining
     * the type's (ContentEditor-conformant) editable class.
     *  Default '' hence no distinct class for editing pages of this type.
     */
    public function RegisterContentType($name, $locator, $friendlyname = '', $editorlocator = '')
    {
        $parms = [
            'type' => $name,
            'friendlyname' => $friendlyname,
            'locator' => $locator,
            'editorlocator' => $editorlocator,
        ];
        $obj = new ContentType($parms);
        ContentTypeOperations::get_instance()->AddContentType($obj);
    }

    /**
     * Register all static (database-recorded) content types for this module.
     * This should be called rarely e.g. during module installation or
     * upgrade or after module-settings change.
     *
     * @abstract
     * @since 2.3
     */
    public function CreateStaticContentTypes() {}

    /**
     * ------------------------------------------------------------------
     * Installation Related Functions
     * ------------------------------------------------------------------
     */

    /**
     * Function called when a module is being installed. This function should
     * do any initialization functions including creating database tables.
     * The default behavior of this function is to include the file named
     * method.install.php located in the module's base directory, if such file
     * exists.
     *
     * A falsy return value, or 1 (numeric, also PHP's 'successful inclusion'
     * indicator), will be treated as an indication of successful completion.
     * Otherwise, the method should return an error message (string), or
     * a different number (e.g. 2).
     *
     * @abstract
     * @return mixed included-method-supplied (e.g. string|int != {0,1}) upon failure, or false upon success
     */
    public function Install()
    {
        $filename = $this->GetModulePath().'/method.install.php';
        if (@is_file($filename)) {
            $gCms = AppSingle::App();
            $db = AppSingle::Db();
            $config = AppSingle::Config();
            if( !AppState::test_state(AppState::STATE_INSTALL) ) $smarty = AppSingle::Smarty();

            $res = include $filename;
            if ($res && $res !== 1) { return $res; }
        }
        return false;
    }


    /**
     * Display a message after a successful installation of the module.
     *
     * @abstract
     * @return XHTML Text
     */
    public function InstallPostMessage()
    {
        return false;
    }

    /**
     * Function called when a module is uninstalled. This function should
     * remove any database tables that it uses and perform any other cleanup duties.
     * The default behavior of this function is to include the file named
     * method.uninstall.php located in the module's base directory, if such file
     * exists.
     *
     * A falsy return value, or 1 (numeric, also PHP's 'successful inclusion'
     * indicator), will be treated as an indication of successful completion.
     * Otherwise, the method should return an error message (string), or
     * a different number (e.g. 2).
     *
     * @abstract
     * @return mixed false | string | int
     */
    public function Uninstall()
    {
        $filename = $this->GetModulePath().'/method.uninstall.php';
        if( @is_file($filename) ) {
            $gCms = AppSingle::App();
            $db = AppSingle::Db();
            $config = AppSingle::Config();
            $smarty = AppSingle::Smarty();
            $res = include $filename;
            if( $res == 1 || $res == '' ) return false;
            if( is_string($res) ) {
                $this->ShowErrors($res);
            }
            return $res;
        }
        else {
            return false;
        }
    }

    /**
     * Function called during module uninstall, to get an indicator of whether to
     * also remove all module events, event handlers, module templates, and preferences.
     * The module must still remove its own database tables and permissions
     * @abstract
     * @return bool Whether the core may remove all module events, event handles, module templates, and preferences on uninstall (defaults to true)
     */
    public function AllowUninstallCleanup()
    {
        return true;
    }

    /**
     * Display a message and a Yes/No dialog before doing an uninstall.  Returning noting
     * (false) will go right to the uninstall.
     *
     * @abstract
     * @return XHTML Text, or false.
     */
    public function UninstallPreMessage()
    {
        return false;
    }

    /**
     * Display a message after a successful uninstall of the module.
     *
     * @abstract
     * @return XHTML Text, or false
     */
    public function UninstallPostMessage()
    {
        return false;
    }

    /**
     * Function called when a module is upgraded. This method should be capable of
     * applying changes from versions older than the immediately-prior one, but
     * that's not mandatory. The default behavior of this method is to include
     * the file named method.upgrade.php, located in the module's base directory,
     * if such file exists.
     *
     * A falsy return value, or 1 (numeric, also PHP's 'successful inclusion'
     * indicator), will be treated as an indication of successful completion.
     * Otherwise, the method should return an error message (string), or
     * a different number (e.g. 2).
     *
     * @param string $oldversion The version we are upgrading from
     * @param string $newversion The version we are upgrading to
     * @return mixed
     */
    public function Upgrade($oldversion, $newversion)
    {
        $filename = $this->GetModulePath().'/method.upgrade.php';
        if( @is_file($filename) ) {
            $gCms = AppSingle::App();
            $db = AppSingle::Db();
            $config = AppSingle::Config();
            $smarty = AppSingle::Smarty();

            $this->modinstall = true; // too bad if this method is sub-classed!
            $res = include $filename;
            $this->modinstall = false;

            if( $res && $res !== 1 ) return $res;
        }
        return false;
    }

    /**
     * Returns a list of dependencies and minimum versions that this module
     * requires. It should return a hash of names and versions, e.g.
     *    return [somemodule'=>'1.0', 'othermodule'=>'1.1'];
     *
     * @abstract
     * @return array
     */
    public function GetDependencies()
    {
        return [];
    }

    /**
     * Checks to see if currently installed modules depend on this module.  This is
     * used by the plugins.php page to make sure that a module can't be uninstalled
     * before any modules depending on it are uninstalled first.
     *
     * @internal
     * @final
     * @return bool
     */
    final public function CheckForDependents() : bool
    {
        $db = AppSingle::Db();

        $query = 'SELECT child_module FROM '.CMS_DB_PREFIX.'module_deps WHERE parent_module = ? LIMIT 1';
        $tmp = $db->GetOne($query,[$this->GetName()]);
        return $tmp != false;
    }

    /**
     * Creates an xml data package from the module directory.
     *
     * @final
     * @return string XML Text
     * @param string $message reference to returned message.
     * @param int $filecount reference to returned file count.
     */
    final public function CreateXMLPackage(&$message, &$filecount)
    {
        return ModuleOperations::get_instance()->CreateXmlPackage($this, $message, $filecount);
    }

    /**
     * Return true if there is an admin for the module.  Returns false by
     * default.
     *
     * @abstract
     * @return bool
     */
    public function HasAdmin()
    {
        return false;
    }

    /**
     * Returns the name of the admin-menu section this module belongs to.
     * This is used to place the module in the appropriate admin navigation
     * section. Valid options are currently:
     *
     * main, content, layout, files, usersgroups, extensions, preferences, siteadmin, myprefs, ecommerce
     *
     * @abstract
     * @return string
     */
    public function GetAdminSection()
    {
        return 'extensions';
    }

    /**
     * Return a array of AdminMenuItem objects representing menu items for the admin nav for this module.
     *
     * This method should do all permissions checking when building the array of objects.
     *
     * @since 2.0
     * @abstract
     * @return mixed array of AdminMenuItem objects, or NULL
     */
    public function GetAdminMenuItems()
    {
        if ($this->VisibleToAdminUser()) {
            return [AdminMenuItem::from_module($this)];
        }
    }

    /**
     * Returns true/false indicating whether the user has appropriate
     * permission(s) to see the module in her/his admin menus. Defaults to true.
     *
     * Typically permission checks are done in the overridden version of this
     * method.
     *
     * @abstract
     * @return bool
     */
    public function VisibleToAdminUser()
    {
        return true;
    }

    /**
     * Returns true/false indicating whether the module should be treated as a
     * plugin module (like {cms_module module='name'}.  Defaults to false.
     * @see CMSModule::RegisterModulePlugin()
     * @deprecated since 2.9 Instead CMSModule::HasCapability() should report CMSMS\CoreCapabilities::PLUGIN_MODULE
     *
     * @abstract
     * @return bool
     */
    public function IsPluginModule()
    {
        return false;
    }

    /**
     * Returns true/false indicating whether the module may be lazy loaded during
     * a front-end request.
     *
     * @since 1.10
     * Unused since 2.9 Modules are always loaded on-demand
     * @return bool
     */
    public function LazyLoadFrontend()
    {
        return true;
    }

    /**
     * Returns true/false indicating whether the module may be lazy-loaded during
     * an admin/backend request.
     *
     * @since 1.10
     * Unused since 2.9 Modules are always loaded on-demand
     * @return bool
     */
    public function LazyLoadAdmin()
    {
        return true;
    }

    /**
     * -------------------------------------------------------------
     * Module capabilities, for checking what a module can do
     * -------------------------------------------------------------
     */

    /**
     * Returns true if the module thinks it has the capability specified
     *
     * @abstract
     * @param string $capability an id specifying which capability to check for, could be "wysiwyg" etc.
     * @param array  $params An associative array further params to get more detailed info about the capabilities. Should be synchronized with other modules of same type
     * @return bool
     */
    public function HasCapability($capability, $params = [])
    {
        return false;
    }

    /**
     * Returns a list of the tasks that this module manages
     *
     * @since 1.8
     * @abstract
     * @return mixed array of task objects, or one such object, or falsy if not handled.
     * Since 2.2 the object(s) may implement the (deprecated) CmsRegularTask
	 * (a.k.a. CMSMS\IRegularTask) interface, or be a descendant-class of
	 * CMSMS\Async\Job, probably of CronJob
     */
    public function get_tasks()
    {
        return false;
    }

    /**
     * Returns a list of the CLI commands that this module supports
     * Modules supporting such commands must subclass this, and therein call
     * here for a security-check before returning commands data
     *
     * @since 2.9
     * @param CMSMS\CLI\App $app (this class may not exist) TODO better namespace
     * @return mixed array of CMSMS\CLI\GetOptExt\Command objects, or one such object, or NULL if not handled.
     */
    public function get_cli_commands($app)
    {
        $config = AppSingle::Config();
        //TODO discuss with calguy: better approach for this stuff
        if( !$config['app_mode'] ) return null;
        if( ! $app instanceof CMSMS\CLI\App ) return null;
        if( !class_exists('CMSMS\\CLI\\GetOptExt\\Command') ) return null;
        return [];
    }

    /**
     * ------------------------------------------------------------------
     * Syntax Highlighter Related Functions
     *
     * These functions are only for syntax-highlight editor modules.
     * ------------------------------------------------------------------
     */

    /**
     * Returns page-header content (probably js, css) for this editor.
     * @deprecated since 2.9. Instead generate and record such content when
     * constructing the textarea element, using get_syntaxeditor_script() and
     * then cachers such as add_page_headtext(), add_page_footertext()
     *
     * @abstract
     * @return string
     */
    public function SyntaxGenerateHeader()
    {
        return '';
    }

    /**
     * ------------------------------------------------------------------
     * Content (html) Rich-Edit Related Functions
     *
     * These methods are only for rich-text-editor modules.
     * ------------------------------------------------------------------
     */

    /**
     * Returns page-header content (probably js, css) for this editor.
     * @deprecated since 2.9. Instead generate and record such content when
     * constructing the textarea element, using get_richeditor_script() and then
     * cachers such as add_page_headtext(), add_page_footertext()
     *
     * @abstract
     * @param string $selector Optional id of the element whose content is to be edited.
     *  If empty, the editor module should assume the selector to be textarea.<ModuleName>.
     * @param string $cssname Optional name of a CMSMS stylesheet to apply.
     *   If $selector is not empty then $cssname is only used for the specific element.
     *   Editor modules might ignore the $cssname parameter, depending on their settings and capabilities.
     * @throws Exception, CmsException
     * @return string
     */
    public function WYSIWYGGenerateHeader($selector = '', $cssname = '')
    {
        return '';
    }

    /**
     * ------------------------------------------------------------------
     * Action Related Functions
     * ------------------------------------------------------------------
     */

    /**
     * Return an action's 'controller', which if it exists, is a function to be
     * called to 'do' the action (instead of including the action file). The
     * callable is expected to be returned by the constructor of a class named
     * like "$name_action" placed in, and namespaced for, folder <path-to-module>/Controllers
     *
     * @since 2.3
     * @param string $name The name of the action to perform
     * @param mixed $id string|null Action identifier e.g. typically 'm1_' for admin
     * @param array  $params The parameters targeted for this module
     * @param mixed int|''|null $returnid Identifier of the page being displayed, ''|null for admin
     * @return mixed callable|null
     */
    protected function get_controller(string $name, $id, array $params, $returnid = null)
    {
        if( isset( $params['controller']) ) {
            $ctrl = $params['controller'];
        } else {
            $c = get_class($this);
            $p = strrpos($c, '\\');
            $namespace = ($p !== false) ? substr($c, $p+1) : $c;
            $ctrl = $namespace."\\Controllers\\{$name}_action";
        }
        if( is_string($ctrl) && class_exists( $ctrl ) ) {
            $ctrl = new $ctrl( $this, $id, $returnid );
        }
        if( is_callable( $ctrl ) ) return $ctrl;
    }

    /**
     * Used for navigation between "pages" of a module.  Forms and links should
     * pass an action with them so that the module will know what to do next.
     * By default, DoAction will be passed 'default' and 'defaultadmin',
     * depending on where the module was called from.  If being used as a module
     * or content type, 'default' will be passed.  If the module was selected
     * from the list on the admin menu, then 'defaultadmin' will be passed.
     *
     * To allow segregating functionality into multiple PHP files the default
     * behavior of this method is to look for a file named action.<action name>.php
     * in the modules directory, and if it exists include it.
     *
     * Variables in-scope for the included file will be:
     *  $action see below
     *  $name   deprecated since 2.9 (use $action)
     *  $id     see below
     *  $params see below
     *  $returnid see below
     *  $gCms   CMSMS application object
     *  $db     database connection object
     *  $config configuration object
     *  $smarty CMSMS subclass of the main smarty object or a template
     *   object (in the latter case the main $smarty is at $smarty->smarty)
     *  $uuid   site-identifier since 2.9
     *
     * Actions may provide their output by direct display and/or return.
     * If the output is entirely displayed, the action should also explicitly
     * return a falsy value, to preempt an unwanted (integer) 1 generated
     * by PHP to signal successful inclusion. If the action needs to return
     * a value of 1, it must be as '1' to distinguish it from PHP's indicator.
     *
     * @param mixed  $action string|falsy The name of the action to perform
     * @param mixed $id string|null Action identifier e.g. typically 'm1_' for admin
     * @param array  $params The parameters specified for the action
     * @param mixed  $returnid Optional id of the page being displayed,
     *  numeric(int) for frontend, ''|null for admin. Default null.
     * @return mixed output from included file or from 'controller' if relevant
     * @throws CmsError404Exception if action not named or not found
     */
    public function DoAction($action, $id, $params, $returnid = null)
    {
        if( !is_numeric($returnid) ) {
            $key = $this->GetName().'::activetab';
            if( isset($_SESSION[$key]) ) {
                $this->SetCurrentTab($_SESSION[$key]);
                unset($_SESSION[$key]);
            }
            if( ($errs = $this->GetErrors()) ) {
                $this->ShowErrors($errs);
            }
            if( ($msg = $this->GetMessage()) ) {
                $this->ShowMessage($msg);
            }
        }

        if( $action ) {
            //In case this method was called directly and is not overridden.
            //See: http://0x6a616d6573.blogspot.com/2010/02/cms-made-simple-166-file-inclusion.html
            $action = preg_replace('/[^A-Za-z0-9\-_+]/', '', $action);

            if( ($controller = $this->get_controller($action, $id, $params, $returnid)) ) {
                if( is_callable($controller ) ) {
                    return $controller($params);
                }
                @trigger_error($action.' action-controller in module '.$this->GetName().' is invalid');
                throw new CmsError404Exception('Invalid module action-controller');
            }
            else {
                $filename = $this->GetModulePath() . DIRECTORY_SEPARATOR . 'action.'.$action.'.php';
                if( is_file($filename) ) {
                    $name = $action; // former name of the action, might be expected by an action
                    // convenient in-scope vars for the included file
                    $gCms = AppSingle::App();
                    $db = AppSingle::Db();
                    $config = AppSingle::Config();
                    //TODO no $smarty -if no template-processing
                    $smarty = (!empty($this->_action_tpl)) ? $this->_action_tpl : AppSingle::Smarty();
                    $uuid = $gCms->GetSiteUUID(); //since 2.9
                    // collect action output (echoed and/or returned)
                    ob_start();
                    $result = include $filename;
                    if( $result === 1 ) { $result = ''; } // ignore PHP's 'successful inclusion' indicator
                    elseif( !($result || is_numeric($result)) ) { $result = ''; }
                    if( $result !== '' ) { echo $result; }
                    $result = ob_get_clean();
                    return $result;
                }
                @trigger_error($action.' is not a recognized action of module '.$this->GetName());
                throw new CmsError404Exception('Module action not found');
            }
        }

        @trigger_error('No name provided for '.$this->GetName()).' module action';
        throw new CmsError404Exception('Module action not named');
    }

    /**
     * Prepare data and do appropriate checks before performing a module action.
     *
     * @internal
     * @ignore
     * @param mixed $action The action name, string|falsy (in which case an exception will be thrown)
     * @param mixed $id string|null The action identifier
     * @param array  $params The action parameters
     * @param mixed  $returnid The current page id. numeric(int) for frontend, null|'' for admin|login requests.
     * @param mixed  $smartob  A CMSMS\internal\template_wrapper object, or CMSMS\internal\Smarty object, or null
     * @return mixed output from action 'controller' if relevant, or null, or '' if output is assigned to a smarty variable
     */
    public function DoActionBase($action, $id, $params, $returnid, $smartob)
    {
        $action = preg_replace('/[^A-Za-z0-9\-_+]/', '', $action); //simple sanitize
        $id = filter_var($id, FILTER_SANITIZE_STRING); //only alphanum

        if( is_numeric($returnid) ) {
            // merge in params from module hints.
            $hints = Utils::get_app_data('__CMS_MODULE_HINT__'.$this->GetName());
            if( is_array($hints) ) {
                foreach( $hints as $key => $value ) {
                    if( isset($params[$key]) ) continue;
                    $params[$key] = $value;
                }
                unset($hints);
            }

            $this->InitializeFrontend(); // just in case
            // to try to avert XSS flaws, clean parameters according to the map generated
            // by this module's InitializeFrontend() method. The map should have been populated
            // using SetParameterType() calls.
            // TODO if InitializeFrontend() has not been called? incomplete ->param_map here
            $params = $this->_cleanParamHash( $this->GetName(),$params,$this->param_map );
        }

        // handle the stupid input type='image' problem.
        foreach( $params as $key => $value ) {
            if( endswith($key,'_x') ) {
                $base = substr($key,0,-2);
                if( isset($params[$base.'_y']) && !isset($params[$base]) ) $params[$base] = $base;
            }
        }

        if (!isset($params['action'])) {
            $params['action'] = $action; // deprecated since 2.3 (the supplied $action variable should be enough)
        }

        if( is_numeric($returnid) ) {
            $returnid = filter_var($returnid, FILTER_SANITIZE_NUMBER_INT);
            $tmp = $params;
            $tmp['module'] = $this->GetName();
            HookOperations::do_hook('module_action', $tmp); //TODO BAD no namespace, some miscreant handler can change the parameters ... deprecate ?
        } else {
            $returnid = null;
        }

        $gCms = AppSingle::App();
        if( ($cando = $gCms->template_processing_allowed()) ) { //CHECKME $CMS_JOB_TYPE ok here?
            if( $smartob instanceof Smarty ) {
                $smarty = $smartob;
            } else {
                $smarty = AppSingle::Smarty();
            }
            // create a template object to hold some default variables
            // the module-action will normally use another template created/derived fom this one
            $tpl = $smarty->createTemplate('string:DUMMY PARENT TEMPLATE',null,null,$smartob);
            $tpl->assign([
            '_action' => $action,
            '_module' => $this->GetName(),
            'actionid' => $id,
            'actionparams' => $params,
            'returnid' => $returnid,
            'mod' => $this,
            ]);

            $this->_action_tpl = $tpl;
        }
        $output = $this->DoAction($action, $id, $params, $returnid);
        if( $cando ) {
            $this->_action_tpl = null;
        }

        if( isset($params['assign']) ) {
            $smartob->assign(sanitize($params['assign']),$output);
            return '';
        }
        return $output;
    }

    /**
     * ------------------------------------------------------------------
     * Form and XHTML Related Methods - relegated to __call()
     * ------------------------------------------------------------------
     */

    /**
     * function CreateFrontendFormStart
     * Returns xhtml representing the start of a module form, optimized for frontend use
     * @deprecated since 2.3. Instead use CMSMS\FormUtils::create_form_start() with $inline = true
     *
     * @param mixed $id string|null The module action id
     * @param mixed  $returnid The page id (int|''|null) to return to when the module is finished its task
     * Optional parameters:
     * @param string $action The name of the action that this form should do when the form is submitted
     * @param string $method Method to use for the form tag.  Defaults to 'post'
     * @param string $enctype Enctype to use, Good for situations where files are being uploaded
     * @param bool   $inline A flag to determine if actions should be handled inline (no moduleinterface.php -- only works for frontend)
     * @param string $idsuffix Text to append to the end of the id and name of the form
     * @param array  $params Extra parameters to pass along when the form is submitted
     * @param string $addtext since 2.9 Text to append to the <form>-statement, for instance for javascript-validation code
     *
     * @return string
     */

    /**
     * function CreateFormStart
     * Returns xhtml representing the start of a module form
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_form_start()
     *
     * @param mixed $id string|null The module action id
     * @param string $action The action that this form should do when the form is submitted
     * Optional parameters:
     * @param mixed  $returnid The page id (int|''|null) to return to when the module is finished its task
     * @param string $method Method to use for the form tag.  Defaults to 'post'
     * @param string $enctype Enctype to use, Good for situations where files are being uploaded
     * @param bool   $inline A flag to determine if actions should be handled inline (no moduleinterface.php -- only works for frontend)
     * @param string $idsuffix Text to append to the end of the id and name of the form
     * @param array  $params Extra parameters to pass along when the form is submitted
     * @param string $addtext Text to append to the <form>-statement, for instance for javascript-validation code
     *
     * @return string
     */

    /**
     * function CreateFormEnd
     * Returns xhtml representing the end of a module form.  This is basically just a wrapper around </form>, but
     * could be extended later on down the road.  It's here mainly for consistency.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_form_end()
     *
     * @return string
     */

    /**
     * function CreateInputText
     * Returns xhtml representing an input textbox.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_input()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the textbox
     * Optional parameters:
     * @param string $value The predefined value of the textbox, if any
     * @param string $size The number of columns wide the textbox should be displayed
     * @param string $maxlength The maximum number of characters that should be allowed to be entered
     * @param string $addtext Any additional text that should be added into the tag when rendered
     *
     * @return string
     */

    /**
     * function CreateLabelForInput
     * Returns xhtml representing a label for an input field. This is basically a wrapper
     * to make sure that id's are placed in names and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_label()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the input field this label is associated to
     * Optional parameters:
     * @param string $labeltext The text in the label (non much help if empty)
     * @param string $addtext Any additional text that should be added into the tag when rendered
     *
     * @return string
     */

    /**
     * function CreateInputFile
     * Returns xhtml representing a file-selector field.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_input()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the textbox
     * Optional parameters:
     * @param string $accept The MIME-type to be accepted, default is all
     * @param string $size The number of columns wide the textbox should be displayed
     * @param string $addtext Any additional text that should be added into the tag when rendered
     *
     * @return string
     */

    /**
     * function CreateInputPassword
     * Returns xhtml representing an input password-box.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_input()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the textbox
     * Optional parameters:
     * @param string $value The predefined value of the textbox, if any
     * @param string $size The number of columns wide the textbox should be displayed
     * @param string $maxlength The maximum number of characters that should be allowed to be entered
     * @param string $addtext Any additional text that should be added into the tag when rendered
     *
     * @return string
     */

    /**
     * function CreateInputHidden
     * Returns xhtml representing a hidden field.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_input()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the hidden field
     * Optional parameters:
     * @param string $value The predefined value of the field, if any
     * @param string $addtext Any additional text that should be added into the tag when rendered
     *
     * @return string
     */

    /**
     * function CreateInputCheckbox
     * Returns xhtml representing a checkbox.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_select()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the checkbox
     * Optional parameters:
     * @param string $value The value returned from the input if selected
     * @param string $selectedvalue The initial value. If equal to $value the checkbox is selected
     * @param string $addtext Any additional text that should be added into the tag when rendered
     *
     * @return string
     */

    /**
     * function CreateInputSubmit
     * Returns xhtml representing a submit button.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_input()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the button
     * Optional parameters:
     * @param string $value The label of the button. Defaults to 'Submit'
     * @param string $addtext Any additional text that should be added into the tag when rendered
     * @param string $image Use an image instead of a regular button
     * @param string $confirmtext Text to display in a confirmation message.
     *
     * @return string
     */

    /**
     * function CreateInputReset
     * Returns xhtml representing a reset button.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_input()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the button
     * Optional parameters:
     * @param string $value The label of the button. Defaults to 'Reset'
     * @param string $addtext Any additional text that should be added into the tag when rendered
     *
     * @return string
     */

    /**
     * function CreateInputDropdown
     * Returns xhtml representing a dropdown list.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it is syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_select()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the dropdown list
     * @param string $items An array of items to put into the dropdown list... they should be $key=>$value pairs
     * Optional parameters:
     * @param int    $selectedindex The default selected index of the dropdown list.  Setting to -1 will result in the first choice being selected
     * @param string $selectedvalue The default selected value of the dropdown list.  Setting to '' will result in the first choice being selected
     * @param string $addtext Any additional text that should be added into the tag when rendered
     *
     * @return string
     */

    /**
     * function CreateInputSelectList
     * Returns xhtml representing a multi-select list.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it is syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_select()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the select list
     * @param array  $items Items to put into the list... they should be $key=>$value pairs
     * Optional parameters:
     * @param array  $selecteditems Items in the list that should be initially selected.
     * @param string $size The number of rows to be visible in the list (before scrolling).
     * @param string $addtext Any additional text that should be added into the tag when rendered
     * @param bool   $multiple Whether multiple selections are allowed (defaults to true)
     *
     * @return string
     */

    /**
     * function CreateInputRadioGroup
     * Returns xhtml representing a set of radio buttons.  This is basically a wrapper
     * to make sure that id's are placed in names and also that it is syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_select()
     *
     * @param mixed $id string|null The module action id
     * @param string $name The html name of the radio group
     * @param string $items An array of items to create as radio buttons... they should be $key=>$value pairs
     * Optional parameters:
     * @param string $selectedvalue The default selected index of the radio group.   Setting to -1 will result in the first choice being selected
     * @param string $addtext Any additional text that should be added into the tag when rendered
     * @param string $delimiter A delimiter to throw between each radio button, e.g., a <br /> tag or something for formatting
     *
     * @return string
     */

    /**
     * function CreateTextArea
     * Returns xhtml representing a textarea.  Also takes WYSIWYG preference into consideration if it's called from the admin side.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_input()
     *
     * @param bool   $enablewysiwyg Should we try to create a WYSIWYG for this textarea?
     * @param mixed $id string|null The module action id
     * @param string $text The text to display in the textarea
     * @param string $name The html name of the textarea
     * Optional parameters:
     * @param string $classname The html class(es) to add to this textarea
     * @param string $htmlid The html id to give to this textarea
     * @param string $encoding The encoding to use for the text
     * @param string $stylesheet The text of the stylesheet associated to this text.  Only used for certain WYSIWYGs
     * @param string $cols The number of characters (columns) wide the resulting textarea should be
     * @param string $rows The number of characters (rows) high the resulting textarea should be
     * @param string $forcewysiwyg The wysiwyg-system to be used, even if the user has chosen another one
     * @param string $wantedsyntax The language the text should be syntaxhightlighted as
     * @param string $addtext Any additional definition(s) to include in the textarea tag
     *
     * @return string
     */

    /**
     * function CreateSyntaxArea
     * Returns xhtml representing a textarea with syntax hilighting applied.
     * Takes the user's hilighter-preference into consideration, if called from the
     * admin side.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_input()
     *
     * @param mixed $id string|null The module action id
     * @param string $text The text to display in the textarea
     * @param string $name The html name of the textarea
     * Optional parameters:
     * @param string $classname The html class(es) to add to this textarea
     * @param string $htmlid The html id to give to this textarea
     * @param string $encoding The encoding to use for the content
     * @param string $stylesheet The text of the stylesheet associated to this content.  Only used for certain WYSIWYGs
     * @param string $cols The number of characters wide (columns) the resulting textarea should be
     * @param string $rows The number of characters high (rows) the resulting textarea should be
     * @param string $addtext Additional definition(s) to go into the textarea tag.
     *
     * @return string
     */

    /**
     * function CreateLink
     * Returns xhtml representing an href link to a module action.  This is
     * basically a wrapper to make sure that id's are placed in names
     * and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_action_link()
     *
     * @param mixed $id string|null The module action id
     * @param string $action The action that this form should do when the link is clicked
     * Optional parameters:
     * @param mixed  $returnid The page id (int|''|null) to return to when the module is finished its task
     * @param string $contents The displayed clickable text or markup. Defaults to 'Click here'
     * @param string $params An array of params that should be included in the URL of the link.  These should be in a $key=>$value format.
     * @param string $warn_message Text to display in a javascript warning box.  If the user clicks no, the link is not followed by the browser.
     * @param bool   $onlyhref A flag to determine if only the href section should be returned
     * @param bool   $inline A flag to determine if actions should be handled inline (no moduleinterface.php -- only works for frontend)
     * @param string $addtext Any additional text that should be added into the tag when rendered
     * @param bool   $targetcontentonly A flag to determine if the link should target the default content are of the destination page.
     * @param string $prettyurl A pretty url segment (related to the root of the website) for a pretty url.
     *
     * @return string
     */

    /**
     * function CreateFrontendLink
     * Returns xhtml representing an href link  This is basically a wrapper
     * to make sure that id's are placed in names and also that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_action_link() with adjusted params
     *
     * @param mixed $id string|null The module action id
     * @param mixed  $returnid The page id (int|''|null) to return to when the module is finished its task
     * @param string $action The action that this form should do when the link is clicked
     * Optional parameters:
     * @param string $contents The displayed clickable text or markup. Defaults to 'Click here'
     * @param string $params An array of params that should be included in the URL of the link.  These should be in a $key=>$value format.
     * @param string $warn_message Text to display in a javascript warning box.  If they click no, the link is not followed by the browser.
     * @param bool   $onlyhref A flag to determine if only the href section should be returned
     * @param bool   $inline A flag to determine if actions should be handled inline (no moduleinterface.php -- only works for frontend)
     * @param string $addtext Any additional text that should be added into the tag when rendered
     * @param bool   $targetcontentonly A flag indicating that the output of this link should target the content area of the destination page.
     * @param string $prettyurl A pretty url segment (relative to the root of the site) to use when generating the link.
     *
     * @return string
     */

    /**
     * function CreateContentLink
     * Returns xhtml representing a link to a site page having the specified id.
     * This is basically a wrapper to make sure that the link gets to
     * where intended and it's syntax-compliant
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_content_link()
     *
     * @param int $pageid the page id of the page we want to direct to
     * Optional parameters:
     * @param string $contents The displayed clickable text or markup. Defaults to 'Click here'
     *
     * @return string
     */

    /**
     * function CreateReturnLink
     * Returns xhtml representing a link to a site page having the specified returnid.
     * This is basically a wrapper to make sure that we go back
     * to where we want to and that it's syntax-compliant.
     * @deprecated since 2.9. Instead use CMSMS\FormUtils::create_return_link()
     *
     * @param mixed $id string|null The module action id
     * @param mixed  $returnid The page id (int|''|null) to return to when the module is finished its task
     * Optional parameters:
     * @param string $contents The text that will have to be clicked to follow the link
     * @param array  $params Parameters to be included in the URL of the link.  These should be in a $key=>$value format.
     * @param bool   $onlyhref A flag to determine if only the href section should be returned
     *
     * @return string
     */

    /*
     * ------------------------------------------------------------------
     * URL Methods
     * ------------------------------------------------------------------
     */

    /**
     * Return the URL for an action of this module
     *
     * @since 1.10
     *
     * @param mixed $id string|null The module action id.
     *  Anything falsy will use the admin id.
     *  cntnt01 indicates that the default content block of the destination page should be used.
     * @param string $action The module action name
     * Optional parameters:
     * @param mixed  $returnid Optional page id (int|''|null) to return to. Default null (i.e. admin)
     * @param array  $params Optional parameters for the URL. Default []. These
     * will be ignored if the prettyurl argument is specified.
     * Since 2.9 array value(s) may be non-scalar.
     * @param bool   $inline Option flag whether the target of the output link
     *  is the same tag on the same page. Default false.
     * @param bool   $targetcontentonly Optional flag whether the target of the
     * generated link targets the content area of the destination page. Default false.
     * @param string $prettyurl Optional URL segment related to the root of the page,
     * for pretty url creation. Used verbatim. May be ':NOPRETTY:' to omit this part. Default ''.
     * @param int    $format since 2.9 URL-format indicator
     *  0 = default: (back-compatible) rawurlencoded parameter keys and values
     *    other than the value for key 'mact', '&amp;' for parameter separators
     *  1 = proper: as for 0, but also encode the 'mact' value
     *  2 = raw: as for 1, except '&' for parameter separators - e.g. for use in js
     *  3 = displayable: no encoding, all html_entitized, probably not usable as-is
     * @return string
     */
    public function create_url($id, $action, $returnid = null, $params = [],
         $inline = false, $targetcontentonly = false, $prettyurl = '', $format = 0)
    {
        $this->_loadUrlMethods();
        return call_user_func('CMSMS\\module_support\\CreateActionUrl',
            $this, $id, $action, $returnid, $params, $inline,
            $targetcontentonly, $prettyurl, $format);
    }

    /**
     * Return the URL to open a website page
     * Effectively replaces calling one of the CreateLink methods with $onlyhref=true.
     *
     * @since 2.9
     *
     * @param mixed $id string|null The module action id.
     * Optional parameters:
     * @param mixed  $returnid Return-page identifier (int|''|null). Default null (i.e. admin)
     * @param array  $params Parameters for the action. Default []
     *  Since 2.9 array value(s) may be non-scalar.
     * @param int    $format URL-format indicator
     *  0 = default: rawurlencoded parameter keys and values, '&amp;' for parameter separators
     *  1 = raw: as for 0, except '&' for parameter separators - e.g. for use in js
     *  2 = displayable: no encoding, all html_entitized, probably not usable as-is
     * @return string
     */
    public function create_pageurl($id, $returnid = null, array $params = [], int $format = 0) : string
    {
        $this->_loadUrlMethods();
        return call_user_func('CMSMS\\module_support\\CreatePageUrl',
            $this, $id, $returnid, $params, $format);
    }

    /**
     * Return a pretty url string for an action of the module
     * This method is called by the create_url and the CreateLink methods if the pretty url
     * argument is not specified in order to attempt automating a pretty url for an action.
     *
     * @abstract
     * @since 1.10
     * @param mixed $id string|null The module action id (cntnt01 indicates that the default content block of the destination page should be used).
     * @param string $action The module action name
     * @param mixed  $returnid Optional page id (int|''|null) to return to. Default null
     * @param array  $params Optional parameters for the URL. These will be ignored if $prettyurl is provided. Default []
     * @param bool   $inline Optional flag whether the target of the output link is the same tag on the same page. Default false
     * @return string
     */
    public function get_pretty_url($id, $action, $returnid = null, $params = [], $inline = false)
    {
        return '';
    }

    /**
     * ------------------------------------------------------------------
     * Redirection Methods
     * ------------------------------------------------------------------
     */

    /**
     * Redirect to the specified tab.
     * Applicable only to admin actions.
     *
     * @since 1.11
     * @author Robert Campbell
     * @param string $tab Optional tab name.  Default current tab.
     * @param mixed|null  $params Optional associative array of params, or null
     * @param string $action Optional action name. Default 'defaultadmin'.
     * @see CMSModule::SetCurrentTab()
     */
    public function RedirectToAdminTab($tab = '', $params = [], $action = '')
    {
        if( $params === '' ) $params = [];
        if( $tab != '' ) $this->SetCurrentTab($tab);
        if( empty($action) ) $action = 'defaultadmin';
        $this->Redirect('m1_',$action,'',$params,false);
    }

    /**
     * Redirects the user to another action of the module.
     * This function is optimized for frontend use.
     *
     * @param mixed $id string|null The module action id
     * @param mixed  $returnid The page id (int|''|null) to return to when the module is finished its task
     * @param string $action The action that this form should do when the form is submitted
     * @param string $params Optional array of parameters to be passed to the action.
     *   These should be in a $key=>$value format.
     * @param bool $inline Optional flag to determine if actions should be handled inline (no moduleinterface.php -- only works for frontend) Default true
     */
    public function RedirectForFrontEnd($id, $returnid, $action, $params = [], $inline = true)
    {
        $this->Redirect($id, $action, $returnid, $params, $inline);
    }

    /**
     * Redirects the user to another action of the module.
     *
     * @param mixed $id string|null The module action id
     * @param string $action The action that this form should do when the form is submitted
     * @param mixed  $returnid Optional page id (int|''|null) to return to when the module is finished its task
     * @param string $params Optional array of parameters to be included in
     *  the URL of the link.  These should be in a $key=>$value format.
     * @param bool $inline A flag determining whether the actions should be
     *  handled inline (no moduleinterface.php -- only works for frontend)
     */
    public function Redirect($id, $action, $returnid = null, $params = [], $inline = false)
    {
        $this->_loadRedirectMethods();
        call_user_func('CMSMS\\module_support\\'.__FUNCTION__,
            $this, $id, $action, $returnid, $params, $inline);
    }

    /**
     * Redirects to an admin page
     * @param string $page PHP script to redirect to
     * @param array  $params Optional array of parameters to be sent to the page
     */
    public function RedirectToAdmin($page, $params = [])
    {
        $this->_loadRedirectMethods();
        call_user_func('CMSMS\\module_support\\'.__FUNCTION__,
            $this, $page, $params);
    }

    /**
     * Redirects the user to a content page outside of the module.  The passed around returnid is
     * frequently used for this so that the user will return back to the page from which they first
     * entered the module.
     *
     * @param int $id Content id to redirect to.
     */
    public function RedirectContent($id)
    {
        redirect_to_alias($id);
    }

    /**
     * ------------------------------------------------------------------
     * Intermodule Functions
     * ------------------------------------------------------------------
     */

    /**
     * Get a reference to another module object
     *
     * @final
     * @param string $module The required module's name.
     * @return mixed CMSModule-derivative module object, or false
     */
    final public static function GetModuleInstance(string $module)
    {
        return ModuleOperations::get_instance()->get_module_instance($module, '');
    }

    /**
     * Returns an array of classes|names of modules having the specified capability
     *
     * @final
     * @param string $capability name of the capability we are checking for. could be "wysiwyg" etc.
     * @param array  $params further params to get more detailed info about the capabilities. Should be syncronized with other modules of same type
     * @return array
     */
    final public function GetModulesWithCapability(string $capability, array $params = []) : array
    {
        $result = [];
        $tmp = ModuleOperations::get_instance()->GetCapableModules($capability,$params);
        if( $tmp ) {
            for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
                if( is_object($tmp[$i]) ) {
                    $result[] = get_class($tmp[$i]);
                }
                else {
                    $result[] = $tmp[$i];
                }
            }
        }
        return $result;
    }

    /**
     * ------------------------------------------------------------------
     * Language Functions
     * ------------------------------------------------------------------
     */

    /**
     * Returns the corresponding translated string for the given key.
     * This method accepts variable arguments. The first one (required) is
     * the translations-array key (a string). Any extra arguments are assumed
     * to be sprintf arguments to be applied to the value corresponding
     * to the key.
     * NOTE there is no automatic pass-through of admin-realm strings which
     * are missing from the module's own translations. admin can of course
     * be explicitly retrieved by calling lang() instead of $this->Lang(),
     * probably at the cost of a substantially-heftier translations cache.
     *
     * @return string
     */
    public function Lang()
    {
        $args = func_get_args(); //...$args would break the API
        //push module name onto front of array
        array_unshift($args,$this->GetName());

        return LangOperations::lang_from_realm(...$args);
    }

    /**
     * ------------------------------------------------------------------
     * Template/Smarty Functions
     * ------------------------------------------------------------------
     */

    /**
     * Get a reference to the smarty template object that was passed in to the the action.
     * This method is only valid within a module action.
     *
     * @final
     * @since 2.0.1
     * @author calguy1000
     * @return mixed Smarty_Internal_Template | null
     */
    final public function GetActionTemplateObject() : Smarty_Internal_Template
    {
        if( $this->_action_tpl ) return $this->_action_tpl;
    }

    /**
     * Build a resource string for an old module templates resource.
     * If the template name provided ends with .tpl a module file template is assumed.
     *
     * @final
     * @since 1.11
     * @author calguy1000
     * @param string $tpl_name The template name.
     * @return string
     */
    final public function GetDatabaseResource(string $tpl_name) : string
    {
        if( endswith($tpl_name,'.tpl') ) return 'module_file_tpl:'.$this->GetName().';'.$tpl_name;
        return 'module_db_tpl:'.$this->GetName().';'.$tpl_name;
    }

    /**
     * Return the resource identifier of a module-specific template.
     * If the template specified ends in .tpl then a file template is assumed.
     * Otherwise a generic cms_template resource is returned.
     *
     * Note: Since 2.2.1 This function will throw a logic exception if a string or eval or extends resource is supplied.
     *
     * @since 2.0
     * @author calguy1000
     * @param string $tpl_name The template name.
     * @return string
     */
    final public function GetTemplateResource(string $tpl_name) : string
    {
        if( strpos($tpl_name,':') !== false ) {
            if( startswith($tpl_name,'string:') || startswith($tpl_name,'eval:') || startswith($tpl_name,'extends:') ) {
                throw new LogicException('Invalid smarty resource specified for a module template.');
            }
            return $tpl_name;
        }
        if( endswith($tpl_name,'.tpl') ) return 'module_file_tpl:'.$this->GetName().';'.$tpl_name;
        return 'cms_template:'.$tpl_name;
    }

    /**
     * Return the resource identifier of a module-specific file template.
     *
     * @final
     * @since 1.11
     * @author calguy1000
     * @param string $tpl_name The template name.
     * @return string
     */
    final public function GetFileResource(string $tpl_name) : string
    {
        return 'module_file_tpl:'.$this->GetName().';'.$tpl_name;
    }

    /**
     * List templates associated with a module
     *
     * @final
     * @param string $modulename Optional name. Default current-module's name.
     * @return array
     */
    final public function ListTemplates(string $modulename = '') : array
    {
        $this->_loadTemplateMethods();
        return call_user_func('CMSMS\\module_support\\'.__FUNCTION__,
            $this, $modulename);
    }

    /**
     * Return the content of a database-stored template.
     * This should be used for admin functions only, as it doesn't involve any smarty caching.
     *
     * @final
     * @param string $tpl_name    The template name.
     * @param string $modulename  Optional name. Default current-module's name.
     * @return mixed string|null
     */
    final public function GetTemplate(string $tpl_name, string $modulename = '')
    {
        $this->_loadTemplateMethods();
        return call_user_func('CMSMS\\module_support\\'.__FUNCTION__,
            $this, $tpl_name, $modulename);
    }

    /**
     * Return the content of the template that resides in  <Modulepath>/templates/{template_name}.tpl
     *
     * @final
     * @param string $tpl_name    The template name
     * @param string $modulename  Since 2.9 optional name. Default current-module's name.
     * @return array
     * @return mixed string|null
     */
    final public function GetTemplateFromFile(string $tpl_name, string $modulename = '')
    {
        $this->_loadTemplateMethods();
        return call_user_func('CMSMS\\module_support\\'.__FUNCTION__,
            $this, $tpl_name, $modulename);
    }

    /**
     * Store a template into the database and associate the template with a module.
     *
     * @final
     * @param string $tpl_name The template name
     * @param string $content The template content
     * @param string $modulename Optional module name. Default current-module's name.
     * @return bool (OR null ?)
     */
    final public function SetTemplate(string $tpl_name, string $content, string $modulename = '')
    {
        $this->_loadTemplateMethods();
        return call_user_func('CMSMS\\module_support\\'.__FUNCTION__,
            $this, $tpl_name, $content, $modulename);
    }

    /**
     * Delete a named module template, or all such templates, from the database
     *
     * @final
     * @param string $tpl_name Optional template name. If empty, all templates associated with the module are deleted.
     * @param string $modulename Optional module name. Default current-module's name.
     * @return bool
     */
    final public function DeleteTemplate(string $tpl_name = '', string $modulename = '') : bool
    {
        $this->_loadTemplateMethods();
        return call_user_func('CMSMS\\module_support\\'.__FUNCTION__,
            $this, $tpl_name, $modulename);
    }

    /**
     * Process a file template through smarty
     *
     * If called from within a module action, this method will use the action template object.
     * Otherwise, the global smarty object will be used.
     *
     * @final
     * @param string  $tpl_name    Template name
     * @param string  $designation Optional cache Designation (ignored)
     * @param bool    $cache       Optional cache flag  (ignored)
     * @param string  $cacheid     Optional unique cache flag (ignored)
     * @return mixed  string or null
     */
    final public function ProcessTemplate(string $tpl_name, string $designation = '', bool $cache = false, string $cacheid = '') : string
    {
        if( strpos($tpl_name, '..') !== false ) return '';
        $tpl = $this->_action_tpl;
        if( !$tpl ) {
            $tpl = AppSingle::Smarty();
        }
        return $tpl->fetch('module_file_tpl:'.$this->GetName().';'.$tpl_name);
    }

    /**
     * Process a smarty template string and return the result.
     * Note, there is no caching.
     *
     * @final
     * @param string $data Input template
     * @return string
     */
    final public function ProcessTemplateFromData(string $data) : string
    {
        return $this->_action_tpl->fetch('string:'.$data);
    }

    /**
     * Process a smarty template associated with a module through smarty and return the result
     *
     * @final
     * @param string $tpl_name Template name
     * @param string $designation (optional) Designation (ignored)
     * @param bool $cache (optional) Cacheable flag (ignored)
     * @param string $modulename (ignored)
     * @return mixed string|null
     */
    final public function ProcessTemplateFromDatabase(string $tpl_name, string $designation = '', bool $cache = false, string $modulename = '')
    {
        return $this->_action_tpl->fetch('module_db_tpl:'.$this->GetName().';'.$tpl_name);
    }

    /**
     * ------------------------------------------------------------------
     * Deprecated User Defined Tag Functions.
     * ------------------------------------------------------------------
     */

    /**
     * Return a list of User Defined Tags (however stored).
     * @final
     * @deprecated since 2.1 No sensible reason for this to be in the module-API
     *
     * @return array
     */
    final public function ListUserTags() : array
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\\SimpleTagOperations->ListSimpleTags()'));
        $ops = SimpleTagOperations::get_instance();
        return $ops->ListSimpleTags();
    }

    /**
     * Return the output generated by a named User Defined Tag.
     * @final
     * @deprecated since 2.1 No sensible reason for this to be in the module-aPI
     *
     * @param string $name   Name of the tag
     * @param mixed  $arguments Optional tag-callable parameters. Normally
     *  [$params[], $smarty]. Default []
     * @return mixed
     */
    final public function CallUserTag(string $name, $arguments = [])
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\\SimpleTagOperations->CallSimpleTag()'));
        $ops = SimpleTagOperations::get_instance();
        return $ops->CallSimpleTag($name, $arguments);
    }

    /**
     * ------------------------------------------------------------------
     * Tab Functions
     * ------------------------------------------------------------------
     */

    /**
     * Set the current tab for the action.
     *
     * Used for the various template forms, this method can be used to control
     * the tab that is displayed by default when redirecting to an admin action
     * that displays multiple tabs.
     *
     * @since 1.11
     * @author calguy1000
     * @param string $tab The tab name
     * @see CMSModule::RedirectToAdminTab()
     */
    public function SetCurrentTab($tab)
    {
        $tab = trim($tab);
        $_SESSION[$this->GetName().'::activetab'] = $tab;
        AdminTabs::set_current_tab($tab);
    }

    /**
     * Return page content representing the start of tab headers.
     * e.g. echo $this->StartTabHeaders();
     *
     * @final
     * @deprecated since 2.9. Instead use CMSMS\AdminTabs::start_tab_headers()
     * @param bool $auto Since 2.9 Whether to automatically generate
     *  continuity-related elements instead of explicit creation of those.
     *  Default true, or false for pre-2.0 behavior.
     * @return string
     */
    final public function StartTabHeaders(bool $auto = true) : string
    {
        return AdminTabs::start_tab_headers($auto);
    }

    /**
     * Return page content representing a specific tab header.
     * e.g.  echo $this->SetTabHeader('preferences',$this->Lang('preferences'));
     *
     * @deprecated since 2.9 Use CMSMS\AdminTabs::set_tab_header(). Not final
     * @param string $tabid The tab id
     * @param string $title The tab title
     * @param bool $active Optional flag indicating whether this tab is active. Default false
     * @return string
     */
    public function SetTabHeader($tabid, $title, $active = false)
    {
        return AdminTabs::set_tab_header($tabid,$title,$active);
    }

    /**
     * Return page content representing the end of tab headers.
     *
     * @final
     * @deprecated since 2.9 Use CMSMS\AdminTabs::end_tab_headers()
     * @param bool $auto Since 2.9 Whether to automatically generate
     *  continuity-related elements instead of explicit creation of those.
     *  Default true, or false for pre-2.0 behavior.
     * @return string
     */
    final public function EndTabHeaders(bool $auto = true) : string
    {
        return AdminTabs::end_tab_headers($auto);
    }

    /**
     * Return page content representing the start of XHTML areas for tabs.
     *
     * @final
     * @deprecated since 2.9 Use CMSMS\AdminTabs::start_tab_content()
     * @param bool $auto Since 2.9 Whether to automatically generate
     *  continuity-related elements instead of explicit creation of those.
     *  Default true, or false for pre-2.0 behavior.
     * @return string
     */
    final public function StartTabContent(bool $auto = true) : string
    {
        return AdminTabs::start_tab_content($auto);
    }

    /**
     * Return page content representing the end of XHTML areas for tabs.
     *
     * @final
     * @deprecated since 2.9 Use CMSMS\AdminTabs::end_tab_content()
     * @param bool $auto Since 2.9 Whether to automatically generate
     *  continuity-related elements instead of explicit creation of those.
     *  Default true, or false for pre-2.0 behavior.
     * @return string
     */
    final public function EndTabContent(bool $auto = true) : string
    {
        return AdminTabs::end_tab_content($auto);
    }

    /**
     * Return page content representing the start of a specific tab
     *
     * @final
     * @deprecated since 2.9 Use CMSMS\AdminTabs::start_tab()
     * @param string $tabid the tab id
     * @param arrray $params Parameters
     * @param bool $auto Since 2.9 Whether to automatically generate
     *  continuity-related elements instead of explicit creation of those.
     *  Default true, or false for pre-2.0 behavior.
     * @see CMSModule::SetTabHeader()
     * @return string
     */
    final public function StartTab(string $tabid, array $params = [], bool $auto = true) : string
    {
        return AdminTabs::start_tab($tabid,$params,$auto);
    }

    /**
     * Return page content representing the end of a specific tab.
     *
     * @final
     * @deprecated since 2.9 Use CMSMS\AdminTabs::end_tab()
     * @param bool $auto Since 2.9 Whether to automatically generate
     *  continuity-related elements instead of explicit creation of those.
     *  Default true, or false for pre-2.0 behavior.
     * @return string
     */
    final public function EndTab(bool $auto = true) : string
    {
        return AdminTabs::end_tab($auto);
    }

    /**
     * ------------------------------------------------------------------
     * Other Functions
     * ------------------------------------------------------------------
     */

    /**
     * Called in the admin theme for every installed module, this method allows
     * the module to output style information for use in the admin theme.
     *
     * @abstract
     * @returns string css text - verbatim content, NOT external-link(s).
     */
    public function AdminStyle()
    {
        return '';
    }

    /**
     * Set the content-type header.
     *
     * @abstract
     * @param string $contenttype Value to set the content-type header too
     */
    public function SetContentType($contenttype)
    {
        AppSingle::App()->set_content_type($contenttype);
    }

    /**
     * Put an event into the audit (admin) log. For consistency, this
     * should be done during most admin events.
     *
     * @final
     * @param mixed  $itemid   useful for working on a specific record (i.e. article or user), but often '' or 0
     * @param string $itemname item name
     * @param mixed  $detail   optional (since 2.9) extra information e.g. action name
     */
    final public function Audit($itemid, string $itemname, string $detail = '')
    {
        audit($itemid, $itemname, $detail);
    }

    /**
     * Append $str to the accumulated 'information' strings to be displayed
     * in a theme-specific dialog during the next request e.g. after redirection
     * For admin-side use only
     *
     * @since 2.9
     * @author Robert Campbell
     * @param mixed $str string|string[] Information message(s).
     */
    public function SetInfo($str)
    {
        $themeObject = Utils::get_theme_object();
        if( is_object($themeObject) ) $themeObject->RecordNotice('info', $str, '', true);
    }

    /**
     * Append $str to the accumulated 'success' strings to be displayed
     * in a theme-specific dialog during the next request e.g. after
     * redirection
     * For admin-side use only
     *
     * @since 1.11
     * @author Robert Campbell
     * @param mixed $str string|string[] Success message(s)
     */
    public function SetMessage($str)
    {
        $themeObject = Utils::get_theme_object();
        if( is_object($themeObject) ) $themeObject->RecordNotice('success', $str, '', true);
    }

    /**
     * Append $str to the accumulated warning strings to be displayed in
     * a theme-specific dialog during the next request e.g. after
     * redirection
     * For admin-side use only
     *
     * @since 2.9
     * @author Robert Campbell
     * @param mixed $str string|string[] Warning message(s)
     */
    public function SetWarning($str)
    {
        $themeObject = Utils::get_theme_object();
        if( is_object($themeObject) ) $themeObject->RecordNotice('warn', $str, '', true);
    }

    /**
     * Append $str to the accumulated error strings to be displayed in
     * a theme-specific error dialog during the next request e.g. after
     * redirection
     * For admin-side use only
     *
     * @since 1.11
     * @author Robert Campbell
     * @param mixed $str string|string[] Error message(s)
     */
    public function SetError($str)
    {
        $themeObject = Utils::get_theme_object();
        if( is_object($themeObject) ) $themeObject->RecordNotice('error', $str, '', true);
    }

    /**
     * Append $message to the accumulated 'information' strings to be displayed
     * in a theme-specific popup dialog during the current request
     * For admin-side use only
     *
     * @since 2.9
     * @param mixed $message string|string[] Information message(s)
     * @return empty string (something might like to echo)
     */
    public function ShowInfo($message)
    {

        if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) {
            $themeObject = Utils::get_theme_object();
            if( is_object($themeObject) ) $themeObject->RecordNotice('info', $message);
        }
        return '';
    }

    /**
     * Append $message to the accumulated 'success' strings to be displayed in a
     * theme-specific popup dialog during the current request
     * For admin-side use only
     *
     * @param mixed $message string|string[] Message(s)
     * @return empty string (something might like to echo)
     */
    public function ShowMessage($message)
    {

        if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) {
            $themeObject = Utils::get_theme_object();
            if( is_object($themeObject) ) $themeObject->RecordNotice('success', $message);
        }
        return '';
    }

    /**
     * Append $message to the accumulated 'warning' strings to be displayed in a
     * theme-specific popup dialog during the current request
     * For admin-side use only
     *
     * @since 2.9
     * @param mixed $message string|string[] Warning message(s)
     * @return empty string (something might like to echo)
     */
    public function ShowWarning($message)
    {

        if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) {
            $themeObject = Utils::get_theme_object();
            if( is_object($themeObject) ) $themeObject->RecordNotice('warn', $message);
        }
        return '';
    }

    /**
     * Append $message to the accumulated error-strings to be displayed in a
     * theme-specific error dialog during the current request
     * For admin-side use only
     *
     * @since 2.9 not final
     * @param mixed $message string|string[] Error message(s)
     * @return empty string (something might like to echo)
     */
    public function ShowErrors($message)
    {

        if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) {
            $themeObject = Utils::get_theme_object();
            if (is_object($themeObject)) $themeObject->RecordNotice('error', $message);
        }
        return '';
    }

    /**
     * ------------------------------------------------------------------
     * Permission Functions
     * ------------------------------------------------------------------
     */

    /**
     * Creates a new permission for use by the module.
     *
     * @final
     * @param string $permission_name Name of the permission to create
     * @param string $permission_text Optional description of the permission
     */
    final public function CreatePermission(string $permission_name, string $permission_text = '')
    {
        try {
            if( !$permission_text ) $permission_text = $permission_name;
            $perm = new Permission();
            $perm->source = $this->GetName();
            $perm->name = $permission_name;
            $perm->text = $permission_text;
            $perm->save();
        }
        catch( Exception $e ) {
            // ignored.
        }
    }

    /**
     * Checks a permission against the currently logged in user.
     *
     * @final
     * @param varargs $perms Since 2.9 The name(s) of the permission(s) to check
     *  against the current user
     *  A permission-name string or array of such string(s), and if the latter,
     *   optionally a following true-valued argument to cause the array members
     *   to be AND'd instead of OR'd
     * @return bool
     */
    final public function CheckPermission(...$perms) : bool
    {
        $userid = get_userid(false);
        if ($userid != null) {
            return check_permission($userid, ...$perms);
        }
        //session expired
        $config = AppSingle::Config();
        redirect($config['admin_url'].'/login.php');
    }

    /**
     * Removes a permission from the system.  If recreated, the
     * permission would have to be set to all groups again.
     *
     * @final
     * @param string $permission_name The name of the permission to remove
     */
    final public function RemovePermission(string $permission_name)
    {
        try {
            $perm = Permission::load($permission_name);
            $perm->delete();
        }
        catch( Exception $e ) {
            // ignored.
        }
    }

    /**
     * ------------------------------------------------------------------
     * Preference Functions
     * ------------------------------------------------------------------
     */

    /**
     * Returns a module preference if it exists, or else the specified default value.
     *
     * @final
     * @param string $preference_name The name of the preference to check
     *   Since 2.9 $preference_name may be '', to get all recorded preferences for the module
     * @param mixed  $defaultvalue    Optional default value (single | array). Default ''.
     * @return mixed value | array
     */
    final public function GetPreference(string $preference_name = '', $defaultvalue='')
    {
        $pref = $this->GetName().AppParams::NAMESPACER;
        if ($preference_name) {
            return AppParams::getraw($pref.$preference_name, $defaultvalue);
        }
        $params = AppParams::getraw($pref, '', true);
        if ($params) {
            $keys = array_keys($params);
            array_walk($keys, function(&$value, $indx, $skip) {
                $value = substr($value, $skip);
            }, strlen($pref));
            return array_combine($keys, array_values($params));
        }
        return [];
    }

    /**
     * Sets a module preference.
     *
     * @final
     * @param string $preference_name The name of the preference to set
     * @param mixed $value string|null The value to set it to
     */
    final public function SetPreference(string $preference_name, $value)
    {
        return AppParams::set($this->GetName().AppParams::NAMESPACER.$preference_name, $value);
    }

    /**
     * Removes a module preference, or if no preference name is specified,
     * removes all module preferences.
     *
     * @final
     * @param string $preference_name Optional name of the preference to remove.  If empty, all preferences associated with the module are removed.
     * @param bool $like since 2.9 Optional flag indicating wildcard removal. Default false.
     */
    final public function RemovePreference(string $preference_name = '', bool $like = false)
    {
        $prefix = $this->GetName().AppParams::NAMESPACER;
        $args = ( $preference_name ) ?
          [$prefix.$preference_name, $like] : [$prefix, true];
        AppParams::remove(...$args);
    }

    /**
     * List all preferences for a specific module by prefix.
     *
     * @final
     * @param string $prefix
     * @return mixed An array of preference names, or null.
     * @since 2.0
     */
    final public function ListPreferencesByPrefix(string $prefix)
    {
        if( !$prefix ) return;
        $prefix = $this->GetName().AppParams::NAMESPACER.$prefix;
        $tmp = AppParams::list_by_prefix($prefix);
        if( $tmp ) {
            for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
                if( !startswith($tmp[$i],$prefix) ) {
                    throw new CmsInvalidDataException(self::class.'::'.__METHOD__.' invalid prefix for preference');
                }
                $tmp[$i] = substr($tmp[$i],strlen($prefix));
            }
            return $tmp;
        }
    }

    /**
     * ------------------------------------------------------------------
     * Event Handler Related Functions
     * ------------------------------------------------------------------
     */

    /**
     * From version 2.2 onwards, CMSMS also has another notification mechanism
     * which can be used instead of Events. Known as a 'Hook'.
     *
     * As in the case of events, it is possible to register(listen) for, and
     * un-register from, named 'reportables'. Registered handers (PHP callbacks)
     * will be called with information about whatever happened. Hook data are
     * less-durable, stored in cache instead of the database.
     *
     * @see HookOperations
     */

    /**
     * Add an event handler for an existing eg event.
     *
     * @final
     * @param string $realm      The name of the module sending the event, or 'Core'
     * @param string $eventname  The name of the event
     * @param bool $removable    Whether this event can be removed from the list
     * @returns mixed bool or nothing ??
     */
    final public function AddEventHandler(string $realm, string $eventname, bool $removable = true)
    {
//       return Events::AddEventHandler( $realm, $eventname, false, $this->GetName(), $removable );
        return Events::AddStaticHandler($realm, $eventname, [$this->GetName(),false], 'M', $removable);
    }

    /**
     * Inform the system about a new event that can be generated
     *
     * @final
     * @param string $eventname The name of the event
     */
    final public function CreateEvent(string $eventname)
    {
        Events::CreateEvent($this->GetName(), $eventname);
    }

    /**
     * An event that this module is listening to has occurred, and should be handled.
     * This method must be over-ridden if this module is capable of handling events
     * of any type.
     *
     * The default behavior of this method is to check for a file named
     *  event.<originator>.<eventname>.php
     * in the module directory, and if such file exists it, include it to handle
     * the event. Variables $gCms, $db, $config and (global) $smarty are in-scope
     * for the inclusion.
     *
     * @abstract
     * @param string $originator The name of the originating module, or 'Core'
     * @param string $eventname The name of the event
     * @param array  $params Parameters to be provided with the event.
     * @return bool
     */
    public function DoEvent($originator, $eventname, &$params)
    {
        if ($originator && $eventname) {
            $filename = $this->GetModulePath().'/event.' . $originator . '.' . $eventname . '.php';

            if (@is_file($filename)) {
                $gCms = AppSingle::App();
                $db = AppSingle::Db();
                $config = AppSingle::Config();
                $smarty = AppSingle::Smarty();
                include $filename;
            }
        }
    }

    /**
     * Get a (translated) description of an event this module created.
     * This method must be over-ridden if this module created any events.
     *
     * @abstract
     * @param string $eventname The name of the event
     * @return string
     */
    public function GetEventDescription($eventname)
    {
        return '';
    }


    /**
     * Get a (langified) description of the details about when an event is
     * created, and the parameters that are delivered with it.
     * This method must be over-ridden if this module created any events.
     *
     * @abstract
     * @param string $eventname The name of the event
     * @return string
     */
    public function GetEventHelp($eventname)
    {
        return '';
    }

    /**
     * A callback indicating if this module has a DoEvent method to
     * handle incoming events.
     *
     * @abstract
     * @return bool
     */
    public function HandlesEvents()
    {
        return false;
    }

    /**
     * Remove an event and all its handlers from the CMS system
     *
     * Note, only events created by this module can be removed.
     *
     * @final
     * @param string $eventname The name of the event
     */
    final public function RemoveEvent(string $eventname)
    {
        Events::RemoveEvent($this->GetName(), $eventname);
    }

    /**
     * Remove an event handler from the CMS system
     * This function removes all handlers to the event, and completely removes
     * all references to this event from the database
     *
     * Note, only events created by this module can be removed.
     *
     * @final
     * @param string $modulename The module name (or Core)
     * @param string $eventname  The name of the event
     */
    final public function RemoveEventHandler(string $modulename, string $eventname)
    {
        Events::RemoveEventHandler($modulename, $eventname, false, $this->GetName());
    }

    /**
     * Trigger an event.
     * This function will call all registered event handlers for the event
     *
     * @final
     * @param string $eventname The name of the event
     * @param array  $params The parameters associated with this event.
     */
    final public function SendEvent(string $eventname, array $params)
    {
        Events::SendEvent($this->GetName(), $eventname, $params);
    }

} // class

/**
 * Indicates that the incoming parameter is expected to be an integer.
 * This is used when cleaning input parameters for a module action or module call.
 */
const CLEAN_INT = 'CLEAN_INT';

/**
 * Indicates that the incoming parameter is expected to be a float
 * This is used when cleaning input parameters for a module action or module call.
 */
const CLEAN_FLOAT = 'CLEAN_FLOAT';

/**
 * Indicates that the incoming parameter is not to be cleaned.
 * This is used when cleaning input parameters for a module action or module call.
 */
const CLEAN_NONE = 'CLEAN_NONE';

/**
 * Indicates that the incoming parameter is a string.
 * This is used when cleaning input parameters for a module action or module call.
 */
const CLEAN_STRING = 'CLEAN_STRING';

/**
 * Indicates that the incoming parameter is a regular expression.
 * This is used when cleaning input parameters for a module action or module call.
 */
const CLEAN_REGEXP = 'regexp:';

/**
 * Indicates that the incoming parameter is an uploaded file.
 * This is used when cleaning input parameters for a module action or module call.
 */
const CLEAN_FILE = 'CLEAN_FILE';

/**
 * @ignore
 */
const CLEANED_FILENAME = 'BAD_FILE';
