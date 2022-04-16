<?php
/*
MicroTiny module action: edit profile
Copyright (C) 2009-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\StylesheetOperations;
use MicroTiny\Profile;

//if( some worthy test fails ) exit;
if (!$this->VisibleToAdminUser()) exit;
$this->SetCurrentTab('settings');

try {
  $name = trim($params['profile'] ?? '');
  if( !$name ) throw new Exception($this->Lang('error_missingparam'));

  if( isset($params['cancel']) ) {
    // handle cancel
    $this->SetInfo($this->Lang('msg_cancelled'));
    $this->RedirectToAdminTab();
  }

  // load the profile
  $profile = Profile::load($name);

  if( isset($params['submit']) ) {
    //
    // handle submit
    //

    foreach( $params as $key => $value ) {
      if( startswith($key,'profile_') ) {
        $key = substr($key,strlen('profile_'));
        $profile[$key] = $value;
      }
    }

    // check if name changed, and if object is a system object, puke
    if( isset($profile['system']) && $profile['system'] && $profile['name'] != $name ) {
      throw new LogicException($this->lang('error_cantchangesysprofilename'));
    }

    $profile->save();
    $this->RedirectToAdminTab();
  }

  // display data, strange formatting but it works...
  $tpl = $smarty->createTemplate($this->GetTemplateResource('editprofile.tpl')); //,null,null,$smarty);
  $tpl->assign('profile',$name)
   ->assign('data',$profile);

  $stylesheets = ['-1'=>$this->Lang('none')] + StylesheetOperations::get_all_stylesheets(TRUE);
  $tpl->assign('stylesheets',$stylesheets);

  $tpl->display();
}
catch (Throwable $t) {
  $this->SetError($t->GetMessage());
  $this->RedirectToAdminTab();
}
