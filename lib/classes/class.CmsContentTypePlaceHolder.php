<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\ContentType'));
require_once  __DIR__.DIRECTORY_SEPARATOR.'class.ContentType.php';
\class_alias(ContentType::class, 'CmsContentTypePlaceHolder', false);
