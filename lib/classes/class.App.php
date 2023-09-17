<?php
/*
Methods and aliases for accessing intra-request system properties and classes
Copyright (C) 2010-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
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
use CMSMS\contenttypes\ErrorPage;
use CMSMS\Database\Connection;
use CMSMS\DeprecationNotice;
use CMSMS\GroupOperations;
use CMSMS\internal\Smarty;
use CMSMS\internal\page_template_parser;
use CMSMS\Lone;
use CMSMS\ModuleOperations;
use CMSMS\PageTreeOperations;
use CMSMS\UserOperations;
use CMSMS\UserTagOperations;
use RuntimeException;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use function CMSMS\add_debug_message;
use function CMSMS\get_debug_messages;
use function CMSMS\get_installed_schema_version;
use function CMSMS\is_frontend_request;
use function CMSMS\is_secure_request;

/**
 * Class of simple methods and aliases for accessing intra-request
 * system properties and classes.
 * Many of the methods are deprecated from CMSMS 3.0
 *
 * @final
 * @package CMS
 * @license GPL
 * @since 3.0
 * @since 0.5 as global-namespace CmsApp etc
 */
final class App
{
    /**
     * A bitflag constant indicating that the request is for a page in the CMSMS admin console
     * @deprecated since 3.0 use AppState::ADMIN_PAGE
     */
    const STATE_ADMIN_PAGE = AppState::ADMIN_PAGE;

    /**
     * A bitflag constant indicating that the request is for an admin login
     * @deprecated since 3.0 use AppState::LOGIN_PAGE
     */
    const STATE_LOGIN_PAGE = AppState::LOGIN_PAGE;

//  const STATE_ASYNC_JOB = AppState::ASYNC_JOB; from 3.0

    /**
     * A bitflag constant indicating that the request is taking place during the installation process
     * @deprecated since 3.0 use AppState::INSTALL
     */
    const STATE_INSTALL = AppState::INSTALL;

    /**
     * A bitflag constant indicating that the request is for a stylesheet
     * @deprecated since 3.0 use AppState::STYLESHEET
     */
    const STATE_STYLESHEET = AppState::STYLESHEET;

    /**
     * A bitflag constant indicating that we are currently parsing page templates
     * @deprecated since 3.0 use AppState::PARSE_TEMPLATE
     */
    const STATE_PARSE_TEMPLATE = AppState::PARSE_TEMPLATE;

    /**
     * @ignore
     */
    private function __clone(): void {}

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __get(string $key)//: mixed
    {
        switch($key) {
        case 'config':
            return Lone::get('Config');
        case 'get':
        case 'instance':
            return $this; //self::get_instance();
        default:
            return Lone::fastget('app.'.$key);
        }
    }

    /**
     * @ignore
     */
    public function __set(string $key, $value): void
    {
        Lone::set('app.'.$key, $value);
    }

    /**
     * Convenience method to get the singleton instance of another class.
     * Normally, get it directly, via a Lone::get('whatever') call.
     *
     * @ignore
     * @throws RuntimeException if $name is not recognized
     */
    #[\ReturnTypeWillChange]
    public function __call(string $name, array $args)//: mixed
    {
        return Lone::get($name, ...$args);
    }

    /**
     * Get an instance of this class.
     * This method is used during request-setup, when caching via the
     * Lone class might not yet be possible. Later, use
     * CMSMS\Lone::get('App') instead of this method, to get the
     * (same) singleton.
     * @since 1.10
     * @deprecated since 3.0 instead use CMSMS\Lone::get('App')
     *
     * @return self
     */
    public static function get_instance(): self
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\Lone::get(\'App\')'));
        return Lone::get('App');
    }

    /**
     * Get the installed-schema version.
     * @since 2.0
     * @deprecated since 3.0 instead use CMSMS\get_installed_schema_version()
     *
     * @return int maybe 0
     */
    public function get_installed_schema_version(): int
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('function', 'CMSMS\get_installed_schema_version'));
        return get_installed_schema_version();
    }

    /**
     * Get the dump-messages.
     * @since 1.9
     * @deprecated since 3.0 instead use CMSMS\get_debug_messages()
     *
     * @return array
     */
    public function get_errors(): array
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('function', 'CMSMS\get_debug_messages'));
        return get_debug_messages();
    }

    /**
     * Add a dump-message.
     * @since 1.9
     * @deprecated since 3.0 instead use CMSMS\add_debug_message()
     *
     * @param string $str The error message
     */
    public function add_error(string $str)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('function', 'CMSMS\add_debug_message'));
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
    public function get_content_type(): string
    {
        $val = (string)Lone::fastget('app.content_type');
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
        Lone::set('app.content_type', $mime_type);
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
        if( !Lone::fastget('app.current_content') || $content instanceof ErrorPage ) {
            Lone::set('app.current_content', $content);
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
        return Lone::fastget('app.current_content');
    }

    /**
     * Get the ID of the current page.
     *
     * @since 2.0
     * @return int id, possibly 0
     */
    public function get_content_id()
    {
        $obj = Lone::fastget('app.current_content');
        return ( is_object($obj) ) ? $obj->Id() : 0;
    }

    /**
     * Get the module-operations instance.
     * @see ModuleOperations
     * @since 3.0 CMSMS\Lone::get('ModuleOperations') may be used instead
     *
     * @return ModuleOperations handle to the ModuleOperations object
     */
    public function GetModuleOperations(): ModuleOperations
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'ModuleOperations\')'));
        return Lone::get('ModuleOperations');
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
        return Lone::get('ModuleOperations')->get_available_modules();
    }

    /**
     * Get an installed-module instance.
     *
     * This method will return an instance of the specified module class, if it
     * is installed and available. Optionally, a check can be performed to test
     * whether the version of the requested module matches the one specified.
     *
     * @since 1.9
     * @deprecated since 3.0 instead use CMSMS\Utils::get_module() or
     *  CMSMS\ModuleOperations::get_module_instance()
     *
     * @param string $modname The module name
     * @param mixed  $version (optional) string|float version number for a check. Default ''
     * @return mixed CMSModule sub-class object | null
     */
    public function GetModuleInstance(string $modname,$version = '')
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Utils::get_module'));
        return Lone::get('ModuleOperations')->get_module_instance($modname,$version);
    }

    /**
     * Record the database connection object.
     * @deprecated since 3.0 Does nothing
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
     * @deprecated since 3.0 Instead, use CMSMS\Lone::get('Db')
     *
     * @return Connection object
     */
    public function GetDb(): Connection
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'Db\')'));
        return Lone::get('Db');
    }

    /**
     * Get the database prefix.
     * @deprecated since 3.0 Instead, use constant CMS_DB_PREFIX
     *
     * @return string
     */
    public function GetDbPrefix(): string
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('constant', 'CMS_DB_PREFIX'));
        return CMS_DB_PREFIX;
    }

    /**
     * Get the CMS config instance.
     * That object contains global paths and settings that do not belong
     * in the database.
     * @deprecated since 3.0 Instead, use CMSMS\Lone::get('Config')
     * @see AppConfig
     *
     * @return AppConfig The configuration object
     */
    public function GetConfig(): AppConfig
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'Config\')'));
        return Lone::get('Config');
    }

    /**
     * Get the user-operations instance.
     * @see UserOperations
     * @since 3.0 CMSMS\Lone::get('UserOperations') may be used instead
     *
     * @return UserOperations handle to the UserOperations object
     */
    public function GetUserOperations(): UserOperations
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'UserOperations\')'));
        return Lone::get('');
    }

    /**
     * Get the content-operations instance.
     * @see ContentOperations
     * @since 3.0 CMSMS\Lone::get('ContentOperations') may be used instead
     *
     * @return ContentOperations handle to the ContentOperations object
     */
    public function GetContentOperations(): ContentOperations
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'ContentOperations\')'));
        return Lone::get('ContentOperations');
    }

    /**
     * Get a bookmark-operations object.
     * @deprecated since 3.0 instead use new CMSMS\BookmarkOperations()
     * @see BookmarkOperations
     *
     * @return BookmarkOperations
     */
    public function GetBookmarkOperations(): BookmarkOperations
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\BookmarkOperations'));
        return new BookmarkOperations();
    }

    /**
     * Get the group-operations instance.
     * @see GroupOperations
     * @since 3.0 CMSMS\Lone::get('GroupOperations') may be used instead
     *
     * @return GroupOperations handle to the GroupOperations instance
     */
    public function GetGroupOperations(): GroupOperations
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'GroupOperations\')'));
        return Lone::get('GroupOperations');
    }

    /**
     * Get the user-plugin-operations instance, for interacting with UDT's
     * @see UserTagOperations
     * @since 3.0 CMSMS\Lone::get('UserTagOperations') may be used instead
     *
     * @return the UserTagOperations singleton
     */
    public function GetUserTagOperations(): UserTagOperations
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'UserTagOperations\')'));
        return Lone::get('UserTagOperations');
    }

    /**
     * Get the Smarty instance, except during install/upgrade/refresh.
     * @see Smarty
     * @see Lone::get('Smarty')
     * @link http://www.smarty.net/manual/en/
     *
     * @return mixed CMSMS\internal\Smarty object | null
     */
    public function GetSmarty(): Smarty
    {
        if( !AppState::test(AppState::INSTALL) ) {
            // we don't load the main Smarty class during installation
            return Lone::get('Smarty');
        }
    }

    /**
     * Get the pages-hierarchy manager.
     * @see also PageTreeNode, one of which is used for each tree-node
     *
     * @return PageTreeOperations singelton
     */
    public function GetHierarchyManager(): PageTreeOperations
    {
        return Lone::get('PageTreeOperations');
    }

    /**
     * Get a template-parser object.
     * @deprecated since 3.0 use new page_template_parser()
     * @see CMSMS\internal\page_template_parser class
     */
    public function get_template_parser()
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\internal\page_template_parser'));
        return new page_template_parser();
    }

    /**
     * Remove files from the website file-cache directories.
     * @deprecated since 3.0 Instead use AdminUtils::clear_cached_files()
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
     * @deprecated since 3.0 instead use CMSMS\AppState::get()
     * @return array  State constants (int's)
     */
    public function get_states(): array
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\AppState::get'));
        return AppState::get();
    }

    /**
     * Report whether the specified state matches the current application state.
     * @since 1.11.2
     * @deprecated since 3.0 instead use CMSMS\AppState::test()
     *
     * @param mixed $state int | deprecated string State identifier, a class constant
     * @return bool
     * @throws UnexpectedValueException if invalid identifier is provided
     */
    public function test_state($state): bool
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
     * @deprecated since 3.0 instead use CMSMS\AppState::add()
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
     * @deprecated since 3.0 instead use CMSMS\AppState::remove()
     *
     * @param mixed $state int | deprecated string The state, a class constant
     * @return bool indicating success
     * @throws UnexpectedValueException if an invalid state is provided.
     */
    public function remove_state($state): bool
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\AppState::remove'));
        AppState::remove($state);
    }

    /**
     * Report whether the current request was executed via the CLI.
     * @since 2.2.10
     * @deprecated since 3.0 CLI operation is not supported
     *
     * @return bool false always
     */
    public function is_cli(): bool
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice(__METHOD__.' always returns false', ''));
        return false;
    }

    /**
     * Report whether the current request is a frontend request.
     * @since 1.11.2
     * @deprecated since 3.0 instead use CMSMS\is_frontend_request()
     *
     * @return bool
     */
    public function is_frontend_request(): bool
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('function', 'CMSMS\is_frontend_request'));
        return is_frontend_request();
    }

    /**
     * Report whether the current request is over HTTPS.
     * @since 1.11.12
     * @deprecated since 3.0 instead use CMSMS\is_secure_request()
     *
     * @return bool
     */
    public function is_https_request(): bool
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('function', 'CMSMS\is_secure_request'));
        return is_secure_request();
    }
} // class

if (!\class_exists('CmsApp', false)) \class_alias(App::class, 'CmsApp', false);
