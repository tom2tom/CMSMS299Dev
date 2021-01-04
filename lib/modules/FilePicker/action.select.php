<?php
/*
FilePicker module action: select
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

if( !isset($gCms) ) exit;
if( CmsApp::get_instance()->is_frontend_request() ) exit;

try {
    $name = $params['name'] ?? ''; //html element name
    $value = $params['value'] ?? ''; //html element initial value
    $type = $params['type'] ?? ''; //type of file to select
    if( !$type ) $type = FileType::IMAGE; //default mode: image selection

    $profile = $this->get_default_profile();
    if( $type ) {
        $parms = [ 'type' => $type ];
        $profile = $profile->overrideWith( $parms );
    }
    echo $this->get_html($name, $value, $profile );
}
catch( Exception $e ) {
    $this->SetError($e->GetMessage()); //probably useless here
}
return '';
