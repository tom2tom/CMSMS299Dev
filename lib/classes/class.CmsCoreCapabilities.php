<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\CoreCapabilities'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.CoreCapabilities.php';
if (!class_exists('CmsCoreCapabilities')) {
    class_alias('CMSMS\CoreCapabilities', 'CmsCoreCapabilities', false);
}
