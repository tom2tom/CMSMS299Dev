<?php
#...
#Copyright (C) 2004-2018 Ted Kulp <ted@cmsmadesimple.org>
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

$CMS_ADMIN_PAGE=1;
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
check_login();

$realm = 'admin';
$key = 'help';
$out = 'NO HELP KEY SPECIFIED';
if( isset($_GET['key']) ) $key = filter_var(trim($_GET['key']),FILTER_SANITIZE_STRING);
if( strstr($key,'__') !== FALSE ) {
  list($realm,$key) = explode('__',$key,2);
}
if( strtolower($realm) == 'core' ) $realm = 'admin';
$out = CmsLangOperations::lang_from_realm($realm,$key);

echo $out;
exit;

#
# EOF
#
?>
