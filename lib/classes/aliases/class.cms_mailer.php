<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\Mailer'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Mailer.php';
class_alias('CMSMS\Mailer', 'cms_mailer', false);
