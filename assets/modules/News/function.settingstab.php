<?php
/*
CMSMS News module defaultadmin action settings-tab populator
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
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;

//data for return-page selector
$str = AdminUtils::CreateHierarchyDropdown(0,(int)$this->GetPreference('detail_returnid',-1),'detail_returnid');
$tpl->assign('detail_returnid',$str);

//data for timeblock selector
$tpl->assign('blockslist',[
    News::HOURBLOCK => $this->Lang('hour'),
    News::HALFDAYBLOCK => $this->Lang('halfday'),
    News::DAYBLOCK => $this->Lang('day'),
]);

//data for approval-email-template selector
$t = -1;
$current = $this->GetPreference('email_template');
$list = [];
$data = $db->getArray('SELECT TP.id,TP.originator,TP.name FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME
 .' TP JOIN '.CMS_DB_PREFIX.TemplateType::TABLENAME.' TT ON TP.type_id = TT.id
WHERE TT.originator = \'News\' AND TT.name= \'approvalmessage\' ORDER BY TP.name');
foreach( $data as $row ) {
    $s = $row['originator'].'::'.$row['name'];
    if( $current == $s ) {
        $t = (int)$row['id'];
    }
    if( $row['originator'] == 'News' || $row['originator'] == '__CORE__' ) {
        $list[$row['id']] = $row['name'];
    }
    else {
        $list[$row['id']] = $s;
    }
}
$tpl->assign('mailbodylist',$list)
 ->assign('email_template',$t);

//->assign('label_allowed_upload_types',$this->Lang('allowed_upload_types'))
//->assign('allowed_upload_types',$this->GetPreference('allowed_upload_types'))
//->assign('formsubmit_emailaddress',$this->GetPreference('formsubmit_emailaddress'))
$tpl
 ->assign('label_alert_drafts',$this->Lang('alert_drafts'))
 ->assign('label_article_pagelimit',$this->Lang('article_pagelimit'))
 ->assign('label_clear_category',$this->Lang('clear_category'))
 ->assign('label_date_format',$this->Lang('date_format'))
 ->assign('label_default_category',$this->Lang('default_category'))
 ->assign('label_detail_returnid',$this->Lang('detail_returnid'))
 ->assign('label_email_subject',$this->Lang('email_subject'))
 ->assign('label_email_to',$this->Lang('email_to'))
 ->assign('label_email_template',$this->Lang('email_template'))
 ->assign('label_expired_searchable',$this->Lang('expired_searchable'))
 ->assign('label_expired_viewable',$this->Lang('expired_viewable'))
 ->assign('label_expiry_interval',$this->Lang('expiry_interval'))
 ->assign('label_summary_wysiwyg',$this->Lang('summary_wysiwyg'))
 ->assign('label_time_format',$this->Lang('time_format'))
 ->assign('label_timeblock',$this->Lang('timeblock'))

 ->assign('alert_drafts',$this->GetPreference('alert_drafts',false))
 ->assign('allow_summary_wysiwyg',$this->GetPreference('allow_summary_wysiwyg',true))
 ->assign('article_pagelimit',$this->GetPreference('article_pagelimit',10))
 ->assign('clear_category',$this->GetPreference('clear_category',false))
 ->assign('date_format',$this->GetPreference('date_format','Y-n-j'))
 ->assign('default_category',$this->GetPreference('default_category',1))
 ->assign('email_subject',$this->GetPreference('email_subject'))
 ->assign('email_to',$this->GetPreference('email_to'))
 ->assign('expired_searchable',$this->GetPreference('expired_searchable',true))
 ->assign('expired_viewable',$this->GetPreference('expired_viewable',false))
 ->assign('expiry_interval',$this->GetPreference('expiry_interval',30))
 ->assign('time_format',$this->GetPreference('time_format','G:i'))
 ->assign('timeblock',$this->GetPreference('timeblock',News::HOURBLOCK))
;
