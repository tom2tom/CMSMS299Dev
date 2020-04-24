<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\AdminTabs'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminTabs.php';
if (!class_exists('cms_admin_tabs', false)) {
    class_alias('CMSMS\AdminTabs', 'cms_admin_tabs', false);
}
