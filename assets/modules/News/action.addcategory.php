<?php
/*
Add category action for CMSMS News module.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Events;
use News\AdminOperations;
use News\Utils;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify News Preferences')) exit;

if (isset($params['cancel'])) {
    $this->RedirectToAdminTab('groups');
}

if( isset($params['parent'])) {
    $parent = (int)$params['parent'];
}
else {
    $parent = -1;
}

$name = '';
if (isset($params['name'])) {
    //if( $parent == 0 ) $parent = -1;
    $name = trim( cleanValue( $params['name'] ));
    if ($name != '') {
        $query = 'SELECT news_category_id FROM '.CMS_DB_PREFIX.'module_news_categories WHERE parent_id = ? AND news_category_name = ?';
        $tmp = $db->GetOne($query,[$parent,$name]);
        if( $tmp ) {
            $this->ShowErrors($this->Lang('error_duplicatename'));
        }
        else {
            $query = 'SELECT max(item_order) FROM '.CMS_DB_PREFIX.'module_news_categories WHERE parent_id = ?';
            $item_order = (int)$db->GetOne($query,[$parent]);
            $item_order++;

            $now = time();
            $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_categories
(news_category_name, parent_id, item_order, create_date) VALUES (?,?,?,?)';
            $parms = [$name,$parent,$item_order,$now];
            $dbr = $db->Execute($query, $parms);
            if ($dbr) {
                $catid = $db->Insert_ID();

                AdminOperations::UpdateHierarchyPositions();

                Events::SendEvent( 'News', 'NewsCategoryAdded', [ 'category_id'=>$catid, 'name'=>$name ] );
                // put mention into the admin log
                audit($catid, 'News category: '.$name, ' Added');

                $this->SetMessage($this->Lang('categoryadded'));
                $this->RedirectToAdminTab('groups');
            }
            $this->ShowErrors($this->Lang('error_detailed', $db->errorMsg()));
        }
    }
    else {
        $this->ShowErrors($this->Lang('nonamegiven'));
    }
}

$tmp = Utils::get_category_list();
$tmp2 = array_flip($tmp);
$categories = [-1=>$this->Lang('none')];
foreach( $tmp2 as $k => $v ) {
    $categories[$k] = $v;
}

$parms = $params; //TODO any extras
unset($parms['action'],$parms['name']);

// Display template
$tpl = $smarty->createTemplate($this->GetTemplateResource('editcategory.tpl'),null,null,$smarty);

$tpl->assign('formaction','addcategory')
 ->assign('formparms',$parms)
 ->assign('parent',$parent)
 ->assign('name',$name)
 ->assign('categories',$categories);

$tpl->display();
return '';
