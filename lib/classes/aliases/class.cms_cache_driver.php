<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\CacheDriver'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.CacheDriver.php';
class_alias('CMSMS\CacheDriver', 'cms_cache_driver', false);
