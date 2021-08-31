<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\SignedCookieOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.SignedCookieOperations.php';
class_alias('CMSMS\SignedCookieOperations', 'cms_cookies', false);
