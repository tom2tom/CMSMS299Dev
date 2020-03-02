<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\\FilePickerProfile'));
require_once __DIR__.DIRECTORY_SEPARATOR.'class.FileSystemProfile.php';
\class_alias('CMSMS\\FileSystemProfile', 'CMSMS\\FilePickerProfile', false);
