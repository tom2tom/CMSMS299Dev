<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\FormUtils'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.FormUtils.php';
class_alias('CMSMS\FormUtils', 'CmsFormUtils', false);
