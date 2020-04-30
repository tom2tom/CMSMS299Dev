<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\TreeOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.TreeOperations.php';
class_alias('CMSMS\TreeOperations', 'cms_tree_operations', false);
