<?php
/*
DesignManager module action: export stylesheet members of the design to a stylesheets group
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\StylesheetsGroup;
use DesignManager\Design;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Manage Stylesheets') ) exit;

$this->SetCurrentTab('designs');

try {
    if( !isset($params['design']) ) {
        throw new Exception($this->Lang('error_missingparam'));
    }

	$sql = 'SELECT D.name,D.description,M.css_id FROM '.
		CMS_DB_PREFIX.Design::TABLENAME.' D JOIN '.CMS_DB_PREFIX.Design::CSSTABLE.
		' M ON D.id = M.design_id WHERE D.id=? OR D.name=? ORDER BY D.id,M.css_order';
    $data = $db->getArray($sql,[$params['design'],$params['design']]);
    if ($data) {
		$group = null;
        foreach ($data as &$row) {
			if (!$group) {
				$group = [$row['name'],$row['description'],[]];
			}
			$group[2][] = (int)$row['css_id'];
        }
        unset($row);
		if (!empty($group[3])) {
			$name = 'Import from design '.$group[0];
			$ob = new StylesheetsGroup();
			$ob->set_name($name);
			$ob->set_description($group[1]);
			$ob->set_members($group[2]);
			$ob->save();
            $this->SetMessage($this->Lang('msg_design_migrated', $name));
		}
    	else {
            $this->SetError($this->Lang('error_design_empty'));
    	}
	}
	else {
        $this->SetError($this->Lang('error_design_empty'));
	}
}
catch( Throwable $t ) {
    $this->SetError($t->GetMessage());
}
$this->RedirectToAdminTab();
