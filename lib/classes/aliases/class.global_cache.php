<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\LoadedData'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.LoadedData.php';
class_alias('CMSMS\LoadedData', 'CMSMS\internal\global_cache', false);
