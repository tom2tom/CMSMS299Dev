<?php
/*
Defaultadmin action for CMSMS News module.
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AdminUtils;
use News\AdminOperations;

if( !isset($gCms) ) exit;
$papp = $this->CheckPermission('Approve News');
$pmod = $this->CheckPermission('Modify News');
$pdel = $this->CheckPermission('Delete News');
$pset = $this->CheckPermission('Modify News Preferences');
if( !($papp || $pmod || $pdel || $pset) ) exit;

if( isset($params['bulk_action']) ) {
    if( empty($params['sel']) ) {
        $this->ShowErrors($this->Lang('error_noarticlesselected'));
    }
    else {
        $sel = [];
        foreach( $params['sel'] as $one ) {
            $one = (int)$one;
            if( $one < 1 ) continue;
            if( in_array($one,$sel) ) continue;
            $sel[] = $one;
        }

        switch($params['bulk_action']) {
        case 'delete':
            if( $pdel ) {
                foreach( $sel as $news_id ) {
                    AdminOperations::delete_article($news_id);
                }
                $this->ShowMessage($this->Lang('msg_success'));
            }
            else {
                $this->ShowErrors($this->Lang('needpermission', 'Modify News'));
            }
            break;

        case 'setcategory':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET news_category_id = ?, modified_date = ?
WHERE news_id IN ('.implode(',',$sel).')';
            $parms = [(int)$params['bulk_category'], time()];
            $db->Execute($query,$parms);
            audit('',$this->GetName(),'category changed on '.count($sel).' articles');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setpublished':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = ?
WHERE news_id IN ('.implode(',',$sel).')';
            $db->Execute($query,['published', time()]);
            audit('',$this->GetName(),'status changed on '.count($sel).' articles');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setdraft':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = ?
WHERE news_id IN ('.implode(',',$sel).')';
            $db->Execute($query,['draft', time()]);
            audit('',$this->GetName(),'status changed on '.count($sel).' articles');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        default:
            break;
        }
    }
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('defaultadmin.tpl'),null,null,$smarty);
$tpl->assign('can_add',$pmod)
 ->assign('can_mod',$pmod)
 ->assign('can_del',$pmod && $pdel)
 ->assign('can_set',$pset)
 ->assign('tab',$params['activetab'] ?? false);

$baseurl = $this->GetModuleURLPath();
$out = <<<EOS
 <link rel="stylesheet" href="{$baseurl}/css/module.css">

EOS;
add_page_headtext($out);

$out = cms_get_script('jquery.SSsort.js');
$js = <<<EOS
<script src="$out"></script>
<script type="text/javascript">
//<![CDATA[
function pagefirst(tbl) {
 $.fn.SSsort.movePage(tbl,false,true);
}
function pagelast(tbl) {
 $.fn.SSsort.movePage(tbl,true,true);
}
function pagenext(tbl) {
 $.fn.SSsort.movePage(tbl,true,false);
}
function pageprev(tbl) {
 $.fn.SSsort.movePage(tbl,false,false);
}
var SSsopts = {
 sortClass: 'SortAble',
 ascClass: 'SortUp',
 descClass: 'SortDown',
 oddClass: 'row1',
 evenClass: 'row2',
 oddsortClass: 'row1s',
 evensortClass: 'row2s'
};
//]]>
</script>
EOS;
add_page_foottext($js);

//variables in scope for inclusions
$userid = get_userid();
$themeObj = cms_utils::get_theme_object();

require_once __DIR__.DIRECTORY_SEPARATOR.'function.itemstab.php';
if( $pmod ) {
    require_once __DIR__.DIRECTORY_SEPARATOR.'function.templatestab.php';
}
if( $pset ) {
    require_once __DIR__.DIRECTORY_SEPARATOR.'function.categoriestab.php';
    // module settings
    $str = AdminUtils::CreateHierarchyDropdown(0,(int)$this->GetPreference('detail_returnid',-1),'detail_returnid');
    $tpl
 ->assign('detail_returnid',$str)
 ->assign('label_alert_drafts',$this->Lang('alert_drafts'))
//->assign('label_allowed_upload_types',$this->Lang('allowed_upload_types'))
 ->assign('label_date_format',$this->Lang('date_format'))
 ->assign('label_default_category',$this->Lang('default_category'))
 ->assign('label_detail_returnid',$this->Lang('detail_returnid'))
 ->assign('label_expired_searchable',$this->Lang('expired_searchable'))
 ->assign('label_expired_viewable',$this->Lang('expired_viewable'))
 ->assign('label_expiry_interval',$this->Lang('expiry_interval'))

 ->assign('alert_drafts',$this->GetPreference('alert_drafts',false))
//->assign('allowed_upload_types',$this->GetPreference('allowed_upload_types'))
 ->assign('date_format',$this->GetPreference('date_format'))
 ->assign('default_category',$this->GetPreference('default_category',1))
//->assign('email_subject',$this->GetPreference(email_subject))
//->assign('email_template',$this->GetPreference(email_template))
//->assign('formsubmit_emailaddress',$this->GetPreference('formsubmit_emailaddress'))
 ->assign('expired_searchable',$this->GetPreference('expired_searchable',false))
 ->assign('expired_viewable',$this->GetPreference('expired_viewable',false))
 ->assign('expiry_interval',$this->GetPreference('expiry_interval',30))
;
}

$tpl->display();
return '';
