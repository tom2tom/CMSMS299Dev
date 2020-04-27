<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'CMSMS\\AuditOperations'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.AuditOperations.php';
class_alias('CMSMS\AuditOperations', 'CMSMS\AuditManager', false);
