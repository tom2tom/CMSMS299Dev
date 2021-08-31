<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\TemplateType'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.TemplateType.php';
class_alias('CMSMS\TemplateType', 'CmsLayoutTemplateType', false);
