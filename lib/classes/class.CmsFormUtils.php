<?php
assert(empty(CMS_DEBUG), new DeprecationNotice('class','CMSMS\\FormUtils'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.FormUtils.php';
\class_alias('CMSMS\\FormUtils', 'CmsFormUtils', false);
