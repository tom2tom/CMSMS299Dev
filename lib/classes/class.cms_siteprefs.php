<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\AppParams'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.AppParams.php';
if (!class_exists('cms_siteprefs', false)) {
    class_alias('CMSMS\AppParams', 'cms_siteprefs', false);
}
