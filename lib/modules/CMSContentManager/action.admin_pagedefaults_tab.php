<?php
# CMSContentManager - A CMSMS module to provide page-content management
# Copyright (C) 2013-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSContentManager\Utils;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

if( isset($params['cancel']) ) {
    redirect('index.php?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]);
}

$page_prefs = Utils::get_pagedefaults();
$this->SetCurrentTab('pagedefaults');

if( isset($params['pagedefaults']) && isset($params['submit']) ) {
    // get settings from params
    $modified = 0;
    foreach( array_keys($page_prefs) as $fld ) {
        if( isset($params[$fld]) ) {
            $page_prefs[$fld] = $params[$fld];
            $modified++;
        }
    }
    if( $modified ) {
        // verify
        if( is_array($page_prefs['disallowed_types']) && in_array($page_prefs['contenttype'],$page_prefs['disallowed_types']) ) {
            $this->SetError($this->Lang('error_contenttype_disallowed'));
        }
        else {
            // save
            $this->SetPreference('page_prefs',serialize($page_prefs));
            $this->SetMessage($this->Lang('msg_prefs_saved'));
        }
    }
}

$this->RedirectToAdminTab('','','admin_settings');
