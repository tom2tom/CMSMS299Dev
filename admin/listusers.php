<?php
/*
Procedure to list all admin console users
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

use CMSMS\AppParams;
use CMSMS\Error403Exception;
use CMSMS\Events;
use CMSMS\SingleItem;
use CMSMS\UserParams;
use function CMSMS\de_specialize;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid();
if (!check_permission($userid, 'Manage Users')) {
//TODO some pushed popup    $themeObject->RecordNotice('error', _la('needpermissionto', '"Modify Site Preferences"'));
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

//--------- Variables ---------
$themeObject = SingleItem::Theme();
$db = SingleItem::Db();
$templateuser = AppParams::get('template_userid');
$page = 1;
$limit = 100;
$message = '';
$error = '';
$userops = SingleItem::UserOperations();
$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
$urlext = get_secure_param();

//---------- Logic ----------

if (isset($_GET['switchuser'])) {
    // switch user functionality is only allowed for members of the admin group
    if ($userops->UserInGroup($userid, 1)) {
        $to_uid = (int)$_GET['switchuser'];
        $to_user = $userops->LoadUserByID($to_uid);
        if (!$to_user) {
            $themeObject->RecordNotice('error', _la('usernotfound'));
        } elseif (!$to_user->active) {
            $themeObject->RecordNotice('error', _la('userdisabled'));
        } else {
            SingleItem::LoginOperations()->set_effective_user($to_user);
            redirect('menu.php'.$urlext.'&section=usersgroups'); // TODO bad section hardcode
        }
    } else {
        $themeObject->RecordNotice('error', _la('permissiondenied'));
    }
} elseif (isset($_GET['toggleactive'])) {
	$to_uid = (int)$_GET['toggleactive'];
    if ($to_uid == 1) {
        $themeObject->RecordNotice('error', _la('errorupdatinguser'));
    } else {
        $thisuser = $userops->LoadUserByID($to_uid);
        if ($thisuser) {
            // modify users, is this enough?
//            $result = false;
            $thisuser->active == 1 ? $thisuser->active = 0 : $thisuser->active = 1;
            Events::SendEvent('Core', 'EditUserPre', ['user' => &$thisuser]);
            $result = $thisuser->save();

            if ($result) {
                // put mention into the admin log
                audit($userid, 'Admin User ' . $thisuser->username, 'Edited');
                Events::SendEvent('Core', 'EditUserPost', ['user' => &$thisuser]);
            } else {
                $themeObject->RecordNotice('error', _la('errorupdatinguser'));
            }
        }
    }
} elseif (isset($_POST['bulk']) && !empty($_POST['multiselect'])) {
//   CMSMS\de_specialize_array($_POST);
	$action = sanitizeVal($_POST['bulkaction'], CMSSAN_PURE); //letters only, specific values
    switch ($action) {
        case 'delete':
            $ndeleted = 0;
            foreach ($_POST['multiselect'] as $uid) {
                $uid = (int)$uid;
                if ($uid <= 1) {
                    continue; // can't delete the magic user...
                }

                if ($uid == $userid) {
                    continue; // can't delete self
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
                Events::SendEvent('Core', 'DeleteUserPre', ['user'=>&$oneuser]);
                $oneuser->Delete();
                Events::SendEvent('Core', 'DeleteUserPost', ['user'=>&$oneuser]);
                audit($uid, 'Admin User ' . $oneuser->username, 'Deleted');
                $ndeleted++;
            }
            if ($ndeleted > 0) {
                $message = _la('msg_userdeleted', $ndeleted);
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

                Events::SendEvent('Core', 'EditUserPre', ['user'=>&$oneuser]);
                UserParams::remove_for_user($uid);
                Events::SendEvent('Core', 'EditUserPost', ['user'=>&$oneuser]);
                audit($uid, 'Admin User ' . $oneuser->username, 'Settings cleared');
                $nusers++;
            }
            if ($nusers > 0) {
                $message = _la('msg_usersedited', $nusers);
            }
            break;

        case 'copyoptions':
            $nusers = 0;
            if (isset($_POST['userlist'])) {
                $fromuser = (int)$_POST['userlist'];
                if ($fromuser > 0) {
                    $prefs = UserParams::get_all_for_user($fromuser);
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
                            UserParams::remove_for_user($uid);
                            foreach ($prefs as $k => $v) {
                                UserParams::set_for_user($uid, $k, $v);
                            }
                            Events::SendEvent('Core', 'EditUserPost', [ 'user'=>&$oneuser ]);
                            audit($uid, 'Admin User ' . $oneuser->username, 'Settings cleared');
                            $nusers++;
                        }
                    }
                }
            }
            if ($nusers > 0) {
                $message = _la('msg_usersedited', $nusers);
            }
            break;

        case 'disable':
            $nusers = 0;
            foreach ($_POST['multiselect'] as $uid) {
                $uid = (int)$uid;
                if ($uid <= 1) {
                    continue; // can't disable the magic user...
                }

                if ($uid == $userid) {
                    continue; // can't disable self.
                }

                $oneuser = $userops->LoadUserById($uid);
                if (!is_object($oneuser)) {
                    continue; // invalid user
                }

                if ($oneuser->active) {
                    Events::SendEvent('Core', 'EditUserPre', ['user'=>&$oneuser]);
                    $oneuser->active = 0;
                    $oneuser->save();
                    Events::SendEvent('Core', 'EditUserPost', ['user'=>&$oneuser]);
                    audit($uid, 'Admin User ' . $oneuser->username, 'Disabled');
                    $nusers++;
                }
            }
            if ($nusers > 0) {
                $message = _la('msg_usersedited', $nusers);
            }
            break;

        case 'enable':
            $nusers = 0;
            foreach ($_POST['multiselect'] as $uid) {
                $uid = (int)$uid;
                if ($uid <= 1) {
                    continue; // can't disable the magic user...
                }

                if ($uid == $userid) {
                    continue; // can't disable self
                }

                $oneuser = $userops->LoadUserById($uid);
                if (!is_object($oneuser)) {
                    continue; // invalid user
                }

                if (!$oneuser->active) {
                    Events::SendEvent('Core', 'EditUserPre', ['user'=>&$oneuser]);
                    $oneuser->active = 1;
                    $oneuser->save();
                    Events::SendEvent('Core', 'EditUserPost', ['user'=>&$oneuser]);
                    audit($uid, 'Admin User ' . $oneuser->username, 'Enabled');
                    $nusers++;
                }
            }
            if ($nusers > 0) {
                $message = _la('msg_usersedited', $nusers);
            }
            break;
    }
}

//------- Script for page footer -------

//$nonce = get_csp_token();
$confirm1 = json_encode(_la('confirm_switchuser'));
$confirm2 = json_encode(_la('confirm_toggleuseractive'));
$confirm3 = json_encode(_la('confirm_delete_user'));
$confirm4 = json_encode(_la('confirm_bulkuserop'));
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
 $('#withselected, #bulksubmit').prop('disabled', true);
 $('#sel_all, .multiselect').on('click', function() {
  if(!$(this).is(':checked')) {
   $('#withselected').prop('disabled', true);
   cms_button_able($('#bulksubmit'), false);
  } else {
   $('#withselected').prop('disabled', false);
   cms_button_able($('#bulksubmit'),true);
  }
 });
 $('#listusers').submit(function(ev) {
  ev.preventDefault();
  var el = this,
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

add_page_foottext($out);

//--------- Display view ---------

if (!empty($error)) {
    $themeObject->RecordNotice('error', $error );
}
if (isset($_GET['message'])) {
    $message = de_specialize($_GET['message']);
}
if (!empty($message)) {
    $themeObject->RecordNotice('success', specialize($message));
}

$userlist = [];
$offset   = ((int)$page - 1) * $limit;
$users = $userops->LoadUsers($limit, $offset);
$is_admin = $userops->UserInGroup($userid, 1);

foreach ($users as &$one) {
    if (!$is_admin && $userops->UserInGroup($one->id, 1)) {
        $one->access_to_user = 0;
    } else {
        $one->access_to_user = 1;
    }
    $one->pagecount = $userops->CountPageOwnershipById($one->id);
    $userlist[$one->id] = $one;
}
unset($one);

$iconadd = $themeObject->DisplayImage('icons/system/newobject.gif', _la('adduser'), '', '', 'systemicon');
$iconedit = $themeObject->DisplayImage('icons/system/edit.gif', _la('edit'), '', '', 'systemicon');
$icondel = $themeObject->DisplayImage('icons/system/delete.gif', _la('delete'), '', '', 'systemicon');
$icontrue = $themeObject->DisplayImage('icons/system/true.gif', _la('yes'), '', '', 'systemicon');
$iconfalse = $themeObject->DisplayImage('icons/system/false.gif', _la('no'), '', '', 'systemicon');
$iconrun = $themeObject->DisplayImage('icons/system/run.gif', _la('switchuser'), '', '', 'systemicon');

$smarty = SingleItem::Smarty();
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
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'userlist' => $userlist,
]);

$content = $smarty->fetch('listusers.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
