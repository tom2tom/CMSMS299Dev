<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'AdminSearch\\Base_slave'));
if (strpos(__DIR__ , 'AdminSearch'.DIRECTORY_SEPARATOR.'lib') === false) {
    $bp = cms_module_path('AdminSearch', true);
    $p = ($bp) ? $bp.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.Base_slave.php' : '';
} else {
    $p = __DIR__.DIRECTORY_SEPARATOR.'class.Base_slave.php';
}
if ($p) {
    require_once $p;
    class_alias('AdminSearch\Base_slave', 'AdminSearch_slave', false);
}
