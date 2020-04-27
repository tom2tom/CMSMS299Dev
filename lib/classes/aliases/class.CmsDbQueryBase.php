<?php
//FUTURE assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\DbQueryBase'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.DbQueryBase.php';
class_alias('CMSMS\DbQueryBase', 'CmsDbQueryBase', false);
