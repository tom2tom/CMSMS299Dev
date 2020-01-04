<?php
/*
Record module settings action for CMSMS News module
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

if (!isset($gCms)) exit;
if( !$this->CheckPermission( 'Modify News Preferences' ) ) return;
if( isset($params['cancel']) ) {
    $this->RedirectToAdminTab('settings');
}

$this->SetPreference('default_category',$params['default_category']);
$this->SetPreference('date_format',trim($params['date_format']));
$t = (int)$params['expiry_interval'];
$t = max(1, min(365,$t));
$this->SetPreference('expiry_interval',$t);
$t = (int)$params['detail_returnid'];
if( $t == 0 ) $t = null;
$this->SetPreference('detail_returnid',$t);
//$this->SetPreference('allowed_upload_types', $params['allowed_upload_types']);
//$this->SetPreference('formsubmit_emailaddress', $params['formsubmit_emailaddress']);
//$this->SetPreference('email_subject',trim($params['email_subject']));
//$this->SetTemplate('email_template',$params['email_template']);
$this->SetPreference('expired_searchable',!empty($params['expired_searchable']));
$this->SetPreference('expired_viewable',!empty($params['expired_viewable']));
$this->SetPreference('alert_drafts',!empty($params['alert_drafts']));

$this->CreateStaticRoutes();
$this->SetMessage($this->Lang('optionsupdated'));
$this->RedirectToAdminTab('settings');
