<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\LockOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.LockOperations.php';
class_alias('CMSMS\LockOperations', 'CmsLockOperations', false);
