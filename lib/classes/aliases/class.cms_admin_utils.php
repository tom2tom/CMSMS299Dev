<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\AdminUtils'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.AdminUtils.php';
class_alias('CMSMS\AdminUtils', 'cms_admin_utils', false);