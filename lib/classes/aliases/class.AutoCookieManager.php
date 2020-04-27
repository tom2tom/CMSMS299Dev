<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\AutoCookieOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.AutoCookieOperations.php';
class_alias('CMSMS\AutoCookieOperations', 'CMSMS\AutoCookieManager', false);
