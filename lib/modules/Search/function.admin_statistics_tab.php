<?php

$smarty->assign('formstart',$this->CreateFormStart($id,'defaultadmin'));
$smarty->assign('formend',$this->CreateFormEnd());
$smarty->assign('wordtext',$this->Lang('word'));
$smarty->assign('counttext',$this->Lang('count'));

$results = [];
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY count DESC';
$dbr = $db->SelectLimit($query,50,0);
while ($dbr && $row = $dbr->FetchRow()) {
    $results[] = $row;
}
if (!$results) {
	$results = null;
}
$smarty->assign('topwords',$results);

echo $this->ProcessTemplate('admin_statistics_tab.tpl');
