<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\PageTreeNode'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.PageTreeNode.php';
class_alias('CMSMS\PageTreeNode', 'cms_content_tree', false);
