<?php
/*
Delete category action for CMSMS News module.
Copyright (C) 2005-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify News Preferences')) return;

$catid = $params['catid'] ?? '';
if (is_numeric($catid)) {
    // Get the category details
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id = ?';
    $row = $db->GetRow($query, [$catid]);

    //Reset all categories using this parent to have no parent (-1)
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_categories SET parent_id = -1, modified_date = '.time().' WHERE parent_id = ?';
    $db->Execute($query, [$catid]);

    //Now remove the category
    $query = 'DELETE FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id = ?';
    $db->Execute($query, [$catid]);

    //And remove it from any articles
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET news_category_id = -1 WHERE news_category_id = ?';
    $db->Execute($query, [$catid]);

    Events::SendEvent( 'News', 'NewsCategoryDeleted', [ 'category_id'=>$catid, 'name'=>$row['news_category_name'] ] );
    audit($catid, 'News category: '.$catid, ' Category deleted');

    AdminOperations::UpdateHierarchyPositions();

    $this->SetMessage($this->Lang('categorydeleted'));
}
$this->RedirectToAdminTab('groups');
