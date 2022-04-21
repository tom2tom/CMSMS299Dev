<?php
/*
Backward compatibility support
Copyright (C) 2011-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

/**
 * Miscellaneous support functions
 *
 * @package CMS
 * @license GPL
 */
if( !function_exists('gzopen') ) {
    if( function_exists('gzopen64') ) {
    /**
     * Polyfill for gzopen() in case that does not exist.
     * Some installs of PHP (after PHP 5.3) use a different zlib library,
     * wherein gzopen is not defined.
     *
     * @since 2.0
     * @ignore
     */
        function gzopen($filename, $mode, $use_include_path = 0) {
            return gzopen64($filename, $mode, $use_include_path);
        }
    } else {
        function gzopen($filename, $mode, $use_include_path = 0) {
            throw new RuntimeException('Function gzopen is not available'); 
        }
    }
}

/**
 * Return the currently configured database prefix.
 * @deprecated since 3.0 Use constant CMS_DB_PREFIX instead
 *
 * @since 0.4
 * @return string
 */
function cms_db_prefix() : string
{
    return CMS_DB_PREFIX;
}

// TODO make this work better for not-yet-recognised replacement classes
// avoid errors about unknown replacement classes (particularly for the installer)
$lvl = error_reporting();
error_reporting(0);

// pre-define aliases which might be used in a type-hint/declaration
// since 3.0
// FWIW lines marked with !! are typehints seen in a nothing-special 2.2.3 distro ..
foreach ([
  'CMSMS\AppConfig' => 'cms_config', // !!
  'CMSMS\CacheDriver' => 'cms_cache_driver', // !! ABSTRACT
  'CMSMS\ContentTree' => 'cms_content_tree', // !!
  'CMSMS\ContentTypeOperations' => 'CmsContentTypePlaceHolder', // !!
  'CMSMS\FolderControls' => 'CMSMS\FilePickerProfile', // !!
  'CMSMS\internal\AdminNotification' => 'CmsAdminThemeNotification', // !!
  'CMSMS\LanguageDetector' => 'CmsLanguageDetector', // !!
  'CMSMS\Route' => 'CmsRoute', // !!
  'CMSMS\Stylesheet' => 'CmsLayoutStylesheet', // !!
  'CMSMS\Template' => 'CmsLayoutTemplate', // !!
  'CMSMS\TemplateType' => 'CmsLayoutTemplateType', // !!
  'CMSMS\Tree' => 'cms_tree', // !!
  'CMSMS\Url' => 'cms_url', // !!
  'DesignManager\Design' => 'CmsLayoutCollection', // !! replacement maybe N/A too
] as $replace => $past) {
    if (!class_exists($past, false)) {
//        $res =
        class_alias($replace, $past, false);
    }
}

error_reporting($lvl);

/* POSSIBLES
//NA 'CMSMS\LogOperations' => 'CMSMS\AuditManager', //AuditManager == interface
//NA 'CMSMS\AutoCookieOperations' => 'CMSMS\AutoCookieManager',
//NA 'CMSMS\Cookies' => 'cms_cookies',
//NA 'CMSMS\SignedCookieOperations' => 'CMSMS\SignedCookieManager',
//NA 'CMSMS\SignedCookieOperations' => 'CMSMS\SignedCookies',
//NA 'CMSMS\tasks\WatchTasksTask' => 'WatchTasksTask',
'OutMailer\Mailer' => 'cms_mailer',
//'OutMailer\Mailer' => 'CMSMS\Mailer',
//'CMSMS\Mailer' => 'cms_mailer',
'CMSMS\AdminMenuItem' => 'CmsAdminMenuItem',
'CMSMS\AdminTabs' => 'cms_admin_tabs',
'CMSMS\AdminTheme' => 'CmsAdminThemeBase',
'CMSMS\AdminUtils' => 'cms_admin_utils',
'CMSMS\AdminUtils' => 'CmsAdminUtils',
'CMSMS\App' => 'CmsApp',
'CMSMS\AppParams' => 'cms_siteprefs',
'CMSMS\Async\RegularJob' => 'CMSMS\Async\RegularTask',
'CMSMS\Bookmark' => 'Bookmark',
'CMSMS\BookmarkOperations' => 'BookmarkOperations',
'CMSMS\CacheFile' => 'cms_filecache_driver',
'CMSMS\CommunicationException' => 'CmsCommunicationException',
'CMSMS\ContentException' => 'CmsContentException',
'CMSMS\ContentOperations' => 'ContentOperations',
'CMSMS\contenttypes\Content' => 'Content',
'CMSMS\contenttypes\ContentBase' => 'ContentBase',
'CMSMS\contenttypes\ErrorPage' => 'ErrorPage',
'CMSMS\contenttypes\Link' => 'Link',
'CMSMS\contenttypes\PageLink' => 'PageLink',
'CMSMS\contenttypes\SectionHeader' => 'SectionHeader',
'CMSMS\contenttypes\Separator' => 'Separator',
'CMSMS\CoreCapabilities' => 'CmsCoreCapabilities',
'CMSMS\DataException' => 'CmsDataException',
'CMSMS\DataException' => 'CmsDataNotFoundException',
'CMSMS\DataException' => 'CmsExtraDataException',
'CMSMS\DataException' => 'CmsInvalidDataException',
'CMSMS\DbQueryBase' => 'CmsDbQueryBase',
'CMSMS\EditContentException' => 'CmsEditContentException',
'CMSMS\Error400Exception' => 'CmsError400Exception',
'CMSMS\Error403Exception' => 'CmsError403Exception',
'CMSMS\Error404Exception' => 'CmsError404Exception',
'CMSMS\Error503Exception' => 'CmsError503Exception',
'CMSMS\Events' => 'Events',
'CMSMS\Exception' => 'CmsException',
'CMSMS\FileSystemException' => 'CmsFileSystemException',
'CMSMS\FormUtils' => 'CmsFormUtils',
'CMSMS\Group' => 'Group',
'CMSMS\GroupOperations' => 'GroupOperations',
'CMSMS\HookOperations' => 'CMSMS\HookManager',
'CMSMS\HttpRequest' => 'cms_http_request',
'CMSMS\internal\Smarty' => 'CMSMS\internal\Smarty_CMS',
'CMSMS\InstallTest' => 'CmsInstallTest',
'CMSMS\IRegularTask' => 'CmsRegularTask',
'CMSMS\LangOperations' => 'CmsLangOperations',
'CMSMS\Lock' => 'CmsLock',
'CMSMS\LockOperations' => 'CmsLockOperations',
'CMSMS\LogicException' => 'CmsLogicException',
'CMSMS\ModuleContentType' => 'CMSModuleContentType',
'CMSMS\ModuleOperations' => 'ModuleOperations',
'CMSMS\Nls' => 'CmsNls',
'CMSMS\NlsOperations' => 'CmsNlsOperations',
'CMSMS\Permission' => 'CmsPermission',
'CMSMS\PrivacyException' => 'CmsPrivacyException',
'CMSMS\RouteOperations' => 'cms_route_manager',
'CMSMS\ScriptsMerger' => 'CMSMS\ScriptManager',
'CMSMS\SignedCookieOperations' => 'cms_cookies',
'CMSMS\SingletonException' => 'CmsSingletonException',
'CMSMS\SQLException' => 'CmsSQLErrorException',
'CMSMS\StopProcessingContentException' => 'CmsStopProcessingContentException',
'CMSMS\StylesheetQuery' => 'CmsLayoutStylesheetQuery',
'CMSMS\StylesMerger' => 'CMSMS\StylesheetManager',
'CMSMS\LoadedData' => 'CMSMS\internal\global_cache',
'CMSMS\LoadedDataType' => 'CMSMS\internal\global_cachable',
'CMSMS\SystemCache' => 'cms_cache_handler',
'CMSMS\tasks\ClearCacheTask' => 'ClearCacheTask',
'CMSMS\tasks\SecurityCheckTask' => 'CmsSecurityCheckTask',
'CMSMS\tasks\VersionCheckTask','CmsVersionCheckTask',
'CMSMS\TemplateQuery' => 'CmsLayoutTemplateQuery',
'CMSMS\TemplatesGroup' => 'CmsLayoutTemplateCategory',
'CMSMS\TemplateTypeAssistant' => 'CMSMS\Layout\TemplateTypeAssistant',
'CMSMS\TreeOperations' => 'cms_tree_operations',
'CMSMS\User' => 'User',
'CMSMS\UserOperations' => 'UserOperations',
'CMSMS\UserParams' => 'cms_userprefs',
'CMSMS\UserTagOperations' => 'CMSMS\UserTagOperations',
'CMSMS\Utils' => 'cms_utils',
'CMSMS\XMLException' => 'CmsXMLErrorException',
*/
