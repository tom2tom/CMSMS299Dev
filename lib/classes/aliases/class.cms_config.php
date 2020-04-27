<?php
//FUTURE assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\AppConfig'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.AppConfig.php';
class_alias('CMSMS\AppConfig', 'cms_config', false);
