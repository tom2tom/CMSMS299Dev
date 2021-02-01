<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMailer\\Mailer'));
$obj = CMSMS\Utils::get_module('CMSMailer'); // TODO OR 1st-detected EMAIL_MODULE
if ($obj) {
    //try to autoload this one
    class_alias('CMSMailer\Mailer', 'cms_mailer', true);
} else {
    throw new Exception('The CMSMailer module is needed to substitute for a cms_mailer object');
}
