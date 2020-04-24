<?php
//deprecated stub file - see replacement classfile instead
assert(empty(CMS_DEPREC), new DeprecationNotice('Class file '.basename(__FILE__).' used'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.SimpleTagOperations.php';
if (!class_exists('UserTagOperations', false)) {
    class_alias('CMSMS\SimpleTagOperations', 'UserTagOperations', false);
}
