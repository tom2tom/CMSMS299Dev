<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\StylesMerger'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.StylesMerger.php';
class_alias('CMSMS\StylesMerger', 'CMSMS\StylesheetManager', false);
