<?php
assert(empty(CMS_DEBUG), new DeprecationNotice('class','CMSMS\\AdminUtils'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminUtils.php';
\class_alias('CMSMS\\AdminUtils', 'cms_admin_utils', false);
