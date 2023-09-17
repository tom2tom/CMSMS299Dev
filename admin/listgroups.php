<?php
/*
Procedure to display all user-groups
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Lone;
use CMSMS\FormUtils;
use CMSMS\ScriptsMerger;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid();
$pmod = check_permission($userid, 'Manage Groups');
$groupops = Lone::get('GroupOperations');

if (isset($_GET['activate']) && isset($_GET['group_id']) && $pmod) {
    $gid = (int)$_GET['group_id']; // simple sanitization
    if ($gid > 1) {
        $group = $groupops->LoadGroupByID($gid);
        if ($group) {
            $group->active = (int)$_GET['activate'];
            $groupops->UpdateGroup($group);
        }
    }
}

$groups = $groupops->LoadGroups();
$grouplist = [];
$themeObject = Lone::get('Theme');
$t = $themeObject->DisplayImage('icons/system/true.gif', 'XXX', '', '', 'systemicon');
$icontrue1 = str_replace('XXX', _la('yes'), $t);
$icontrue2 = str_replace('XXX', _la('activetip2'), $t); // for links
$t = $themeObject->DisplayImage('icons/system/false.gif', 'XXX', '', '', 'systemicon');
$iconfalse1 = str_replace('XXX', _la('no'), $t);
$iconfalse2 = str_replace('XXX', _la('activetip1'), $t); // for links

$urlext = get_secure_param();

if ($pmod) {
    $u = 'changegroupassign.php'.$urlext.'&group_id=XXX';
    $t = _la('assignments');
    $icon = $themeObject->DisplayImage('icons/system/groupassign.gif', $t, '', '', 'systemicon');
    $assignlink = '<a href="'.$u.'" class="assign_group" data-group-id="XXX">'.$icon.'</a>'.PHP_EOL;

    $u = 'deletegroup.php'.$urlext.'&group_id=XXX';
    $t = _la('delete');
    $icon = $themeObject->DisplayImage('icons/system/delete.gif',$t , '', '', 'systemicon');
    $deletelink = '<a href="'.$u.'" class="delete_group js-delete" data-group-id="XXX">'.$icon.'</a>'.PHP_EOL;

    $u = 'editgroup.php'.$urlext.'&group_id=XXX';
    $t = _la('edit');
    $icon = $themeObject->DisplayImage('icons/system/edit.gif', $t, '', '', 'systemicon');
    $editlink = '<a href="'.$u.'" class="edit_group" data-group-id="XXX">'.$icon.'</a>'.PHP_EOL;

    $u = 'changegroupperm.php'.$urlext.'&group_id=XXX';
    $t = _la('permissions');
    $icon = $themeObject->DisplayImage('icons/system/permissions.gif', $t, '', '', 'systemicon');
    $permlink = '<a href="'.$u.'" class="permit_group" data-group-id="XXX">'.$icon.'</a>'.PHP_EOL;

    $t = _la('activetip2');
    $activlink = "<a href=\"listgroups.php{$urlext}&group_id=XXX&activate=0\" class=\"toggleactive\" title=\"$t\">YYY</a>";
    $t = _la('activetip1');
    $inactivlink = "<a href=\"listgroups.php{$urlext}&group_id=XXX&activate=1\" class=\"toggleactive\" title=\"$t\">YYY</a>";

    $menus = [];
    foreach ($groups as $one) {
        $gid = $one->id;
        $item = [
            'id' => $gid,
            'name' => $one->name,
            'desc' => $one->description,
            'active' => $one->active
        ];
        if ($gid == 1) {
            $item['status'] = str_replace(['XXX', 'YYY'], [$gid, $icontrue1], $activlink);
        } elseif ($one->active) {
            $item['status'] = str_replace(['XXX', 'YYY'], [$gid, $icontrue2], $activlink);
        } else {
            $item['status'] = str_replace(['XXX', 'YYY'], [$gid, $iconfalse2], $inactivlink);
        }
        $grouplist[] = $item;

        $acts = [];
        $acts[] = ['content'=>str_replace('XXX', $gid, $editlink)];
        $acts[] = ['content'=>str_replace('XXX', $gid, $assignlink)];
        $acts[] = ['content'=>str_replace('XXX', $gid, $permlink)];
        if ($gid != 1) {
            $acts[] = ['content'=>str_replace('XXX', $gid, $deletelink)];
        }
        $menus[] = FormUtils::create_menu($acts, ['id'=>'Group'.$gid]);
    }
} else { //!$pmod
    foreach ($groups as $one) {
        $gid = $one->id;
        $item = [
            'id' => $gid,
            'name' => $one->name,
            'desc' => $one->description,
        ];
        if ($gid == 1 || $one->active) {
            $item['status'] = str_replace(['XXX', 'YYY'], [$gid, $icontrue1], $activlink);
        } else {
            $item['status'] = str_replace(['XXX', 'YYY'], [$gid, $iconfalse1], $inactivlink);
        }
        $grouplist[] = $item;
    }
}

/* TODO consider (somewhat dangerous) bulk-actions: activate, deactivate, delete
$bulkactions = [];
$bulkactions['disable'] = _la('disable'); deactivate
$bulkactions['enable'] = _la('enable'); activate
$bulkactions['delete'] = _la('delete');
*/

$tblpaged = 'false';
$elid1 = 'null';
$elid2 = 'null';
$n = count($grouplist);
$sellength = 10; //OR some $_REQUEST[]
if ($n > 10) {
    $tblpaged = 'true';
    $tblpages = (int)ceil($n / 10);
    if ($tblpages > 2) {
        $elid1 = '"pspage"';
        $elid2 = '"ntpage"';
    }
    $pagelengths = [10 => 10];
    if ($n > 20) { $pagelengths[20] = 20; }
    if ($n > 40) { $pagelengths[40] = 40; }
    $pagelengths[0] = _la('all');
} else {
    $tblpages = 1;
    $pagelengths = [];
}

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery.SSsort.js', 1);
$jsm->queue_matchedfile('jquery.ContextMenu.js', 1);
$out = $jsm->page_content();

$s1 = addcslashes(_la('confirm_togglegroupactive'), "'\n\r");
$s2 = addcslashes(_la('confirm_delete_group'), "'\n\r");

$out .= <<<EOS
<script>
var listtable;
$(function() {
 listtable = document.getElementById('groupslist');
 var opts = {
  sortClass: 'SortAble',
  ascClass: 'SortUp',
  descClass: 'SortDown',
  oddClass: 'row1',
  evenClass: 'row2',
  oddsortClass: 'row1s',
  evensortClass: 'row2s'
 };
 if($tblpaged) {
  var xopts = $.extend({}, opts, {
   paginate: true,
   pagesize: $sellength,
   firstid: 'ftpage',
   previd: $elid1,
   nextid: $elid2,
   lastid: 'ltpage',
   selid: 'tblpagerows',
   currentid: 'cpage',
   countid: 'tpage'//,
// onPaged: function(table,pageid){}
  });
  $(listtable).SSsort(xopts);
  $('#tblpagerows').on('change',function() {
   var l = parseInt(this.value);
   if(l === 0) {
    $('#tblpagelink').hide();//TODO hide label-part 'per page'
   } else {
    $('#tblpagelink').show();//TODO show label-part 'per page'
   }
  });
 } else {
  $(listtable).SSsort(opts);
 }
 $(listtable).find('[context-menu]').ContextMenu();
 $('.toggleactive').on('click', function(ev) {
  ev.preventDefault();
  cms_confirm_linkclick(this, '$s1');
  return false;
 });
 $('.js-delete').on('click', function(ev) {
  ev.preventDefault();
  cms_confirm_linkclick(this, '$s2');
  return false;
 });
});
</script>
EOS;
add_page_foottext($out);


$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = Lone::get('Smarty');
$smarty->assign([
    'pmod' => $pmod,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'editurl' => 'editgroup.php',
    'grouplist' => $grouplist,
    'tblpages' => $tblpages,
    'pagelengths' => $pagelengths,
    'currentlength' => $sellength
]);

if ($pmod) {
    $iconadd = $themeObject->DisplayImage('icons/system/newobject.gif', _la('add'), '', '', 'systemicon');
    $iconmenu = $themeObject->DisplayImage('icons/system/menu.gif', _ld('layout','title_menu'), '', '', 'systemicon');
    $smarty->assign([
        'addurl' => 'addgroup.php',
        'groupmenus' => $menus,
        'iconadd' => $iconadd,
        'iconmenu' => $iconmenu
    ]);
}

$content = $smarty->fetch('listgroups.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
