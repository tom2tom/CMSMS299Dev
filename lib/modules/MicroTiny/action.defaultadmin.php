<?php
/*
MicroTiny module action: defaultadmin
Copyright (C) 2009-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use MicroTiny\Profile;

//if( some worthy test fails ) exit;
if(!$this->VisibleToAdminUser() ) exit;

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl')); //,null,null,$smarty);

// some default profiles

try {
  $list = Profile::list_all();
  if( !$list || !is_array($list) ) throw new Exception('No profiles found');
  $profiles = [];
  foreach( $list as $one ) {
    $profiles[] = Profile::load($one);
  }
  $tpl->assign('profiles',$profiles);
}
catch( Throwable $t ) {
  $this->ShowErrors($t->GetMessage());
  $tpl->assign('profiles',null);
}

$tpl->display();
