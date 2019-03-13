<?php
if (CMS_DEBUG) throw new Exception('Deprecated class CmsFormUtils used');
require_once __DIR__.DIRECTORY_SEPARATOR.'class.FormUtils.php';
\class_alias('CMSMS\FormUtils', 'CmsFormUtils', false);
