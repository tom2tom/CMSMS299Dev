<?php
/*
Procedure to display and modify website preferences/settings
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//use CMSMS\MultiEditor;
//use CMSMS\internal\module_meta;
use CMSContentManager\ContentBase;
use CMSContentManager\contenttypes\Content;
use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\ContentOperations;
use CMSMS\CoreCapabilities;
use CMSMS\Crypto;
use CMSMS\FileType;
use CMSMS\FormUtils;
use CMSMS\HookOperations;
use CMSMS\Mailer;
use CMSMS\ModuleOperations;
use CMSMS\SysDataCache;
use CMSMS\Url;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('menu.php'.$urlext);
}

$userid = get_userid(); // <- Also checks login
$access = check_permission($userid, 'Modify Site Preferences');
$themeObject = Utils::get_theme_object();

if (!$access) {
    //TODO a push-notification $themeObject->RecordNotice('error', lang('needpermissionto', '"Modify Site Preferences"'));
    return;
}

/**
 * Interpret octal permissions $perms into human-readable strings
 *
 * @param int $perms The permissions to process
 * @return array strings for owner,group,other
 */
function siteprefs_interpret_permissions(int $perms) : array
{
    $owner = [];
    $group = [];
    $other = [];

    if ($perms & 0400) {
        $owner[] = lang('read');
    }
    if ($perms & 0200) {
        $owner[] = lang('write');
    }
    if ($perms & 0100) {
        $owner[] = lang('execute');
    }
    if ($perms & 0040) {
        $group[] = lang('read');
    }
    if ($perms & 0020) {
        $group[] = lang('write');
    }
    if ($perms & 0010) {
        $group[] = lang('execute');
    }
    if ($perms & 0004) {
        $other[] = lang('read');
    }
    if ($perms & 0002) {
        $other[] = lang('write');
    }
    if ($perms & 0001) {
        $other[] = lang('execute');
    }

    return [$owner,$group,$other];
}

/**
 * Interpret permissions $permsarr into a human-readable string
 *
 * @param array $permsarr 3-members
 * @return string
 */
function siteprefs_display_permissions(array $permsarr) : string
{
    $tmparr = [lang('owner'),lang('group'),lang('other')];
    if (count($permsarr) != 3) {
        return lang('permissions_parse_error');
    }

    $result = [];
    for ($i = 0; $i < 3; $i++) {
        $str = $tmparr[$i].': ';
        $str .= implode(',', $permsarr[$i]);
        $result[] = $str;
    }
    $str = implode('<br />&nbsp;&nbsp;', $result);
    return $str;
}

$errors = [];
$messages = [];

$config = AppSingle::Config();
$pretty_urls = $config['url_rewriting'] == 'none' ? 0 : 1;
$devmode = $config['develop_mode'];

$tpw = $_POST['mailprefs_password'] ?? ''; // preserve these verbatim
$tmsg = $_POST['sitedownmessage'] ?? '';
unset($_POST['mailprefs_password'], $_POST['sitedownmessage']); // if any
cms_specialchars_decode_array($_POST);

$seetab = (isset($_POST['active_tab'])) ? sanitizeVal($_POST['active_tab']) : '';

if (isset($_POST['testmail'])) {
    if (!AppParams::get('mail_is_set', 0)) {
        $errors[] = lang('error_mailnotset_notest');
    } elseif (empty($_POST['mailtest_testaddress'])) {
        $errors[] = lang('error_mailtest_noaddress');
    } else {
//        $addr = filter_input(INPUT_POST, 'mailtest_testaddress', FILTER_SANITIZE_EMAIL);
         //BUT PHP's FILTER_VALIDATE_EMAIL mechanism is not entirely reliable - see notes at https://www.php.net/manual/en/function.filter-var.php
        $addr = trim($_POST['mailtest_testaddress']);
        if ($addr && !is_email($addr)) {
            $errors[] = lang('error_mailtest_notemail');
        } elseif ($addr) {
            // we got an email, and we have settings.
            try {
                $mailer = new Mailer();
                $mailer->AddAddress($addr);
                $mailer->IsHTML(true);
                $mailer->SetBody(lang('mail_testbody', 'siteprefs'));
                $mailer->SetSubject(lang('mail_testsubject', 'siteprefs'));
                $mailer->Send();
                if ($mailer->IsError()) {
                    $errors[] = $mailer->GetErrorInfo();
                } else {
                    $messages[] = lang('testmsg_success');
                }
            } catch (Throwable $t) {
                $errors[] = $t->GetMessage();
            }
        }
    }
}

if (isset($_POST['testumask'])) {
    $testdir = TMP_CACHE_LOCATION;
    $testfile = $testdir.DIRECTORY_SEPARATOR.'dummy.tst';
    if (!is_writable($testdir)) {
        $errors[] = lang('errordirectorynotwritable');
    } else {
        @umask(octdec($global_umask));

        $fh = @fopen($testfile, 'w');
        if (!$fh) {
            $errors[] = lang('errorcantcreatefile').' ('.$testfile.')';
        } else {
            @fclose($fh);
            $filestat = stat($testfile);
            if ($filestat == false) {
                $errors[] = lang('errorcantcreatefile');
            }

            if (function_exists('posix_getpwuid')) {
                //function posix_getpwuid not available on WAMP systems
                $userinfo = @posix_getpwuid($filestat[4]);
                $username = $userinfo['name'] ?? lang('unknown');
                $permsstr = siteprefs_display_permissions(siteprefs_interpret_permissions($filestat[2]));
                $messages[] = sprintf('%s: %s<br />%s:<br />&nbsp;&nbsp;%s', lang('owner'), $username, lang('permissions'), $permsstr);
            } else {
                $errors[] = sprintf('%s: %s<br />%s:<br />&nbsp;&nbsp;%s', lang('owner'), 'N/A', lang('permissions'), 'N/A');
            }
            @unlink($testfile);
        }
    }
}

if (isset($_POST['submit'])) {
    if ($access) {
        switch ($seetab) {
            case 'general':
                $val = sanitizeVal(trim($_POST['sitename']), 0); // AND nl2br() ? striptags() ?
                AppParams::set('sitename', $val);
                $val = trim($_POST['site_logo']);
                if ($val) {
                    $tmp = (new Url())->sanitize($val);
                    AppParams::set('site_logo', $tmp);
                } else {
                    AppParams::set('site_logo', '');
                }
                $val = (!empty($_POST['frontendlang'])) ? sanitizeVal($_POST['frontendlang']) : '';
                AppParams::set('frontendlang', $val);
                // TODO sanitize metadata
                AppParams::set('metadata', $_POST['metadata']);
                $val = (!empty($_POST['logintheme'])) ? sanitizeVal($_POST['logintheme'], 3) : ''; // consistent with theme folder-name
                AppParams::set('logintheme', $val);
                $val = $_POST['defaultdateformat'];
                if ($val) { $val = preg_replace('~[^a-zA-Z%,.\-:/ ]~', '', trim($val)); } // strftime()-only formats 2.99 breaker?
                AppParams::set('defaultdateformat', $val);
                AppParams::set('thumbnail_width', filter_input(INPUT_POST, 'thumbnail_width', FILTER_SANITIZE_NUMBER_INT));
                AppParams::set('thumbnail_height', filter_input(INPUT_POST, 'thumbnail_height', FILTER_SANITIZE_NUMBER_INT));
                $val = (!empty($_POST['backendwysiwyg'])) ? sanitizeVal($_POST['backendwysiwyg'], 11, ':') : ''; // allow '::'
                if ($val) {
                    if (strpos($val, '::') !== false) {
                        $parts = explode('::', $val);
                        AppParams::set('wysiwyg', $parts[0]); //module
                        if ($parts[0] != $parts[1]) { AppParams::set('wysiwyg_type', $parts[1]); } //component
                        else { AppParams::set('wysiwyg_type', ''); }
                    } else {
                        AppParams::set('wysiwyg', $val); //aka rich-text editor
                        AppParams::set('wysiwyg_type', '');
                    }
                } else {
                    AppParams::set('wysiwyg', '');
                    AppParams::set('wysiwyg_type', '');
                }
                $val = sanitizeVal($_POST['wysiwygtheme'], 1); // OR 21 ?
                if ($val) {
                    $val = strtolower(strtr($val, ' ', '_')); // CHECKME
                }
                AppParams::set('wysiwyg_theme', $val);
                $val = (!empty($_POST['frontendwysiwyg'])) ? sanitizeVal($_POST['frontendwysiwyg'], 11, ':') : '';
                if ($val) {
                    if (strpos($val, '::') !== false) {
                        $parts = explode('::', $val);
                        AppParams::set('frontendwysiwyg', $parts[0]); //module
                        if ($parts[0] != $parts[1]) { AppParams::set('frontendwysiwyg_type', $parts[1]); } //component
                        else { AppParams::set('frontendwysiwyg_type', ''); }
                    } else {
                        AppParams::set('frontendwysiwyg', $val); //aka rich-text editor
                        AppParams::set('frontendwysiwyg_type', '');
                    }
                } else {
                    AppParams::set('frontendwysiwyg', '');
                    AppParams::set('frontendwysiwyg_type', '');
                }
                $val = (!empty($_POST['search_module'])) ? sanitizeVal($_POST['search_module'], 3) : '';
                AppParams::set('searchmodule', $val);
                break;
            case 'editcontent':
                if ($pretty_urls) {
                    AppParams::set('content_autocreate_urls', filter_input(INPUT_POST, 'content_autocreate_urls', FILTER_SANITIZE_NUMBER_INT));
                    AppParams::set('content_autocreate_flaturls', filter_input(INPUT_POST, 'content_autocreate_flaturls', FILTER_SANITIZE_NUMBER_INT));
                    AppParams::set('content_mandatory_urls', filter_input(INPUT_POST, 'content_mandatory_urls', FILTER_SANITIZE_NUMBER_INT));
                }
                AppParams::set('content_imagefield_path', sanitizeVal($_POST['content_imagefield_path'], 31));
                AppParams::set('content_thumbnailfield_path', sanitizeVal($_POST['content_thumbnailfield_path'], 31));
                AppParams::set('contentimage_path', sanitizeVal($_POST['contentimage_path'], 31));
                AppParams::set('content_cssnameisblockname', filter_input(INPUT_POST, 'content_cssnameisblockname', FILTER_SANITIZE_NUMBER_INT));
                $val = (!empty($_POST['basic_attributes'])) ?
                    implode(',', ($_POST['basic_attributes'])) : '';
                AppParams::set('basic_attributes', $val);
                $val = (!empty($_POST['disallowed_contenttypes'])) ?
                    implode(',', $_POST['disallowed_contenttypes']) : '';
                AppParams::set('disallowed_contenttypes', $val);
                break;
            case 'sitedown':
                $val = $_POST['sitedownexcludes'] ?? '';
                if ($val) {
                    //comma-separated sequence of IP addresses, each exact | range | subnet
                    //Hence 0-9a-f :,.-[]/ A-F also allowed, l/c preferred
                    $tmp = preg_replace('~[^0-9a-fA-F:,.\-\[\]/]~', '', $val);
                    $val = strtolower($tmp);
                }
                AppParams::set('sitedownexcludes', $val);
                AppParams::set('sitedownexcludeadmins', !empty($_POST['sitedownexcludeadmins']));

                $sitedown = !empty($_POST['site_downnow']);
                if ($tmsg || !$sitedown) {
                    // TODO sanitizeVal($tmsg, 22) for html incl. tags
                    $prevsitedown = AppParams::get('site_downnow', 0);
                    if (!$prevsitedown && $sitedown) {
                        audit('', 'Global Settings', 'Sitedown enabled');
                    } elseif ($prevsitedown && !$sitedown) {
                        audit('', 'Global Settings', 'Sitedown disabled');
                    }
                    AppParams::set('site_downnow', $sitedown);
                    AppParams::set('sitedownmessage', $tmsg);
                } else {
                    $errors[] = lang('error_sitedownmessage');
                }
                break;
            case 'mail':
                // gather mailprefs (except password - excised pre-$_POST-cleanup)
                $prefix = 'mailprefs_';
                foreach ($_POST as $key => $val) {
                    if (!startswith($key, $prefix)) {
                        continue;
                    }
                    $key = substr($key, strlen($prefix));
                    $mailprefs[$key] = sanitizeVal($val, 22);
                }
                // validate
                if ($mailprefs['from'] == '') {
                    $errors[] = lang('error_fromrequired');
                } elseif (!is_email($mailprefs['from'])) {
                    $errors[] = lang('error_frominvalid');
                }
                if ($mailprefs['mailer'] == 'smtp') {
                    if ($mailprefs['host'] == '') {
                        $errors[] = lang('error_hostrequired');
                    }
                    if ($mailprefs['port'] == '') {
                        $mailprefs['port'] = 25;
                    } // convenience.
                    if ($mailprefs['port'] < 1 || $mailprefs['port'] > 10240) {
                        $errors[] = lang('error_portinvalid');
                    }
                    if ($mailprefs['timeout'] == '') {
                        $mailprefs['timeout'] = 180;
                    }
                    if ($mailprefs['timeout'] < 1 || $mailprefs['timeout'] > 3600) {
                        $errors[] = lang('error_timeoutinvalid');
                    }
                    if ($mailprefs['smtpauth']) {
                        if ($mailprefs['username'] == '') {
                            $errors[] = lang('error_usernamerequired');
                        }
                        if ($tpw == '') {
                            $errors[] = lang('error_passwordrequired');
                        } else {
                            $val = sanitizeVal($tpw, 0);
                            if ($val == $tpw) {
                                $mailprefs['password'] = base64_encode(Crypto::encrypt_string(trim($tpw)));
                            } else {
                                $errors[] = lang('error_passwordinvalid');
                            }
                        }
                    }
                }
                // save.
                if (!$errors) {
                    AppParams::set('mail_is_set', 1);
                    AppParams::set('mailprefs', serialize($mailprefs));
                }
                break;
            case 'advanced':
                AppParams::set('loginmodule', sanitizeVal($_POST['login_module'], 3));
                $val = filter_input(INPUT_POST, 'lock_timeout', FILTER_SANITIZE_NUMBER_INT);
                if ($val != 0) $val = max(5, min(480, $val));
                AppParams::set('lock_timeout', $val);
                $val = filter_input(INPUT_POST, 'lock_refresh', FILTER_SANITIZE_NUMBER_INT);
                if ($val != 0) $val = max(30, min(3600, $val));
                AppParams::set('lock_refresh', $val);
                $val = filter_input(INPUT_POST, 'smarty_cachelife', FILTER_SANITIZE_NUMBER_INT);
                if ($val == 0) {
                    if (trim($_POST['smarty_cachelife']) === '') {
                        $val = -1;
                    }
                }
                AppParams::set('smarty_cachelife', $val);
                AppParams::set('smarty_cachemodules', filter_input(INPUT_POST, 'smarty_cachemodules', FILTER_SANITIZE_NUMBER_INT));
                AppParams::set('smarty_cacheusertags', !empty($_POST['smarty_cacheusertags']));
                AppParams::set('smarty_compilecheck', !empty($_POST['smarty_compilecheck']));
//               AdminUtils::clear_cached_files();

                $val = sanitizeVal($_POST['syntaxtype'], 11, ':'); // allow '::'
                if ($val) {
                    if (strpos($val, '::') !== false) {
                        $parts = explode('::', $val);
                        AppParams::set('syntaxhighlighter', $parts[0]); //module
                        if ($parts[0] != $parts[1]) { AppParams::set('syntax_type', $parts[1]); }//component
                    } else {
                        AppParams::set('syntaxhighlighter', $val);
                        AppParams::set('syntax_type', '');
                    }
                } else {
                    AppParams::set('syntaxhighlighter', '');
                    AppParams::set('syntax_type', '');
                }
                $val = sanitizeVal($_POST['syntaxtheme'], 1); // OR 21?
                if ($val) {
                    $val = strtolower(strtr($val, ' ', '_')); // CHECKME
                }
                AppParams::set('syntax_theme', $val);
                if ($devmode) {
                    $val = trim($_POST['help_url']);
                    if ($val) {
                        $tmp = (new Url())->sanitize($val);
                        AppParams::set('site_help_url', $tmp);
                    } else {
                        AppParams::set('site_help_url', '');
                    }
                }
//              AppParams::set('xmlmodulerepository', $_POST['xmlmodulerepository']);
                AppParams::set('checkversion', !empty($_POST['checkversion']));
                $val = $_POST['global_umask'] ?? ''; // maybe-octal integer as a string
                AppParams::set('global_umask', ($val) ? preg_replace('/^0-9/', '', $val) : $val);
                AppParams::set('allow_browser_cache', !empty($_POST['allow_browser_cache']));
                AppParams::set('browser_cache_expiry', filter_input(INPUT_POST, 'browser_cache_expiry', FILTER_SANITIZE_NUMBER_INT));
                AppParams::set('auto_clear_cache_age', filter_input(INPUT_POST, 'auto_clear_cache_age', FILTER_SANITIZE_NUMBER_INT));
                //TODO this in relevant module
                AppParams::set('adminlog_lifetime', filter_input(INPUT_POST, 'adminlog_lifetime', FILTER_SANITIZE_NUMBER_INT));
                break;
        } //switch tab

        SysDataCache::get_instance()->release('site_preferences');

        if (!$errors) {
            // put mention into the admin log
            audit('', 'Global Settings', 'Edited');
            $messages[] = lang('siteprefsupdated');
        }
    } else {
        $errors[] = lang('noaccessto', 'Modify Site Permissions');
    }
}

//$contentimage_useimagepath = 0;
//$sitedownmessagetemplate = '-1';

/**
 * Get old/new preferences
 */

//TODO this in relevant module
$adminlog_lifetime = AppParams::get('adminlog_lifetime', 2592000); //3600*24*30

$allow_browser_cache = AppParams::get('allow_browser_cache', 0);
$auto_clear_cache_age = AppParams::get('auto_clear_cache_age', 0);
$basic_attributes = AppParams::get('basic_attributes', null);
$browser_cache_expiry = AppParams::get('browser_cache_expiry', 60);
$checkversion = AppParams::get('checkversion', 1);
//CHECKME content manager module for these ?
$content_autocreate_flaturls = AppParams::get('content_autocreate_flaturls', 0);
$content_autocreate_urls = AppParams::get('content_autocreate_urls', 0);
$content_cssnameisblockname = AppParams::get('content_cssnameisblockname', 1);
$content_imagefield_path = AppParams::get('content_imagefield_path', '');
$content_mandatory_urls = AppParams::get('content_mandatory_urls', 0);
$content_thumbnailfield_path = AppParams::get('content_thumbnailfield_path', '');
$contentimage_path = AppParams::get('contentimage_path', '');
$defaultdateformat = AppParams::get('defaultdateformat', '');
$disallowed_contenttypes = AppParams::get('disallowed_contenttypes', '');

$frontendlang = AppParams::get('frontendlang', '');

$wysiwygmodule = AppParams::get('frontendwysiwyg', '');
$wysiwygtype = AppParams::get('frontendwysiwyg_type', '');
$frontendwysiwyg = ($wysiwygtype) ? $wysiwygmodule .'::'.$wysiwygtype : $wysiwygmodule;

$global_umask = AppParams::get('global_umask');
if ($global_umask === '') {
    $allmode = get_server_permissions()[3]; // read + write + access/exec for files or dirs
    $global_umask = substr(decoct(~$allmode), 0, 3);
} elseif ($global_umask[0] !== '0') {
    $global_umask = substr(decoct((int)$global_umask), 0, 3); // OR ,4?
}
if ($global_umask[0] !== '0') {
    $global_umask = '0'.$global_umask;
}
$lock_refresh = (int)AppParams::get('lock_refresh', 120);
$lock_timeout = (int)AppParams::get('lock_timeout', 60);
$login_module = AppParams::get('loginmodule', '');
$logintheme = AppParams::get('logintheme', '');
$mail_is_set = AppParams::get('mail_is_set', 0);
$metadata = AppParams::get('metadata', '');
$search_module = AppParams::get('searchmodule', 'Search');
if ($devmode) {
    $help_url = AppParams::get('site_help_url', '');
}
$sitedown = AppParams::get('site_downnow', 0);
$sitedownexcludeadmins = AppParams::get('sitedownexcludeadmins', 0);
$sitedownexcludes = AppParams::get('sitedownexcludes', '');
$sitedownmessage = AppParams::get('sitedownmessage', '<p>The website is currently off line. Check back later.</p>'); // no specialchar(), used in textarea ?
$sitelogo = AppParams::get('site_logo', '');
$sitename = AppParams::get('sitename', 'CMSMS Website'); //cms_specialchar() ?
$smarty_cachelife = AppParams::get('smarty_cachelife', -1);
if ($smarty_cachelife < 0) {
    $smarty_cachelife = '';
}
$smarty_cachemodules = AppParams::get('smarty_cachemodules', 0); // default value back-compatible
$smarty_cacheusertags = AppParams::get('smarty_cacheusertags', false);
$smarty_compilecheck = AppParams::get('smarty_compilecheck', 1);
$syntaxmodule = AppParams::get('syntaxhighlighter');
$syntaxtype = AppParams::get('syntax_type');
$syntaxer = ($syntaxtype) ? $syntaxmodule .'::'.$syntaxtype : $syntaxmodule;
$syntaxtheme = AppParams::get('syntax_theme', '');
$thumbnail_height = AppParams::get('thumbnail_height', 96);
$thumbnail_width = AppParams::get('thumbnail_width', 96);
//$xmlmodulerepository = AppParams::get('xmlmodulerepository', '');
$wysiwygmodule = AppParams::get('wysiwyg', ''); //aka rich-text editor module
$wysiwygtype = AppParams::get('wysiwyg_type', '');
$wysiwyg = ($wysiwygtype) ? $wysiwygmodule .'::'.$wysiwygtype : $wysiwygmodule;
$wysiwygtheme = AppParams::get('wysiwyg_theme', '');

$mailprefs = [
  'mailer'=>'mail',
  'host'=>'localhost', //actual >> (new Url())->santitize()
  'port'=>25,
  'from'=>'root@localhost.localdomain', //actual >> FILTER_SANITIZE_EMAIL ? too strict ?
  'fromuser'=>'CMS Administrator', //actual >> cms_specialchars() ? sanitizeVal() ?
  'sendmail'=>'/usr/sbin/sendmail', //actual >> sanitizeVal() ?
  'smtpauth'=>0,
  'username'=>'', //actual >> cms_specialchars() ? sanitizeVal() ?
  'password'=>'', //actual >> sanitizeVal() ?
  'secure'=>'',
  'timeout'=>60,
  'charset'=>'utf-8',
];
$tmp = AppParams::get('mailprefs');
if ($tmp) {
    $mailprefs = array_merge($mailprefs, unserialize($tmp, ['allowed_classes' => false]));
}

foreach ([
  'from',
  'fromuser',
  'username',
] as $param) {
    $mailprefs[$param] = cms_specialchars($mailprefs[$param]);
}
$mailprefs['password'] = Crypto::decrypt_string(base64_decode($mailprefs['password']));

$modules = ModuleOperations::get_modules_with_capability(CoreCapabilities::SITE_SETTINGS);
if ($modules) {
    // load them, if not already done
    foreach ($modules as $i => $modname) {
        $modules[$i] = Utils::get_module($modname);
    }
    $list = HookOperations::do_hook_accumulate('ExtraSiteSettings');
    // assist the garbage-collector
    foreach ($modules as $i => $modname) {
        $modules[$i] = null;
    }
    $externals = [];
    foreach ($list as $info) {
        // TODO any adjustments
        if (empty($info['text'])) {
            $info['text'] = lang('settings_linktext');
        }
        $externals[] = $info;
    }
    //$col = new Collator(TODO);
    uasort($externals, function($a, $b) { // use($col)
        return strnatcmp($a['title'], $b['title']); //TODO return $col->compare($a['title'],$b['title']);
    });
    $smarty->assign('externals', $externals);
}

/**
 * Build page
 */

$dir = $config['image_uploads_path'];
$filepicker = Utils::get_filepicker_module();
if ($filepicker) {
    $tmp = $filepicker->get_default_profile($dir, $userid);
    $profile = $tmp->overrideWith(['top'=>$dir, 'type'=>FileType::IMAGE]);
    $logoselector = $filepicker->get_html('image', $sitelogo, $profile);
    $logoselector = str_replace(['name="image"', 'size="50"', 'readonly="readonly"'], ['id="sitelogo" name="site_logo"', 'size="60"', ''], $logoselector);
}
else {
    $logoselector = create_file_dropdown('image', $dir, $sitelogo, 'jpg,jpeg,png,gif', '', true, '', 'thumb_', 0, 1);
}

// Error if cache folders are not writable
if (!is_writable(TMP_CACHE_LOCATION) || !is_writable(TMP_TEMPLATES_C_LOCATION)) {
    $errors[] = lang('cachenotwritable');
}

if ($errors) {
    $themeObject->RecordNotice('error', $errors);
}
if ($messages) {
    $themeObject->RecordNotice('success', $messages);
}

// no ScripsMerger due to multi-$vars, hence lots of change?
$baseurl = CMS_ASSETS_URL.'/js';
$js = <<<EOS
 <script type="text/javascript" src="{$baseurl}/jquery-inputCloak.min.js"></script>
EOS;
add_page_headtext($js);
//$nonce = get_csp_token();
$submit = lang('submit');
$cancel = lang('cancel');
$editortitle = lang('syntax_editor_deftheme');
$nofile = json_encode(lang('nofiles'));
$badfile = json_encode(lang('errorwrongfile'));
$confirm = json_encode(lang('siteprefs_confirm'));

$out = <<<EOS
<script type="text/javascript">
//<![CDATA[
function on_mailer() {
 switch ($('#mailer').val()) {
  case 'mail':
   $('#set_smtp').find('input,select').prop('disabled',true);
   $('#set_sendmail').find('input,select').prop('disabled',true);
   break;
  case 'smtp':
   $('#set_sendmail').find('input,select').prop('disabled',true);
   $('#set_smtp').find('input,select').prop('disabled',false);
   break;
  case 'sendmail':
   $('#set_smtp').find('input,select').prop('disabled',true);
   $('#set_sendmail').find('input,select').prop('disabled',false);
   break;
 }
}
$(function() {
 on_mailer();
 $('#password').inputCloak({
  type:'see1',
  symbol:'\u25CF'
 });
 $('#mailertest').on('click', function(e) {
  cms_dialog($('#testpopup'),{
   modal: true,
   width: 'auto'
  });
  return false;
 });
 $('#testcancel').on('click', function(e) {
  cms_dialog($('#testpopup'),'close');
  return false;
 });
 $('#testsend').on('click', function(e) {
  cms_dialog($('#testpopup'),'close');
  $(this).closest('form').submit();
 });
 var b = $('#importbtn');
 if(b.length > 0) {
  b.on('click', function() {
   cms_dialog($('#importdlg'), {
    modal: true,
    buttons: {
     {$submit}: function() {
      var file = $('#xml_upload').val();
      if(file.length === 0) {
       cms_alert($nofile);
      } else {
       var ext = file.split('.').pop().toLowerCase();
       if(ext !== 'xml') {
        cms_alert($badfile);
       } else {
        $(this).dialog('close');
        $('#importform').submit();
       }
      }
     },
     {$cancel}: function() {
      $(this).dialog('close');
     }
    },
    width: 'auto'
   });
  });
  $('#deletebtn').on('click', function() {
   cms_dialog($('#deletedlg'), {
    modal: true,
    buttons: {
     {$submit}: function() {
      $(this).dialog('close');
      $('#deleteform').submit();
     },
     {$cancel}: function() {
      $(this).dialog('close');
     }
    },
    width: 'auto'
   });
  });
 }
 b = $('#exportbtn');
 if(b.length > 0) {
  b.on('click', function() {
   cms_dialog($('#exportdlg'), {
    modal: true,
    width: 'auto',
    buttons: {
     {$submit}: function() {
      $(this).dialog('close');
      $('#exportform').submit();
     },
     {$cancel}: function() {
      $(this).dialog('close');
     }
    }
   });
  });
 }
 $('#theme_help .cms_helpicon').on('click', function() {
  var key = $('input[name="editortype"]:checked').attr('data-themehelp-key');
  if (key) {
   var self = this;
   $.get(cms_data.ajax_help_url, {
    key: key
   }, function(text) {
    var data = {
     cmshelpTitle: '$editortitle'
    };
    cms_help(self, data, text);
   });
  }
 });
 $('#mailer').on('change', on_mailer);
 $('[name="submit"]').on('click', function(e) {
  e.preventDefault();
  cms_confirm_btnclick(this, $confirm);
  return false;
 });
});
//]]>
</script>

EOS;
add_page_foottext($out);

$modops = ModuleOperations::get_instance();
$smarty = AppSingle::Smarty();

$tmp = [-1 => lang('none')];
$modules = $modops->GetCapableModules(CoreCapabilities::SEARCH_MODULE);
if ($modules) {
    for ($i = 0, $n = count($modules); $i < $n; $i++) {
        $tmp[$modules[$i]] = $modules[$i];
    }
    $smarty->assign('search_module', $search_module);
} else {
    $smarty->assign('search_module', lang('none'));
}
$smarty->assign('search_modules', $tmp);

if ($devmode) {
    $smarty->assign('help_url', $help_url);
}

$tmp = ['' => lang('theme')];
$modules = $modops->GetCapableModules(CoreCapabilities::LOGIN_MODULE);
if ($modules) {
    for ($i = 0, $n = count($modules); $i < $n; $i++) {
        if ($modules[$i] == $modops::STD_LOGIN_MODULE) {
            $tmp[$modules[$i]] = lang('default');
        } else {
            $tmp[$modules[$i]] = $modules[$i];
        }
    }
}
$smarty->assign('login_module', $login_module)
  ->assign('login_modules', $tmp);

$maileropts = [
  'mail' => 'mail',
  'sendmail' => 'sendmail',
  'smtp' => 'smtp',
];
$secopts = [
  '' => lang('none'),
  'ssl' => 'SSL',
  'tls' => 'TLS',
];
$smarty->assign([
  'maileritems' => $maileropts,
  'secure_opts' => $secopts,
  'mail_is_set' => $mail_is_set,
  'mailprefs' => $mailprefs,
  'languages' => get_language_list(),
  'tab' => $seetab,
  'pretty_urls' => $pretty_urls,
]);
$tmp = AdminTheme::GetAvailableThemes();
if ($tmp) {
    $smarty->assign('themes', $tmp)
      ->assign('logintheme', AppParams::get('logintheme', reset($tmp)))
      ->assign('exptheme', $config['develop_mode']);
} else {
    $smarty->assign('themes', null)
      ->assign('logintheme', null);
}
$smarty->assign('modtheme', check_permission($userid, 'Modify Site Preferences'));

// Rich-text (html) editors
$tmp = $modops->GetCapableModules(CoreCapabilities::WYSIWYG_MODULE);
if ($tmp) {
  $editors = []; //for backend
  $fronts = [];
  for ($i = 0, $n = count($tmp); $i < $n; ++$i) {
    $ob = Utils::get_module($tmp[$i]);
    if (method_exists($ob, 'ListEditors')) { //aka ($ob instanceof MultiEditor)
      $all = $ob->ListEditors();
      foreach ($all as $editor=>$val) {
        $one = new stdClass();
        $one->label = $ob->Lang(strtolower($editor).'_friendlyname');
        $one->value = $val; // as module::editor
        list($modname, $edname) = explode('::', $val);
        list($realm, $key) = $ob->GetMainHelpKey($edname);
        if ($key) {
          if (!$realm) { $realm = $modname; }
          $one->mainkey = $realm.'__'.$key;
        } else {
          $one->mainkey = null;
        }
        list($realm, $key) = $ob->GetThemeHelpKey($edname);
        if ($key) {
          if (!$realm) { $realm = $modname; }
            $one->themekey = $realm.'__'.$key;
          } else {
            $one->themekey = null;
        }
        if ($modname == $wysiwygmodule && $edname == $wysiwygtype) { $one->checked = true; }
        $editors[] = $one;
        $fronts[$val] = $one->label;
      }
    } else {
      $one = new stdClass();
      $one->label = $ob->GetFriendlyName();
      $one->value = $tmp[$i];
      $one->mainkey = null;
      $one->themekey = null;
      if ($tmp[$i] == $wysiwyg) { $one->checked = true; }
      $editors[] = $one;
      $fronts[$one->value] = $one->label;
    }
  }
  usort($editors, function ($a,$b) { return strcmp($a->label, $b->label); });
  uasort($fronts, function ($a,$b) { return strcmp($a, $b); });
} else {
  $editors = null;
  $fronts = null;
}
$smarty->assign('wysiwyg_opts', $editors);

//frontend wysiwyg
$fronts = ['' => lang('none')] + $fronts;
$smarty->assign('wysiwyg', $fronts)
  ->assign('frontendwysiwyg', $frontendwysiwyg);

// Syntax-highlight editors
$tmp = $modops->GetCapableModules(CoreCapabilities::SYNTAX_MODULE);
if ($tmp) {
  $editors = [];
  for ($i = 0, $n = count($tmp); $i < $n; ++$i) {
    $ob = Utils::get_module($tmp[$i]);
    if (method_exists($ob, 'ListEditors')) { //aka ($ob instanceof MultiEditor)
      $all = $ob->ListEditors();
      foreach ($all as $editor=>$val) {
        $one = new stdClass();
        $one->label = $ob->Lang(strtolower($editor).'_friendlyname');
        $one->value = $val; // as module::editor
        list($modname, $edname) = explode('::', $val);
        list($realm, $key) = $ob->GetMainHelpKey($edname);
        if ($key) {
        if (!$realm) { $realm = $modname; }
          $one->mainkey = $realm.'__'.$key;
        } else {
          $one->mainkey = null;
        }
        list($realm, $key) = $ob->GetThemeHelpKey($edname);
        if ($key) {
          if (!$realm) { $realm = $modname; }
          $one->themekey = $realm.'__'.$key;
        } else {
          $one->themekey = null;
        }
        if ($modname == $syntaxmodule && $edname == $syntaxtype) { $one->checked = true; }
        $editors[] = $one;
      }
    } elseif ($tmp[$i] != 'MicroTiny') { //that's only for html :(
      $one = new stdClass();
      $one->label = $ob->GetFriendlyName();
      $one->value = $tmp[$i];
      $one->mainkey = null;
      $one->themekey = null;
      if ($tmp[$i] == $syntaxer) { $one->checked = true; }
      $editors[] = $one;
    }
  }
  usort($editors, function ($a,$b) { return strcmp($a->label, $b->label); });

  $one = new stdClass();
  $one->value = '';
  $one->label = lang('none');
  $one->mainkey = null;
  $one->themekey = null;
  if (!$syntaxer) { $one->checked = true; }
  array_unshift($editors, $one);
} else {
  $editors = null;
}
$smarty->assign('syntax_opts', $editors);

//TODO check values special'd for display
$smarty->assign([
  'adminlog_lifetime' => $adminlog_lifetime,
  'allow_browser_cache' => $allow_browser_cache,
  'auto_clear_cache_age' => $auto_clear_cache_age,
  'backendwysiwyg' => $wysiwyg,
  'basic_attributes' => explode(',', $basic_attributes),
  'browser_cache_expiry' => $browser_cache_expiry,
  'checkversion' => $checkversion,
  'content_autocreate_flaturls' => $content_autocreate_flaturls,
  'content_autocreate_urls' => $content_autocreate_urls,
  'content_cssnameisblockname' => $content_cssnameisblockname,
  'content_imagefield_path' => $content_imagefield_path,
  'content_mandatory_urls' => $content_mandatory_urls,
  'content_thumbnailfield_path' => $content_thumbnailfield_path,
  'contentimage_path' => $contentimage_path,
  'defaultdateformat' => cms_specialchars($defaultdateformat),
  'disallowed_contenttypes' => explode(',', $disallowed_contenttypes),
  'frontendlang' => $frontendlang,
  'global_umask' => $global_umask,
  'helpicon' => $themeObject->DisplayImage('icons/system/info.png', 'help','','', 'cms_helpicon'),
  'lock_refresh' => $lock_refresh,
  'lock_timeout' => $lock_timeout,
  'login_module' => $login_module,
  'logoselect' => $logoselector,
//  'metadata' => $metadata, // TODO considersyntax editor for html
  'search_module' => $search_module,
  'sitedown' => $sitedown,
  'sitedownexcludeadmins' => $sitedownexcludeadmins,
  'sitedownexcludes' => $sitedownexcludes,
  'sitelogo' => $sitelogo,
  'sitename' => cms_specialchars($sitename),
  'smarty_cachelife' => $smarty_cachelife,
  'smarty_cacheusertags' => $smarty_cacheusertags,
  'smarty_compilecheck' => $smarty_compilecheck,
  'syntax_theme' => cms_specialchars($syntaxtheme),
  'testresults' => lang('untested'),
  'thumbnail_height' => $thumbnail_height,
  'thumbnail_width' => $thumbnail_width,
  'wysiwyg_theme' => cms_specialchars($wysiwygtheme),
  ])
  ->assign('textarea_metadata', FormUtils::create_textarea([
    'wantedsyntax' => 'html',
    'htmlid' => 'globalmetadata',
    'name' => 'metadata',
    'class' => 'pagesmalltextarea',
    'value' => $metadata, // verbatim value
  ]))
  ->assign('textarea_sitedownmessage', FormUtils::create_textarea([
    'enablewysiwyg' => 1,
    'htmlid' => 'sitedownmessage',
    'name' => 'sitedownmessage',
    'class' => 'pagesmalltextarea',
    'value' => $sitedownmessage, // verbatim value
  ]));

$tmp = [];
$keys = ['yes','no','module_setting'];
foreach ([1, 0, 2] as $i => $val) {
    $one = new stdClass();
    $one->label = lang($keys[$i]);
    $one->value = $val;
    $one->checked = ($val == $smarty_cachemodules);
    $tmp[] = $one;
}
$smarty->assign('smarty_cachemodules', $tmp);

$tmp = [
  86400 => lang('adminlog_1day'),
  86400*7 => lang('adminlog_1week'),
  86400*14 => lang('adminlog_2weeks'),
  86400*31 => lang('adminlog_1month'),
  86400*31*3 => lang('adminlog_3months'),
  86400*30*6 => lang('adminlog_6months'),
  -1 => lang('adminlog_manual'),
];
$smarty->assign('adminlog_options', $tmp);

$all_attributes = null;
//$txt = null;
$content_obj = new Content(); // i.e. the default content-type
$list = $content_obj->GetPropertiesArray();
if ($list) {
    $all_attributes = [];
    for ($i = 0, $n = count($list); $i < $n; ++$i) {
        $arr = $list[$i];
        $tmp = $arr['tab'];
        // exclude some items
        if ($tmp == ContentBase::TAB_PERMS) {
            continue;
        }
        if (!isset($all_attributes[$tmp])) {
            $all_attributes[$tmp] = ['label'=>lang_by_realm('CMSContentManager',$tmp),'value'=>[]];
        }
        $all_attributes[$tmp]['value'][] = ['value'=>$arr['name'],'label'=>lang_by_realm('CMSContentManager',$arr['name'])];
    }
//    $txt = FormUtils::create_option($all_attributes);
}
//$txt = FormUtils::create_option($all_attributes);

$realm = 'CMSContentManager'; //TODO generalize
$contentops = ContentOperations::get_instance();
$all_contenttypes = $contentops->ListContentTypes(false, false, false, $realm);
$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign([
  'all_attributes' => $all_attributes, // OR $txt ?
  'all_contenttypes' => $all_contenttypes,
  'backurl' => $themeObject->backUrl(),
  'extraparms' => $extras,
  'selfurl' => $selfurl,
  'smarty_cacheoptions' => ['always'=>lang('always'), 'never'=>lang('never'), 'moduledecides'=>lang('moduledecides')],
  'smarty_cacheoptions2' => ['always'=>lang('always'), 'never'=>lang('never')],
  'titlemenu' => [lang('menutext'), lang('title')],
  'urlext' => $urlext,
  'yesno' => [0=>lang('no'), 1=>lang('yes')],
]);

$content = $smarty->fetch('sitesettings.tpl');
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
