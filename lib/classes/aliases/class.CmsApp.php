<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\App'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.App.php';
class_alias('CMSMS\App', 'CmsApp', false);
