<?php

$results = [];
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_search_words ORDER BY count DESC';
$dbr = $db->SelectLimit($query,50,0);
while ($dbr && $row = $dbr->FetchRow()) {
    $results[] = $row;
}
if (!$results) {
	$results = null;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_statistics_tab.tpl'),null,null,$smarty);

$tpl->assign('formstart',$this->CreateFormStart($id,'defaultadmin'))
 ->assign('formend',$this->CreateFormEnd())
 ->assign('wordtext',$this->Lang('word'))
 ->assign('counttext',$this->Lang('count'))
 ->assign('topwords',$results);

$tpl->display();
