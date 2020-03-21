<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\Nls'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Nls.php';
\class_alias(Nls::class, 'CmsNls', false);
