<?php
/*
Delete category action for CMSMS News module.
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\Events;
use News\AdminOperations;
use function CMSMS\log_info;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify News Preferences')) exit;

// TODO icon/image removal if not needed

$catid = $params['catid'] ?? '';
if (is_numeric($catid)) {
    // Get the category details
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id = ?';
    $row = $db->getRow($query, [$catid]);

    //Reset all categories using this parent to have no parent (-1)
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_categories SET parent_id = -1, modified_date = ? WHERE parent_id = ?';
    $longnow = $db->DbTimeStamp(time(),false);
    $db->execute($query, [$longnow, $catid]);

    //Remove the category
    $query = 'DELETE FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id = ?';
    $db->execute($query, [$catid]);

    //And any articles
    if ($this->GetPreference('clear_category', false)) {
        $query = 'DELETE FROM '.CMS_DB_PREFIX.'module_news WHERE news_category_id = ?';
    } else {
        $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET news_category_id = 1 WHERE news_category_id = ?';
    }
    $db->execute($query, [$catid]);

    Events::SendEvent('News', 'NewsCategoryDeleted', ['category_id'=>$catid, 'name'=>$row['news_category_name']]);
    log_info($catid, 'News category: '.$catid, ' Category deleted');

    AdminOperations::UpdateHierarchyPositions();

    $this->SetMessage($this->Lang('categorydeleted'));
}
$this->RedirectToAdminTab('groups');
