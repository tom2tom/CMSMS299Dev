<?php

assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'AdminSearch\\Tools'));
$p = cms_module_path('AdminSearch', true);
if ($p) {
    $p .= DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.Tools.php';
    require_once $p;
    class_alias('AdminSearch\Tools', 'AdminSearch_tools', false);
}
