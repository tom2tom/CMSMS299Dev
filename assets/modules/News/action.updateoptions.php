<?php
/*
Record module settings action for CMSMS News module
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

use CMSMS\TemplateOperations;

//if (some worthy test fails) exit;
if( !$this->CheckPermission( 'Modify News Preferences' ) ) exit;
if( isset($params['cancel']) ) {
    $this->RedirectToAdminTab('settings');
}

//$this->SetPreference('allowed_upload_types',$params['allowed_upload_types']);
//$this->SetPreference('formsubmit_emailaddress',$params['formsubmit_emailaddress']);

$this->SetPreference('alert_drafts',!empty($params['alert_drafts']));
$this->SetPreference('allow_summary_wysiwyg',!empty($params['allow_summary_wysiwyg']));

$t = (int)$params['article_pagelimit'];
$t = max(5,min(50,$t));
$this->SetPreference('article_pagelimit',$t);

$this->SetPreference('clear_category',!empty($params['clear_category']));
// TODO sanitizeVal() where relevant
$this->SetPreference('date_format',trim($params['date_format']));
$this->SetPreference('default_category',(int)$params['default_category']);

$t = (int)$params['detail_returnid'];
if( $t == 0 ) { $t = null; }
$this->SetPreference('detail_returnid',$t);

$this->SetPreference('email_subject',trim($params['email_subject']));

$t = (int)$params['email_template'];
$row = $db->getRow('SELECT originator,name FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE id=?',[$t]);
$this->SetPreference('email_template',$row['originator'].'::'.$row['name']);

$this->SetPreference('email_to',trim($params['email_to']));

$t = (int)$params['expiry_interval'];
$t = max(1,min(365,$t));
$this->SetPreference('expiry_interval',$t);

$this->SetPreference('expired_searchable',!empty($params['expired_searchable']));
$this->SetPreference('expired_viewable',!empty($params['expired_viewable']));

$this->SetPreference('timeblock',(int)$params['timeblock']);

$this->CreateStaticRoutes(); // ??
$this->SetMessage($this->Lang('optionsupdated'));
$this->RedirectToAdminTab('settings');
