<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\HookOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.HookOperations.php';
class_alias('CMSMS\HookOperations', 'CMSMS\HookManager', false);
