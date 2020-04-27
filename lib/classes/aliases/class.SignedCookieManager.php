<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\SignedCookieOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.SignedCookieOperations.php';
class_alias('CMSMS\SignedCookieOperations', 'CMSMS\SignedCookieManager', false);
