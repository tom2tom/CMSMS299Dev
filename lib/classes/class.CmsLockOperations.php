<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\LockOperations'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.LockOperations.php';
\class_alias('CMSMS\\LockOperations', 'CmsLockOperations', false);
