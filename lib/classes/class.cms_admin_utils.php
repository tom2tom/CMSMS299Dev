<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\AdminUtils'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminUtils.php';
if (!class_exists('cms_admin_utils', false)) {
    class_alias('CMSMS\AdminUtils', 'cms_admin_utils', false);
}
