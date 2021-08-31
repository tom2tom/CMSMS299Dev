<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\AdminTheme'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.AdminTheme.php';
class_alias('CMSMS\AdminTheme', 'CmsAdminTheme', false);
