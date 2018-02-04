<?php
#-------------------------------------------------------------------------
# Marigold- An admin theme for CMS Made Simple
# (c) 2012 by Author: Goran Ilic (ja@ich-mach-das.at) http://dev.cmsmadesimple.org/users/uniqu3
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005 by Ted Kulp (wishy@cmsmadesimple.org)
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.
#
#-------------------------------------------------------------------------
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

if ($changepwhash != '') {
	$smarty->assign('changepwhash', $changepwhash);
}

$smarty->assign('encoding', CmsNlsOperations::get_encoding());
$smarty->assign('config', $config);
