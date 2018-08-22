<?php

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

$entryarray = [];
$max = $db->GetOne('SELECT max(item_order) as max_item_order FROM '.CMS_DB_PREFIX.'module_news_fielddefs');

$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs ORDER BY item_order';
$dbresult = $db->Execute($query);
$admintheme = cms_utils::get_theme_object();
$rowclass = 'row1';

while ($dbresult && $row = $dbresult->FetchRow()) {
    $onerow = new stdClass();

    $onerow->id = $row['id'];
    $onerow->name = $this->CreateLink($id, 'admin_editfielddef', $returnid, $row['name'], ['fdid'=>$row['id']]);
    $onerow->type = $this->Lang($row['type']);
    $onerow->max_length = $row['max_length'];
    $onerow->item_order = $row['item_order'];

    if ($onerow->item_order > 1) {
        $onerow->uplink = $this->CreateLink($id, 'admin_movefielddef', $returnid, $admintheme->DisplayImage('icons/system/arrow-u.gif', $this->Lang('up'),'','','systemicon'), ['fdid'=>$row['id'], 'dir'=>'up']);
    }
    else {
        $onerow->uplink = '';
    }
    if ($max > $onerow->item_order) {
        $onerow->downlink = $this->CreateLink($id, 'admin_movefielddef', $returnid, $admintheme->DisplayImage('icons/system/arrow-d.gif', $this->Lang('down'),'','','systemicon'), ['fdid'=>$row['id'], 'dir'=>'down']);
    }
    else {
        $onerow->downlink = '';
    }

    $onerow->editlink = $this->CreateLink($id, 'admin_editfielddef', $returnid, $admintheme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'),'','','systemicon'), ['fdid'=>$row['id']]);

    $onerow->delete_url = $this->create_url($id, 'admin_deletefielddef', $returnid, ['fdid'=>$row['id']]);

    $entryarray[] = $onerow;
    ($rowclass=='row1'?$rowclass='row2':$rowclass='row1');
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('customfieldstab.tpl'),null,null,$smarty);

$tpl->assign('items', $entryarray)
 ->assign('itemcount', count($entryarray))

 ->assign('addurl', $this->create_url($id,'admin_addfielddef'))
 ->assign('addlink', $this->CreateLink($id, 'admin_addfielddef', $returnid, $admintheme->DisplayImage('icons/system/newfolder.gif', $this->Lang('addfielddef'),'','','systemicon'), [], '', false, false, '') .' '. $this->CreateLink($id, 'admin_addfielddef', $returnid, $this->Lang('addfielddef'), [], '', false, false, 'class="pageoptions"'))

 ->assign('fielddeftext', $this->Lang('fielddef'))
 ->assign('typetext', $this->Lang('type'));

#Display template
$tpl->display();
