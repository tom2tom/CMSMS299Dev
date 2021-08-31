<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\ContentTree'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.ContentTree.php';
class_alias('CMSMS\ContentTree', 'cms_content_tree', false);
