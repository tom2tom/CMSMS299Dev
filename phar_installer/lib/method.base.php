<?php

use cms_siteprefs;
use cms_utils;
use CMSMS\AdminTheme;
use CMSMS\Events;
use CMSMS\Group;
use CMSMS\User;
use CMSMS\UserOperations;
use function cms_installer\get_app;
use function cms_installer\lang;

const CURRENT_SCHEMA = 206;

// vars set in includer: $admin_user, $siteinfo, $wiz, $app etc

if (!isset($app)) {
	$app = get_app();
}
$config = $app->get_config(); //more-or-less same as $siteinfo[]

//
// some of the system-wide default settings
//
verbose_msg(lang('install_initsiteprefs'));
$cachtype = $wiz->get_data('cachemode');
$corenames = $config['coremodules'];
$cores = implode(',',$corenames);
$theme = reset(AdminTheme::GetAvailableThemes());
$helpurl =  ( !empty($siteinfo['supporturl']) ) ? $siteinfo['supporturl'] : '';
$uuid = trim(base64_encode(cms_utils::random_string(24)), '='); //db hates storing some chars verbatim
$ultras = json_encode(['Modify Restricted Files','Modify DataBase Direct','Remote Administration']);

foreach ([
	'allow_browser_cache' => 1, // allow browser to cache cachable pages
	'auto_clear_cache_age' => 60, // tasks-parameter: cache files for 60 days by default (see also cache_lifetime)
	'browser_cache_expiry' => 60, // browser can cache pages for 60 minutes
	'cache_autocleaning' => 1,
	'cache_driver' => $cachtype, //'auto', or 'file' if no supported cache-extension was detected
	'cache_file_blocking' => 0,
	'cache_file_locking' => 1,
	'cache_lifetime' => 3600, // cache entries live for 1 hr
	'cdn_url' => 'https://cdnjs.cloudflare.com', // or e.g. https://cdn.jsdelivr.net, https://cdnjs.com/libraries
	'checkversion' => 1,
	'coremodules' => $cores, // aka ModuleOperations::CORENAMES_PREF
	'defaultdateformat' => '%e %B %Y',
	'enablesitedownmessage' => 0, //deprecated since 2.9 use site_downnow
	'frontendlang' => 'en_US',
	'global_umask' => '022',
	'lock_refresh' => 120,
	'lock_timeout' => 60,
	'loginmodule' => '', // login UI defined by current theme
	'logintheme' => $theme,
	'metadata' => '<meta name="Generator" content="CMS Made Simple - Copyright (C) 2004-' . date('Y') . '. All rights reserved." />'."\n".'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n",
	'schema_version' => CURRENT_SCHEMA,
	'site_help_url' => $helpurl,
	'site_uuid' => $uuid, // almost-certainly-unique signature of this site
	'sitedownexcludeadmins' => 0,
	'sitedownexcludes' => '',
	'sitedownmessage' => '',
	'site_downnow' => 0, //see also deprecated enablesitedownmessage
	'site_logo' => '',
//	'sitemask' => '', for old (md5-hashed) admin-user passwords - useless in new installs
	'sitename' => $siteinfo['sitename'],
	'smarty_cachelife' => -1, // smarty default
	'smarty_cachemodules' => 0,
	'smarty_cachesimples' => 0,
	'smarty_compilecheck' => 1, //see also deprecated use_smartycompilecheck
	'thumbnail_height' => 96,
	'thumbnail_width' => 96,
	'ultraroles' => $ultras,
	'use_smartycompilecheck' => 1, //deprecated since 2.9 use smarty_compilecheck
] as $name => $val) {
	cms_siteprefs::set($name, $val);
}

status_msg(lang('install_requireddata'));

//$query = 'INSERT INTO '.CMS_DB_PREFIX.'version VALUES (CURRENT_SCHEMA)';
//$db->Execute($query);
//verbose_msg(lang('install_setschemaver'));

//
// permissions
// note: some of these have been exported to CMSContentManager or DesignManager install routines.
//
verbose_msg(lang('install_initsiteperms'));
$all_perms = [];
foreach( [
//	'Add Pages', >CM
	'Add Templates',
//	'Manage All Content', >CM
//	'Manage Designs', >DM
	'Manage Groups',
	'Manage My Account',
	'Manage My Bookmarks',
	'Manage My Settings',
	'Manage Stylesheets',
	'Manage Users',
//	'Modify Any Page', >CM
	'Modify DataBase Direct', //for remote management, sans admin console
	'Modify Events',
	'Modify User Plugins',
	'Modify Files',
	'Modify Modules',
	'Modify Permissions',
	'Modify Restricted Files',
//	'Modify Site Assets', no deal !!
	'Modify Site Preferences',
	'Modify Templates',
	'Remote Administration',  //for remote management, sans admin console kinda Modify DataBase Direct + Modify Restricted Files
//	'Remove Pages', >CM
//	'Reorder Content', >CM
	'View Tag Help',
	] as $one_perm ) {
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

//
// initial groups
//
verbose_msg(lang('install_initsitegroups'));
$group = new Group();
$group->name = 'Admin';
$group->description = lang('grp_admin_desc');
$group->active = 1;
$group->Save();

$gid1 = $group->id;

$group = new Group();
$group->name = 'CodeManager';
$group->description = lang('grp_coder_desc');
$group->active = 1;
$group->Save();
$group->GrantPermission('Modify Restricted Files');
//$group->GrantPermission('Modify Site Assets');
$group->GrantPermission('Modify User Plugins');
/* too risky
$group = new Group();
$group->name = 'AssetManager';
$group->description = 'Members of this group can add/edit/delete website asset-files';
$group->active = 1;
$group->Save();
$group->GrantPermission('Modify Site Assets');
*/
/* migrated to ContentManager install routine
$group = new Group();
$group->name = 'Editor';
$group->description = 'Members of this group can manage content';
$group->active = 1;
$group->Save();
$group->GrantPermission('Manage All Content');
$group->GrantPermission('Manage My Account');
$group->GrantPermission('Manage My Bookmarks');
$group->GrantPermission('Manage My Settings');
*/
/* migrated to DesignManager install routine
$group = new Group();
$group->name = 'Designer';
$group->description = 'Members of this group can manage stylesheets, templates, and content';
$group->active = 1;
$group->Save();
$group->GrantPermission('Add Templates');
$group->GrantPermission('Manage All Content'); ContentManager >> racy!
$group->GrantPermission('Manage Designs');
$group->GrantPermission('Manage My Account');
$group->GrantPermission('Manage My Bookmarks');
$group->GrantPermission('Manage My Settings');
$group->GrantPermission('Manage Stylesheets');
$group->GrantPermission('Modify Files');
$group->GrantPermission('Modify Templates');
*/

//
// initial user account
//
verbose_msg(lang('install_initsiteusers'));
$admin_user = new User();
$admin_user->username = $adminaccount['username'];
if( !empty($adminaccount['emailaddr']) ) $admin_user->email = $adminaccount['emailaddr'];
else $admin_user->email = '';
$admin_user->active = 1;
$admin_user->adminaccess = 1;
$admin_user->password = password_hash( $adminaccount['password'], PASSWORD_DEFAULT );
$admin_user->Save();

UserOperations::get_instance()->AddMemberGroup($admin_user->id,$gid1);
cms_userprefs::set_for_user($admin_user->id,'wysiwyg','MicroTiny'); // TODO if MicroTiny present -the only user-preference we need now

//
// standard events
// some of these have been exported to CMSContentManager or DesignManager install routines.
//
verbose_msg(lang('install_initevents'));
/* >DM
Events::CreateEvent('Core','AddDesignPost');
Events::CreateEvent('Core','AddDesignPre');
*/
Events::CreateEvent('Core','AddGroupPost');
Events::CreateEvent('Core','AddGroupPre');
Events::CreateEvent('Core','AddStylesheetPost');
Events::CreateEvent('Core','AddStylesheetPre');
Events::CreateEvent('Core','AddTemplatePost');
Events::CreateEvent('Core','AddTemplatePre');
Events::CreateEvent('Core','AddTemplateTypePost');
Events::CreateEvent('Core','AddTemplateTypePre');
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
Events::CreateEvent('Core','DeleteStylesheetPost');
Events::CreateEvent('Core','DeleteStylesheetPre');
Events::CreateEvent('Core','DeleteTemplatePost');
Events::CreateEvent('Core','DeleteTemplatePre');
Events::CreateEvent('Core','DeleteTemplateTypePost');
Events::CreateEvent('Core','DeleteTemplateTypePre');
Events::CreateEvent('Core','DeleteUserPost');
Events::CreateEvent('Core','DeleteUserPre');
/* >DM
Events::CreateEvent('Core','EditDesignPost');
Events::CreateEvent('Core','EditDesignPre');
*/
Events::CreateEvent('Core','EditGroupPost');
Events::CreateEvent('Core','EditGroupPre');
Events::CreateEvent('Core','EditStylesheetPost');
Events::CreateEvent('Core','EditStylesheetPre');
Events::CreateEvent('Core','EditTemplatePost');
Events::CreateEvent('Core','EditTemplatePre');
Events::CreateEvent('Core','EditTemplateTypePost');
Events::CreateEvent('Core','EditTemplateTypePre');

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
Events::CreateEvent('Core','MetadataPostRender');
Events::CreateEvent('Core','MetadataPreRender');

Events::CreateEvent('Core','PageTopPostRender');
Events::CreateEvent('Core','PageTopPreRender');
Events::CreateEvent('Core','PageHeadPostRender');
Events::CreateEvent('Core','PageHeadPreRender');
Events::CreateEvent('Core','PageBodyPostRender');
Events::CreateEvent('Core','PageBodyPreRender');
Events::CreateEvent('Core','PostRequest');

Events::CreateEvent('Core','SmartyPostCompile');
Events::CreateEvent('Core','SmartyPreCompile');

Events::CreateEvent('Core','StylesheetPostCompile');
Events::CreateEvent('Core','StylesheetPostRender');
Events::CreateEvent('Core','StylesheetPreCompile');
Events::CreateEvent('Core','TemplatePostCompile');
Events::CreateEvent('Core','TemplatePreCompile');
Events::CreateEvent('Core','TemplatePreFetch');

function create_private_dir(string $destdir, string $relative_dir)
{
//    $relative_dir = trim($relative_dir);
//    if( !$relative_dir ) return;
    $dir = $destdir.DIRECTORY_SEPARATOR.$relative_dir;
    if( !is_dir($dir) ) {
        @mkdir($dir,0771,true);
    }
    @touch($dir.DIRECTORY_SEPARATOR.'index.html');
}

// create the assets (however named) directory structure
verbose_msg(lang('install_createassets'));
$na = $config['assetsdir'] ?? 'assets';
$np = $config['pluginsdir'] ?? 'simple_plugins';
$destdir = $app->get_destdir().DIRECTORY_SEPARATOR.$na;
create_private_dir($destdir,'admin_custom');
create_private_dir($destdir,'configs');
create_private_dir($destdir,'css');
create_private_dir($destdir,'images');
create_private_dir($destdir,'module_custom');
create_private_dir($destdir,'modules'); //CHECKME if distinct place for non-core modules
create_private_dir($destdir,'plugins');
create_private_dir($destdir,'resources');
create_private_dir($destdir,$np); //UDTfiles
create_private_dir($destdir,'templates');