<?php
#MicroTiny: a module for CMS Made Simple to allow WYSIWYG editing using a TinyMCE-derivative
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

use MicroTiny\Utils;

class MicroTiny extends CMSModule
{
  const PROFILE_FRONTEND = '__frontend__';
  const PROFILE_ADMIN = '__admin__';

  public function GetAdminDescription() { return $this->Lang('admindescription'); }
  public function GetAuthor() { return 'Morten Poulsen'; }
  public function GetAuthorEmail() { return '&lt;morten@poulsen.org&gt;'; }
  public function GetChangeLog() { @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
  public function GetDependencies() { return ['FilePicker'=>'1.1']; }
  public function GetFriendlyName() { return $this->Lang('friendlyname'); }
  public function GetHelp() { return $this->Lang('help'); }
  public function GetName() { return 'MicroTiny'; }
  public function GetVersion(){ return '2.3'; }
  public function HasAdmin() { return true; }
  public function IsPluginModule() { return true; } //deprecated
//  public function LazyLoadAdmin() { return true; }
//  public function LazyLoadFrontend() { return true; }
  public function MinimumCMSVersion() { return '2.8.900'; }
  public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

  public function WYSIWYGGenerateHeader($selector = '',$cssname = '')
  {
    return Utils::WYSIWYGGenerateHeader($selector, $cssname);
  }

  public function HasCapability($capability, $params=[])
  {
    switch ($capability) {
      case CmsCoreCapabilities::CORE_MODULE:
      case CmsCoreCapabilities::PLUGIN_MODULE:
      case CmsCoreCapabilities::WYSIWYG_MODULE:
      case CmsCoreCapabilities::SITE_PREFERENCER:
      case CmsCoreCapabilities::USER_PREFERENCER:
        return TRUE;
    }
    return FALSE;
  }

  //TODO hook functions to populate 'centralised' site and user settings update

} // class

/**
 * Return string-form boolean corresponding to $val (for populating js boolean values)
 * @param mixed $val
 * @return string
 */
function mt_jsbool($val) : string
{
  return ( cms_to_bool($val) ) ? 'true' : 'false';
}
