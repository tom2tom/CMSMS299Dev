<?php
assert(empty(CMS_DEBUG), new DeprecationNotice('class','CMSMS\\ThemeBase'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.ThemeBase.php';
\class_alias('CMSMS\\ThemeBase', 'CmsAdminThemeBase', false);
