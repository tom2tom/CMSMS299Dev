<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\internal\\AdminNotification'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR.'class.AdminNotification.php';
class_alias('CMSMS\internal\AdminNotification', 'CMSMS\internal\AdminThemeNotification', false);
class_alias('CMSMS\internal\AdminNotification', 'CmsAdminThemeNotification', false);
