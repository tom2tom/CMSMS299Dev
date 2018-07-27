<?php
/*
FolderControls module action: defaultadmin
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

if (!function_exists('cmsms')) {
    exit;
}
if (!$this->CheckPermission('Modify Site Preferences')) {
    exit;
}

if (!empty($params['delete'])) {
    try {
        $setid = (int) get_parameter_value($params, 'setid');
        if ($setid < 1) {
            throw new LogicException('Invalid id provided to delete operation');
        }

        if (!$set) {
            throw new LogicException('Invalid id provided to delete operation');
        }

        $default = cms_siteprefs::get('defaultcontrolset', -1);
        if ($default == $profile->id) {
            cms_siteprefs::set('defaultcontrolset', -999);
        }

    } catch (Exception $e) {
        $this->SetError($e->GetMessage());
    }
} elseif (!empty($params['default'])) {
    cms_siteprefs::set('defaultcontrolset', (int)$params['setid']); //TODO module setting
}

$default = cms_siteprefs::get('defaultcontrolset', -1); //TODO module setting
$sets = [];
$ob = new FolderControls\ControlSet();
$data = $ob->get_all();
if ($data) {
    $tz = (!empty($config['timezone'])) ? $config['timezone'] : 'UTC';
    $dt = new DateTime(null, new DateTimeZone($tz));
    $fmt = cms_siteprefs::get('defaultdateformat', 'Y-m-d');
    foreach ($data as &$row) {
        $oneset = new stdClass();
        $oneset->id = $row['id'];
        $oneset->name = $row['name'];
        $oneset->reltop = $row['top'] ?? '';
        $dt->modify($row['create_date']);
        $oneset->created = $dt->format($fmt);
        $dt->modify($row['modified_date']);
        $oneset->modified = $dt->format($fmt);
        $sets[] = $oneset;
    }
    unset($row);
}

$addurl = $this->create_url($id,'opencontrol',$returnid, ['setid'=>-1]);
$editurl = $this->create_url($id,'opencontrol',$returnid, []);
$delurl = $this->create_url($id,'defaultadmin',$returnid, ['delete'=>1]);
$defaulturl = $this->create_url($id,'defaultadmin',$returnid, ['default'=>1]);

$themeObject = cms_utils::get_theme_object();
$iconadd = $themeObject->DisplayImage('icons/system/newobject.gif', lang('add'), '', '', 'systemicon');
$iconedit = $themeObject->DisplayImage('icons/system/edit.gif', lang('edit'), '', '', 'systemicon');
$icondel = $themeObject->DisplayImage('icons/system/delete.gif', lang('delete'), '', '', 'systemicon');
$iconyes = $themeObject->DisplayImage('icons/system/true.gif', lang('yes'), '', '', 'systemicon');
$iconno = $themeObject->DisplayImage('icons/system/false.gif', lang('no'), '', '', 'systemicon');

$smarty->assign([
    'addurl' => $addurl,
    'editurl' => $editurl,
    'deleteurl' => $delurl,
    'defaulturl' => $defaulturl,
    'iconadd' => $iconadd,
    'iconedit' => $iconedit,
    'icondel' => $icondel,
    'iconyes' => $iconyes,
    'iconyes' => $iconno,
    'dfltset_id' => $default,
    'sets' => $sets,
]);

//TODO js for delete-confirm

echo $this->processTemplate('adminpanel.tpl');
