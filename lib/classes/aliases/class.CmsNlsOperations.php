<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\NlsOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.NlsOperations.php';
class_alias('CMSMS\NlsOperations', 'CmsNlsOperations', false);
