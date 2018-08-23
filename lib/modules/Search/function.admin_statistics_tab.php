<?php

$words = [];
$query = 'SELECT word,count FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY count DESC';
$dbr = $db->SelectLimit($query,50,0);
while ($dbr && $row = $dbr->FetchRow()) {
    $words[] = $row;
}
if ($words) {
	$tpl->assign('formstart1',$this->CreateFormStart($id,'defaultadmin'));
} else {
	$words = null;
}
$tpl->assign('topwords',$words);

