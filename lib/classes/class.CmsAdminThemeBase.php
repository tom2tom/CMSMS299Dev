<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\AdminTheme'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminTheme.php';
if (!class_exists('CmsAdminTheme', false)) {
    class_alias('CMSMS\AdminTheme', 'CmsAdminTheme', false);
}
