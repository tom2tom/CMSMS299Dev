<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\DbQueryBase'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.DbQueryBase.php';
//abstract class CmsDbQueryBase extends CMSMS\DbQueryBase {}
class_alias('CMSMS\DbQueryBase', 'CmsDbQueryBase', false);
