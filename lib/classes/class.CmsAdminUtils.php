<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\AdminUtils'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminUtils.php';
if (!class_exists('CmsAdminUtils', false)) {
    class_alias('CMSMS\AdminUtils', 'CmsAdminUtils', false);
}
