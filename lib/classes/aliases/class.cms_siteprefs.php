<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\AppParams'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.AppParams.php';
class_alias('CMSMS\AppParams', 'cms_siteprefs', false);
