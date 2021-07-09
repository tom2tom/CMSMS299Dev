<?php
/*
MicroTiny module action: get pages via ajax
Copyright (C) 2009-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

if( !isset($gCms) ) exit;
if( !check_login() ) exit; // admin only.... but any admin

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

$out = null;
$term = trim(strip_tags($_REQUEST['term'] ?? '')); // OR tag-chars ok?
$alias = trim(strip_tags($_REQUEST['alias'] ?? '')); // sanitizeVal() ?

if( $alias ) {
    $query = 'SELECT content_id,content_name,menu_text,content_alias,id_hierarchy FROM '.CMS_DB_PREFIX.'content
WHERE content_alias = ? AND active = 1';
    $dbr = $db->GetRow($query,[$alias]);
    if( $dbr ) {
        $lbl = "{$dbr['content_name']} ({$dbr['id_hierarchy']})";
        $out = ['label'=>$lbl, 'value'=>$dbr['content_alias']];
        echo json_encode($out);
    }
}
elseif( $term ) {
    $query = 'SELECT content_id,content_name,menu_text,content_alias,id_hierarchy FROM '.CMS_DB_PREFIX.'content
WHERE (content_name LIKE ? OR menu_text LIKE ? OR content_alias LIKE ?) AND active = 1
ORDER BY default_content DESC, hierarchy';
    // injection-prevention by escStr() plus prepared statement
    $wm = '%'.$db->escStr($term).'%';
    $dbr = $db->GetArray($query, [$wm, $wm, $wm]);
    if( $dbr ) {
        // found some pages to match
        $out = [];
        // load the content objects
        foreach( $dbr as $row ) {
            $lbl = "{$row['content_name']} ({$row['id_hierarchy']})";
            $out[] = ['label'=>$lbl, 'value'=>$row['content_alias']];
        }
        echo json_encode($out);
    }
}

exit;
