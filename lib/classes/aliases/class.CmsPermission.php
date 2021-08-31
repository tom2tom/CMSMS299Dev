<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\Permission'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Permission.php';
class_alias('CMSMS\Permission', 'CmsPermission', false);
