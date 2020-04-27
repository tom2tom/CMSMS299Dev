<?php
//FUTURE assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\emplateType'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.emplateType.php';
class_alias('CMSMS\emplateType', 'CmsLayoutTemplateType', false);
