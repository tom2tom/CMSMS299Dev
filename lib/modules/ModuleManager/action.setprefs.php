<?php
# ModuleManager module action: set preferences
# Copyright (C) 2008-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
if( !$this->CheckPermission('Modify Site Preferences' ) ) exit;

$this->SetCurrentTab('prefs');

if( $config['develop_mode'] && !empty($params['reseturl']) ) {
    $this->SetPreference('module_repository',ModuleManager::_dflt_request_url);
    $this->SetMessage($this->Lang('msg_urlreset'));
    $this->RedirectToAdminTab();
}
if( isset($params['dl_chunksize']) ) $this->SetPreference('dl_chunksize',(int)trim($params['dl_chunksize']));
$latestdepends = (int)get_parameter_value($params,'latestdepends');
$this->SetPreference('latestdepends',$latestdepends);


if( $config['develop_mode'] ) {
    if( isset($params['url']) ) $this->SetPreference('module_repository',trim($params['url']));
    $disable_caching = (int)get_parameter_value($params,'disable_caching');
    $this->SetPreference('disable_caching',$disable_caching);
    $this->SetPreference('allowuninstall',(int)get_parameter_value($params,'allowuninstall'));
}
else {
    $this->SetPreference('allowuninstall',0);
}

$this->SetMessage($this->Lang('msg_prefssaved'));
$this->RedirectToAdminTab();
