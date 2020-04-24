<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\ContentType'));
require_once  __DIR__.DIRECTORY_SEPARATOR.'class.ContentType.php';
if (!class_exists('CmsContentTypePlaceHolder', false)) {
    class_alias('CMSMS\ContentType', 'CmsContentTypePlaceHolder', false);
}
