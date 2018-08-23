<?php
# FilePicker module action: set default profile
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

if( !defined('CMS_VERSION') ) exit;
if( !$this->VisibleToAdminUser() ) exit;

try {
    $profile_id = (int) get_parameter_value($params,'pid');
    if( $profile_id < 1 ) throw new LogicException('Invalid profile id passed to delete_profile action');

    $profile = $this->_dao->loadById( $profile_id );
    if( !$profile ) throw new LogicException('Invalid profile id passed to delete_profile action');

    $this->_dao->setDefault( $profile );
}
catch( Exception $e ) {
    $this->SetError( $e->GetMessage() );
}
$this->RedirectToAdminTab();
