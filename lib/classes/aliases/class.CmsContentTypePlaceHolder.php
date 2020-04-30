<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\ContentTypeOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.ContentTypeOperations.php';
class_alias('CMSMS\ContentTypeOperations', 'CmsContentTypePlaceHolder', false);
