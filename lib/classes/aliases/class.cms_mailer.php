<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'OutMailer\Mailer'));
$mod = CMSMS\Utils::get_module('OutMailer'); // TODO OR 1st-detected EMAIL_MODULE
if ($mod) {
    //try to autoload this one
    class_alias('OutMailer\Mailer', 'cms_mailer', true);
} else {
    throw new Exception('The OutMailer module is needed to substitute for a cms_mailer object');
}
