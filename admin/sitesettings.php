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

//use CMSMS\IMultiEditor;
//use CMSMS\Mailer; CMSMailer\Mailer; //TODO if no CMSMailer present, revert to mail()
use ContentManager\ContentBase;
use ContentManager\contenttypes\Content;
use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\CoreCapabilities;
use CMSMS\Events;
use CMSMS\FileType;
use CMSMS\FormUtils;
use CMSMS\HookOperations;
use CMSMS\SingleItem;
use CMSMS\Url;
use CMSMS\Utils;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('menu.php'.$urlext.'&section=siteadmin'); // TODO bad section-hardcode
}

$userid = get_userid();
$access = check_permission($userid, 'Modify Site Preferences');
$themeObject = SingleItem::Theme();

if (!$access) {
    //TODO a push-notification $themeObject->RecordNotice('error', lang('needpermissionto', '"Modify Site Preferences"'));
    // OR throw a 403
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

$config = SingleItem::Config();
$pretty_urls = $config['url_rewriting'] == 'none' ? 0 : 1;
$devmode = $config['develop_mode'];

//$tpw = $_POST['mailprefs_password'] ?? ''; // preserve these verbatim
$tmsg = $_POST['sitedownmessage'] ?? '';
//unset($_POST['mailprefs_password'], $_POST['sitedownmessage']); // if any
unset($_POST['sitedownmessage']); // if any
de_specialize_array($_POST);

$seetab = (isset($_POST['active_tab'])) ? sanitizeVal($_POST['active_tab'], CMSSAN_NAME) : '';
/*
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
                $mailer = new Mailer(); // TODO
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
*/
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


/* TODO if ($devmode)
UI for & processing of system-cache-related params e.g.
'cache_driver' 'predis','memcached','apcu','yac','file' or 'auto'
'cache_autocleaning' bool (and hence lifetime)
'cache_lifetime' int seconds (0 for unlimited)
'cache_file_blocking' bool for a file-cache
'cache_file_locking' bool ditto
*/

if (isset($_POST['submit'])) {
    if ($access) {
        switch ($seetab) {
            case 'general':
                $val = sanitizeVal(trim($_POST['sitename']), CMSSAN_NONPRINT); // AND nl2br() ? striptags() ?
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
                $val = (!empty($_POST['logintheme'])) ? sanitizeVal($_POST['logintheme'], CMSSAN_FILE) : ''; // consistent with theme folder-name
                AppParams::set('logintheme', $val);
                $val = $_POST['defaultdateformat'];
                if ($val) { $val = preg_replace('~[^a-zA-Z%,.\-:/ ]~', '', trim($val)); } // strftime()-only formats 2.99 breaker?
                AppParams::set('defaultdateformat', $val);
                AppParams::set('thumbnail_width', filter_input(INPUT_POST, 'thumbnail_width', FILTER_SANITIZE_NUMBER_INT));
                AppParams::set('thumbnail_height', filter_input(INPUT_POST, 'thumbnail_height', FILTER_SANITIZE_NUMBER_INT));
                $val = (!empty($_POST['backendwysiwyg'])) ? sanitizeVal($_POST['backendwysiwyg'], CMSSAN_PUNCTX, ':') : ''; // allow '::'
                if ($val) {
                    if (strpos($val, '::') !== false) {
                        $parts = explode('::', $val, 2);
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
                $val = sanitizeVal($_POST['wysiwygtheme'], CMSSAN_PUNCT); // OR CMSSAN_PURESPC ?
                if ($val) {
                    $val = strtolower(strtr($val, ' ', '_')); // CHECKME
                }
                AppParams::set('wysiwyg_theme', $val);
                $val = (!empty($_POST['frontendwysiwyg'])) ? sanitizeVal($_POST['frontendwysiwyg'], CMSSAN_PUNCTX, ':') : '';
                if ($val) {
                    if (strpos($val, '::') !== false) {
                        $parts = explode('::', $val, 2);
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
                $val = (!empty($_POST['search_module'])) ? sanitizeVal($_POST['search_module'], CMSSAN_FILE) : '';
                AppParams::set('searchmodule', $val);

                if (isset($_POST['login_module'])) {
                    AppParams::set('loginmodule', sanitizeVal($_POST['login_module'], CMSSAN_FILE));
                }
                AppParams::set('loginprocessor', sanitizeVal($_POST['login_processor'])); // , CMSSAN_TODO
                if (isset($_POST['password_level'])) {
                    AppParams::set('password_level', (int)$_POST['password_level']);
                    AppParams::set('username_level', (int)$_POST['username_settings']);
                }
                break;
            case 'editcontent':
                if ($pretty_urls) {
                    AppParams::set('content_autocreate_urls', filter_input(INPUT_POST, 'content_autocreate_urls', FILTER_SANITIZE_NUMBER_INT));
                    AppParams::set('content_autocreate_flaturls', filter_input(INPUT_POST, 'content_autocreate_flaturls', FILTER_SANITIZE_NUMBER_INT));
                    AppParams::set('content_mandatory_urls', filter_input(INPUT_POST, 'content_mandatory_urls', FILTER_SANITIZE_NUMBER_INT));
                }
                AppParams::set('content_imagefield_path', sanitizeVal($_POST['content_imagefield_path'], CMSSAN_PATH));
                AppParams::set('content_thumbnailfield_path', sanitizeVal($_POST['content_thumbnailfield_path'], CMSSAN_PATH));
                AppParams::set('contentimage_path', sanitizeVal($_POST['contentimage_path'], CMSSAN_PATH));
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
                    // TODO CMSMS\sanitizeVal($tmsg, CMSSAN_NONPRINT) CMS_SAN_TODO etc for html incl. tags
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
/*            case 'credentials':
                if (isset($_POST['login_module'])) {
                    AppParams::set('loginmodule', sanitizeVal($_POST['login_module'], CMSSAN_FILE));
                }
                AppParams::set('loginprocessor', sanitizeVal($_POST['login_processor'])); // , CMSSAN_TODO
                AppParams::set('password_level', (int)$_POST['password_level']);
                AppParams::set('username_level', (int)$_POST['username_settings']);
                break;
            case 'mail':
                // gather mailprefs (except password - excised pre-$_POST-cleanup)
                $prefix = 'mailprefs_';
                foreach ($_POST as $key => $val) {
                    if (!startswith($key, $prefix)) {
                        continue;
                    }
                    $key = substr($key, strlen($prefix));
                    $mailprefs[$key] = CMSMS\sanitizeVal($val, CMSSAN_TODO); // OR CMSSAN_NONPRINT ? OR CMSSAN_NAME?
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
                            $val = CMSMS\sanitizeVal($tpw, CMSSAN_NONPRINT);
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
*/
            case 'advanced':
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

                $val = sanitizeVal($_POST['syntaxtype'], CMSSAN_PUNCTX, ':'); // allow '::'
                if ($val) {
                    if (strpos($val, '::') !== false) {
                        $parts = explode('::', $val, 2);
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
                $val = sanitizeVal($_POST['syntaxtheme'], CMSSAN_PUNCT); // OR CMSSAN_PURESPC?
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
                AppParams::set('adminlog_lifetime', filter_input(INPUT_POST, 'adminlog_lifetime', FILTER_SANITIZE_NUMBER_INT));
                $val = max(1, min(10, (int)$_POST['jobinterval']));
                AppParams::set('jobinterval', $val * 60); // recorded as seconds
                $val = max(2, min(120, (int)$_POST['jobtimeout']));
                AppParams::set('jobtimeout', $val);
/*
                $val = CMSMS\de_specialize($_POST['joburl']);
                if ($val) {
                    $tmp = (new Url())->sanitize($val);
                    if ($tmp) {
                        AppParams::set('joburl', $tmp);
                    } else {
                        $themeObject->RecordNotice('error', lang_by_realm('jobs', 'err_url'));
                    }
                } else {
                    AppParams::set('joburl', '');
                }
*/
                break;
        } //switch tab

        SingleItem::LoadedData()->refresh('site_params');

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
$content_imagefield_path = AppParams::get('content_imagefield_path');
$content_mandatory_urls = AppParams::get('content_mandatory_urls', 0);
$content_thumbnailfield_path = AppParams::get('content_thumbnailfield_path');
$contentimage_path = AppParams::get('contentimage_path');
$defaultdateformat = AppParams::get('defaultdateformat');
$disallowed_contenttypes = AppParams::get('disallowed_contenttypes');
if (Events::ListEventHandlers('Core', 'CheckUserData')) {
    // handler(s) exist for validation
    $password_level = AppParams::get('password_level', 0);
    $username_level = AppParams::get('username_level', 0);
} else {
    $password_level = null;
    $username_level = null;
}

$frontendlang = AppParams::get('frontendlang');

$wysiwygmodule = AppParams::get('frontendwysiwyg');
$wysiwygtype = AppParams::get('frontendwysiwyg_type');
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
$jobinterval = (int)AppParams::get('jobinterval') / 60; // show as minutes
$jobtimeout = (int)AppParams::get('jobtimeout');
//$joburl = AppParams::get('joburl');
//if ($joburl) { $joburl = specialize($joburl); }
$lock_refresh = (int)AppParams::get('lock_refresh', 120);
$lock_timeout = (int)AppParams::get('lock_timeout', 60);
$login_processor = AppParams::get('loginprocessor');
$login_module = AppParams::get('loginmodule'); //, CMSMS\ModuleOperations::STD_LOGIN_MODULE);
$logintheme = AppParams::get('logintheme');
//$mail_is_set = AppParams::get('mail_is_set', 0);
$metadata = AppParams::get('metadata');
$search_module = AppParams::get('searchmodule', 'Search');
if ($devmode) {
    $help_url = AppParams::get('site_help_url');
}
$sitedown = AppParams::get('site_downnow', 0);
$sitedownexcludeadmins = AppParams::get('sitedownexcludeadmins', 0);
$sitedownexcludes = AppParams::get('sitedownexcludes');
$sitedownmessage = AppParams::get('sitedownmessage', '<p>The website is currently off line. Check back later.</p>'); // no specialchar(), used in textarea ?
$sitelogo = AppParams::get('site_logo');
$sitename = AppParams::get('sitename', 'CMSMS Website');
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
$syntaxtheme = AppParams::get('syntax_theme');
$thumbnail_height = AppParams::get('thumbnail_height', 96);
$thumbnail_width = AppParams::get('thumbnail_width', 96);
//$xmlmodulerepository = AppParams::get('xmlmodulerepository', '');
$wysiwygmodule = AppParams::get('wysiwyg'); //aka rich-text editor module
$wysiwygtype = AppParams::get('wysiwyg_type');
$wysiwyg = ($wysiwygtype) ? $wysiwygmodule .'::'.$wysiwygtype : $wysiwygmodule;
$wysiwygtheme = AppParams::get('wysiwyg_theme');

/*$mailprefs = [
  'mailer'=>'mail',
  'host'=>'localhost', //actual >> (new Url())->santitize()
  'port'=>25,
  'from'=>'root@localhost.localdomain', //actual >> FILTER_SANITIZE_EMAIL ? too strict ?
  'fromuser'=>'CMS Administrator', //actual >> specialize() ? sanitizeVal() ?
  'sendmail'=>'/usr/sbin/sendmail', //actual >> sanitizeVal() ?
  'smtpauth'=>0,
  'username'=>'', //actual >> specialize() ? sanitizeVal() ?
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
    $mailprefs[$param] = specialize($mailprefs[$param]);
}
$mailprefs['password'] = Crypto::decrypt_string(base64_decode($mailprefs['password']));
*/
$modnames = SingleItem::LoadedMetadata()->get('capable_modules', false, CoreCapabilities::SITE_SETTINGS);
if ($modnames) {
    // load them, if not already done
    for ($i = 0, $n = count($modnames); $i < $n; ++$i) {
        $modnames[$i] = Utils::get_module($modnames[$i]); // TODO $modops = SingleItem::ModuleOperations() ->get ....
		if ($modnames[$i]) { $modnames[$i]->InitializeAdmin(); }
    }
    $list = HookOperations::do_hook_accumulate('ExtraSiteSettings');
    // assist the garbage-collector
    for ($i = 0; $i < $n; ++$i) {
        $modnames[$i] = null;
    }
    $externals = [];
    if ($list) {
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
    }
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
/*
$baseurl = CMS_ASSETS_URL.'/js';
$js = <<<EOS
 <script type="text/javascript" src="{$baseurl}/jquery-inputCloak.min.js"></script>
EOS;
add_page_headtext($js);
*/
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
/*
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
*/
$(function() {
/*
 on_mailer();
 $('#password').inputCloak({
  type:'see1',
  symbol:'\u25CF'
 });
 $('#mailer').on('change', on_mailer);
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
*/
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

$smarty = SingleItem::Smarty();

$tmp = [-1 => lang('none')];
$modnames = SingleItem::LoadedMetadata()->get('capable_modules', false, CoreCapabilities::SEARCH_MODULE);
if ($modnames) {
    for ($i = 0, $n = count($modnames); $i < $n; $i++) {
        $tmp[$modnames[$i]] = $modnames[$i];
    }
    $smarty->assign('search_module', $search_module);
} else {
    $smarty->assign('search_module', lang('none'));
}
$smarty->assign('search_modules', $tmp);

if ($devmode) {
    $smarty->assign('help_url', $help_url);
}

$modnames = SingleItem::LoadedMetadata()->get('capable_modules', false, CoreCapabilities::LOGIN_MODULE);
if ($modnames && count($modnames) > 1) {
    for ($i = 0, $n = count($modnames); $i < $n; $i++) {
        if ($modnames[$i] == $modops::STD_LOGIN_MODULE) {
            $tmp[$modnames[$i]] = lang('default');
        } else {
            $tmp[$modnames[$i]] = $modnames[$i];
        }
    }
    $smarty->assign('login_module', $login_module)
     ->assign('login_modules', $tmp);
}

$tmp = ['' => lang('theme'), 'module' => lang('default')];
$smarty->assign('login_handler', $login_processor)
 ->assign('login_handlers', $tmp);


/*
$maileropts = [
  'mail' => 'PHP',
  'sendmail' => 'Sendmail',
  'smtp' => 'SMTP',
];
$secopts = [
  '' => lang('none'),
  'ssl' => 'SSL',
  'tls' => 'TLS',
];
*/
$smarty->assign([
//  'maileritems' => $maileropts,
//  'secure_opts' => $secopts,
//  'mail_is_set' => $mail_is_set,
//  'mailprefs' => $mailprefs,
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
$modnames = SingleItem::LoadedMetadata()->get('capable_modules', false, CoreCapabilities::WYSIWYG_MODULE);
if ($modnames) {
  $editors = []; //for backend
  $fronts = [];
  for ($i = 0, $n = count($modnames); $i < $n; ++$i) {
    $mod = Utils::get_module($modnames[$i]);
    if (method_exists($mod, 'ListEditors')) { //OR ($mod instanceof IMultiEditor)
      $all = $mod->ListEditors();
      foreach ($all as $editor=>$val) {
        $one = new stdClass();
        $one->label = $mod->Lang(strtolower($editor).'_publicname');
        $one->value = $val; // as module::editor
        list($modname, $edname) = explode('::', $val, 2);
        list($realm, $key) = $mod->GetMainHelpKey($edname);
        if ($key) {
          if (!$realm) { $realm = $modname; }
          $one->mainkey = $realm.'__'.$key;
        } else {
          $one->mainkey = null;
        }
        list($realm, $key) = $mod->GetThemeHelpKey($edname);
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
      $one->label = $mod->GetFriendlyName();
      $one->value = $modnames[$i];
      $one->mainkey = null;
      $one->themekey = null;
      if ($modnames[$i] == $wysiwyg) { $one->checked = true; }
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
$modnames = SingleItem::LoadedMetadata()->get('capable_modules', false, CoreCapabilities::SYNTAX_MODULE);
if ($modnames) {
  $editors = [];
  for ($i = 0, $n = count($modnames); $i < $n; ++$i) {
    $mod = Utils::get_module($modnames[$i]);
    if (method_exists($mod, 'ListEditors')) { //OR ($mod instanceof IMultiEditor)
      $all = $mod->ListEditors();
      foreach ($all as $editor=>$val) {
        $one = new stdClass();
        $one->label = $mod->Lang(strtolower($editor).'_publicname');
        $one->value = $val; // as module::editor
        list($modname, $edname) = explode('::', $val, 2);
        list($realm, $key) = $mod->GetMainHelpKey($edname);
        if ($key) {
        if (!$realm) { $realm = $modname; }
          $one->mainkey = $realm.'__'.$key;
        } else {
          $one->mainkey = null;
        }
        list($realm, $key) = $mod->GetThemeHelpKey($edname);
        if ($key) {
          if (!$realm) { $realm = $modname; }
          $one->themekey = $realm.'__'.$key;
        } else {
          $one->themekey = null;
        }
        if ($modname == $syntaxmodule && $edname == $syntaxtype) { $one->checked = true; }
        $editors[] = $one;
      }
    } elseif ($modnames[$i] != 'MicroTiny') { //that's only for html :(
      $one = new stdClass();
      $one->label = $mod->GetFriendlyName();
      $one->value = $modnames[$i];
      $one->mainkey = null;
      $one->themekey = null;
      if ($modnames[$i] == $syntaxer) { $one->checked = true; }
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
  'defaultdateformat' => specialize($defaultdateformat),
  'disallowed_contenttypes' => explode(',', $disallowed_contenttypes),
  'frontendlang' => $frontendlang,
  'global_umask' => $global_umask,
  'helpicon' => $themeObject->DisplayImage('icons/system/info.png', 'help','','', 'cms_helpicon'),
  'jobinterval' => $jobinterval,
  'jobtimeout' => $jobtimeout,
//  'joburl' => $joburl,
  'lock_refresh' => $lock_refresh,
  'lock_timeout' => $lock_timeout,
  'login_module' => $login_module,
  'logoselect' => $logoselector,
//  'metadata' => $metadata, // TODO considersyntax editor for html
  'passwordlevel' => $password_level,
  'search_module' => $search_module,
  'sitedown' => $sitedown,
  'sitedownexcludeadmins' => $sitedownexcludeadmins,
  'sitedownexcludes' => $sitedownexcludes,
  'sitelogo' => $sitelogo,
  'sitename' => specialize($sitename),
  'smarty_cachelife' => $smarty_cachelife,
  'smarty_cacheusertags' => $smarty_cacheusertags,
  'smarty_compilecheck' => $smarty_compilecheck,
  'syntax_theme' => specialize($syntaxtheme),
  'testresults' => lang('untested'),
  'thumbnail_height' => $thumbnail_height,
  'thumbnail_width' => $thumbnail_width,
  'usernamelevel' => $username_level,
  'wysiwyg_theme' => specialize($wysiwygtheme),
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

if ($password_level !== null) {
    $pass_levels = [
        0 => lang('unrestricted'),
        1 => lang('guess_medium'),
        2 => lang('guess_hard'),
        3 => lang('guess_vhard'),
    ];
    // TODO lang
    $uname_levels = [
        0 => lang('unrestricted'),
        1 => 'Type 1',
        2 => 'Type 2',
        3 => 'Type 3',
    ];
    $smarty->assign([
      'pass_levels' => $pass_levels,
      'uname_levels' => $uname_levels,
    ]);
}

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
            $all_attributes[$tmp] = ['label'=>lang_by_realm('ContentManager',$tmp),'value'=>[]];
        }
        $all_attributes[$tmp]['value'][] = ['value'=>$arr['name'],'label'=>lang_by_realm('ContentManager',$arr['name'])];
    }
//    $txt = FormUtils::create_option($all_attributes);
}
//$txt = FormUtils::create_option($all_attributes);

$realm = 'ContentManager'; //TODO generalize
$contentops = SingleItem::ContentTypeOperations();
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
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
