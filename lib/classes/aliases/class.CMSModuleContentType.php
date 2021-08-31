<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\ModuleContentType'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.ModuleContentType.php';
//abstract class CMSModuleContentType extends CMSMS\ModuleContentType {}
class_alias('CMSMS\ModuleContentType', 'CMSModuleContentType', false);
