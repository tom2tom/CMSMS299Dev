<?php
/*
Set type default template action for CMSMS News module.
Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\TemplateOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify News Preferences') ) return;
if( !isset($params['tpl']) ) return;

try {
	$tpl = TemplateOperations::get_template($params['tpl']);
	$tpl->set_type_dflt();
	TemplateOperations::save_template($tpl);
}
catch( Throwable $t ) {
	$this->SetError($t->getMessage());
}

$this->RedirectToAdminTab('templates');
