<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\internal\ExtendedModuleInfo'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR.'class.ExtendedModuleInfo.php';
class_alias('CMSMS\internal\ExtendedModuleInfo', 'CmsExtendedModuleInfo', false);
