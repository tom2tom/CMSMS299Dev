<?php
#code for CMSMS
#Copyright (C) 2004-2008 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2008-2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.
#
#$Id$

$orig_memory = (function_exists('memory_get_usage') ? memory_get_usage() : 0);

$CMS_ADMIN_PAGE=1;
$CMS_TOP_MENU='main';
//$CMS_ADMIN_TITLE='adminhome';  probably intended to be some other var
$CMS_ADMIN_TITLE='mainmenu';
$CMS_EXCLUDE_FROM_RECENT=1;

require_once '..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

// if this page was accessed directly, and the secure param name is not in the URL
// but it is in the session, assume it is correct.
if( isset($_SESSION[CMS_USER_KEY]) && !isset($_GET[CMS_SECURE_PARAM_NAME]) ) $_REQUEST[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];

check_login();

include_once 'header.php';
$section = (isset($_GET['section'])) ? trim($_GET['section']) : '';
// todo: we should just be getting the html, and giving it to the theme. maybe
$themeObject->do_toppage($section);
$out = CMSMS\HookManager::do_hook_accumulate('admin_add_headtext');
if( $out ) {
    foreach( $out as $one ) {
        $one = trim($one);
        if( $one ) $themeObject->add_headtext($one);
    }
}
include_once 'footer.php';
