<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\ScriptsMerger'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.ScriptsMerger.php';
class_alias('CMSMS\ScriptsMerger', 'CMSMS\ScriptManager', false);
