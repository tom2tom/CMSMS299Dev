<?php
//FUTURE assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\Cookies'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Cookies.php';
class_alias('CMSMS\Cookies', 'cms_cookies', false);
