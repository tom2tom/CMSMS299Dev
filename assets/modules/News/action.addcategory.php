<?php
/*
CMSMS News module add-category action.
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

use CMSMS\Events;
use News\AdminOperations;
use News\Utils;
use function CMSMS\de_specialize;
use function CMSMS\specialize;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify News Preferences') ) exit;

if( isset($params['cancel']) ) {
    $this->RedirectToAdminTab('groups');
}

if( isset($params['parent']) ) {
    $parent = (int)$params['parent'];
}
else {
    $parent = -1;
}

if( isset($params['name']) ) {
    //if( $parent == 0 ) $parent = -1;
    $tmp = de_specialize(trim($params['name']));
    // valid URL chars allowed (tho' some must be encoded in URL's)
    $name = preg_replace('<[^A-Za-z0-9\-._~:/?#[]@!$&\'()*+,;=%\x00-\xff]>', '', $tmp); //2.99+
    if( $name ) {
        //small race-risk here
        $query = 'SELECT news_category_id FROM '.CMS_DB_PREFIX.'module_news_categories WHERE parent_id = ? AND news_category_name = ?';
        $tmp = $db->GetOne($query, [$parent, $name]);
        if( $tmp ) {
            $this->ShowErrors($this->Lang('error_duplicatename'));
        }
        else {
            $query = 'SELECT MAX(item_order) FROM '.CMS_DB_PREFIX.'module_news_categories WHERE parent_id = ?';
			$dbr = $db->GetOne($query, [$parent]);
            $item_order = (int)$dbr + 1;
            $now = time();

            $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_categories
(news_category_name, parent_id, item_order, create_date) VALUES(?,?,?,?)';
            $dbr = $db->Execute($query, [$name, $parent, $item_order, $now]);
            if( $dbr ) {
                $catid = $db->Insert_ID();

                AdminOperations::UpdateHierarchyPositions();

                Events::SendEvent('News', 'NewsCategoryAdded', ['category_id'=>$catid, 'name'=>$name]);
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
else {
    $name = '';
}

$categories = [-1 => $this->Lang('none')];
$tmp = Utils::get_category_list();
foreach( $tmp as $nm => $cid ) {
    $categories[(int)$cid] = specialize($nm);
}

$parms = $params; //TODO any extras, any specialize()'s
unset($parms['action'], $parms['name']);

// Display template
$tpl = $smarty->createTemplate($this->GetTemplateResource('editcategory.tpl')); //, null, null, $smarty);

$tpl->assign('formaction', 'addcategory')
 ->assign('formparms', $parms)
 ->assign('parent', $parent)
 ->assign('name', $name)
 ->assign('categories', $categories);

$tpl->display();
return '';
