<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\SimpleTagOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.SimpleTagOperations.php';
class_alias('CMSMS\SimpleTagOperations', 'CMSMS\UserTagOperations', false);
