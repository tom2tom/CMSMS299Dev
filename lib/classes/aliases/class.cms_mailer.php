<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\Mailer'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.Mailer.php';
class_alias('CMSMS\Mailer', 'cms_mailer', false);