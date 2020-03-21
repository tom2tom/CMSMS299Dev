<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\internal\\SysDataCache'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.SysDataCache.php';
\class_alias(SysDataCache::class, 'CMSMS\\internal\\global_cache', false);
