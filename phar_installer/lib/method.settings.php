<?php
/*
Copyright (C) 2019-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\Crypto;
use CMSMS\Events;
use CMSMS\Group;
use CMSMS\Lone;
use CMSMS\Permission;
use CMSMS\User;
use CMSMS\UserParams;
use function cms_installer\get_server_permissions;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\rrmdir;
use function cms_installer\startswith;

// vars set in includer: $admin_user, $choices[], $wiz, $app, $destdir etc

//
// create tmp directories and included index.html's
//
verbose_msg(lang('install_createtmpdirs'));

function touch_tree(string $from, string $destdir)
{
    $l = strlen($destdir);
    do {
        if (!is_file($from.DIRECTORY_SEPARATOR.'index.php')) {
            touch($from.DIRECTORY_SEPARATOR.'index.html');
        }
        $fp = dirname($from);
        if (strlen($fp) <= $l || !startswith($fp, $destdir)) {
            break;
        }
        $from = $fp; // iterate (upwards)
    } while (is_dir($from));
}

$modes = get_server_permissions();
$dirmode = $modes[3]; // folder read + write + access
$fp = constant('TMP_CACHE_LOCATION');
if (!$fp) {
    $fp = joinpath($destdir, 'tmp', 'cache');
}
@mkdir($fp, $dirmode, true);
touch_tree($fp, $destdir);
$fp = constant('PUBLIC_CACHE_LOCATION');
if (!$fp) {
    $fp = joinpath($destdir, 'tmp', 'cache', 'public');
}
@mkdir($fp, $dirmode, true);
touch_tree($fp, $destdir);
$fp = constant('TMP_TEMPLATES_C_LOCATION');
if (!$fp) {
    $fp = joinpath($destdir, 'tmp', 'templates_c');
}
@mkdir($fp, $dirmode, true);
touch_tree($fp, $destdir);

function create_private_dir(string $basedir, string $reldir, int $mode)
{
    $fp = $basedir.DIRECTORY_SEPARATOR.$reldir;
    if (!is_dir($fp)) {
        @mkdir($fp, $mode, true);
    } else {
        @chmod($fp, $mode); // TODO recurse all folders
    }
    @touch($fp.DIRECTORY_SEPARATOR.'index.html');
}

//deprecated from 3.0 support modules etc which use stuff in old folders
function create_deprec_links(string $basedir)
{
    foreach ([
    ['styles', 'css'],
    ['media', 'images'],
    ] as $names) {
        $tp = $basedir.DIRECTORY_SEPARATOR.$names[1];
        if (is_file($tp)) {
            unlink($tp);
        } elseif (is_dir($tp)) {
            rrmdir($tp);
        }
        $fp = $basedir.DIRECTORY_SEPARATOR.$names[0];
        symlink($fp, $tp);
    }
}

//
// create the lib folders tree
//
//verbose_msg(lang('install_create TODO'));
$bp = $destdir.DIRECTORY_SEPARATOR.'lib';
foreach ([
    'classes',
    'font',
    'jquery',
    'js',
    'lang',
    'layouts',
    'media',
    'nls',
    'plugins',
    'security',
    'styles',
    'vendor',
] as $name) {
    //TODO suitable mode e.g. read-only for these dirs
    create_private_dir($bp, $name, $dirmode);
}

create_deprec_links($bp);

$config = $app->get_config(); //more-or-less same as $choices[]
//
// create the assets (however named) folders tree
//
verbose_msg(lang('install_createassets'));
$r = $config['assetsdir'] ?? 'assets';
$bp = $destdir.DIRECTORY_SEPARATOR.$r;
$s = $config['pluginsdir'] ?? 'user_plugins';  //UDTfiles
foreach ([
    'admin_custom',
    'classes',
    'configs', // see also admin/configs
    'font',
    'jobs',
    'js',
    'layouts',
    'media', //TODO + deprecated 'images' symlink?
    'module_custom',
    'modules', // for non-core modules during installation at least
    'plugins',
//  'resources',
    'styles', //TODO + deprecated 'css' symlink?
    'themes',
    $s,
//  'vendor', needed?
] as $name) {
    //TODO suitable mode e.g. read-only for these dirs
    create_private_dir($bp, $name, $dirmode);
}

create_deprec_links($bp);

status_msg(lang('install_requireddata'));

//
// some of the system-wide default settings
//
verbose_msg(lang('install_initsiteprefs'));
$cachtype = $wiz->get_data('cachemode');
$corenames = $config['coremodules'];
$cores = implode(',', $corenames);
$global_umask = substr(decoct(~$dirmode), 0, 3);  // read + write + access/exec for files or dirs
if ($global_umask[0] !== '0') {
    $global_umask = '0'.$global_umask;
}
$list = AdminTheme::GetAvailableThemes(); // need ref for reset()
$theme = reset($list);
$schema = $app->get_dest_schema();
$sysname = $app->get_dest_name(); // or else 'Anonymous Alfred' ...
$sysver = $app->get_dest_version(); // or else '0.0.0' ...
$helpurl = (!empty($choices['supporturl'])) ? $choices['supporturl'] : '';
$salt = Crypto::random_string(16, true);
$r = substr($salt, 0, 2);
$s = Crypto::random_string(32, false, true);
$uuid = strtr($s, '+/', $r);
$ultras = json_encode(['Modify Database', 'Modify Database Content', 'Modify Restricted Files', 'Remote Administration']);

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
    'cms_schema_version' => $schema,
    'cms_version' => $sysver, // ultimate source of CMSMS version value
    'cms_version_name' => $sysname, // public identifier for CMSMS version
    'coremodules' => $cores, // aka ModuleOperations::CORENAMES_PREF
    'current_theme' => '', // frontend theme name
    'date_format' => 'j %B Y', // mixed format
    'datetime_format' => 'j %B Y g:i %P', // ditto
    'defaultdateformat' => '%e %B %Y %l:%M %P', // localizable strftime()-compatible format OR %#e ... on Windows ?
    'enablesitedownmessage' => 0, // deprecated since 3.0 use site_downnow
    'frontendlang' => 'en_US',
    'frontendwysiwyg' => 'HTMLEditor',
    'global_umask' => $global_umask,
    'jobinterval' => 180, // min-gap between job-processing's 1 .. 600 seconds
    'joblastrun' => 0,
    'jobtimeout' => 5, // max jobs execution-time 2 .. 120 seconds
    'joburl' => '', // custom url for job processing
    'lock_refresh' => 120,
    'lock_timeout' => 60,
    'loginmodule' => '', // TODO CMSMS\ModuleOperation::STD_LOGIN_MODULE
    'loginprocessor' => '', // login UI defined by current theme
    'loginsalt' => $salt,
    'logintheme' => $theme,
    'metadata' => '<meta name="Generator" content="CMS Made Simple - Copyright (C) 2004-' . date('Y') . '. All rights reserved." />'."\n".'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n",
    'password_level' => 0, // p/w policy-type enumerator
//    'password_life' => 0, // p/w lifetime (days) NO TIMEOUT SUPPORT
    'site_help_url' => $helpurl,
    'site_uuid' => $uuid, // almost-certainly-unique signature of this site (see also siteuuid-file)
    'sitedownexcludeadmins' => 0,
    'sitedownexcludes' => '',
    'sitedownmessage' => '',
    'site_downnow' => 0, //see also deprecated enablesitedownmessage
    'site_logo' => '',
//    'sitemask' => '', for old (md5-hashed) admin-user passwords - useless in new installs
    'sitename' => $choices['sitename'],
    'smarty_cachelife' => -1, // smarty default
    'smarty_cachemodules' => 0, // CSMS2-compatible
    'smarty_cacheusertags' => 0, // CSMS2-compatible
    'smarty_compilecheck' => 1, //see also deprecated use_smartycompilecheck
    'thumbnail_height' => 96,
    'thumbnail_width' => 96,
    'ultraroles' => $ultras,
    'use_smartycompilecheck' => 1, //deprecated since 3.0 use smarty_compilecheck
    'username_level' => 0, // policy-type enumerator
    'wysiwyg' => 'HTMLEditor',
] as $name => $val) {
    AppParams::set($name, $val);
}

// siteuuid-file: content = 72 random-bytes without any NUL char
//verbose_msg(lang('install_TODO'));
$s = Crypto::random_string(72); //max byte-length of BCRYPT passwords
$p = -1;
while (($p = strpos($s, '\0', $p + 1)) !== false) {
    $c = crc32(substr($s, 0, $p) . 'A') & 0xff;
    $s[$p] = $c;
}
$fp = $bp.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'siteuuid.dat';
file_put_contents($fp, $s);
chmod($fp, $modes[0]); // read-only

//$query = 'INSERT INTO '.CMS_DB_PREFIX.'version VALUES (CURRENT_SCHEMA)';
//$db->execute($query);
//verbose_msg(lang('install_setschemaver'));

//
// permissions
// note: some of these have been exported to ContentManager or DesignManager install routines.
//
verbose_msg(lang('install_initsiteperms'));
//$all_perms = [];
foreach ([
    'Clear Admin Log',
//    'Add Pages', >CM
//    'Add Templates', //TODO migrate to 'Modify Templates'
//    'Manage All Content', >CM
//    'Manage Designs', >DM
    ['Manage Groups', 'Manage user-group existence, properties, membership'],
//    ['Manage Jobs', 'Manage asynchronous jobs'],
    'Manage My Account',
    'Manage My Bookmarks',
    'Manage My Settings',
    'Manage Stylesheets',
    'Manage Users',
    'Manage User Plugins', // TODO description
//    'Modify Any Page', >CM
    ['Modify Database', 'Change database tables existence, structure'],
    ['Modify Database Content', 'Modify recorded data via SSH'], // add/remove/update stored data - for remote management, sans admin console
    'Modify Events',
    'Modify Files',
    'Modify Modules',
    'Modify Permissions',
    ['Modify Restricted Files', 'Modify site-operation files'], // i.e. outside the uploade tree
//    'Modify Site Assets', no deal !!
    'Modify Site Preferences',
    'Modify Templates',
    'Modify Themes', //>TM ?
    ['Remote Administration', 'Site administration via SSH'],  //for remote management, sans admin console kinda Modify Database Content + Modify Restricted Files
//    'Remove Pages', >CM
//    'Reorder Content', >CM
    'View Admin Log',
    ['View Restricted Files', 'Inspect site-operation files'],
    'View Tag Help',
    'View UserTag Help',
    ] as $one_perm) {
    $permission = new Permission();
    if (is_array($one_perm)) {
        $permission->name = $one_perm[0];
        $permission->desc = $one_perm[1];
    } else {
        $permission->name = $one_perm;
    }
    try {
        $permission->save();
//        $all_perms[$one_perm] = $permission;
    } catch (Throwable $t) {
        // nothing here (incl. ignore duplicate permission)
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
$group->GrantPermission('Manage User Plugins');
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
$ops = Lone::get('UserOperations');
$admin_user = new User();
$admin_user->username = $adminaccount['username'];
$admin_user->firstname = 'Site';
$admin_user->lastname = 'Administrator';
if (!empty($adminaccount['emailaddr'])) {
    $admin_user->email = $adminaccount['emailaddr'];
} else {
    $admin_user->email = '';
}
$admin_user->active = 1;
//$admin_user->adminaccess = 1;
$admin_user->password = $ops->PreparePassword($adminaccount['password']);
$admin_user->Save();

$ops->AddMemberGroup($admin_user->id, $gid1);
UserParams::set_for_user($admin_user->id, 'wysiwyg', 'HTMLEditor'); // the only user-preference we need now
//UserParams::set_for_user($admin_user->id, 'wysiwyg_type', '');
//UserParams::set_for_user($admin_user->id, 'wysiwyg_theme', '');

//
// standard events
// some of these have been exported to ContentManager or DesignManager install routines.
//
verbose_msg(lang('install_initevents'));
foreach ([
/* >DM
    'AddDesignPost',
    'AddDesignPre',
*/
    'AddGroupPost',
    'AddGroupPre',
    'AddStylesheetPost',
    'AddStylesheetPre',
    'AddTemplatePost',
    'AddTemplatePre',
    'AddTemplateTypePost',
    'AddTemplateTypePre',
    'AddUserPost',
    'AddUserPre',
    'ChangeGroupAssignPost',
    'ChangeGroupAssignPre',
    'CheckUserData',
/* >CM
    'ContentDeletePost',
    'ContentDeletePre',
    'ContentEditPost',
    'ContentEditPre',

    'ContentPostCompile',
    'ContentPostRender',
    'ContentPreCompile',
    'ContentPreRender', // 2.2
*/
/* >DM
    'DeleteDesignPost',
    'DeleteDesignPre',
*/
    'DeleteGroupPost',
    'DeleteGroupPre',
    'DeleteStylesheetPost',
    'DeleteStylesheetPre',
    'DeleteTemplatePost',
    'DeleteTemplatePre',
    'DeleteTemplateTypePost',
    'DeleteTemplateTypePre',
    'DeleteUserPost',
    'DeleteUserPre',
/* >DM
    'EditDesignPost',
    'EditDesignPre',
*/
    'EditGroupPost',
    'EditGroupPre',
    'EditStylesheetPost',
    'EditStylesheetPre',
    'EditTemplatePost',
    'EditTemplatePre',
    'EditTemplateTypePost',
    'EditTemplateTypePre',

    'EditUserPost',
    'EditUserPre',

    'JobFailed', // aka JobOperations::EVT_ONFAILEDJOB

    'LoginFailed',
    'LoginPost',
    'LogoutPost',
    'LostPassword',
    'LostPasswordReset',

    'ModuleInstalled',
    'ModuleUninstalled',
    'ModuleUpgraded',
    'MetadataPostRender',
    'MetadataPreRender',

    'PageTopPostRender',
    'PageTopPreRender',
    'PageHeadPostRender',
    'PageHeadPreRender',
    'PageBodyPostRender',
    'PageBodyPreRender',
    'PostRequest',

    'SmartyPostCompile',
    'SmartyPreCompile',

    'StylesheetPostCompile',
    'StylesheetPostRender',
    'StylesheetPreCompile',
    'TemplatePostCompile',
    'TemplatePreCompile',
    'TemplatePreFetch',
] as $s) {
    Events::CreateEvent('Core', $s);
}

Events::AddStaticHandler('Core', 'PostRequest', 'CMSMS\internal\JobOperations::begin_async_work', 'C', false);
Events::AddStaticHandler('Core', 'ModuleInstalled', 'CMSMS\internal\JobOperations::event_handler', 'C', false);
Events::AddStaticHandler('Core', 'ModuleUninstalled', 'CMSMS\internal\JobOperations::event_handler', 'C', false);
Events::AddStaticHandler('Core', 'ModuleUpgraded', 'CMSMS\internal\JobOperations::event_handler', 'C', false);
