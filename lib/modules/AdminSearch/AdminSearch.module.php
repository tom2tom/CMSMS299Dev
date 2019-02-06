<?php
# AdminSearch - A CMSMS addon module to perform database searches.
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

final class AdminSearch extends CMSModule
{
  public function GetAdminDescription() { return $this->Lang('moddescription'); }
  public function GetAdminSection() { return 'siteadmin'; }
  public function GetAuthor() { return 'Robert Campbell'; }
  public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
  public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.inc'); }
  public function GetFriendlyName()  { return $this->Lang('friendlyname');  }
  public function GetHelp() { return $this->Lang('help'); }
  public function GetVersion()  { return '1.1'; }
  public function HasAdmin() { return true; }
  public function IsAdminOnly() { return true; }
  public function LazyLoadAdmin() { return true; }
  public function LazyLoadFrontend() { return true; }
  public function MinimumCMSVersion()  { return '2.2.900'; }

  public function VisibleToAdminUser()
  {
    return $this->can_search();
  }

  protected function can_search()
  {
    return $this->CheckPermission('Use Admin Search');
  }

  public function InstallPostMessage()
  {
    return $this->Lang('postinstall');
  }

  public function UninstallPostMessage()
  {
    return $this->Lang('postuninstall');
  }

  public function DoAction($name,$id,$params,$returnid='')
  {
    $smarty = CmsApp::get_instance()->GetSmarty();
    $smarty->assign('mod',$this);
    return parent::DoAction($name,$id,$params,$returnid);
  }

  public function HasCapability($capability,$params=[])
  {
    return ($capability == CmsCoreCapabilities::ADMINSEARCH);
  }

  public function get_adminsearch_slaves()
  {
    $patn = cms_join_path(__DIR__,'lib','class.*slave.php');
    $files = glob($patn);
    if( $files ) {
      $output = [];
      foreach( $files as $onefile ) {
        $parts = explode('.',basename($onefile));
        $classname = implode('.',array_slice($parts,1,count($parts)-2));
        if( $classname == 'slave' ) continue;
        $output[] = __CLASS__.'\\'.$classname;
      }
      return $output;
    }
	return [];
  }
} // class
