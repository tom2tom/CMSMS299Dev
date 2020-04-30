<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\TemplateQuery'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.TemplateQuery.php';
class_alias('CMSMS\TemplateQuery', 'CmsLayoutTemplateQuery', false);
