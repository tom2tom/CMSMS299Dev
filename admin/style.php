<?php
/*
Styles retriever used by some admin-themes
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

/*
This is used in some admin-themes to populate styles.
Slow and inefficient, avoid using it if possible.
*/

use CMSMS\AppState;
use CMSMS\NlsOperations;
use CMSMS\SingleItem;
use function CMSMS\sendheaders;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
AppState::set(AppState::STYLESHEET | AppState::ADMIN_PAGE);
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$cms_readfile = function($filename)
{
  $result = file_get_contents($filename);
  if( $result ) {
    echo $result;
    return TRUE;
  }
  return FALSE;
};

sendheaders('text/css','utf-8');

$style = 'style';
$dir = NlsOperations::get_language_direction();
if( $dir == 'rtl' ) $style .= '-rtl';
if( isset($_GET['ie']) ) $style .= '_ie';
$style .= '.css';

$theme = SingleItem::Theme()->themeName;
$fn = cms_join_path(__DIR__,'themes',$theme,'css',$style);
if( is_file($fn) ) $cms_readfile($fn);
$fn = cms_join_path(__DIR__,'themes',$theme,'extcss',$style);
if( is_file($fn) ) $cms_readfile($fn);

// this is crappily slow and inefficient !!
$allmodules = SingleItem::ModuleOperations()->GetLoadedModules();
if( $allmodules ) {
  foreach( $allmodules as &$object ) {
    if( !is_object($object) ) continue;
    if( $object->HasAdmin() ) echo $object->AdminStyle();
  }
  unset($object);
}

AppState::remove(AppState::ADMIN_PAGE);
AppState::remove(AppState::STYLESHEET);
