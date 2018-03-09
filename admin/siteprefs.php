<?php
#procedure to display and modify website preferences/settings
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

/**
 * Init variables / objects
 */

$CMS_ADMIN_PAGE=1;
$CMS_TOP_MENU='admin';
$CMS_ADMIN_TITLE='preferences';

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$userid = get_userid(); // <- Also checks login

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
if (isset($_POST['cancel'])) {
    redirect('index.php'.$urlext);
    return;
}

$access = check_permission($userid, 'Modify Site Preferences');

include_once 'header.php';

if (!$access) {
//TODO some immediate popup    $themeObject->RecordMessage('error', lang('needpermissionto', '"Modify Site Preferences"'));
    return;
}

/**
 * A convenience function to interpret octal permissions, and return
 * a human readable string.  Uses the lang() function for translation.
 *
 * @internal
 * @param int The permissions to test.
 * @return string
 */
function siteprefs_interpret_permissions($perms)
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

function siteprefs_display_permissions($permsarr)
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

$gCms = cmsms();
$db = $gCms->GetDb();
$config = $gCms->GetConfig();

$pretty_urls = $config['url_rewriting'] == 'none' ? 0 : 1;
$mail_is_set = cms_siteprefs::get('mail_is_set', 0);
$testresults = lang('untested');
$thumbnail_width = 96;
$thumbnail_height = 96;
$sitedownexcludes = '';
$sitedownexcludeadmins = '';
$disallowed_contenttypes = '';
$basic_attributes = null;
$xmlmodulerepository = '';
$checkversion = 1;
$defaultdateformat = '';
$enablesitedownmessage = '0';
$lock_timeout = 60;
$sitedownmessage = '<p>Site is currently down.  Check back later.</p>';
$sitedownmessagetemplate = '-1';
$metadata = '';
$sitelogo = '';
$sitename = 'CMSMS Website';
$frontendlang = '';
$frontendwysiwyg = '';
$global_umask = '022';
$login_module = '';
$logintheme = 'default';
$backendwysiwyg = '';
$auto_clear_cache_age = 0;
$allow_browser_cache = 0;
$browser_cache_expiry = 60;
$content_autocreate_urls = 0;
$content_autocreate_flaturls = 0;
$content_mandatory_urls = 0;
$contentimage_useimagepath = 0;
$content_imagefield_path = '';
$content_thumbnailfield_path = '';
$content_cssnameisblockname = 1;
$contentimage_path = '';
$adminlog_lifetime = (3600*24*31);
$search_module = 'Search';
$use_smartycompilecheck = 1;
$mailprefs = [
  'mailer'=>'mail',
  'host'=>'localhost',
  'port'=>25,
  'from'=>'root@localhost.localdomain',
  'fromuser'=>'CMS Administrator',
  'sendmail'=>'/usr/sbin/sendmail',
  'smtpauth'=>0,
  'username'=>'',
  'password'=>'',
  'secure'=>'',
  'timeout'=>60,
  'charset'=>'utf-8',
];

/**
 * Get preferences
 */
$allow_browser_cache = cms_siteprefs::get('allow_browser_cache', $allow_browser_cache);
$browser_cache_expiry = cms_siteprefs::get('browser_cache_expiry', $browser_cache_expiry);
$auto_clear_cache_age = cms_siteprefs::get('auto_clear_cache_age', $auto_clear_cache_age);
$thumbnail_width = cms_siteprefs::get('thumbnail_width', $thumbnail_width);
$thumbnail_height = cms_siteprefs::get('thumbnail_height', $thumbnail_height);
$global_umask = cms_siteprefs::get('global_umask', $global_umask);
$frontendlang = cms_siteprefs::get('frontendlang', $frontendlang);
$frontendwysiwyg = cms_siteprefs::get('frontendwysiwyg', $frontendwysiwyg);
$enablesitedownmessage = cms_siteprefs::get('enablesitedownmessage', $enablesitedownmessage);
$sitedownmessage = cms_siteprefs::get('sitedownmessage', $sitedownmessage);
$xmlmodulerepository = cms_siteprefs::get('xmlmodulerepository', $xmlmodulerepository);
$checkversion = cms_siteprefs::get('checkversion', $checkversion);
$defaultdateformat = cms_siteprefs::get('defaultdateformat', $defaultdateformat);
$login_module = cms_siteprefs::get('loginmodule', $login_module);
$logintheme = cms_siteprefs::get('logintheme', $logintheme);
$backendwysiwyg = cms_siteprefs::get('backendwysiwyg', $backendwysiwyg);
$metadata = cms_siteprefs::get('metadata', $metadata);
$sitelogo = cms_siteprefs::get('sitelogo', $sitelogo);
$sitename = cms_html_entity_decode(cms_siteprefs::get('sitename', $sitename));
$lock_timeout = (int)cms_siteprefs::get('lock_timeout', $lock_timeout);
$sitedownexcludes = cms_siteprefs::get('sitedownexcludes', $sitedownexcludes);
$sitedownexcludeadmins = cms_siteprefs::get('sitedownexcludeadmins', $sitedownexcludeadmins);
$disallowed_contenttypes = cms_siteprefs::get('disallowed_contenttypes', $disallowed_contenttypes);
$basic_attributes = cms_siteprefs::get('basic_attributes', $basic_attributes);
$content_autocreate_urls = cms_siteprefs::get('content_autocreate_urls', $content_autocreate_urls);
$content_autocreate_flaturls = cms_siteprefs::get('content_autocreate_flaturls', $content_autocreate_flaturls);
$content_mandatory_urls = cms_siteprefs::get('content_mandatory_urls', $content_mandatory_urls);
$content_imagefield_path = cms_siteprefs::get('content_imagefield_path', $content_imagefield_path);
$content_thumbnailfield_path = cms_siteprefs::get('content_thumbnailfield_path', $content_thumbnailfield_path);
$content_cssnameisblockname = cms_siteprefs::get('content_cssnameisblockname', $content_cssnameisblockname);
$contentimage_path = cms_siteprefs::get('contentimage_path', $contentimage_path);
$adminlog_lifetime = cms_siteprefs::get('adminlog_lifetime', $adminlog_lifetime);
$search_module = cms_siteprefs::get('searchmodule', $search_module);
$use_smartycompilecheck = cms_siteprefs::get('use_smartycompilecheck', $use_smartycompilecheck);
$tmp = cms_siteprefs::get('mailprefs');
if ($tmp) {
    $mailprefs = unserialize($tmp);
}

cleanArray($_POST);

/**
 * Check tab
 */
$tab=(isset($_POST['active_tab'])) ? trim(cleanValue($_POST['active_tab'])) : '';

/**
 * Submit
 */
if (!empty($_POST['testmail'])) {
    if (!$mail_is_set) {
        $errors[] = lang('error_mailnotset_notest');
    } elseif ($_POST['mailtest_testaddress'] == '') {
        $errors[] = lang('error_mailtest_noaddress');
    } else {
        $addr = filter_var($_POST['mailtest_testaddress'], FILTER_SANITIZE_EMAIL);
        if (!is_email($addr)) {
            $errors[] = lang('error_mailtest_notemail');
        } else {
            // we got an email, and we have settings.
            try {
                $mailer = new cms_mailer();
                $mailer->AddAddress($addr);
                $mailer->IsHTML(true);
                $mailer->SetBody(lang('mail_testbody', 'siteprefs'));
                $mailer->SetSubject(lang('mail_testsubject', 'siteprefs'));
                $mailer->Send();
                if ($mailer->IsError()) {
                    $errors[] = $mailer->GetErrorInfo();
                }
                $message .= lang('testmsg_success');
            } catch (\Exception $e) {
                $errors[] = $e->GetMessage();
            }
        }
    }
}

if (!empty($_POST['testumask'])) {
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

if(isset($_POST['submit'])) {
    if($access) {
        switch($tab) {
            case 'general':
                cms_siteprefs::set('sitename', trim($_POST['sitename']));
                cms_siteprefs::set('sitelogo', trim(filter_var($_POST['sitelogo'],FILTER_SANITIZE_URL)));
                if(!empty($_POST['frontendlang'])) {
                    $frontendlang = $_POST['frontendlang'];
				} else {
                    $frontendlang = '';
				}
                cms_siteprefs::set('frontendlang', $frontendlang);
                cms_siteprefs::set('metadata', $_POST['metadata']);
                if(!empty($_POST['logintheme'])) {
                    $logintheme = $_POST['logintheme'];
                } else {
					$logintheme = '';
				}
                cms_siteprefs::set('logintheme', $logintheme);
/*                if(!empty($_POST['backendwysiwyg'])) {
                    $backendwysiwyg = $_POST['backendwysiwyg'];
                } else {
					$backendwysiwyg = '';
				}
                cms_siteprefs::set('backendwysiwyg', $backendwysiwyg);
*/
                // undo some cleaning
                $defaultdateformat = str_replace('&#37;', '%', $_POST['defaultdateformat']);
                cms_siteprefs::set('defaultdateformat', $defaultdateformat);
                $thumbnail_width = (int) $_POST['thumbnail_width'];
                cms_siteprefs::set('thumbnail_width', $thumbnail_width);
                $thumbnail_height = (int) $_POST['thumbnail_height'];
                cms_siteprefs::set('thumbnail_height', $thumbnail_height);
                if(!empty($_POST['frontendwysiwyg'])) {
                    $frontendwysiwyg = $_POST['frontendwysiwyg'];
				} else {
					$frontendwysiwyg = '';
				}
                cms_siteprefs::set('frontendwysiwyg', $frontendwysiwyg);
                if(!empty($_POST['search_module'])) {
                    $search_module = trim($_POST['search_module']);
                } else {
					$search_module = '';
				}
                cms_siteprefs::set('searchmodule', $search_module);
                break;
            case 'editcontent':
                if($pretty_urls) {
                    $content_autocreate_urls = (int) $_POST['content_autocreate_urls'];
                    cms_siteprefs::set('content_autocreate_urls', $content_autocreate_urls);
                    $content_autocreate_flaturls = (int) $_POST['content_autocreate_flaturls'];
                    cms_siteprefs::set('content_autocreate_flaturls', $content_autocreate_flaturls);
                    $content_mandatory_urls = (int) $_POST['content_mandatory_urls'];
                    cms_siteprefs::set('content_mandatory_urls', $content_mandatory_urls);
                }
                $content_imagefield_path = trim($_POST['content_imagefield_path']);
                cms_siteprefs::set('content_imagefield_path', $content_imagefield_path);
                $content_thumbnailfield_path = trim($_POST['content_thumbnailfield_path']);
                cms_siteprefs::set('content_thumbnailfield_path', $content_thumbnailfield_path);
                $contentimage_path = trim($_POST['contentimage_path']);
                cms_siteprefs::set('contentimage_path', $contentimage_path);
                $content_cssnameisblockname = (int) $_POST['content_cssnameisblockname'];
                cms_siteprefs::set('content_cssnameisblockname', $content_cssnameisblockname);
                if(!empty($_POST['basic_attributes'])) {
                    $basic_attributes = implode(',', ($_POST['basic_attributes']));
                } else {
                    $basic_attributes = null;
                }
                cms_siteprefs::set('basic_attributes', $basic_attributes);
                $disallowed_contenttypes = '';
                if(!empty($_POST['disallowed_contenttypes'])) {
                    $disallowed_contenttypes = implode(',', $_POST['disallowed_contenttypes']);
                }
                cms_siteprefs::set('disallowed_contenttypes', $disallowed_contenttypes);
                break;
            case 'sitedown':
                if(!empty($_POST['sitedownexcludes'])) {
                    $sitedownexcludes = trim($_POST['sitedownexcludes']);
                }
                $sitedownexcludeadmins = (int) $_POST['sitedownexcludeadmins'];
                $prevsitedown = $enablesitedownmessage;
                $enablesitedownmessage = !empty($_POST['enablesitedownmessage']);
                if(!empty($_POST['sitedownmessage'])) {
                    $sitedownmessage = $_POST['sitedownmessage'];
                }
                if(!$prevsitedown && $enablesitedownmessage) {
                    audit('', 'Global Settings', 'Sitedown enabled');
                }
                elseif($prevsitedown && !$enablesitedownmessage) {
                    audit('', 'Global Settings', 'Sitedown disabled');
                }
                $tmp = trim(strip_tags($sitedownmessage));
                if($tmp) {
                    cms_siteprefs::set('enablesitedownmessage', $enablesitedownmessage);
                } else {
                    $errors[] = lang('error_sitedownmessage');
                }
                cms_siteprefs::set('sitedownmessage', $sitedownmessage);
                cms_siteprefs::set('sitedownexcludes', $sitedownexcludes);
                cms_siteprefs::set('sitedownexcludeadmins', $sitedownexcludeadmins);
                break;
            case 'mail':
                // gather mailprefs
                $prefix = 'mailprefs_';
                foreach($_POST as $key => $val) {
                    if(!startswith($key, $prefix)) {
                        continue;
                    }
                    $key = substr($key, strlen($prefix));
                    $mailprefs[$key] = trim($val);
                }
                // validate
                if($mailprefs['from'] == '') {
                    $errors[] = lang('error_fromrequired');
                }
                elseif(!is_email($mailprefs['from'])) {
                    $errors[] = lang('error_frominvalid');
                }
                if($mailprefs['mailer'] == 'smtp') {
                    if($mailprefs['host'] == '') {
                        $errors[] = lang('error_hostrequired');
                    }
                    if($mailprefs['port'] == '') {
                        $mailprefs['port'] = 25;
                    } // convenience.
                    if($mailprefs['port'] < 1 || $mailprefs['port'] > 10240) {
                        $errors[] = lang('error_portinvalid');
                    }
                    if($mailprefs['timeout'] == '') {
                        $mailprefs['timeout'] = 180;
                    }
                    if($mailprefs['timeout'] < 1 || $mailprefs['timeout'] > 3600) {
                        $errors[] = lang('error_timeoutinvalid');
                    }
                    if($mailprefs['smtpauth']) {
                        if($mailprefs['username'] == '') {
                            $errors[] = lang('error_usernamerequired');
                        }
                        if($mailprefs['password'] == '') {
                            $errors[] = lang('error_passwordrequired');
                        }
                    }
                }
                // save.
                if(!$errors) {
                    cms_siteprefs::set('mail_is_set', 1);
                    cms_siteprefs::set('mailprefs', serialize($mailprefs));
                }
                break;
            case 'advanced':
                $lock_timeout = (int) $_POST['lock_timeout'];
                cms_siteprefs::set('lock_timeout', $lock_timeout);
                $xmlmodulerepository = cleanValue($_POST['xmlmodulerepository']);
                cms_siteprefs::set('xmlmodulerepository', $xmlmodulerepository);
                $checkversion = !empty($_POST['checkversion']);
                cms_siteprefs::set('checkversion', $checkversion);
                $global_umask = cleanValue($_POST['global_umask']);
                cms_siteprefs::set('global_umask', $global_umask);
                $allow_browser_cache = (int) $_POST['allow_browser_cache'];
                cms_siteprefs::set('allow_browser_cache', $allow_browser_cache);
                $browser_cache_expiry = (int) $_POST['browser_cache_expiry'];
                cms_siteprefs::set('browser_cache_expiry', $browser_cache_expiry);
                $auto_clear_cache_age = (int) $_POST['auto_clear_cache_age'];
                cms_siteprefs::set('auto_clear_cache_age', $auto_clear_cache_age);
                $adminlog_lifetime = (int) $_POST['adminlog_lifetime'];
                cms_siteprefs::set('adminlog_lifetime', $adminlog_lifetime);
                break;
            case 'smarty':
                if(!empty($_POST['use_smartycompilecheck'])) {
                    $use_smartycompilecheck = (int) $_POST['use_smartycompilecheck'];
                    cms_siteprefs::set('use_smartycompilecheck', $use_smartycompilecheck);
                }
                $gCms->clear_cached_files();
                break;
        } //switch tab

        if(!$errors) {
            // put mention into the admin log
            audit('', 'Global Settings', 'Edited');
            $messages[] = lang('siteprefsupdated');
        }
    } else {
        $errors[] = lang('noaccessto', 'Modify Site Permissions');
    }
} //
/**
 * Build page
 */

// Error if cache folders are not writable
if (!is_writable(TMP_CACHE_LOCATION) || !is_writable(TMP_TEMPLATES_C_LOCATION)) {
    $errors[] = lang('cachenotwritable');
}

if ($errors) {
    $themeObject->RecordMessage('error', $errors);
}
if ($messages) {
    $themeObject->RecordMessage('success', $messages);
}

$modops= ModuleOperations::get_instance();
$tmp = [-1 => lang('none')];
$modules = $modops->get_modules_with_capability('search');
if (is_array($modules) && ($n = count($modules))) {
    for ($i = 0; $i < $n; $i++) {
        $tmp[$modules[$i]] = $modules[$i];
    }
    $smarty->assign('search_module', null); //TODO current selection
} else {
    $smarty->assign('search_module', lang('none'));
}
$smarty->assign('search_modules', $tmp);

$tmp = [-1 => lang('default')];
$modules = $modops->get_modules_with_capability('adminlogin');
if (is_array($modules) && ($n = count($modules))) {
    for ($i = 0; $i < $n; $i++) {
        $tmp[$modules[$i]] = $modules[$i];
    }
    $smarty->assign('login_module', $login_module);
} else {
    $smarty->assign('login_module', lang('default'));
}
$smarty->assign('login_modules', $tmp);

$maileritems = [];
$maileritems['mail'] = 'mail';
$maileritems['sendmail'] = 'sendmail';
$maileritems['smtp'] = 'smtp';
$smarty->assign('maileritems', $maileritems);
$opts = [];
$opts[''] = lang('none');
$opts['ssl'] = 'SSL';
$opts['tls'] = 'TLS';
$smarty->assign('secure_opts', $opts);
$smarty->assign('mail_is_set', $mail_is_set);
$smarty->assign('mailprefs', $mailprefs);

$smarty->assign('languages', get_language_list());
$smarty->assign('tab', $tab);
$smarty->assign('pretty_urls', $pretty_urls);

// need a list of wysiwyg modules.

$tmp = \CMSMS\internal\module_meta::get_instance()->module_list_by_capability('wysiwyg');
$n = count($tmp);
$tmp2 = [-1 => lang('none')];
for ($i = 0; $i < $n; $i++) {
   $tmp2[$tmp[$i]] = $tmp[$i];
}
$smarty->assign('wysiwyg', $tmp2);

$tmp = glob(__DIR__.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
if ($tmp) {
    $themes = [];
	foreach ($tmp as $dir) {
		$file = basename($dir);
        if (@is_readable($dir.DIRECTORY_SEPARATOR.$file.'Theme.php')) {
	        $themes[$file] = $file;
        }
    }
    $smarty->assign('themes', $themes);
    $smarty->assign('logintheme', cms_siteprefs::get('logintheme', 'default'));
} else {
    $smarty->assign('themes', null);
    $smarty->assign('logintheme', null);
}

$smarty->assign('sitename', $sitename);
$smarty->assign('sitelogo', $sitelogo);
$smarty->assign('global_umask', $global_umask);
$smarty->assign('testresults', $testresults);
$smarty->assign('frontendlang', $frontendlang);
$smarty->assign('frontendwysiwyg', $frontendwysiwyg);
$smarty->assign('backendwysiwyg', $backendwysiwyg);
$smarty->assign('metadata', $metadata);
$smarty->assign('enablesitedownmessage', $enablesitedownmessage);
$smarty->assign('textarea_sitedownmessage', create_textarea(true, $sitedownmessage, 'sitedownmessage', 'pagesmalltextarea'));
$smarty->assign('checkversion', $checkversion);
$smarty->assign('defaultdateformat', $defaultdateformat);
$smarty->assign('lock_timeout', $lock_timeout);
$smarty->assign('sitedownexcludes', $sitedownexcludes);
$smarty->assign('sitedownexcludeadmins', $sitedownexcludeadmins);
$smarty->assign('basic_attributes', explode(',', $basic_attributes));
$smarty->assign('disallowed_contenttypes', explode(',', $disallowed_contenttypes));
$smarty->assign('thumbnail_width', $thumbnail_width);
$smarty->assign('thumbnail_height', $thumbnail_height);
$smarty->assign('allow_browser_cache', $allow_browser_cache);
$smarty->assign('browser_cache_expiry', $browser_cache_expiry);
$smarty->assign('auto_clear_cache_age', $auto_clear_cache_age);
$smarty->assign('content_autocreate_urls', $content_autocreate_urls);
$smarty->assign('content_autocreate_flaturls', $content_autocreate_flaturls);
$smarty->assign('content_mandatory_urls', $content_mandatory_urls);
$smarty->assign('content_imagefield_path', $content_imagefield_path);
$smarty->assign('content_thumbnailfield_path', $content_thumbnailfield_path);
$smarty->assign('content_cssnameisblockname', $content_cssnameisblockname);
$smarty->assign('contentimage_path', $contentimage_path);
$smarty->assign('adminlog_lifetime', $adminlog_lifetime);
$smarty->assign('search_module', $search_module);
$smarty->assign('use_smartycompilecheck', $use_smartycompilecheck);

$tmp = [
  60*60*24=>lang('adminlog_1day'),
  60*60*24*7=>lang('adminlog_1week'),
  60*60*24*14=>lang('adminlog_2weeks'),
  60*60*24*31=>lang('adminlog_1month'),
  60*60*24*31*3=>lang('adminlog_3months'),
  60*60*24*31*6=>lang('adminlog_6months'),
  -1=>lang('adminlog_manual'),
];
$smarty->assign('adminlog_options', $tmp);

$smarty->assign('lang_autoclearcache', lang('autoclearcache'));

$smarty->assign('lang_cancel', lang('cancel'));
$smarty->assign('lang_submit', lang('submit'));
$smarty->assign('lang_clearcache', lang('clearcache'));
$smarty->assign('lang_clear', lang('clear'));
$smarty->assign('lang_frontendlang', lang('frontendlang'));
$smarty->assign('lang_frontendwysiwygtouse', lang('frontendwysiwygtouse'));
$smarty->assign('lang_template', lang('template'));
$smarty->assign('lang_date_format_string_help', lang('date_format_string_help'));
$smarty->assign('lang_info_sitedownexcludes', lang('info_sitedownexcludes'));

$all_attributes = null;

$content_obj = new Content; // should this be the default type?
$list = $content_obj->GetProperties();
if (is_array($list) && count($list)) {
    // pre-remove some items.
    $all_attributes = [];
    for ($i = 0; $i < count($list); $i++) {
        $obj = $list[$i];
        if ($obj->tab == $content_obj::TAB_PERMS) {
            continue;
        }
        if (!isset($all_attributes[$obj->tab])) {
            $all_attributes[$obj->tab] = ['label'=>lang($obj->tab),'value'=>[]];
        }
        $all_attributes[$obj->tab]['value'][] = ['value'=>$obj->name,'label'=>lang($obj->name)];
    }
}
$txt = CmsFormUtils::create_option($all_attributes);

$smarty->assign('all_attributes', $all_attributes);
$smarty->assign('smarty_cacheoptions', ['always'=>lang('always'),'never'=>lang('never'),'moduledecides'=>lang('moduledecides')]);
$smarty->assign('smarty_cacheoptions2', ['always'=>lang('always'),'never'=>lang('never')]);

$contentops = cmsms()->GetContentOperations();
$all_contenttypes = $contentops->ListContentTypes(false, false);
$smarty->assign('all_contenttypes', $all_contenttypes);

$smarty->assign('yesno', [0=>lang('no'),1=>lang('yes')]);
$smarty->assign('titlemenu', [lang('menutext'),lang('title')]);

$smarty->assign('backurl', $themeObject->backUrl());
$selfurl = basename(__FILE__);
$smarty->assign('selfurl', $selfurl);
$smarty->assign('urlext', $urlext);

$smarty->display('siteprefs.tpl');

include_once 'footer.php';
