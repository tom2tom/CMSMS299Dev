<?php

use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\Crypto;
use CMSMS\Events;
use CMSMS\Group;
use CMSMS\Permission;
use CMSMS\User;
use CMSMS\UserParams;
use CMSMS\UserOperations;
use function cms_installer\lang;

// vars set in includer: $admin_user, $siteinfo[], $wiz, $app, $destdir etc

//
// create tmp directories
//
verbose_msg(lang('install_createtmpdirs'));
$fp = constant('TMP_CACHE_LOCATION');
if (!$fp) $fp = $destdir.DIRECTORY_SEPARATOR.'tmp/cache';
@mkdir($fp,0771,true);
touch($fp.DIRECTORY_SEPARATOR.'index.html');
$fp = constant('PUBLIC_CACHE_LOCATION');
if (!$fp) $fp = $destdir.DIRECTORY_SEPARATOR.'tmp/cache/public';
@mkdir($fp,0771,true);
touch($fp.DIRECTORY_SEPARATOR.'index.html');
$fp = constant('TMP_TEMPLATES_C_LOCATION');
if (!$fp) $fp = $destdir.DIRECTORY_SEPARATOR.'tmp/templates_c';
@mkdir($fp,0771,true);
touch($fp.DIRECTORY_SEPARATOR.'index.html');

function create_private_dir(string $basedir, string $reldir)
{
	$fp = $basedir.DIRECTORY_SEPARATOR.$reldir;
	if (!is_dir($fp)) {
		@mkdir($fp, 0771, true);
	} // else clear it!
	@touch($fp.DIRECTORY_SEPARATOR.'index.html');
}

$config = $app->get_config(); //more-or-less same as $siteinfo[]
//
// create the assets (however named) folders tree
//
verbose_msg(lang('install_createassets'));
$name = $config['assetsdir'] ?? 'assets';
$bp = $destdir.DIRECTORY_SEPARATOR.$name;
$name = $config['pluginsdir'] ?? 'simple_plugins';
create_private_dir($bp,'admin_custom');
create_private_dir($bp,'configs');
create_private_dir($bp,'css');
create_private_dir($bp,'images');
create_private_dir($bp,'module_custom');
create_private_dir($bp,'modules'); // for non-core modules during installation at least
create_private_dir($bp,'plugins');
create_private_dir($bp,'resources');
create_private_dir($bp,$name); //UDTfiles
create_private_dir($bp,'templates');

foreach ([
	$destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'modules',
	$bp.DIRECTORY_SEPARATOR.'modules',
] as $fp) {
	file_put_contents ($fp.DIRECTORY_SEPARATOR.'DO NOT DELETE', <<<EOS
This directory is used during system install | upgrade | refresh but otherwise should contain just this file, and a dummy index.html.

EOS
);
}

//
// some of the system-wide default settings
//
verbose_msg(lang('install_initsiteprefs'));
$cachtype = $wiz->get_data('cachemode');
$corenames = $config['coremodules'];
$cores = implode(',', $corenames);
$theme = reset(AdminTheme::GetAvailableThemes());
$schema = $app->get_dest_schema();
$helpurl = (!empty($siteinfo['supporturl'])) ? $siteinfo['supporturl'] : '';
$salt = Crypto::random_string(16, true);
$r = substr($salt, 0, 2);
$s = Crypto::random_string(32, false, true);
$uuid = strtr($s, '+/', $r);
$ultras = json_encode(['Modify Database','Modify Database Content','Modify Restricted Files','Remote Administration']);

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
	'loginsalt' => $salt,
	'logintheme' => $theme,
	'metadata' => '<meta name="Generator" content="CMS Made Simple - Copyright (C) 2004-' . date('Y') . '. All rights reserved." />'."\n".'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n",
	'password_level' => 0, // min-strength enumerator
	'password_life' => 0, // lifetime (days)
	'schema_version' => $schema,
	'site_help_url' => $helpurl,
	'site_uuid' => $uuid, // almost-certainly-unique signature of this site (see also siteuuid-file)
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
	AppParams::set($name, $val);
}

// siteuuid-file: content = 72 random-bytes without any NUL char
$s = Crypto::random_string(72); //max byte-length of BCRYPT passwords
$p = -1;
while (($p = strpos($s, '\0', $p+1)) !== false) {
	$c = crc32(substr($s, 0, $p) . 'A') & 0xff;
	$s[$p] = $c;
}
$fp = $bp.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'siteuuid.dat';
file_put_contents($fp, $s);
chmod($fp, 0400); //TODO to suit current|server process c.f. config.php

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
foreach ([
//	'Add Pages', >CM
//	'Add Templates', //TODO migrate to 'Modify Templates'
//	'Manage All Content', >CM
//	'Manage Designs', >DM
	['Manage Groups', 'Manage user-group existence, properties, membership'],
	'Manage My Account',
	'Manage My Bookmarks',
	'Manage My Settings',
	'Manage Stylesheets',
	'Manage Users',
//	'Modify Any Page', >CM
	['Modify Database', 'Change database tables existence, structure'],
	['Modify Database Content', 'Modify recorded data via SSH'], // add/remove/update stored data - for remote management, sans admin console
	'Modify Events',
	'Manage Simple Plugins',
	'Modify Files',
	'Modify Modules',
	'Modify Permissions',
	['Modify Restricted Files', 'Modify site-operation files via SSH'],
//	'Modify Site Assets', no deal !!
	'Modify Site Preferences',
	'Modify Templates',
	['Remote Administration', 'Site administration via SSH'],  //for remote management, sans admin console kinda Modify Database Content + Modify Restricted Files
//	'Remove Pages', >CM
//	'Reorder Content', >CM
	'View Tag Help',
	] as $one_perm) {
  $permission = new Permission();
  $permission->source = 'Core';
  if (is_array($one_perm)) {
	  $permission->name = $one_perm[0];
	  $permission->text = $one_perm[1];
  } else {
	  $permission->name = $one_perm;
	  $permission->text = ucfirst($one_perm);
  }
  try {
	$permission->save();
	$all_perms[$one_perm] = $permission;
  } catch (Throwable $t) {
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
$group->GrantPermission('Manage Simple Plugins');
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
$ops = UserOperations::get_instance();
$admin_user = new User();
$admin_user->username = $adminaccount['username'];
$admin_user->firstname = 'Site';
$admin_user->lastname = 'Administrator';
if (!empty($adminaccount['emailaddr'])) { $admin_user->email = $adminaccount['emailaddr']; }
else { $admin_user->email = ''; }
$admin_user->active = 1;
//$admin_user->adminaccess = 1;
$admin_user->password = $ops->PreparePassword($adminaccount['password']);
$admin_user->Save();

$ops->AddMemberGroup($admin_user->id,$gid1);
UserParams::set_for_user($admin_user->id,'wysiwyg','MicroTiny'); // TODO if MicroTiny present -the only user-preference we need now

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
