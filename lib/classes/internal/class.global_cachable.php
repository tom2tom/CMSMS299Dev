<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\SysDataCacheDriver'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.SysDataCacheDriver.php';
if (!class_exists('CMSMS\internal\global_cachable', false)) {
    class_alias('CMSMS\SysDataCacheDriver', 'CMSMS\internal\global_cachable', false);
}
