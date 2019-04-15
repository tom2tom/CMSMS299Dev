<?php

use CMSContentManager\Utils;

$tpl->assign('startform', $this->CreateFormStart ($id, 'updateoptions', $returnid))

 ->assign('title_formsubmit_emailaddress',$this->Lang('formsubmit_emailaddress'))
 ->assign('formsubmit_emailaddress',$this->GetPreference('formsubmit_emailaddress',''))

 ->assign('title_email_subject',$this->Lang('email_subject'))
 ->assign('email_subject',$this->GetPreference('email_subject',''))

 ->assign('title_email_template',$this->Lang('email_template'))
 ->assign('email_template',$this->GetTemplate('email_template'));

$categorylist = [];
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
$dbresult = $db->Execute($query);

while ($dbresult && $row = $dbresult->FetchRow()) {
    $categorylist[$row['long_name']] = $row['news_category_id'];
}

$tpl->assign('title_default_category', $this->Lang('default_category'))
 ->assign('categorylist',array_flip($categorylist))
 ->assign('default_category',$this->GetPreference('default_category'))

 ->assign('title_allowed_upload_types',$this->Lang('allowed_upload_types'))
 ->assign('allowed_upload_types',$this->GetPreference('allowed_upload_types'))

 ->assign('title_auto_create_thumbnails',$this->Lang('auto_create_thumbnails'))

 ->assign('title_hide_summary_field',$this->Lang('hide_summary_field'))
 ->assign('hide_summary_field',$this->GetPreference('hide_summary_field',0))

 ->assign('title_allow_summary_wysiwyg',$this->Lang('allow_summary_wysiwyg'))
 ->assign('allow_summary_wysiwyg',$this->GetPreference('allow_summary_wysiwyg',1))

 ->assign('title_expiry_interval',$this->Lang('expiry_interval'))
 ->assign('expiry_interval',$this->GetPreference('expiry_interval',180))

 ->assign('title_allow_fesubmit', $this->Lang('allow_fesubmit'))
 ->assign('allow_fesubmit',$this->GetPreference('allow_fesubmit',0))

 ->assign('title_expired_searchable',$this->Lang('expired_searchable'))
 ->assign('expired_searchable',$this->GetPreference('expired_searchable'))

 ->assign('title_expired_viewable',$this->Lang('expired_viewable'))
 ->assign('expired_viewable',$this->GetPreference('expired_viewable',1))
 ->assign('info_expired_viewable',$this->Lang('info_expired_viewable'));

$statusdropdown = [];
$statusdropdown[$this->Lang('draft')] = 'draft';
$statusdropdown[$this->Lang('published')] = 'published';

$tpl->assign('statuses',array_flip($statusdropdown))
 ->assign('title_fesubmit_status',$this->Lang('fesubmit_status'))
 ->assign('fesubmit_status',$this->GetPreference('fesubmit_status'))
 ->assign('input_fesubmit_status',
		$this->CreateInputDropdown($id,'fesubmit_status',$statusdropdown,-1,$this->GetPreference('fesubmit_status','draft')))

 ->assign('title_fesubmit_redirect',$this->Lang('fesubmit_redirect'))
 ->assign('fesubmit_redirect',$this->GetPreference('fesubmit_redirect'));

$str = Utils::CreateHierarchyDropdown(0,$this->GetPreference('detail_returnid',-1),$id.'detail_returnid');

$tpl->assign('title_detail_returnid',$this->Lang('title_detail_returnid'))
 ->assign('input_detail_returnid', $str)
 ->assign('info_detail_returnid',$this->Lang('info_detail_returnid'))

 ->assign('title_submission_settings',$this->Lang('title_submission_settings'))
 ->assign('title_fesubmit_settings',$this->Lang('title_fesubmit_settings'))
 ->assign('title_notification_settings',$this->Lang('title_notification_settings'))
 ->assign('title_detail_settings',$this->Lang('title_detail_settings'))
 ->assign('alert_drafts',$this->GetPreference('alert_drafts',1));
