<?php
#...
#Copyright (C) 2009-2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of the Microtiny module for CMS Made Simple
# <http://dev.cmsmadesimple.org/projects/microtiny>
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

if( !cmsms() ) exit;
if (!$this->VisibleToAdminUser()) return;

// some default profiles

try {
  $list = MicroTiny\microtiny_profile::list_all();
  if( !is_array($list) || count($list) == 0 ) throw new CmsInvalidDataException('No profiles found');
  $profiles = array();
  foreach( $list as $one ) {
    $profiles[] = MicroTiny\microtiny_profile::load($one);
  }
  $smarty->assign('profiles',$profiles);
  echo $this->ProcessTemplate('settings.tpl');
}
catch( Exception $e ) {
  $this->SetError($e->GetMessage()); //probably useless
}
#
# EOF
#
?>
