<?php

use CMSMS\Group;

status_msg('Adding missing permissions');

//$gCms = cmsms();
$perms = ['Manage Stylesheets'];
$all_perms = [];
foreach( $perms as $one_perm ) {
    $permission = new CmsPermission();
    $permission->source = 'Core';
    $permission->name = $one_perm;
    $permission->text = $one_perm;
	try {
		$permission->save();
		$all_perms[$one_perm] = $permission;
	} catch (Exception $e) {
		// nothing here
	}
}

$groups = Group::load_all();
foreach( $groups as $group ) {
    if( strtolower($group->name == 'designer') ) {
        $group->GrantPermission('Manage Stylesheets');
    }
}
