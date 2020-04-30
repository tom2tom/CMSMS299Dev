<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\CoreCapabilities'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.CoreCapabilities.php';
class_alias('CMSMS\CoreCapabilities', 'CmsCoreCapabilities', false);
