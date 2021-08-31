<?php
/*
Tab populator for CMSMS News module.
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\Utils;
use function CMSMS\specialize;

// TODO show icon/image for each

// Generate a list of current categories
$entryarray = [];

$query = 'SELECT news_category_id,news_category_name,hierarchy FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
$rst = $db->execute($query);
if ($rst) {
    $admintheme = Utils::get_theme_object();
    $icon = $admintheme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'), '', '', 'systemicon');
    while (($row = $rst->FetchRow())) {
        $cid = (int)$row['news_category_id'];
        $depth = count(preg_split('/\./', $row['hierarchy']));
        $onerow = new stdClass();
        $onerow->id = $cid;
        $onerow->depth = $depth - 1;
        $onerow->name = specialize($row['news_category_name']);
        $onerow->edit_url = $this->create_action_url($id, 'editcategory', ['catid'=>$cid]);
        $onerow->editlink = $this->CreateLink($id, 'editcategory', $returnid, $icon, ['catid'=>$cid]);
        if ($cid > 1) {
            $onerow->delete_url = $this->create_action_url($id, 'deletecategory', ['catid'=>$cid]);
        } else {
            $onerow->delete_url = null;
        }
        $entryarray[] = $onerow;
    }
    $rst->Close();
}

$tpl->assign('cats', $entryarray)
 ->assign('catcount', count($entryarray));
