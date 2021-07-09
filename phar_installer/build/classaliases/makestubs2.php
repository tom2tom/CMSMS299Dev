#!/usr/bin/env php
<?php

$root = __DIR__.DIRECTORY_SEPARATOR;

/*
lib/classes/contenttypes/class.Content.php CMSMS
lib/classes/contenttypes/class.ContentBase.php CMSMS
lib/classes/contenttypes/class.ErrorPage.php CMSMS
lib/classes/contenttypes/class.Link.php CMSMS
lib/classes/contenttypes/class.PageLink.php CMSMS
lib/classes/contenttypes/class.SectionHeader.php CMSMS
lib/classes/contenttypes/class.Separator.php CMSMS

lib/classes/internal/class.AdminThemeNotification.php

lib/classes/Async/class.JobManager.php 'CMSMS\Async\JobOperations',

*/
$newnames = [
'ContentOperations',
'Events',
'Group',
'GroupOperations',
'ModuleOperations',
'User',
'UserOperations',
'FileSystemProfile',
'Hookoperations'
];

foreach ([
'ContentOperations',
'Events',
'Group',
'GroupOperations',
'ModuleOperations',
'User',
'UserOperations',
'FilePickerProfile',
'HookManager',
] as $i => $base) {
    $fp = $root.'class.'.$base.'.php';
    touch($fp);
    $alias = $newnames[$i];
    file_put_contents($fp, <<<EOS
<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'CMSMS\\\\$alias'));
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'class.$alias.php';
class_alias('CMSMS\\$alias', '$base', false);

EOS
    );
}

