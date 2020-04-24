<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\SimpleTagOperations'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.SimpleTagOperations.php';
if (!class_exists('UserTagOperations', false)) {
    class_alias('CMSMS\SimpleTagOperations', 'UserTagOperations', false);
}
