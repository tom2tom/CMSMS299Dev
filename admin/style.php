<?php
#style retriever used by some admin-themes
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

/*
This is used in some admin-themes to populate styles.
Slow and inefficient, avoid using it if possible.
*/

use CMSMS\AppState;
use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
AppState::add_state(AppState::STATE_STYLESHEET);
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$cms_readfile = function($filename)
{
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

$themeObject = Utils::get_theme_object();
$theme = $themeObject->themeName;
$style = 'style';
cms_admin_sendheaders('text/css');

$dir = NlsOperations::get_language_direction();
if( $dir == 'rtl' ) $style .= '-rtl';
if( isset($_GET['ie']) ) $style .= '_ie';
$style .= '.css';

$fn = __DIR__.'/themes/'.$theme.'/css/'.$style;
if( is_file($fn) ) $cms_readfile($fn);
$fn = __DIR__.'/themes/'.$theme.'/extcss/'.$style;
if( is_file($fn) ) $cms_readfile($fn);

// this is crappily slow and inefficient !!
$allmodules = ModuleOperations::get_instance()->GetLoadedModules();
if( $allmodules ) {
  foreach( $allmodules as &$object ) {
    if( !is_object($object) ) continue;
    if( $object->HasAdmin() ) echo $object->AdminStyle();
  }
  unset($object);
}

AppState::remove_state(AppState::STATE_ADMIN_PAGE);
AppState::remove_state(AppState::STATE_STYLESHEET);
