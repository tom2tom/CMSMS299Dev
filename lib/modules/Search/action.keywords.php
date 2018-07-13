<?php
if( !isset($gCms) ) exit;

$pageid = $params['pageid'] ?? $returnid;

$query = 'SELECT idx.word
  FROM '.CMS_DB_PREFIX.'module_search_index idx INNER JOIN '.CMS_DB_PREFIX.'module_search_items i ON idx.item_id = i.id
  WHERE i.content_id = \''.$pageid.'\'
    AND i.module_name = \'Search\'
    AND i.extra_attr = \'content\'
  ORDER BY idx.count DESC';

$wordcount = $params['count'] ?? 500;
$dbr = $db->SelectLimit( $query, (int)$wordcount, 0 );

$wordlist = [];
while( $dbr && ($row = $dbr->FetchRow() ) ) {
    $wordlist[] = $row['word'];
}
echo implode(',',$wordlist);
