<?php
/*
Procedure for the current admin user to modify her/his own account data
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Error403Exception;
use CMSMS\Events;
use CMSMS\ScriptsMerger;
use CMSMS\Lone;
use CMSMS\UserOperations;
use function CMSMS\de_specialize_array;
use function CMSMS\log_info;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('menu.php'.$urlext.'&section=usersgroups'); // TODO bad hardcode
}

$userid = get_userid();

$themeObject = Lone::get('Theme');

if (!check_permission($userid, 'Manage My Account')) {
//TODO some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage My Account')), ...);
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$userobj = Lone::get('UserOperations')->LoadUserByID($userid);

/* DEBUG
$tester = function($user, $pw)
{
	$lvl = 2; //AppParams::get('password_level', 0);
	switch ($lvl) {
		case 3:
			$s = 'Very Strong';
			break;
		case 2:
			$s = 'Strong';
			break;
		case 1:
			$s = 'Medium';
			break;
		default:
			return [true, '']; // don't care
	}
	// prohibited obvious references
	$avoids = ['CMSMS', 'cmsms', $user->username];
	if ($user->firstname !== '') { $avoids[] = $user->firstname; }
	if ($user->lastname !== '') { $avoids[] = $user->lastname; }
	$tmp = trim($user->firstname.$user->lastname);
	if ($tmp) { $avoids[] = $tmp; }
	$tmp = AppParams::get('sitename');
	if ($tmp) {
		$avoids[] = strtr(strtolower($tmp), [' '=>'', '_'=>'']);
		$avoids[] = $tmp;
	}
	// custom evaluation options
	$options = [
		'disable' => ['upper','lower','numeric','special'],
		'maxlen-guessable-test' => 12,
		'strength' => $s,
	];
	$checker = new StupidPass(64, $avoids, '', [], $options);
	if (!$checker->validate($pw)) {
		$errs = $checker->getErrors();
		return [false, $errs];
	}
	return [true, ''];
};

$Ares = $tester($userobj, 's11ku');
*/

if (isset($_POST['submit'])) {
    $errors = [];
    $usrops = new UserOperations();

    $tpw = $_POST['password'] ?? ''; // preserve these verbatim
    $tpw2 = $_POST['passwordagain'] ?? '';
    unset($_POST['password'], $_POST['passwordagain']);
    $nmok = $pwok = true;

    de_specialize_array($_POST);
    // validate submitted data
    $tmp = trim($_POST['user']);
    $username = sanitizeVal($tmp, CMSSAN_ACCOUNT);
    if ($username != $tmp) {
        $errors[] = _la('illegalcharacters', _la('username'));
        $nmok = false; // no name-policy-check
    } elseif (!($username || is_numeric($username))) { // allow username '0' ???
        $errors[] = _la('nofieldgiven', _la('username'));
        $nmok = false;
    } elseif ($username != $userobj->username) {
        //check for duplication of new name
        if (!$usrops->ReserveUsername($username)) {
            $errors[] = _la('errorbadname');
            $nmok = false;
        }
    }

    if ($tpw) {
        //per https://pages.nist.gov/800-63-3/sp800-63b.html : valid = printable ASCII | space | Unicode
        $password = sanitizeVal($tpw, CMSSAN_NONPRINT);
        if (!$password || $password != $tpw) {
            $errors[] = _la('illegalcharacters', _la('password'));
            $pwok = false; // no pw-policy-check
        } else {
            $again = sanitizeVal($tpw2, CMSSAN_NONPRINT);
            if ($password != $again) {
                $errors[] = _la('nopasswordmatch');
                $pwok = false;
            }
        }
    } else {
        $password = null;
    }

    //$tmp = trim($_POST['email']);
    //ignore invalid chars in the email
    //BUT PHP's FILTER_VALIDATE_EMAIL mechanism is not entirely reliable - see notes at https://www.php.net/manual/en/function.filter-var.php
    //$email = filter_var($tmp, FILTER_SANITIZE_EMAIL);
    $email = trim($_POST['email']);
    if ($email && !is_email($email)) {
        $errors[] = _la('invalidemail').': '.$email;
    }

    //if credentials policy(ies) apply, check
    if (($nmok && $username && $username != $userobj->username) ||
        ($pwok && $password && $usrops->PreparePassword($password) != $userobj->password)) {
          // record properties involved in credentials checks
//        $userobj->username = $username;
        $val = sanitizeVal($_POST['firstname'], CMSSAN_NONPRINT); // OR ,CMSSAN_PUNCT OR ,CMSSAN_PURESPC OR no-gibberish 3.0 breaking change
        if ($val) { $userobj->firstname = $val; }
        $val = sanitizeVal($_POST['lastname'], CMSSAN_NONPRINT); // OR ,CMSSAN_PUNCT OR ,CMSSAN_PURESPC OR no-gibberish 3.0 breaking change
        if ($val) { $userobj->lastname = $val; }
        $userobj->email = $email; // TODO might be bad
        $msg = ''; //feedback err msg holder
        Events::SendEvent('Core', 'CheckUserData', [
            'user'=>$userobj,
            'username'=> (($nmok) ? $username : null),
            'password'=> (($pwok) ? $password : null),
            'update'=>true,
            'report'=>&$msg,
        ]);
        if ($msg) {
            $errors[] = $msg;
        }
    }
    if (!$errors) {
        if (!$password || $userobj->SetPassword($password)) {
            Events::SendEvent('Core', 'EditUserPre', ['user'=>$userobj]);

            $result = $userobj->Save();
            if ($result) {
                // put mention into the admin log
                log_info($userid, 'Admin User '.$userobj->username, 'Edited');
                Events::SendEvent('Core', 'EditUserPost', [ 'user'=>$userobj ]);
                $themeObject->RecordNotice('success', _la('accountupdated'));
                $userobj->password = '';
            } else {
                $errors[] = _la('error_internal');
            }
        } elseif ($password) {
            $errors[] = _la('error_passwordinvalid');
        }
    }
    if ($errors) {
        $themeObject->RecordNotice('error', $errors);
        $userobj->password = $password;
    }
} else { // no submitting now
    $userobj->password = '';
}
/*
 * [Re]build page
 */

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery-inputCloak.js', 1);
$js = <<<EOS
$(function() {
 $('#password,#passagain').inputCloak({
  type:'see4',
  symbol:'\u25CF'
 });
});
EOS;
$jsm->queue_string($js, 3);
$out = $jsm->page_content();
if ($out) {
    add_page_foottext($out);
}

$userobj->username = specialize($userobj->username);
$userobj->firstname = specialize($userobj->firstname);
$userobj->lastname = specialize($userobj->lastname);
$userobj->email = specialize($userobj->email);

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = Lone::get('Smarty');
$smarty->assign([
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'userobj'=>$userobj,
]);

$content = $smarty->fetch('useraccount.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
