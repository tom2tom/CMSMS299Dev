<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\SystemCache'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.SystemCache.php';
if (!class_exists('cms_cache_handler', false)) {
    class_alias('CMSMS\SystemCache', 'cms_cache_handler', false);
}
