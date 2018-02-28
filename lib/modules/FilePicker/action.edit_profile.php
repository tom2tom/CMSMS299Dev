<?php
# Module: FilePicker - A CMSMS addon module to provide file picking capabilities.
# Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# This file is part of the FilePicker module.
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

use FilePicker\Profile;

if( !defined('CMS_VERSION') ) exit;
if( !$this->VisibleToAdminUser() ) exit;

if( isset($params['cancel']) ) $this->RedirectToAdminTab();

try {
    $profile_id = (int) get_parameter_value($params,'pid');
    $profile = new Profile();

    if( $profile_id > 0 ) {
        $profile = $this->_dao->loadById( $profile_id );
        if( !$profile ) throw new \LogicException('Invalid profile id passed to edit_profile action');
    }

    if( isset($params['submit']) ) {
        try {
            $profile = $profile->overrideWith( $params );
            $this->_dao->save( $profile );
            $this->RedirectToAdminTab();
        }
        catch( \FilePicker\ProfileException $e ) {
            $this->ShowErrors($this->Lang($e->GetMessage()));
        }
    }

    $smarty->assign('profile',$profile);
    echo $this->ProcessTemplate('edit_profile.tpl');
}
catch( \CmsInvalidDataException $e ) {
    $this->SetError( $this->Lang( $e->GetMessage() ) );
    $this->RedirectToAdminTab();
}
catch( \Exception $e ) {
    $this->SetError( $e->GetMessage() );
    $this->RedirectToAdminTab();
}
