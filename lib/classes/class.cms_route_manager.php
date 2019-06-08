<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\RouteManager'));
require_once  __DIR__.DIRECTORY_SEPARATOR.'class.RouteManager.php';
\class_alias('CMSMS\\RouteManager', 'cms_route_manager', false);
