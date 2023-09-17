<?php
/*
AdminSearch module installation process
Copyright (C) 2012-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\Lone;

if (empty($this) || !($this instanceof AdminSearch)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$this->CreatePermission('Use Admin Search',$this->Lang('perm_Use_Admin_Search'));

$groups = Lone::get('GroupOperations')->LoadGroups();

if( $groups ) {
    foreach( $groups as $one_group ) {
        $one_group->GrantPermission('Use Admin Search');
    }
}

//try to re-locate deprecated class-aliases into autoloader search-path
$bp = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'aliases';
if (is_writable($bp)) {
    $tpl1 = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.%s.php';
    $tpl2 = $bp.DIRECTORY_SEPARATOR.'class.%s.php';
    foreach (['AdminSearch_tools','AdminSearch_slave'] as $nm) {
        $fp = sprintf($tpl1, $nm);
        $tp = sprintf($tpl2, $nm);
        try {
            copy($fp, $tp);
        } catch (Throwable $t) {} //ignore error
    }
}
