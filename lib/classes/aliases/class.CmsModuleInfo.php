<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\internal\ModuleInfo'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR.'class.ModuleInfo.php';
class_alias('CMSMS\internal\ModuleInfo', 'CmsModuleInfo', false);
