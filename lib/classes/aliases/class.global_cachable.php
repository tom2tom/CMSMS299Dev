<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\LoadedDataType'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.LoadedDataType.php';
class_alias('CMSMS\LoadedDataType', 'CMSMS\internal\global_cachable', false);
