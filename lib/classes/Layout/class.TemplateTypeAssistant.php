<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\TemplateTypeAssistant'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.TemplateTypeAssistant.php';
class_alias('CMSMS\TemplateTypeAssistant', 'CMSMS\Layout\TemplateTypeAssistant', false);
