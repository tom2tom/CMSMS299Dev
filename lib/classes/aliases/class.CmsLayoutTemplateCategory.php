<?php
//FUTURE assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\TemplatesGroup'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.TemplatesGroup.php';
class_alias('CMSMS\TemplatesGroup', 'CmsLayoutTemplateCategory', false);
