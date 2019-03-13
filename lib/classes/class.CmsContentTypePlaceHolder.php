<?php
if (CMS_DEBUG) throw new Exception('Deprecated class CmsContentTypePlaceHolder used');
require_once  __DIR__.DIRECTORY_SEPARATOR.'class ContentTypePlaceHolder.php';
\class_alias('CMSMS\ContentTypePlaceHolder', 'CmsContentTypePlaceHolder', false);
