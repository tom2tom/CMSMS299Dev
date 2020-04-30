<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\LanguageDetector'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.LanguageDetector.php';
//abstract class CmsLanguageDetector extends CMSMS\LanguageDetector {}
class_alias('CMSMS\LanguageDetector', 'CmsLanguageDetector', false);
