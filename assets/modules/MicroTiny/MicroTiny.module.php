<?php
/*
MicroTiny: a module for CMS Made Simple to allow WYSIWYG editing using a TinyMCE-derivative
Copyright (C) 2009-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\CoreCapabilities;
use CMSMS\HookOperations;
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
//public function LazyLoadAdmin() { return true; }
//public function LazyLoadFrontend() { return true; }
  public function MinimumCMSVersion() { return '2.999'; }
  public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

  public function HasCapability($capability, $params=[])
  {
    switch ($capability) {
//      case CoreCapabilities::CORE_MODULE: abandoned
      case CoreCapabilities::PLUGIN_MODULE:
      case CoreCapabilities::WYSIWYG_MODULE:
      case CoreCapabilities::SITE_SETTINGS:
      case CoreCapabilities::USER_SETTINGS:
        return true;
    }
    return false;
  }

  public function InitializeAdmin()
  {
    HookOperations::add_hook('ExtraSiteSettings', [$this, 'ExtraSiteSettings']);
    HookOperations::add_hook('ExtraUserSettings', [$this, 'ExtraUserSettings']);
  }

  /**
   * Hook function to populate 'centralised' site settings UI
   * @internal
   * @ignore
   * @since 2.3 / CMSMS 3.0
   * @return array
   */
  public function ExtraSiteSettings()
  {
    //TODO check permission local or Site Prefs
    return [
     'title'=> $this->Lang('settings_title'),
     //'desc'=> 'useful text goes here', // optional useful text
     'url'=> $this->create_action_url('', 'defaultadmin', ['activetab'=>'settings']), // if permitted
     //optional 'text' => custom link-text | explanation e.g need permission
    ];
  }

  /**
   * UNUSED Hook function to populate 'centralised' user settings UI
   * @internal
   * @ignore
   * @since 2.3 / CMSMS 3.0
   * @return array
   */
  public function ExtraUserSettings()
  {
    return []; //TODO
  }

  /**
   * Return the 'public' name of the backend editor
   * @since 2.3 / CMSMS 3.0
   */
  public function GetEditorName() : string
  {
    return $this->Lang('editor_publicname');
  }

  /**
   * Return and/or populate page-header content (js and/or css) needed to use this WYSIWYG.
   * Module-API method, used during post-action page-processing and/or by cms_init_editor plugin.
   *
   * @param string $selector Optional .querySelector()-compatible CSS selector
   * @param string $cssname Optional stylesheet name
   * @param array $params Optional expanded setup parameters
   * @return string, empty for admin pages (for which the header is populated directly)
   */
  public function WYSIWYGGenerateHeader($selector = '', $cssname = '', $params = [])
  {
    return Utils::WYSIWYGGenerateHeader($selector, $cssname, $params);
  }
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
