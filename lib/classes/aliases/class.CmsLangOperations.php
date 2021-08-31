<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\LangOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.LangOperations.php';
class_alias('CMSMS\LangOperations', 'CmsLangOperations', false);
