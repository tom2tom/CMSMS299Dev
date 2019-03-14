<?php
if (CMS_DEBUG) throw new Exception('Deprecated class CmsNls used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.Nls.php';
\class_alias('CMSMS\\Nls', 'CmsNls', false);
