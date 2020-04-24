<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\SysDataCache'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.SysDataCache.php';
if (!class_exists('CMSMS\internal\global_cache', false)) {
    class_alias('CMSMS\SysDataCache', 'CMSMS\internal\global_cache', false);
}
