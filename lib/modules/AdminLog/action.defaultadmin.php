<?php
/*
AdminLog module action: defaultadmin
Copyright (C) 2017-2020 CMS Made Simple Foundation <foundationcmsmadesimple.org>
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

if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) exit;

$fn = __DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.'admin_styles.css';
if( is_file($fn) ) {
    $txt = file_get_contents($fn);
    if( $txt ) {
        $txt = "<style>\n".$txt.'</style>';
        $this->AddAdminHeaderText($txt);
    }
}

include(__DIR__.DIRECTORY_SEPARATOR.'action.admin_log_tab.php');
