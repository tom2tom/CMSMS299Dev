<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\NlsOperations'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.NlsOperations.php';
\class_alias(NlsOperations::class, 'CmsNlsOperations', false);
