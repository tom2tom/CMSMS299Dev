<?php

// Put together a list of current categories
$entryarray = [];

$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
$dbresult = $db->Execute($query);
$admintheme = cms_utils::get_theme_object();

while ($dbresult && $row = $dbresult->FetchRow()) {
  $onerow = new stdClass();
  $depth = count(preg_split('/\./', $row['hierarchy']));
  $onerow->id = $row['news_category_id'];
  $onerow->depth = $depth - 1;
  $onerow->edit_url = $this->create_url($id,'editcategory',$returnid,['catid'=>$row['news_category_id']]);
  $onerow->name = $row['news_category_name'];
  $onerow->editlink = $this->CreateLink($id, 'editcategory', $returnid, $admintheme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'),'','','systemicon'), ['catid'=>$row['news_category_id']]);
  if ($onerow->id > 1) {
    $onerow->delete_url = $this->create_url($id,'deletecategory',$returnid,
					  ['catid'=>$row['news_category_id']]);
  } else {
    $onerow->delete_url = null;
  }
  $entryarray[] = $onerow;
}

$tpl->assign('cats', $entryarray)
 ->assign('catcount', count($entryarray));
