<?php
/*
ContentManager module ajax-processor action - find pages having specified
 text and that the current user may edit
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple .org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple .org>

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

use ContentManager\ContentListBuilder;
use CMSMS\SingleItem;
use function CMSMS\de_specialize;

if( !$this->CheckContext() ) exit;
if( !$this->CanEditContent() ) exit;

$out = [];

if( isset($_REQUEST['term']) ) {
    // from 2.0, tags in here are retained
    $str = de_specialize($_REQUEST['term']); // no extra sanitizeVal() - too many places/types to search
}
else {
    $term = '';
}
if( $term ) {
    $query = 'SELECT content_id,hierarchy,content_name,menu_text,page_url,content_alias FROM ' . CMS_DB_PREFIX . 'content
WHERE (content_name LIKE ? OR menu_text LIKE ? OR page_url LIKE ? OR content_alias LIKE ?)';

    if( !($this->CheckPermission('Manage All Content') || $this->CheckPermission('Modify Any Page')) ) {
        $pages = author_pages(get_userid(FALSE));
        if( !$pages ) exit;
        // query only these pages
        $query .= ' AND content_id IN (' . implode(', ', $pages) . ')';
    }

	$wm = $db->escStr($term);
    $str = '%' . $wm . '%';
    $list = $db->getArray($query, [$str, $str, $str, $str]);
    if( $list ) {
        $builder = new ContentListBuilder($this);
        $builder->expand_all(); // it'd be cool to open all parents to each item.
        $contentops = SingleItem::ContentOperations();
        foreach( $list as $row ) {
            $label = $contentops->CreateFriendlyHierarchyPosition($row['hierarchy']);
            $label .= $row['content_name'] . ' / ' . $row['menu_text'];
            if( $row['content_alias'] ) $label .= ' / ' . $row['content_alias'];
            if( $row['page_url'] ) $label .= ' / ' . $row['page_url'];
            $out[] = ['label'=>$label, 'value'=>$row['content_id']];
        }
    }
}

echo json_encode($out);
exit;
