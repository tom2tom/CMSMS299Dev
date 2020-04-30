<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\Stylesheet'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Stylesheet.php';
class_alias('CMSMS\Stylesheet', 'CmsLayoutStylesheet', false);
