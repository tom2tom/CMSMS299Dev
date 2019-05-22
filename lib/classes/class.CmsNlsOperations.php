<?php
assert(empty(CMS_DEBUG), new DeprecationNotice('class','CMSMS\\NlsOperations'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.NlsOperations.php';
\class_alias('CMSMS\\NlsOperations', 'CmsNlsOperations', false);
