<?php
# CMSContentManager - A CMSMS module to provide page-content management.
# Copyright (C) 2013-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

if( isset($params['cancel']) ) {
    redirect('index.php?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]);
}

$this->SetCurrentTab('general');
$timeout = (int)$params['locktimeout'];
if( $timeout != 0 ) $timeout = max(5,min(480,$timeout));
$this->SetPreference('locktimeout',$timeout);

$timeout = (int)$params['lockrefresh'];
if( $timeout != 0 ) $timeout = max(30,min(3540,(int)$params['lockrefresh']));
$this->SetPreference('lockrefresh',$timeout);

$template_list_mode = get_parameter_value($params,'template_list_mode','designpage');
$this->SetPreference('template_list_mode',$template_list_mode);

$this->SetMessage($this->Lang('msg_prefs_saved'));
$this->RedirectToAdminTab('','','admin_settings');
