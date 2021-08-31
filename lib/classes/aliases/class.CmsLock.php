<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\Lock'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Lock.php';
class_alias('CMSMS\Lock', 'CmsLock', false);
