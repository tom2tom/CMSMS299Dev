<?php

$smarty->assign('formstart',$this->CreateFormStart($id,'defaultadmin'));
$smarty->assign('formend',$this->CreateFormEnd());
$smarty->assign('wordtext',$this->Lang('word'));
$smarty->assign('counttext',$this->Lang('count'));
$smarty->assign('exportcsv',
        '<button type="submit" name="'.$id.'exportcsv" id="'.$id.'exportcsv" class="adminsubmit icon do">'.$this->Lang('export_to_csv').'</button>');
$smarty->assign('clearwordcount',
        '<button type="submit" name="'.$id.'clearwordcount" id="'.$id.'clearwordcount" class="adminsubmit icon undo" onclick="return confirm(\''.$this->Lang('confirm_clearstats').'\');">'.$this->Lang('clear').'</button>');

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
