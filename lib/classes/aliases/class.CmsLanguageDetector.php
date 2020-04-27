<?php
//FUTURE assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\LanguageDetector'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.LanguageDetector.php';
class_alias('CMSMS\LanguageDetector', 'CmsLanguageDetector', false);
