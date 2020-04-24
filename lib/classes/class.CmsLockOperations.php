<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\LockOperations'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.LockOperations.php';
if (!class_exists('CmsLockOperations', false)) {
    class_alias('CMSMS\LockOperations', 'CmsLockOperations', false);
}
