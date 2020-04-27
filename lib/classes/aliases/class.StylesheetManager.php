<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\StyleOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.StyleOperations.php';
class_alias('CMSMS\StyleOperations', 'CMSMS\StylesheetManager', false);
