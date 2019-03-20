<?php
#MicroTiny module action: edit profile
#Copyright (C) 2009-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\StylesheetOperations;
use MicroTiny\Profile;

if( !cmsms() ) exit;
if (!$this->VisibleToAdminUser()) return;
$this->SetCurrentTab('settings');

try {
  $name = trim(get_parameter_value($params,'profile'));
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
      throw new CmsInvalidDataException($this->lang('error_cantchangesysprofilename'));
    }

    $profile->save();
    $this->RedirectToAdminTab();
  }

  // display data, strange formatting but it works...
  $tpl = $smarty->createTemplate($this->GetTemplateResource('admin_editprofile.tpl'),null,null,$smarty);
  $tpl->assign('profile',$name)
   ->assign('data',$profile);

  $stylesheets = ['-1'=>$this->Lang('none')] + StylesheetOperations::load_all_stylesheets(TRUE);
  $tpl->assign('stylesheets',$stylesheets);

  $tpl->display();
}
catch( Exception $e ) {
  $this->SetError($e->GetMessage());
  $this->RedirectToAdminTab();
}
