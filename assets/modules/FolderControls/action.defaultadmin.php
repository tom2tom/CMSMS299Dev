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

$ob = new FolderControls\Operations();

if (!empty($params['delete'])) {
    try {
        $setid = (int) get_parameter_value($params, 'setid');
        if ($setid < 1) {
            throw new UnexpectedValueException('Invalid id provided to delete operation');
        }
        if (!$ob->delete($setid)) {
            throw new RuntimeException('Failed to delete the specified item');
        }
        $default = cms_siteprefs::get('defaultcontrolset', -1);
        if ($default == $setid) {
            cms_siteprefs::set('defaultcontrolset', -1);
        }
    } catch (Exception $e) {
        $this->ShowErrors($e->GetMessage());
    }
} elseif (!empty($params['default'])) {
    cms_siteprefs::set('defaultcontrolset', (int)$params['setid']); //TODO module setting
}

$default = cms_siteprefs::get('defaultcontrolset', -1); //TODO module setting
$sets = [];
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

    $prompt = json_encode($this->Lang('confirm_delete_set'));
    $js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
 $('.deleteset').on('click', function(ev) {
  ev.preventDefault();
  cms_confirm_linkclick(this, $prompt);
  return false;
 });
});
//]]>
</script>

EOS;
    $this->AdminBottomContent($js);
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

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl'),null,null,$smarty);

$tpl->assign([
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

$tpl->display();

