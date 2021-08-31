<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\RouteOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.RouteOperations.php';
class_alias('CMSMS\RouteOperations', 'cms_route_manager', false);
