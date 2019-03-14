<?php
if (CMS_DEBUG) throw new Exception('Deprecated class CmsLock used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Lock.php';
\class_alias('CMSMS\\Lock', 'CmsLock', false);
