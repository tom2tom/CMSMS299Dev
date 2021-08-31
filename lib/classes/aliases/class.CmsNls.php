<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\Nls'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Nls.php';
class_alias('CMSMS\Nls', 'CmsNls', false);
