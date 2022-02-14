<?php
/*
Defaultadmin action for CMSMS News module.
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Utils;
use News\AdminOperations;
use function CMSMS\log_notice;

//if( some worthy test fails ) exit;
$papp = $this->CheckPermission('Approve News');
$pmod = $this->CheckPermission('Modify News');
$pprop = $this->CheckPermission('Propose News');
$pdel = $this->CheckPermission('Delete News');
$pset = $this->CheckPermission('Modify News Preferences');
if( !($papp || $pmod || $pprop || $pdel || $pset) ) exit;

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
                $this->ShowErrors($this->Lang('needpermission','Modify News'));
            }
            break;

        case 'setcategory':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET news_category_id = ?, modified_date = ?
WHERE news_id IN ('.implode(',',$sel).')';
            $longnow = $db->DbTimeStamp(time(),false);
            $parms = [(int)$params['bulk_category'],$longnow];
            $db->execute($query,$parms);
            log_notice($this->GetName().'::defaultadmin','Category changed for '.count($sel).' articles');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setpublished':
            if( $papp || $pmod ) {
                $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = ?
WHERE news_id IN ('.implode(',',$sel).')';
                $longnow = $db->DbTimeStamp(time(),false);
                $db->execute($query,['published',$longnow]);
                log_notice($this->GetName().'::defaultadmin','Published status set for '.count($sel).' articles');
                $this->ShowMessage($this->Lang('msg_success'));
            }
            else {
                $this->ShowErrors($this->Lang('needpermission','Approve News')); // TODO or modify
            }
            break;

        case 'setdraft':
            if( $papp || $pmod ) {
                $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = ?
WHERE news_id IN ('.implode(',',$sel).')';
                $longnow = $db->DbTimeStamp(time(),false);
                $db->execute($query,['draft',$longnow]);
                log_notice($this->GetName().'::defaultadmin','Draft status set for '.count($sel).' articles');
                $this->ShowMessage($this->Lang('msg_success'));
            }
            else {
                $this->ShowErrors($this->Lang('needpermission','Approve News')); // TODO or modify
            }
            break;

        default:
            break;
        }
    }
}

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

$tpl = $smarty->createTemplate($this->GetTemplateResource('defaultadmin.tpl')); //,null,null,$smarty);
$tpl->assign('can_add',$pmod || $pprop)
 ->assign('can_mod',$pmod)
 ->assign('can_del',$pmod && $pdel)
 ->assign('can_set',$pset)
 ->assign('tab',$params['activetab'] ?? false);

//other variables in scope for inclusions
$userid = get_userid();
$themeObj = Utils::get_theme_object();

require_once __DIR__.DIRECTORY_SEPARATOR.'function.itemstab.php';
if( $pmod ) {
    require_once __DIR__.DIRECTORY_SEPARATOR.'function.templatestab.php';
}
if( $pset ) {
    require_once __DIR__.DIRECTORY_SEPARATOR.'function.categoriestab.php';
    require_once __DIR__.DIRECTORY_SEPARATOR.'function.settingstab.php';
}

$tpl->display();
