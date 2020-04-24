<?php
//deprecated stub file - see replacement classfile instead
assert(empty(CMS_DEPREC), new DeprecationNotice('Class file '.basename(__FILE__).' used'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.StylesOperations.php';
if (!class_exists('CMSMS\StylesheetManager', false)) {
   class_alias('CMSMS\StylesOperations', 'CMSMS\StylesheetManager', false);
}
