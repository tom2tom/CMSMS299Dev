<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\Route'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Route.php';
class_alias('CMSMS\Route', 'CmsRoute', false);
