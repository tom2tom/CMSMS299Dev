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
namespace CMSMS;

use CMSMS\AdminUtils;
use CMSMS\AppConfig;
use CMSMS\AppState;
use CMSMS\BookmarkOperations;
use CMSMS\ContentOperations;
use CMSMS\ContentTree;
use CMSMS\contenttypes\ErrorPage;
use CMSMS\Database\Connection;
use CMSMS\DeprecationNotice;
use CMSMS\GroupOperations;
use CMSMS\internal\Smarty;
use CMSMS\ModuleOperations;
use CMSMS\SingleItem;
use CMSMS\UserOperations;
use CMSMS\UserTagOperations;
use RuntimeException;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use function CMSMS\add_debug_message;
use function CMSMS\get_debug_messages;
use function CMSMS\get_installed_schema_version;

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
            return SingleItem::get('app.'.$key);
        }
    }

    /**
     * @ignore
     */
    public function __set(string $key, $value)
    {
        SingleItem::set('app.'.$key, $value);
    }

    /**
     * Convenience method to get the singleton instance of another class.
     * Normally, get it directly, via a SingleItem::whatever() call.
     *
     * @ignore
     * @throws RuntimeException if $name is not recognized
     */
    public function __call(string $name, array $args)
    {
        return SingleItem::$name(...$args);
    }

    /**
     * Get the singleton instance of this class.
     * This method is used during request-setup, when caching via the
     * SingleItem class might not yet be possible. Later, use
     * CMSMS\SingleItem::App() instead of this method, to get the
     * (same) singleton.
     * @since 1.10
     *
     * @return self
     */
    public static function get_instance() : self
    {
        if( !self::$_instance ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Get the installed-schema version.
     * @since 2.0
     * @deprecated since 2.99 instead use CMSMS\get_installed_schema_version()
     *
     * @return int maybe 0
     */
    public function get_installed_schema_version() : int
    {
        return get_installed_schema_version();
    }

    /**
     * Get the dump-messages.
     * @since 1.9
     * @deprecated since 2.99 instead use CMSMS\get_debug_messages()
     *
     * @return array
     */
    public function get_errors() : array
    {
        return get_debug_messages();
    }

    /**
     * Add a dump-message.
     * @since 1.9
     * @deprecated since 2.99 instead use CMSMS\add_debug_message()
     *
     * @param string $str The error message
     */
    public function add_error(string $str)
    {
        add_debug_message($str);
    }

    /**
     * Get the request content type (for frontend requests).
     *
     * If no content type is explicitly set, text/html is assumed.
     *
     * @since 2.0
     * @return string
     */
    public function get_content_type() : string
    {
        $val = (string)SingleItem::get('app.content_type');
        return ($val) ? $val : 'text/html';
    }

    /**
     * Set the request content type (a mime type).
     *
     * @param string $mime_type
     * @since 2.0
     */
    public function set_content_type(string $mime_type = '')
    {
        SingleItem::set('app.content_type', $mime_type);
    }

    /**
     * Set the current-page content object, if not already done.
     *
     * @since 2.0
     * @internal
     * @ignore
     * @param mixed $content one of the content classes, CMSMS or ContentManager namespace
     */
    public function set_content_object($content)
    {
        if( !SingleItem::get('app.current_content') || $content instanceof ErrorPage ) {
            SingleItem::set('app.current_content', $content);
        }
    }

    /**
     * Get the current-page content object.
     *
     * @since 2.0
     * @return mixed content-object (CMSMS or ContentManager namespace) or null
     */
    public function get_content_object()
    {
        return SingleItem::get('app.current_content');
    }

    /**
     * Get the ID of the current page.
     *
     * @since 2.0
     * @return int id, possibly 0
     */
    public function get_content_id()
    {
        $obj = SingleItem::get('app.current_content');
        return ( is_object($obj) )  ? $obj->Id() : 0;
    }

    /**
     * Get the module-operations instance.
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
     * Get the names of installed and available modules.
     *
     * This method will return an array of module names that are installed, loaded and ready for use.
     * Suitable for iteration with GetModuleInstance
     *
     * @see App::GetModuleInstance()
     * @since 1.9
     * @return array module-name(s)
     */
    public function GetAvailableModules()
    {
        return SingleItem::ModuleOperations()->get_available_modules();
    }

    /**
     * Get an installed-module instance.
     *
     * This method will return an instance of the specified module class, if it
     * is installed and available. Optionally, a check can be performed to test
     * whether the version of the requested module matches the one specified.
     *
     * @since 1.9
     * @deprecated since 2.99 instead use Utils::get_module() or ModuleOperations::get_module_instance()
     *
     * @param string $modname The module name
     * @param mixed  $version (optional) string|float version number for a check. Default ''
     * @return mixed CMSModule sub-class object | null
     */
    public function GetModuleInstance(string $modname,$version = '')
    {
        return SingleItem::ModuleOperations()->get_module_instance($modname,$version);
    }

    /**
     * Record the database connection object.
     * @deprecated since 2.99 Does nothing
     * @internal
     *
     * @param Connection $conn UNUSED
     */
    public function _setDb(Connection $conn)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice(__METHOD__.' does nothing', ''));
    }

    /**
     * Get the database-connection instance.
     * @deprecated since 2.99 Instead, use SingleItem::Db()
     *
     * @return Connection object
     */
    public function GetDb() : Connection
    {
        return SingleItem::Db();
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
     * Get the CMS config instance.
     * That object contains global paths and settings that do not belong
     * in the database.
     * @see AppConfig
     *
     * @return AppConfig The configuration object
     */
    public function GetConfig() : AppConfig
    {
        return SingleItem::Config();
    }

    /**
     * Get the user-operations instance.
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
     * Get the content-operations instance.
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
     * Get a bookmark-operations object.
     * @deprecated since 2.99 instead use new CMSMS\BookmarkOperations()
     * @see BookmarkOperations
     *
     * @return BookmarkOperations
     */
    public function GetBookmarkOperations() : BookmarkOperations
    {
        return new BookmarkOperations();
    }

    /**
     * Get the group-operations instance.
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
     * Get the user-plugin-operations instance, for interacting with UDT's
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
     * Get the Smarty instance, except during install/upgrade/refresh.
     * @see Smarty
     * @see SingleItem::Smarty()
     * @link http://www.smarty.net/manual/en/
     *
     * @return mixed CMSMS\internal\Smarty object | null
     */
    public function GetSmarty() : Smarty
    {
        if( !AppState::test(AppState::INSTALL) ) {
            // we don't load the main Smarty class during installation
            return SingleItem::Smarty();
        }
    }

    /**
     * Get the cached pages-hierarchy-manager instance.
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
     * Does nothing. Formerly, get the template-parser instance.
     * @deprecated since 2.99 use new page_template_parser()
     * @see CMSMS\internal\page_template_parser class
     */
    public function get_template_parser()
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\internal\page_template_parser'));
    }

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

    /**
     * Report whether the current request was executed via the CLI.
     * @since 2.2.10
     * @deprecated since 2.99 CLI operation is not supported
     *
     * @return bool false always
     */
    public function is_cli() : bool
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice(__METHOD__.' always returns false', ''));
        return false;
    }

    /**
     * Report whether the current request is a frontend request.
     * @since 1.11.2
     *
     * @return bool
     */
    public function is_frontend_request() : bool
    {
        return AppState::test(AppState::FRONT_PAGE);
    }

    /** Report whether the current request was over HTTPS.
     * @since 1.11.12
     *
     * @return bool
     */
    public function is_https_request() : bool
    {
        return !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off';
    }
} // class
