<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\Lock'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Lock.php';
if (!class_exists('CmsLock', false)) {
    class_alias('CMSMS\Lock', 'CmsLock', false);
}
