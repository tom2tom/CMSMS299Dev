<?php
#make relevant css available for a login page
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

//Deprecated since 2.3 - just include the relevant .css file(s) in the page header during its construction

use CMSMS\AppState;
use CMSMS\NlsOperations;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
AppState::add_state(AppState::STATE_LOGIN_PAGE);
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
//require_once("../lib/classes/class.user.inc.php");

$themeObject = Utils::get_theme_object();
$theme = $themeObject->themeName;
$defaulttheme = 'Marigold'; //TODO some sensible default

$cms_readfile = function($filename) {
  ob_start();
  echo file_get_contents($filename);
  $result = ob_get_contents();
  ob_end_clean();
  if( !empty($result) ) {
    echo $result;
    return TRUE;
  }
  return FALSE;
};

header('Content-type: text/css; charset=' . NlsOperations::get_encoding());
if (is_file(__DIR__."/themes/$theme/css/style.css")) {
    echo file_get_contents(__DIR__.'/themes/'.$theme.'/css/style.css');
}
else {
    echo file_get_contents(__DIR__.'/themes/'.$defaulttheme.'/css/style.css');
}

if (is_file(__DIR__.'/themes/'.$theme.'/extcss/style.css')) {
    $cms_readfile(__DIR__.'/themes/'.$theme.'/extcss/style.css');
}
