<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\TreeOperations'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.TreeOperations.php';
if (!class_exists('cms_tree_operations', false)) {
    class_alias('CMSMS\TreeOperations', 'cms_tree_operations', false);
}
