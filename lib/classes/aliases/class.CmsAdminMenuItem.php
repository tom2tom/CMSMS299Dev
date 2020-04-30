<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\AdminMenuItem'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.AdminMenuItem.php';
class_alias('CMSMS\AdminMenuItem', 'CmsAdminMenuItem', false);
