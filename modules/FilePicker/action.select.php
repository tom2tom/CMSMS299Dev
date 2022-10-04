<?php
/*
FilePicker module action: select
Copyright (C) 2016-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\FileType;
use function CMSMS\is_frontend_request;

/*
Display an input-text element with ancillaries which support file picking.
Associated js is pushed into the page footer.
*/

//if( some worthy test fails ) exit;
if( is_frontend_request() ) exit;

try {
    $name = $params['name'] ?? ''; //html element name
    $value = $params['value'] ?? ''; //html element initial value
    $type = $params['type'] ?? ''; //type of file to select
    if( !$type ) {
        $parms = ['type' => FileType::IMAGE]; //default mode: image selection
    }
    elseif( is_numeric($type) ) {
        $parms = ['type' => (int)$type];
    }
    else {
        $parms = ['typename' => strtoupper(trim($type))];
    }
    $profile = $this->get_default_profile();
    $profile = $profile->overrideWith($parms);
    echo $this->get_html($name, $value, $profile );
}
catch( Throwable $t ) {
    $this->ShowErrorPage($t->GetMessage());
}
