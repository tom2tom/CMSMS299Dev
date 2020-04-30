<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\internal\\Smarty'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR.'class.Smarty.php';
class_alias('CMSMS\internal\Smarty', 'CMSMS\internal\Smarty_CMS', false);
