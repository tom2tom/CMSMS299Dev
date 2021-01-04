<?php
/*
-----------------------------------------------------------------------
Plugin: AdminLTE
Purpose: An Admin theme for CMS Made Simple
Author: CMS Made Simple Development Team
Plugins page: https://cmsmadesimple.org
------------------------------------------------------------------------
Based on the OneEleven/Marigold theme for CMS Made Simple tm
Equally released under GNU General Public License version 2+
Original author: Goran Ilic
------------------------------------------------------------------------
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see http://www.gnu.org/licenses/licenses.html#GPL
------------------------------------------------------------------------
*/

$gCms = \CmsApp::get_instance();
$config = $gCms->GetConfig();
$smarty = $gCms->GetSmarty();

debug_buffer('Debug in the page is: ' . $error);

if (isset($error) && $error != '') {
    $smarty->assign('error', $error);
} else if (isset($warningLogin) && $warningLogin != '') {
    $smarty->assign('warninglogin', $warningLogin);
} else if (isset($acceptLogin) && $acceptLogin != '') {
    $smarty->assign('acceptlogin', $acceptLogin);
}

if ($changepwhash != '')
    $smarty->assign('changepwhash', $changepwhash);

$smarty->assign('encoding', CmsNlsOperations::get_encoding());
$smarty->assign('config', $config);
