<?php
if (CMS_DEBUG) throw new Exception('Deprecated class CmsNlsOperations used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.NlsOperations.php';
\class_alias('CMSMS\\NlsOperations', 'CmsNlsOperations', false);
