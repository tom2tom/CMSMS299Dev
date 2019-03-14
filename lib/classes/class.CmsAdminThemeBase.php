<?php
if (CMS_DEBUG) throw new Exception('Deprecated class CmsAdminThemeBase used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.ThemeBase.php';
\class_alias('CMSMS\\ThemeBase', 'CmsAdminThemeBase', false);
