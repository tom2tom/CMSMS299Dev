<?php
assert(empty(CMS_DEBUG), new DeprecationNotice('class','CMSMS\\Nls'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Nls.php';
\class_alias('CMSMS\\Nls', 'CmsNls', false);
