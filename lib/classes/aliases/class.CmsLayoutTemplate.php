<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\Template'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Template.php';
class_alias('CMSMS\Template', 'CmsLayoutTemplate', false);
