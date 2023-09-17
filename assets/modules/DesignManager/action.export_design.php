<?php
/*
DesignManager module action: export design
Copyright (C) 2012-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use DesignManager\Design;
use DesignManager\design_exporter;
use function CMSMS\sanitizeVal;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Manage Designs') ) exit;

//$this->SetCurrentTab('designs');

if( !isset($params['design']) || $params['design'] == '' ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->Redirect($id,'defaultadmin'.$returnid);
}

try {
    // and the work...
    $the_design = Design::load($params['design']);
    $exporter = new design_exporter($the_design);
    $xml = $exporter->get_xml();

    // clear any output buffers.
    $handlers = ob_list_handlers();
    for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

    $fn = sanitizeVal($the_design->get_name(),CMSSAN_FILE); // OR ,CMSSAN_PATH ?
    // headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/force-download');
    header('Content-Disposition: attachment; filename='.$fn.'.xml');

    // output
    echo $xml;
    exit;
}
catch( Throwable $t ) {
    $this->SetError($t->GetMessage());
    $this->RedirectToAdminTab();
}
