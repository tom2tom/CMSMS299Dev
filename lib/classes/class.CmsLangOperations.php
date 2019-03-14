<?php
if (CMS_DEBUG) throw new Exception('Deprecated class CmsLangOperations used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.LangOperations.php';
\class_alias('CMSMS\\LangOperations', 'CmsLangOperations', false);
