<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\UserParams'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.UserParams.php';
class_alias('CMSMS\UserParams', 'cms_userprefs', false);
