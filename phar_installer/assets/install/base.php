<?php

use CMSMS\Events;
use CMSMS\Group;
use CMSMS\User;
use CMSMS\UserOperations;
use function __installer\get_app;

global $admin_user;

status_msg(ilang('install_requireddata'));

$query = 'INSERT INTO '.CMS_DB_PREFIX.'version VALUES (205)';
$db->Execute($query);
verbose_msg(ilang('install_setschemaver'));

//
// site preferences
//
verbose_msg(ilang('install_initsiteprefs'));
cms_siteprefs::set('adminlog_lifetime',3600*24*31); // admin log entries only live for 60 days.
cms_siteprefs::set('allow_browser_cache',1); // allow browser to cache cachable pages
cms_siteprefs::set('auto_clear_cache_age',60); // cache files for only 60 days by default
cms_siteprefs::set('browser_cache_expiry',60); // browser can cache pages for 60 minutes.
cms_siteprefs::set('global_umask','022');
cms_siteprefs::set('metadata',"<meta name=\"Generator\" content=\"CMS Made Simple - Copyright (C) 2004-" . date('Y') . ". All rights reserved.\" />\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n");

//
// permissions
// note: some of these have been exported to CmsContentManager or DesignManager install routines.
//
verbose_msg(ilang('install_initsiteperms'));
$all_perms = [];
foreach( [
//	'Add Pages', >CM
//	'Add Templates', >DM
//	'Manage All Content', >CM
//	'Manage Designs', >DM
	'Manage Groups',
	'Manage My Account',
	'Manage My Bookmarks',
	'Manage My Settings',
//	'Manage Stylesheets', >DM
	'Manage Users',
//	'Modify Any Page', >CM
	'Modify Events',
	'Modify Files',
	'Modify Modules',
	'Modify Permissions',
	'Modify Simple Plugins',
	'Modify Site Code',
	'Modify Site Preferences',
//	'Modify Templates', >DM
//	'Remove Pages', >CM
//	'Reorder Content', >CM
	'View Tag Help',
	] as $one_perm ) {
  $permission = new CmsPermission();
  $permission->source = 'Core';
  $permission->name = $one_perm;
  $permission->text = $one_perm;
  $permission->save();
  $all_perms[$one_perm] = $permission;
}

//
// initial groups
//
verbose_msg(ilang('install_initsitegroups'));
$admin_group = new Group();
$admin_group->name = 'Admin';
$admin_group->description = 'Members of this group can manage the entire site.';
$admin_group->active = 1;
$admin_group->Save();

/* migrated to ContentManager install routine
$editor_group = new Group();
$editor_group->name = 'Editor';
$editor_group->description = 'Members of this group can manage content';
$editor_group->active = 1;
$editor_group->Save();
$editor_group->GrantPermission('Manage All Content');
$editor_group->GrantPermission('Manage My Account');
$editor_group->GrantPermission('Manage My Bookmarks');
$editor_group->GrantPermission('Manage My Settings');
*/
/* migrated to DesignManager install routine
$designer_group = new Group();
$designer_group->name = 'Designer';
$designer_group->description = 'Members of this group can manage stylesheets, templates, and content';
$designer_group->active = 1;
$designer_group->Save();
$designer_group->GrantPermission('Add Templates');
$designer_group->GrantPermission('Manage All Content'); ContentManager >> racy!
$designer_group->GrantPermission('Manage Designs');
$designer_group->GrantPermission('Manage My Account');
$designer_group->GrantPermission('Manage My Bookmarks');
$designer_group->GrantPermission('Manage My Settings');
$designer_group->GrantPermission('Manage Stylesheets');
$designer_group->GrantPermission('Modify Files');
$designer_group->GrantPermission('Modify Templates');
*/

//
// initial user account
//
verbose_msg(ilang('install_initsiteusers'));
$sitemask = cms_siteprefs::get('sitemask');
$admin_user = new User();
$admin_user->username = $adminaccount['username'];
if( isset($adminaccount['emailaddr']) && $adminaccount['emailaddr'] ) $admin_user->email = $adminaccount['emailaddr'];
$admin_user->active = 1;
$admin_user->adminaccess = 1;
$admin_user->password = password_hash( $adminaccount['password'], PASSWORD_DEFAULT );
$admin_user->Save();
UserOperations::get_instance()->AddMemberGroup($admin_user->id,$admin_group->id);
cms_userprefs::set_for_user($admin_user->id,'wysiwyg','MicroTiny'); // the one, and only user preference we need.

//
// Events
// Events are deprecated:  hooks are used now.
// some of these have been exported to CmsContentManager or DesignManager install routines.
//
verbose_msg(ilang('install_initevents'));
/* >DM
Events::CreateEvent('Core','AddDesignPost');
Events::CreateEvent('Core','AddDesignPre');
*/
Events::CreateEvent('Core','AddGroupPost');
Events::CreateEvent('Core','AddGroupPre');
/* >DM
Events::CreateEvent('Core','AddStylesheetPost');
Events::CreateEvent('Core','AddStylesheetPre');
Events::CreateEvent('Core','AddTemplatePost');
Events::CreateEvent('Core','AddTemplatePre');
Events::CreateEvent('Core','AddTemplateTypePost');
Events::CreateEvent('Core','AddTemplateTypePre');
*/
Events::CreateEvent('Core','AddUserPost');
Events::CreateEvent('Core','AddUserPre');
Events::CreateEvent('Core','ChangeGroupAssignPost');
Events::CreateEvent('Core','ChangeGroupAssignPre');
/* >CM
Events::CreateEvent('Core','ContentDeletePost');
Events::CreateEvent('Core','ContentDeletePre');
Events::CreateEvent('Core','ContentEditPost');
Events::CreateEvent('Core','ContentEditPre');

Events::CreateEvent('Core','ContentPostCompile');
Events::CreateEvent('Core','ContentPostRender');
Events::CreateEvent('Core','ContentPreCompile');
Events::CreateEvent('Core','ContentPreRender'); // 2.2
*/
/* >DM
Events::CreateEvent('Core','DeleteDesignPost');
Events::CreateEvent('Core','DeleteDesignPre');
*/
Events::CreateEvent('Core','DeleteGroupPost');
Events::CreateEvent('Core','DeleteGroupPre');
/* >DM
Events::CreateEvent('Core','DeleteStylesheetPost');
Events::CreateEvent('Core','DeleteStylesheetPre');
Events::CreateEvent('Core','DeleteTemplatePost');
Events::CreateEvent('Core','DeleteTemplatePre');
Events::CreateEvent('Core','DeleteTemplateTypePost');
Events::CreateEvent('Core','DeleteTemplateTypePre');
*/
Events::CreateEvent('Core','DeleteUserPost');
Events::CreateEvent('Core','DeleteUserPre');
/* >DM
Events::CreateEvent('Core','EditDesignPost');
Events::CreateEvent('Core','EditDesignPre');
*/
Events::CreateEvent('Core','EditGroupPost');
Events::CreateEvent('Core','EditGroupPre');
/* >DM
Events::CreateEvent('Core','EditStylesheetPost');
Events::CreateEvent('Core','EditStylesheetPre');
Events::CreateEvent('Core','EditTemplatePost');
Events::CreateEvent('Core','EditTemplatePre');
Events::CreateEvent('Core','EditTemplateTypePost');
Events::CreateEvent('Core','EditTemplateTypePre');
*/
Events::CreateEvent('Core','EditUserPost');
Events::CreateEvent('Core','EditUserPre');
Events::CreateEvent('Core','LoginFailed');

Events::CreateEvent('Core','LoginPost');
Events::CreateEvent('Core','LogoutPost');
Events::CreateEvent('Core','LostPassword');
Events::CreateEvent('Core','LostPasswordReset');

Events::CreateEvent('Core','ModuleInstalled');
Events::CreateEvent('Core','ModuleUninstalled');
Events::CreateEvent('Core','ModuleUpgraded');
Events::CreateEvent('Core','SmartyPostCompile');
Events::CreateEvent('Core','SmartyPreCompile');
/* >DM
Events::CreateEvent('Core','StylesheetPostCompile');
Events::CreateEvent('Core','StylesheetPostRender');
Events::CreateEvent('Core','StylesheetPreCompile');
Events::CreateEvent('Core','TemplatePostCompile');
Events::CreateEvent('Core','TemplatePreCompile');
Events::CreateEvent('Core','TemplatePreFetch');
*/
$create_private_dir = function(string $destdir, string $relative_dir) {
//    $relative_dir = trim($relative_dir);
//    if( !$relative_dir ) return;

    $dir = $destdir.DIRECTORY_SEPARATOR.$relative_dir;
    if( !is_dir($dir) ) {
        @mkdir($dir,0771,true);
    }
    @touch($dir.DIRECTORY_SEPARATOR.'index.html');
};

// create the assets directory structure
verbose_msg(ilang('install_createassets'));
$app = get_app();
$destdir = $app->get_destdir().DIRECTORY_SEPARATOR.'assets';
$create_private_dir($destdir,'admin_custom');
$create_private_dir($destdir,'configs');
$create_private_dir($destdir,'css');
$create_private_dir($destdir,'images');
$create_private_dir($destdir,'module_custom');
$create_private_dir($destdir,'modules');
$create_private_dir($destdir,'plugins');
$create_private_dir($destdir,'simple_plugins');
$create_private_dir($destdir,'templates');
