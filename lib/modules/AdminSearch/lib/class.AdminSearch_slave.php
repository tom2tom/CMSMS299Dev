<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'AdminSearch\\Slave'));
$p = cms_module_path('AdminSearch', true);
if ($p) {
    $p .= DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.Slave.php';
    require_once $p;
    class_alias('AdminSearch\Slave', 'AdminSearch_slave', false);
}
