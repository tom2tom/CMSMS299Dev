<?php
//FUTURE assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\StylesheetQuery'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.StylesheetQuery.php';
class_alias('CMSMS\StylesheetQuery', 'CmsLayoutStylesheetQuery', false);
