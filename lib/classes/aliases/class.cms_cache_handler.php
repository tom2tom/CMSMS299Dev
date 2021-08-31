<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\SystemCache'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.SystemCache.php';
class_alias('CMSMS\SystemCache', 'cms_cache_handler', false);
