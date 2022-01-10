<?php
/*
DesignManager module action: export template members of the design to a templates group
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\TemplatesGroup;
use DesignManager\Design;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify Templates') ) exit;

$this->SetCurrentTab('designs');

try {
	//TODO
}
catch( Throwable $t ) {
    $this->SetError($t->GetMessage());
}
$this->RedirectToAdminTab();
