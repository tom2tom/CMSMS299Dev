<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\internal\\SysDataCacheDriver'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.SysDataCacheDriver.php';
\class_alias('CMSMS\internal\SysDataCacheDriver', 'CMSMS\internal\global_cachable', false);
