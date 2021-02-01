<?php
/*
Backward compatibility support
Copyright (C) 2011-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

/**
 * Miscellaneous support functions
 *
 * @package CMS
 * @license GPL
 */
if( !function_exists('gzopen') ) {
    /**
     * Wrapper for gzopen in case it does not exist.
     * Some installs of PHP (after PHP 5.3) use a different zlib library, and therefore gzopen is not defined.
     * This method works around that.
     *
     * @since 2.0
     * @ignore
     */
    function gzopen( $filename , $mode , $use_include_path = 0 ) {
        return gzopen64($filename, $mode, $use_include_path);
    }
}

/**
 * Return the currently configured database prefix.
 * @deprecated since 2.99 Use constant CMS_DB_PREFIX instead
 *
 * @since 0.4
 * @return string
 */
function cms_db_prefix() : string
{
    return CMS_DB_PREFIX;
}

// pre-define aliases which might be used in a type-hint/declaration
// since 2.99
// FWIW lines marked with !! are typehints seen in a nothing-special 2.2.3 distro ..
class_alias('CMSMS\AppConfig', 'cms_config', false); // !!
class_alias('CMSMS\CacheDriver', 'cms_cache_driver', false); // !!
class_alias('CMSMS\ContentTree', 'cms_content_tree', false); // !!
class_alias('CMSMS\ContentTypeOperations', 'CmsContentTypePlaceHolder', false); // !!
class_alias('CMSMS\FileSystemControls', 'CMSMS\FilePickerProfile', false); // !!
class_alias('CMSMS\internal\AdminNotification', 'CmsAdminThemeNotification', false); // !!
class_alias('CMSMS\LanguageDetector', 'CmsLanguageDetector', false); // !!
class_alias('CMSMS\Route', 'CmsRoute', false); // !!
class_alias('CMSMS\Stylesheet', 'CmsLayoutStylesheet', false); // !!
class_alias('CMSMS\Template', 'CmsLayoutTemplate', false); // !!
class_alias('CMSMS\TemplateType', 'CmsLayoutTemplateType', false); // !!
class_alias('CMSMS\Tree', 'cms_tree', false); // !!
class_alias('CMSMS\Url', 'cms_url', false); // !!
class_alias('DesignManager\Design', 'CmsLayoutCollection', false); // !!
/*
//NA class_alias('CMSMS\AuditOperations', 'CMSMS\AuditManager', false); AuditManager == interface
//NA class_alias('CMSMS\AutoCookieOperations', 'CMSMS\AutoCookieManager', false);
//NA class_alias('CMSMS\Cookies', 'cms_cookies', false);
//NA class_alias('CMSMS\SignedCookieOperations', 'CMSMS\SignedCookieManager', false);
//NA class_alias('CMSMS\SignedCookieOperations', 'CMSMS\SignedCookies', false);
//NA class_alias('CMSMS\tasks\WatchTasksTask','WatchTasksTask', false);
class_alias('CMSMailer\Mailer', 'cms_mailer', false);
//class_alias('CMSMailer\Mailer', 'CMSMS\Mailer', false);
//class_alias('CMSMS\Mailer', 'cms_mailer', false);
class_alias('CMSMS\AdminMenuItem', 'CmsAdminMenuItem', false);
class_alias('CMSMS\AdminTabs', 'cms_admin_tabs', false);
class_alias('CMSMS\AdminTheme', 'CmsAdminThemeBase', false);
class_alias('CMSMS\AdminUtils', 'cms_admin_utils', false);
class_alias('CMSMS\AdminUtils', 'CmsAdminUtils', false);
class_alias('CMSMS\App', 'CmsApp', false);
class_alias('CMSMS\AppParams', 'cms_siteprefs', false);
class_alias('CMSMS\Async\RegularJob', 'CMSMS\Async\RegularTask', false);
class_alias('CMSMS\Bookmark', 'Bookmark');
class_alias('CMSMS\BookmarkOperations', 'BookmarkOperations', false);
class_alias('CMSMS\CacheFile', 'cms_filecache_driver', false);
class_alias('CMSMS\CommunicationException', 'CmsCommunicationException', false);
class_alias('CMSMS\ContentException', 'CmsContentException', false);
class_alias('CMSMS\ContentOperations', 'ContentOperations', false);
class_alias('CMSMS\contenttypes\Content', 'Content', false);
class_alias('CMSMS\contenttypes\ContentBase', 'ContentBase', false);
class_alias('CMSMS\contenttypes\ErrorPage', 'ErrorPage', false);
class_alias('CMSMS\contenttypes\Link', 'Link', false);
class_alias('CMSMS\contenttypes\PageLink', 'PageLink', false);
class_alias('CMSMS\contenttypes\SectionHeader', 'SectionHeader', false);
class_alias('CMSMS\contenttypes\Separator', 'Separator', false);
class_alias('CMSMS\CoreCapabilities', 'CmsCoreCapabilities', false);
class_alias('CMSMS\DataException', 'CmsDataException', false);
class_alias('CMSMS\DataException', 'CmsDataNotFoundException', false);
class_alias('CMSMS\DataException', 'CmsExtraDataException', false);
class_alias('CMSMS\DataException', 'CmsInvalidDataException', false);
class_alias('CMSMS\DbQueryBase', 'CmsDbQueryBase', false);
class_alias('CMSMS\EditContentException', 'CmsEditContentException', false);
class_alias('CMSMS\Error400Exception', 'CmsError400Exception', false);
class_alias('CMSMS\Error403Exception', 'CmsError403Exception', false);
class_alias('CMSMS\Error404Exception', 'CmsError404Exception', false);
class_alias('CMSMS\Error503Exception', 'CmsError503Exception', false);
class_alias('CMSMS\Events', 'Events', false);
class_alias('CMSMS\Exception', 'CmsException', false);
class_alias('CMSMS\FileSystemException', 'CmsFileSystemException', false);
class_alias('CMSMS\FormUtils', 'CmsFormUtils', false);
class_alias('CMSMS\Group', 'Group', false);
class_alias('CMSMS\GroupOperations', 'GroupOperations', FALSE);
class_alias('CMSMS\HookOperations', 'CMSMS\HookManager', false);
class_alias('CMSMS\HttpRequest', 'cms_http_request', false);
class_alias('CMSMS\internal\ContentTree', 'cms_content_tree', false);
class_alias('CMSMS\internal\Smarty', 'CMSMS\internal\Smarty_CMS', false);
class_alias('CMSMS\IRegularTask', 'CmsRegularTask', false);
class_alias('CMSMS\LangOperations', 'CmsLangOperations', false);
class_alias('CMSMS\Lock', 'CmsLock', false);
class_alias('CMSMS\LockOperations', 'CmsLockOperations', false);
class_alias('CMSMS\LogicException', 'CmsLogicException', false);
class_alias('CMSMS\ModuleContentType', 'CMSModuleContentType', false);
class_alias('CMSMS\ModuleOperations', 'ModuleOperations', false);
class_alias('CMSMS\Nls', 'CmsNls', false);
class_alias('CMSMS\NlsOperations', 'CmsNlsOperations', false);
class_alias('CMSMS\Permission', 'CmsPermission', false);
class_alias('CMSMS\PrivacyException', 'CmsPrivacyException', false);
class_alias('CMSMS\RouteOperations', 'cms_route_manager', false);
class_alias('CMSMS\ScriptsMerger', 'CMSMS\ScriptManager', false);
class_alias('CMSMS\SignedCookieOperations', 'cms_cookies', false);
class_alias('CMSMS\SingletonException', 'CmsSingletonException', false);
class_alias('CMSMS\SQLErrorException', 'CmsSQLErrorException', false);
class_alias('CMSMS\StopProcessingContentException', 'CmsStopProcessingContentException', false);
class_alias('CMSMS\StylesheetQuery', 'CmsLayoutStylesheetQuery', false);
class_alias('CMSMS\StylesMerger', 'CMSMS\StylesheetManager', false);
class_alias('CMSMS\SysDataCache', 'CMSMS\internal\global_cache', false);
class_alias('CMSMS\SysDataCacheDriver', 'CMSMS\internal\global_cachable', false);
class_alias('CMSMS\SystemCache', 'cms_cache_handler', false);
class_alias('CMSMS\tasks\ClearCacheTask', 'ClearCacheTask', false);
class_alias('CMSMS\tasks\SecurityCheckTask', 'CmsSecurityCheckTask', false);
class_alias('CMSMS\tasks\VersionCheckTask','CmsVersionCheckTask',false);
class_alias('CMSMS\TemplateQuery', 'CmsLayoutTemplateQuery', false);
class_alias('CMSMS\TemplatesGroup', 'CmsLayoutTemplateCategory', false);
class_alias('CMSMS\TemplateTypeAssistant', 'CMSMS\Layout\TemplateTypeAssistant', false);
class_alias('CMSMS\TreeOperations', 'cms_tree_operations', false);
class_alias('CMSMS\User', 'User', false);
class_alias('CMSMS\UserOperations', 'UserOperations', false);
class_alias('CMSMS\UserParams', 'cms_userprefs', false);
class_alias('CMSMS\UserTagOperations', 'CMSMS\UserTagOperations', false);
class_alias('CMSMS\Utils', 'cms_utils', false);
class_alias('CMSMS\XMLErrorException', 'CmsXMLErrorException', false);
*/
