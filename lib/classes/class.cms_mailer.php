<?php
assert(empty(CMS_DEBUG), new DeprecationNotice('class','CMSMS\\Mailer'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Mailer.php';
\class_alias('CMSMS\\Mailer', 'cms_mailer', false);
