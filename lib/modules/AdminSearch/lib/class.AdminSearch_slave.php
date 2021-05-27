<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'AdminSearch\\Base_slave'));
$p = cms_module_path('AdminSearch', true);
if ($p) {
    $p .= DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.Base_slave.php';
    require_once $p;
    class_alias('AdminSearch\Base_slave', 'AdminSearch_slave', false);
}
