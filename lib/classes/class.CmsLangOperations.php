<?php
assert(empty(CMS_DEBUG), new DeprecationNotice('Class file '.basename(__FILE__).' used'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.LangOperations.php';
\class_alias('CMSMS\\LangOperations', 'CmsLangOperations', false);
