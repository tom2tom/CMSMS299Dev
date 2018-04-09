<?php
#CMSMS admin-login and -logout processing for a theme
#Copyright (C) 2004-2016 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
 * Send lost-password recovery email to a specified admin user (by name)
 *
 * @param object $user user data
 * @return results from the attempt to send a message.
 */
function send_recovery_email(User $user)
{
    $obj = new cms_mailer();
    $obj->IsHTML(TRUE);
    $obj->AddAddress($user->email,cms_html_entity_decode($user->firstname.' '.$user->lastname));
    $obj->SetSubject(lang('lostpwemailsubject',html_entity_decode(get_site_preference('sitename','CMSMS Site'))));

    $config = cms_config::get_instance();
    $url = $config['admin_url'] . '/login.php?recoverme=' . md5(md5($config['root_path'] . '--' . $user->username . md5($user->password)));
    $body = lang('lostpwemail',cms_html_entity_decode(get_site_preference('sitename','CMSMS Site')), $user->username, $url, $url);

    $obj->SetBody($body);
    $res = $obj->Send();
    if ($res) {
        audit('','Core','Sent Lost Password Email for '.$user->username);
    } else {
        //TODO handle error
        //throw new CmsLoginError (TODO);
    }
    return $res;
}

/**
 * Find the user id corresponding to the given identity-hash
 *
 * @param string the hash
 * @return object The matching user object if found, or null otherwise.
 */
function find_recovery_user($hash)
{
    $config = cms_config::get_instance();
    $userops = CmsApp::get_instance()->GetUserOperations();

    foreach ($userops->LoadUsers() as $user) {
        if ($hash == md5(md5($config['root_path'] . '--' . $user->username . md5($user->password)))) return $user;
    }
    return null;
}

function check_secure_param($id)
{
    $expected = $_SESSION[$csrf_key] ?? null;
    $provided = $_POST['csrf'] ?? null; //comparison value, no sanitize needed (unless $_SESSION hacked!)
    if (!$expected || !$provided || $expected != $provided) {
        throw new \RuntimeException ($this->Lang('err_csrfinvalid').' ('.$id.')');
    }
}

$gCms = CmsApp::get_instance();
$login_ops = CMSMS\internal\LoginOperations::get_instance();
$csrf_key = md5(__FILE__);

//Redirect to the normal login screen if we hit cancel on the forgot pw one
//Otherwise, see if we have a forgotpw hit

if ((isset($_REQUEST['forgotpwform']) || isset($_REQUEST['forgotpwchangeform'])) && isset($_REQUEST['logincancel'])) {
    redirect('login.php');
} elseif (isset($_REQUEST['forgotpwform']) && isset($_REQUEST['forgottenusername'])) {
    $userops = $gCms->GetUserOperations();
    $forgot_username = filter_var($_REQUEST['forgottenusername'], FILTER_SANITIZE_STRING);
    unset($_REQUEST['forgottenusername'],$_POST['forgottenusername']);
    CMSMS\HookManager::do_hook('Core::LostPassword', [ 'username'=>$forgot_username]);
    $oneuser = $userops->LoadUserByUsername($forgot_username);
    unset($_REQUEST['loginsubmit'],$_POST['loginsubmit']);

    if ($oneuser != null) {
        if ($oneuser->email == '') {
            $error = lang('nopasswordforrecovery');
        } elseif (send_recovery_email($oneuser)) {
            $warning = lang('recoveryemailsent');
        } else {
            $error = lang('errorsendingemail');
        }
    } else {
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        CMSMS\HookManager::do_hook('Core::LoginFailed', [ 'user'=>$forgot_username ]);
        $error = lang('usernotfound');
    }
} elseif (!empty($_REQUEST['recoverme'])) {
//    check_secure_param('002');
    $user = find_recovery_user(cleanVariable($_REQUEST['recoverme']));
    if ($user == null) {
        $error = lang('usernotfound');
    } else {
        $changepwhash = true;
    }
} elseif (!empty($_REQUEST['forgotpwchangeform'])) {
//    check_secure_param('003');
    $user = find_recovery_user($_REQUEST['changepwhash']);
    if ($user == null) {
        $error = lang('usernotfound');
    } elseif ($_REQUEST['password'] != '') {
        if ($_REQUEST['password'] == $_REQUEST['passwordagain']) {
            $user->SetPassword($_REQUEST['password']);
            $user->Save();
            // put mention into the admin log
            $ip_passw_recovery = cms_utils::get_real_ip();
            audit('','Core','Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
            CMSMS\HookManager::do_hook('Core::LostPasswordReset', [ 'uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery ]);
            $message = lang('passwordchangedlogin');
            $changepwhash = '';
        } else {
            $error = lang('nopasswordmatch');
            $changepwhash = $_REQUEST['changepwhash'];
        }
    } else {
        $error = lang('nofieldgiven', lang('password'));
        $changepwhash = $_REQUEST['changepwhash'];
    }
}

$config = cms_config::get_instance();

if (isset($_SESSION['logout_user_now'])) {
    // this does the actual logout stuff.
    unset($_SESSION['logout_user_now']);
    debug_buffer("Logging out.  Cleaning cookies and session variables.");
    $userid = $login_ops->get_loggedin_uid();
    $username = $login_ops->get_loggedin_username();
    CMSMS\HookManager::do_hook('Core::LogoutPre', [ 'uid'=>$userid, 'username'=>$username ]);
    $login_ops->deauthenticate(); // unset all the cruft needed to make sure we're logged in.
    CMSMS\HookManager::do_hook('Core::LogoutPost', [ 'uid'=>$userid, 'username'=>$username ]);
    audit($userid, "Admin Username: ".$username, 'Logged Out');
}

if (isset($_POST['cancel'])) {
//    $login_ops->deauthenticate(); // just in case
    redirect('login.php');
} elseif (isset($_POST['submit'])) {
    // login form submitted
    $login_ops->deauthenticate();
    $username = (isset($_POST['username'])) ? cleanValue($_POST['username']) : null;
    $password = $_POST['password'] ?? null; //no cleanup: any char is valid, & hashed before storage
    $userops = $gCms->GetUserOperations();

    class CmsLoginError extends CmsException {}

    try {
//        check_secure_param('004');
        if (!$password) throw new CmsLoginError(lang('usernameincorrect'));
        $oneuser = $userops->LoadUserByUsername($username, $password, TRUE, TRUE);
        if (!$oneuser) throw new CmsLoginError(lang('usernameincorrect'));
        if (! $oneuser->Authenticate ($password)) {
            throw new CmsLoginError (lang('usernameincorrect'));
        }
        $login_ops->save_authentication($oneuser);

        // put mention into the admin log
        audit($oneuser->id, "Admin Username: ".$oneuser->username, 'Logged In');

        // send the post login event
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        CMSMS\HookManager::do_hook('Core::LoginPost', ['user'=>&$oneuser]);

        // redirect outa hre somewhere
        if (isset($_SESSION['login_redirect_to'])) {
            // we previously attempted a URL but didn't have the user key in the request.
            $url_ob = new cms_url($_SESSION['login_redirect_to']);
            unset($_SESSION['login_redirect_to']);
            $url_ob->erase_queryvar('_s_');
            $url_ob->erase_queryvar('sp_');
            $url_ob->set_queryvar(CMS_SECURE_PARAM_NAME,$_SESSION[CMS_USER_KEY]);
            $url = (string) $url_ob;
            redirect($url);
        } else {
            // find the users homepage, if any, and redirect there.
            $homepage = cms_userprefs::get_for_user($oneuser->id,'homepage');
            if (!$homepage) {
                $homepage = $config['admin_url'];
            }
            // quick hacks to remove old secure param name from homepage url
            // and replace with the correct one.
            $homepage = str_replace('&amp;','&',$homepage);
            $tmp = explode('?',$homepage);
            @parse_str($tmp[1],$tmp2);
            if (in_array('_s_',array_keys($tmp2))) unset($tmp2['_s_']);
            if (in_array('sp_',array_keys($tmp2))) unset($tmp2['sp_']);
            $tmp2[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
            foreach ($tmp2 as $k => $v) {
                $tmp3[] = $k.'='.$v;
            }
            $homepage = $tmp[0].'?'.implode('&amp;',$tmp3);

            // and redirect.
            $homepage = cms_html_entity_decode($homepage);
            if (!startswith($homepage,'http') && !startswith($homepage,'//') && startswith($homepage,'/')) $homepage = CMS_ROOT_URL.$homepage;
            redirect($homepage);
        }
    } catch (Exception $e) {
        $error = $e->GetMessage();
        debug_buffer("Login failed.  Error is: " . $error);
        unset($_POST['password'],$_REQUEST['password']);
        CMSMS\HookManager::do_hook('Core::LoginFailed', [ 'user'=>$_POST['username'] ]);
        // put mention into the admin log
        $ip_login_failed = cms_utils::get_real_ip();
        audit('', '(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Login Failed');
    }
}

// vars for smarty

$params = [];
$params['actionid'] = ''; //maybe altered upstream
$params['admin_url'] = $config['admin_url'];

$params['encoding'] = CmsNlsOperations::get_encoding();
$lang = CmsNlsOperations::get_current_language();
if (($p = strpos($lang,'_')) !== false) {
    $lang = substr($lang,0,$p);
}
$params['lang_code'] = $lang;
$params['lang_dir'] = CmsNlsOperations::get_language_direction();
$sitelogo = cms_siteprefs::get('sitelogo');
if ($sitelogo) {
    if (!preg_match('~^\w*:?//~',$sitelogo)) {
        $sitelogo = CMS_ROOT_URL.'/'.trim($sitelogo, ' /');
    }
}
$params['sitelogo'] = $sitelogo;
$_SESSION[$csrf_key] = $params['csrf'] = md5(uniqid($csrf_key));

if (isset($error)) $params['error'] = $error;
if (isset($warning)) $params['warning'] = $warning;
if (isset($message)) $params['message'] = $message;
if (isset($changepwhash)) $params['changepwhash'] = $changepwhash;
