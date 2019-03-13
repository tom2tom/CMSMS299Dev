<?php
if (!empty(CMS_DEBUG)) throw new Exception('Deprecated class CmsLockOperations used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.LockOperations.php';
\class_alias('CMSMS\LockOperations', 'CmsLockOperations', false);
