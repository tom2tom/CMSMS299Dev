<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\SysDataCacheDriver'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.SysDataCacheDriver.php';
