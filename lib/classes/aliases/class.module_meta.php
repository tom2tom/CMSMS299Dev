<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\LoadedMetadata'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.LoadedMetadata.php';
class_alias('CMSMS\LoadedMetadata', 'module_meta', false);
