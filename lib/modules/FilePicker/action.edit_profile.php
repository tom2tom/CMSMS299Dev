<?php
# FilePicker module action: edit profile
# Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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
use FilePicker\ProfileException;

if( !defined('CMS_VERSION') ) exit;
if( !$this->VisibleToAdminUser() ) exit;

if( isset($params['cancel']) ) $this->RedirectToAdminTab();

try {
    $profile_id = (int) get_parameter_value($params,'pid');
    $profile = new Profile();

    if( $profile_id > 0 ) {
        $profile = $this->_dao->loadById( $profile_id );
        if( !$profile ) throw new LogicException('Invalid profile id passed to edit_profile action');
    }

    if( isset($params['submit']) ) {
        try {
            $profile = $profile->overrideWith( $params );
            $this->_dao->save( $profile );
            $this->RedirectToAdminTab();
        }
        catch( ProfileException $e ) {
            $this->ShowErrors($this->Lang($e->GetMessage()));
        }
    }

//TODO ensure flexbox css for multi-row .colbox, .rowbox.flow, .boxchild

    $tpl = $smarty->createTemplate($this->GetTemplateResource('edit_profile.tpl'),null,null,$smarty);
    $tpl->assign('profile',$profile);
    $tpl->display();
    return false;
}
catch( CmsInvalidDataException $e ) {
    $this->SetError( $this->Lang( $e->GetMessage() ) );
    $this->RedirectToAdminTab();
}
catch( Exception $e ) {
    $this->SetError( $e->GetMessage() );
    $this->RedirectToAdminTab();
}
