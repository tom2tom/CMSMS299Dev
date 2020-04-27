<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\ScriptOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.ScriptOperations.php';
class_alias('CMSMS\ScriptOperations', 'CMSMS\ScriptManager', false);
