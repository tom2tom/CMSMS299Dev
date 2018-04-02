<?php
# Ghostgum - an admin theme for CMS Made Simple
# Copyright (C) 2012 Goran Ilic <ja@ich-mach-das.at>
# Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
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

use CMSMS\internal\Smarty;

$smarty = Smarty::get_instance();

if (!empty($error)) {
	debug_buffer('Debug in the page is: ' . $error);
	$smarty->assign('error', $error);
} elseif (!empty($warningLogin)) {
	$smarty->assign('warninglogin', $warningLogin);
} elseif (!empty($acceptLogin)) {
	$smarty->assign('acceptlogin', $acceptLogin);
}

if (!empty($changepwhash)) {
	$smarty->assign('changepwhash', $changepwhash);
}

$smarty->assign('encoding', CmsNlsOperations::get_encoding());
$smarty->assign('lang', get_site_preference('frontendlang'));
$config = cms_config::get_instance();
$smarty->assign('config', $config);
