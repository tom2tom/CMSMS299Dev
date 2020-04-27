<?php
//FUTURE assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\Url'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Url.php';
class_alias('CMSMS\Url', 'cms_url', false);
