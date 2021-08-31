<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\StylesheetQuery'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.StylesheetQuery.php';
class_alias('CMSMS\StylesheetQuery', 'CmsLayoutStylesheetQuery', false);
