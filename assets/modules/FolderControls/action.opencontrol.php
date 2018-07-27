<?php
/*
FolderControls module action: add or edit an access-controls set (aka 'profile')
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\FileType;

if (!function_exists('cmsms')) {
    exit;
}
if (!$this->CheckPermission('Modify Site Preferences')) {
    exit;
}

if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', $returnid);
}

$themeObject = cms_utils::get_theme_object();
$setid = $params['setid'] ?? -1;
$setid = (int)$setid;

$ob = new FolderControls\ControlSet();

if (isset($params['submit'])) {
    try {
//TODO update all $ob params from $params
/*      foreach ([
'name'
'top_dir'
'can_delete'=>self::FLAG_YES,
'can_mkdir'=>self::FLAG_YES,
'can_mkfile'=>self::FLAG_YES,
'exclude_groups'=>[], //array of group-id's
'exclude_users'=>[],  //array of user-id's
'exclude_patterns'=>[], //array of regex's - barred item-names
'match_groups'=>[], //array of group-id's
'match_users'=>[], //array of user-id's
'match_patterns'=>[], //array of regex's - acceptable item-names
'show_hidden'=>self::FLAG_NO,
'show_thumbs'=>self::FLAG_YES,
'sort_by'=>'name', // item-property - name,size,created,modified + [a[sc]] | d[esc]
'sort_asc' bool
'file_types'=>[FileType::ANY], //array of acceptable type-enumerators//
//      ] as $key) {$ob->$key = $params[$key]; }
*/
        $ob->save();
        $themeObject->ParkNotice('success', $this->Lang('controlsupdated'));
        redirect('listcontrols.php'.$urlext);
    } catch (ProfileException $e) {
        $themeObject->RecordNotice('error', $e->GetMessage());
        $set = new stdClass();
        //TODO populate $set from $params data
    }
} elseif ($setid > 0) {
    $row = $db->GetRow('SELECT id,name,data FROM '.CMS_DB_PREFIX.'module_excontrols WHERE id=?',[$setid]);
    if ($row) {
        $set = new stdClass();
        $set->id = $row['id'];
        $set->name = $row['name'];
        $detail = unserialize($row['data']);
        foreach ($detail as $k => $v) {
            $set->$k = $v;
        }
    } else {
        $themeObject->ParkNotice('error', 'TODO');
        $this->Redirect($id, 'defaultadmin', $returnid);
    }
} else {
    $set = (object)$ob->getRawData();
    $set->id = -1;
    $set->name = '';
    $set->reltop = '';
}

$arr = [];
foreach (FileType::getNames() as $key) {
    switch ($key) {
        case 'NONE':
        case 'ANY':
            break;
        default:
            if (strncmp($key, 'TYPE_', 5) !== 0) {
                $arr[$key] = FileType::getValue($key);
            }
            break;
    }
}
ksort($arr);
$sel = ($set->file_types) ? $set->file_types : $arr['ALL'];
$typesel = CmsFormUtils::create_select([
    'type' => 'list',
    'name' => 'file_types',
    'id' => 'filetype',
    'multiple' => true,
    'options' => $arr,
    'selectedvalue' => $sel,
]);

// TODO real selector for sortfield
// sort: 'name','size','date' modified?? created ??  & optionally appended ',[a[sc]]' or ',d[esc]'
$arr = [
    'name' => $this->Lang('name'),
    'size' => 'Size',
    'date' => 'Last modified',
];
$sel = ($set->sort_by) ? $set->sort_by : 'name'; //TODO parse direction
$sortasc = 1; //TODO from $set->sort_by if any
$sortsel = CmsFormUtils::create_select([
    'type' => 'drop',
    'name' => 'sort_by',
    'id' => 'sortby',
    'options' => array_flip($arr),
    'selectedvalue' => $sel,
]);

// don't care about users' active-state
$sql = 'SELECT user_id,first_name,last_name FROM '.CMS_DB_PREFIX.'users WHERE WHERE user_id!=1 AND admin_access=1 ORDER BY last_name, first_name';
$rows = $db->GetArray($sql);
$users = [-1 => $this->Lang('all_users')];
foreach ($rows as &$one) {
    $users[$one['user_id']] = $one['first_name'].' '.$one['last_name'];
}
unset($one);

if (count($users) > 1) {
    $sel = ($set->match_users) ? $set->match_users : -1;
    $inusersel = CmsFormUtils::create_select([
        'type' => 'list',
        'name' => 'match_users',
        'id' => 'incuser',
        'multiple' => true,
        'options' => array_flip($users),
        'selectedvalue' => $sel,
    ]);
} else {
    $inusersel = $this->Lang('nouser');
}

unset($users[-1]);
if ($users) {
    $sel = ($set->exclude_users) ? $set->exclude_users : '';
    $outusersel = CmsFormUtils::create_select([
        'type' => 'list',
        'name' => 'exclude_users',
        'id' => 'exuser',
        'multiple' => true,
        'options' => array_flip($users),
        'selectedvalue' => $sel,
    ]);
} else {
    $outusersel = $this->Lang('nouser');
}

// don't care about groups' active-state
$sql = 'SELECT group_id,group_name FROM '.CMS_DB_PREFIX.'groups WHERE group_id!=1 ORDER BY group_name';
$grps = $db->GetAssoc($sql);

if ($grps) {
    $D = [-1 => $this->Lang('all_groups')] + $grps;
    $sel = ($set->match_groups) ? $set->match_groups : -1;
    $ingrpsel = CmsFormUtils::create_select([
        'type' => 'list',
        'name' => 'match_groups',
        'id' => 'incgrp',
        'multiple' => true,
        'options' => array_flip($D),
        'selectedvalue' => $sel,
    ]);
} else {
    $ingrpsel = $this->Lang('nogroup');
}

if ($grps) {
    $sel = ($set->exclude_groups) ? $set->exclude_groups : '';
    $outgrpsel = CmsFormUtils::create_select([
        'type' => 'list',
        'name' => 'exclude_groups',
        'id' => 'exgrp',
        'multiple' => true,
        'options' => array_flip($grps),
        'selectedvalue' => $sel,
    ]);
} else {
    $outgrpsel = $this->Lang('nogroup');
}

$smarty->assign([
    'startform' => $this->CreateFormStart($id, 'opencontrol', $returnid),
    'types' => $typesel,
    'sorts' => $sortsel,
    'sortasc' => $sortasc,
    'incusers' => $inusersel,
    'excusers' => $outusersel,
    'incgroups' => $ingrpsel,
    'excgroups' => $outgrpsel,
    'set' => $set,
]);

//TODO this equal-widthing doesn't work
$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
 cms_equalWidth($('.boxchild label'));
});
//]]>
</script>
EOS;
$themeObject->add_footertext($js);

echo $this->processTemplate('opencontrol.tpl');
