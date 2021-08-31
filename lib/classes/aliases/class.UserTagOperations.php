<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\UserTagOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.UserTagOperations.php';
class_alias('CMSMS\UserTagOperations', 'CMSMS\UserTagOperations', false);
