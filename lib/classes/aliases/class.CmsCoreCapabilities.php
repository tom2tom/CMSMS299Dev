<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\CapabilityType'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.CapabilityType.php';
class_alias('CMSMS\CapabilityType', 'CmsCoreCapabilities', false);
