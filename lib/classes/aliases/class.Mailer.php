<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMailer\\Mailer'));
require_once dirname(__DIR__,2).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'CMSMailer'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.Mailer.php';
class_alias('CMSMailer\Mailer', 'CMSMS\Mailer', false);
