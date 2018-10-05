<?php

use CMSMS\Events;
use News\Adminops;
use News\Ops;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) return;

$parent = -1;
if( isset($params['parent'])) $parent = (int)$params['parent'];
if (isset($params['cancel'])) $this->RedirectToAdminTab('categories','','admin_settings');

$name = '';
if (isset($params['name'])) {
    //if( $parent == 0 ) $parent = -1;
    $name = trim( cleanValue( $params['name'] ));
    if ($name != '') {
        $query = 'SELECT news_category_id FROM '.CMS_DB_PREFIX.'module_news_categories WHERE parent_id = ? AND news_category_name = ?';
        $tmp = $db->GetOne($query,[$parent,$name]);
        if( $tmp ) {
            $this->ShowErrors($this->Lang('error_duplicatename'));
        }
        else {
            $query = 'SELECT max(item_order) FROM '.CMS_DB_PREFIX.'module_news_categories WHERE parent_id = ?';
            $item_order = (int)$db->GetOne($query,[$parent]);
            $item_order++;

            $catid = $db->GenID(CMS_DB_PREFIX.'module_news_categories_seq');
			$now = time();
            $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_categories (news_category_id, news_category_name, parent_id, item_order, create_date) VALUES (?,?,?,?,?)';
            $parms = [$catid,$name,$parent,$item_order,$now];
            $db->Execute($query, $parms);

            Adminops::UpdateHierarchyPositions();

            Events::SendEvent( 'News', 'NewsCategoryAdded', [ 'category_id'=>$catid, 'name'=>$name ] );
            // put mention into the admin log
            audit($catid, 'News category: '.$name, ' Category added');

            $this->SetMessage($this->Lang('categoryadded'));
            $this->RedirectToAdminTab('categories','','admin_settings');
        }
    }
    else {
        $this->ShowErrors($this->Lang('nonamegiven'));
    }
}

$tmp = Ops::get_category_list();
$tmp2 = array_flip($tmp);
$categories = [-1=>$this->Lang('none')];
foreach( $tmp2 as $k => $v ) {
    $categories[$k] = $v;
}

// Display template
$tpl = $smarty->createTemplate($this->GetTemplateResource('editcategory.tpl'),null,null,$smarty);

$tpl->assign('parent',$parent)
 ->assign('name',$name)
 ->assign('categories',$categories)
 ->assign('startform', $this->CreateFormStart($id, 'addcategory', $returnid))
 ->assign('endform', $this->CreateFormEnd())
 ->assign('inputname', $this->CreateInputText($id, 'name', $name, 20, 255));
//see template ->assign('submit', $this->CreateInputSubmit($id, 'submit', lang('submit')))
// ->assign('cancel', $this->CreateInputSubmit($id, 'cancel', lang('cancel')))
//see DoActionBase() ->assign('mod',$this);

$tpl->display();

