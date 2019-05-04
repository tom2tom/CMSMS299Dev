<?php
#procedure to list all backend users
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

use CMSMS\Events;
use CMSMS\internal\LoginOperations;
use CMSMS\UserOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$userid = get_userid();

$themeObject = cms_utils::get_theme_object();

if (!check_permission($userid, 'Manage Users')) {
    //TODO some immediate popup
    return;
}

/*--------------------
 * Variables
 ---------------------*/

$gCms         = cmsms();
$db           = $gCms->GetDb();
$templateuser = cms_siteprefs::get('template_userid');
$page         = 1;
$limit        = 100;
$message      = '';
$error        = '';
$userops      = new UserOperations();
$selfurl      = basename(__FILE__);

/*--------------------
 * Logic
 ---------------------*/

if (isset($_GET['switchuser'])) {
    // switch user functionality is only allowed to members of the admin group
    if (!$userops->UserInGroup($userid, 1)) {
        $themeObject->RecordNotice('error', lang('permissiondenied'));
    } else {
        $to_uid = (int) $_GET['switchuser'];
        $to_user = $userops->LoadUserByID($to_uid);
        if (!$to_user) {
            $themeObject->RecordNotice('error', lang('usernotfound'));
        }
        if (! $to_user->active) {
            $themeObject->RecordNotice('error', lang('userdisabled'));
        } else {
            (new LoginOperations())->set_effective_user($to_user);
            $urlext = '?' . CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY];
            redirect('index.php'.$urlext);
        }
    }
} elseif (isset($_GET['toggleactive'])) {
    if ($_GET['toggleactive'] == 1) {
        $themeObject->RecordNotice('error', lang('errorupdatinguser'));
    } else {
        $thisuser = $userops->LoadUserByID((int)$_GET['toggleactive']);
        if ($thisuser) {
            // modify users, is this enough?
            $userid = get_userid();

            $result = false;
            $thisuser->active == 1 ? $thisuser->active = 0 : $thisuser->active = 1;
            Events::SendEvent('Core', 'EditUserPre', [ 'user' => &$thisuser ]);
            $result = $thisuser->save();

            if ($result) {
                // put mention into the admin log
                audit($userid, 'Admin Username: ' . $thisuser->username, 'Edited');
                Events::SendEvent('Core', 'EditUserPost', [ 'user' => &$thisuser ]);
            } else {
                $themeObject->RecordNotice('error', lang('errorupdatinguser'));
            }
        }
    }
} elseif (isset($_POST['bulk']) && isset($_POST['multiselect']) && $_POST['multiselect']) {
    switch ($_POST['bulkaction']) {
        case 'delete':
            $ndeleted = 0;
            foreach ($_POST['multiselect'] as $uid) {
                $uid = (int)$uid;
                if ($uid <= 1) {
                    continue; // can't delete the magic user...
                }

                if ($uid == get_userid()) {
                    continue; // can't delete self.
                }

                $oneuser = $userops->LoadUserById($uid);
                if (!is_object($oneuser)) {
                    continue; // invalid user
                }

                $ownercount = $userops->CountPageOwnershipById($uid);
                if ($ownercount > 0) {
                    continue; // can't delete user who owns pages.
                }

                // ready to delete.
                Events::SendEvent('Core', 'DeleteUserPre', [ 'user'=>&$oneuser ]);
                $oneuser->Delete();
                Events::SendEvent('Core', 'DeleteUserPost', [ 'user'=>&$oneuser ]);
                audit($uid, 'Admin Username: ' . $oneuser->username, 'Deleted');
                $ndeleted++;
            }
            if ($ndeleted > 0) {
                $message = lang('msg_userdeleted', $ndeleted);
            }
            break;

        case 'clearoptions':
            $nusers = 0;
            foreach ($_POST['multiselect'] as $uid) {
                $uid = (int)$uid;
                if ($uid <= 1) {
                    continue;
                } // can't edit the magic user...

                $oneuser = $userops->LoadUserById($uid);
                if (!is_object($oneuser)) {
                    continue;
                } // invalid user

                Events::SendEvent('Core', 'EditUserPre', [ 'user'=>&$oneuser ]);
                cms_userprefs::remove_for_user($uid);
                Events::SendEvent('Core', 'EditUserPost', [ 'user'=>&$oneuser ]);
                audit($uid, 'Admin Username: ' . $oneuser->username, 'Settings cleared');
                $nusers++;
            }
            if ($nusers > 0) {
                $message = lang('msg_usersedited', $nusers);
            }
            break;

        case 'copyoptions':
            $nusers = 0;
            if (isset($_POST['userlist'])) {
                $fromuser = (int)$_POST['userlist'];
                if ($fromuser > 0) {
                    $prefs = cms_userprefs::get_all_for_user($fromuser);
                    if ($prefs) {
                        foreach ($_POST['multiselect'] as $uid) {
                            $uid = (int)$uid;
                            if ($uid <= 1) {
                                continue; // can't edit the magic user...
                            }

                            if ($uid == $fromuser) {
                                continue; // can't overwrite the same users prefs.
                            }
                            $oneuser = $userops->LoadUserById($uid);
                            if (!is_object($oneuser)) {
                                continue; // invalid user
                            }

                            Events::SendEvent('Core', 'EditUserPre', [ 'user'=>&$oneuser ]);
                            cms_userprefs::remove_for_user($uid);
                            foreach ($prefs as $k => $v) {
                                cms_userprefs::set_for_user($uid, $k, $v);
                            }
                            Events::SendEvent('Core', 'EditUserPost', [ 'user'=>&$oneuser ]);
                            audit($uid, 'Admin Username: ' . $oneuser->username, 'Settings cleared');
                            $nusers++;
                        }
                    }
                }
            }
            if ($nusers > 0) {
                $message = lang('msg_usersedited', $nusers);
            }
            break;

        case 'disable':
            $nusers = 0;
            foreach ($_POST['multiselect'] as $uid) {
                $uid = (int)$uid;
                if ($uid <= 1) {
                    continue; // can't disable the magic user...
                }

                if ($uid == get_userid()) {
                    continue; // can't disable self.
                }

                $oneuser = $userops->LoadUserById($uid);
                if (!is_object($oneuser)) {
                    continue; // invalid user
                }

                if ($oneuser->active) {
                    Events::SendEvent('Core', 'EditUserPre', [ 'user'=>&$oneuser ]);
                    $oneuser->active = 0;
                    $oneuser->save();
                    Events::SendEvent('Core', 'EditUserPost', [ 'user'=>&$oneuser ]);
                    audit($uid, 'Admin Username: ' . $oneuser->username, 'Disabled');
                    $nusers++;
                }
            }
            if ($nusers > 0) {
                $message = lang('msg_usersedited', $nusers);
            }
            break;

        case 'enable':
            $nusers = 0;
            foreach ($_POST['multiselect'] as $uid) {
                $uid = (int)$uid;
                if ($uid <= 1) {
                    continue; // can't disable the magic user...
                }

                if ($uid == get_userid()) {
                    continue; // can't disable self.
                }

                $oneuser = $userops->LoadUserById($uid);
                if (!is_object($oneuser)) {
                    continue; // invalid user
                }

                if (!$oneuser->active) {
                    Events::SendEvent('Core', 'EditUserPre', [ 'user'=>&$oneuser ]);
                    $oneuser->active = 1;
                    $oneuser->save();
                    Events::SendEvent('Core', 'EditUserPost', [ 'user'=>&$oneuser ]);
                    audit($uid, 'Admin Username: ' . $oneuser->username, 'Enabled');
                    $nusers++;
                }
            }
            if ($nusers > 0) {
                $message = lang('msg_usersedited', $nusers);
            }
            break;
    }
}

/*--------------------
 * Script for page footer
 ---------------------*/

$confirm1 = json_encode(lang('confirm_switchuser'));
$confirm2 = json_encode(lang('confirm_toggleuseractive'));
$confirm3 = json_encode(lang('confirm_delete_user'));
$confirm4 = json_encode(lang('confirm_bulkuserop'));
$out = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('#sel_all').cmsms_checkall();
 $('.switchuser').on('click', function(ev) {
  ev.preventDefault();
  cms_confirm_linkclick(this, $confirm1);
  return false;
 });
 $('.toggleactive').on('click', function(ev) {
  ev.preventDefault();
  cms_confirm_linkclick(this, $confirm2);
  return false;
 });
 $('.js-delete').on('click', function(ev) {
  ev.preventDefault();
  cms_confirm_linkclick(this, $confirm3);
  return false;
 });
 $('#withselected, #bulksubmit').attr('disabled', 'disabled');
 $('#sel_all, .multiselect').on('click', function() {
  if(!$(this).is(':checked')) {
   $('#withselected').attr('disabled', 'disabled');
   cms_button_able($('#bulksubmit'), false);
  } else {
   $('#withselected').removeAttr('disabled');
   cms_button_able($('#bulksubmit'),true);
  }
 });
 $('#listusers').submit(function(ev) {
  ev.preventDefault();
  val el = this,
    v = $('#withselected').val();
  if(v === 'delete') {
   cms_confirm($confirm3).done(function() {
    $(el).off('submit').submit();
   });
  } else {
   cms_confirm($confirm4).done(function() {
    $(el).off('submit').submit();
   });
  }
  return false;
 });
 $('#withselected').change(function() {
  var v = $(this).val();
  if(v === 'copyoptions') {
   $('#userlist').show();
  } else {
   $('#userlist').hide();
  }
 });
});
//]]>
</script>
EOS;

$themeObject->add_footertext($out);

/*--------------------
 * Display view
 ---------------------*/

if (!empty($error)) {
    $themeObject->RecordNotice('error', $error );
}
if (isset($_GET['message'])) {
    $message = preg_replace('/\</', '', $_GET['message']);
}
if (!empty($message)) {
    $themeObject->RecordNotice('success', $message);
}

$userlist = [];
$offset   = ((int)$page - 1) * $limit;
$users = $userops->LoadUsers($limit, $offset);
$is_admin = $userops->UserInGroup($userid, 1);

foreach ($users as &$one) {
    $one->access_to_user = 1;
    if ($userops->UserInGroup($one->id, 1) && !$userops->UserInGroup($userid, 1)) {
        $one->access_to_user = 0;
    }
    $one->pagecount = $userops->CountPageOwnershipById($one->id);
    $userlist[$one->id] = $one;
}
unset($one);

$iconadd = $themeObject->DisplayImage('icons/system/newobject.gif', lang('adduser'), '', '', 'systemicon');
$iconedit = $themeObject->DisplayImage('icons/system/edit.gif', lang('edit'), '', '', 'systemicon');
$icondel = $themeObject->DisplayImage('icons/system/delete.gif', lang('delete'), '', '', 'systemicon');
$icontrue = $themeObject->DisplayImage('icons/system/true.gif', lang('yes'), '', '', 'systemicon');
$iconfalse = $themeObject->DisplayImage('icons/system/false.gif', lang('no'), '', '', 'systemicon');
$iconrun = $themeObject->DisplayImage('icons/system/run.gif', lang('TODO'), '', '', 'systemicon'); //used for switch-user

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
    'addurl' => 'adduser.php',
    'editurl' => 'edituser.php',
    'deleteurl' => 'deleteuser.php',
    'is_admin' => $is_admin,
    'iconadd' => $iconadd,
    'icondel' => $icondel,
    'iconedit' => $iconedit,
    'iconfalse' => $iconfalse,
    'iconrun' => $iconrun,
    'icontrue' => $icontrue,
    'my_userid' => $userid,
    'urlext' => $urlext,
    'selfurl' => $selfurl,
    'userlist' => $userlist,
]);

include_once 'header.php';
$smarty->display('listusers.tpl');
include_once('footer.php');
