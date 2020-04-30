<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\Hookoperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Hookoperations.php';
class_alias('CMSMS\Hookoperations', 'CMSMS\HookManager', false);
