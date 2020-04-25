<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\UserParams'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.UserParams.php';
if (!class_exists('cms_siteprefs', false)) {
    class_alias('CMSMS\UserParams', 'cms_siteprefs', false);
}
