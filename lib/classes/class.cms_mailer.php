<?php
if (CMS_DEBUG) throw new Exception('Deprecated class cms_mailer used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Mailer.php';
\class_alias('CMSMS\Mailer', 'cms_mailer', false);
