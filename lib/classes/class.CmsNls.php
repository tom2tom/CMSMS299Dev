<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\Nls'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Nls.php';
if (!class_exists('CmsNls', false)) {
    class_alias('CMSMS\Nls', 'CmsNls', false);
}
