<?php
namespace CMSMS;
use CMSMS\DeprecationNotice;
use const CMS_DEPREC;
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\FileSystemControls'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.FileSystemControls.php';
\class_alias('CMSMS\FileSystemControls', 'CMSMS\FilePickerProfile', false);
