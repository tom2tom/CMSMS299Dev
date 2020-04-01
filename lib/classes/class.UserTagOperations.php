<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\SimpleTagOperations'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.SimpleTagOperations.php';
\class_alias('CMSMS\SimpleTagOperations', 'UserTagOperations', false);
