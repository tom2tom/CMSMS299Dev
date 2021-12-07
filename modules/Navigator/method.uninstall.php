<?php
/*
Navigator module uninstallation process
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\TemplateType;
use function CMSMS\log_error;

if( empty($this) || !($this instanceof Navigator)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$this->RemovePreference();
$this->DeleteTemplate();
$this->RemoveSmartyPlugin();

try {
    $types = TemplateType::load_all_by_originator('Navigator');
    foreach( $types as $type ) {
        try {
            $templates = $type->get_template_list();
            if( $templates ) {
                foreach( $templates as $tpl ) {
                    $tpl->delete();
                }
            }
        }
        catch (Throwable $t) {
            log_error($t->GetMessage(),$this->GetName().'::method.uninstall');
        }
        $type->delete();
    }
}
catch (Throwable $t) {
    log_error($t->GetMessage(),$this->GetName().'::method.uninstall');
    return $t->GetMessage();
}
