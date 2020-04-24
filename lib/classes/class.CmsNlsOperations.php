<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\NlsOperations'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.NlsOperations.php';
if (!class_exists('CmsNlsOperations', false)) {
    class_alias('CMSMS\NlsOperations', 'CmsNlsOperations', false);
}