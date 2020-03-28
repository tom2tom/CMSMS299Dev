<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\AdminTheme'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminTheme.php';
\class_alias('CMSMS\AdminTheme', 'CmsAdminAdminTheme', false);
