<?php
if (CMS_DEBUG) throw new Exception('Deprecated class CmsAdminUtils used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminUtils.php';
\class_alias('CMSMS\AdminUtils', 'CmsAdminUtils', false);
