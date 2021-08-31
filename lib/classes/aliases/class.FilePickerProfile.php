<?php
namespace CMSMS;
use CMSMS\DeprecationNotice;
use const CMS_DEPREC;
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\FolderControls'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.FolderControls.php';
\class_alias('CMSMS\FolderControls', 'CMSMS\FilePickerProfile', false);
