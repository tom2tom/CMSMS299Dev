<?php
assert(empty(CMS_DEBUG), new DeprecationNotice('class','CMSMS\\ContentTypePlaceHolder'));
require_once  __DIR__.DIRECTORY_SEPARATOR.'class ContentTypePlaceHolder.php';
\class_alias('CMSMS\\ContentTypePlaceHolder', 'CmsContentTypePlaceHolder', false);
