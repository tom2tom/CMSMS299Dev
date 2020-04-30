<?php
//FUTURE assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\Tree'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Tree.php';
class_alias('CMSMS\Tree', 'cms_tree', false);
