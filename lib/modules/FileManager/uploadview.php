<?php
#CMSMS FileManager module  action: uploadview
#Copyright (C) 2006-2018 by Morten Poulsen <morten@poulsen.org>
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

if (!isset($gCms)) exit;

if (!$this->CheckPermission('Modify Files')) exit;

$smarty->assign('formstart',$this->CreateFormStart($id, 'upload', $returnid, 'post', 'multipart/form-data'));
$smarty->assign('actionid',$id);
$smarty->assign('action_url',$this->create_url($id, 'upload', $returnid));
$smarty->assign('refresh_url',$this->create_url($id, 'upload', '', ['noform'=>1]));
$smarty->assign('maxfilesize',$config["max_upload_size"]);
$smarty->assign('formend',$this->CreateFormEnd());

$post_max_size = filemanager_utils::str_to_bytes(ini_get('post_max_size'));
$upload_max_filesize = filemanager_utils::str_to_bytes(ini_get('upload_max_filesize'));
$smarty->assign('max_chunksize',min($upload_max_filesize,$post_max_size-1024));
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    $smarty->assign('is_ie',1);
}
$smarty->assign('ie_upload_message',$this->Lang('ie_upload_message'));

echo $this->ProcessTemplate('uploadview.tpl');
