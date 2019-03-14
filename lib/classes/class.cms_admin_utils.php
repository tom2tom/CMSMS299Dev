<?php
if (CMS_DEBUG) throw new Exception('Deprecated class cms_admin_utils used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminUtils.php';
\class_alias('CMSMS\\AdminUtils', 'cms_admin_utils', false);
