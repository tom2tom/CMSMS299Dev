<?php

use CMSMS\Events;
use News\news_admin_ops;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

$catid = '';
if (isset($params['catid'])) $catid = $params['catid'];

// Get the category details
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories
           WHERE news_category_id = ?';
$row = $db->GetRow( $query, [ $catid ] );

//Reset all categories using this parent to have no parent (-1)
$query = 'UPDATE '.CMS_DB_PREFIX.'module_news_categories SET parent_id=?, modified_date='.$db->DbTimeStamp(time()).' WHERE parent_id=?';
$db->Execute($query, [-1, $catid]);

//Now remove the category
$query = 'DELETE FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id = ?';
$db->Execute($query, [$catid]);

//And remove it from any articles
$query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET news_category_id = -1 WHERE news_category_id = ?';
$db->Execute($query, [$catid]);

Events::SendEvent( 'News', 'NewsCategoryDeleted', [ 'category_id'=>$catid, 'name'=>$row['news_category_name'] ] );
audit($catid, 'News category: '.$catid, ' Category deleted');

news_admin_ops::UpdateHierarchyPositions();

$this->SetMessage($this->Lang('categorydeleted'));
$this->RedirectToAdminTab('categories','','admin_settings');
