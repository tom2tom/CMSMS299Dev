<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\AdminUtils'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminUtils.php';
\class_alias(AdminUtils::class, 'cms_admin_utils', false);
