<?php
/*
Tab populator for CMSMS News module.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

// Generate a list of current categories
$entryarray = [];

$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
$rst = $db->Execute($query);
if ($rst) {
    $admintheme = cms_utils::get_theme_object();
    while (($row = $rst->FetchRow())) {
        $onerow = new stdClass();
        $depth = count(preg_split('/\./', $row['hierarchy']));
        $onerow->id = $row['news_category_id'];
        $onerow->depth = $depth - 1;
        $onerow->edit_url = $this->create_url($id,'editcategory',$returnid,['catid'=>$row['news_category_id']]);
        $onerow->name = $row['news_category_name'];
        $onerow->editlink = $this->CreateLink($id, 'editcategory', $returnid, $admintheme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'),'','','systemicon'), ['catid'=>$row['news_category_id']]);
        if ($onerow->id > 1) {
            $onerow->delete_url = $this->create_url($id,'deletecategory',$returnid,
                            ['catid'=>$row['news_category_id']]);
        } else {
            $onerow->delete_url = null;
        }
        $entryarray[] = $onerow;
    }
    $rst->Close();
}

$tpl->assign('cats', $entryarray)
 ->assign('catcount', count($entryarray));
