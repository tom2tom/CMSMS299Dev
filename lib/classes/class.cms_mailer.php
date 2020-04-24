<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\Mailer'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Mailer.php';
if (!class_exists('cms_mailer', false)) {
    class_alias('CMSMS\Mailer', 'cms_mailer', false);
}
