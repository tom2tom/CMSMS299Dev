<?php
//FUTURE assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\ModuleContentType'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.ModuleContentType.php';
class_alias('CMSMS\ModuleContentType', 'CMSModuleContentType', false);
