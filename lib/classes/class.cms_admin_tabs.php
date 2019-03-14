<?php
if (CMS_DEBUG) throw new Exception('Deprecated class cms_admin_tabs used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AdminTabs.php';
\class_alias('CMSMS\\AdminTabs', 'cms_admin_tabs', false);
