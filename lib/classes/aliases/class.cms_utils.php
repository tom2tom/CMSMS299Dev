<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\Utils'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Utils.php';
class_alias('CMSMS\Utils', 'cms_utils', false);
