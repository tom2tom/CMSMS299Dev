<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'AdminSearch\Tools'));
if (strpos(__DIR__ , 'AdminSearch'.DIRECTORY_SEPARATOR.'lib') === false) {
    $bp = cms_module_path('AdminSearch', true);
    $p = ($bp) ? $bp.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.Tools.php' : '';
} else {
    $p = __DIR__.DIRECTORY_SEPARATOR.'class.Tools.php';
}
if ($p) {
    require_once $p;
    class_alias('AdminSearch\Tools', 'AdminSearch_tools', false);
}
