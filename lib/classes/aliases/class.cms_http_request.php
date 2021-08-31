<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\HttpRequest'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.HttpRequest.php';
class_alias('CMSMS\HttpRequest', 'cms_http_request', false);
