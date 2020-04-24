<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\FormUtils'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.FormUtils.php';
if (!class_exists('CmsFormUtils', false)) {
    class_alias('CMSMS\FormUtils', 'CmsFormUtils', false);
}
