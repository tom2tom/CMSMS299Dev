<?php
//deprecated stub file - see replacement classfile instead
assert(empty(CMS_DEPREC), new DeprecationNotice('Class file '.basename(__FILE__).' used'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.SignedCookieOperations.php';
if (!class_exists('CMSMS\SignedCookieManager', false)) {
    class_alias('CMSMS\SignedCookieOperations','CMSMS\SignedCookieManager', false);
}
