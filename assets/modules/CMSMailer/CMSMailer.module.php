<?php
# CMSMailer module: a wrapper around PHPMailer
# Copyright (C) 2015-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

class CMSMailer extends CMSModule
{
  private $the_mailer = null;

  public function __construct()
  {
    $this->the_mailer = new CMSMS\Mailer(FALSE);
  }

  public function GetName() { return 'CMSMailer'; }
  public function GetFriendlyName() { return $this->Lang('friendlyname'); }
  public function GetVersion() { return '6.2.14'; }
  public function MinimumCMSVersion() { return '1.99-alpha0'; }
  public function GetHelp() { return $this->Lang('help'); }
  public function GetAuthor() { return 'Robert Campbell'; }
  public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
  public function GetChangeLog() { return @file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'changelog.inc'); }
  public function IsPluginModule() { return FALSE; }
  public function HasAdmin() { return FALSE; }
  public function GetAdminSection() { return 'extensions'; }
  public function GetAdminDescription() { return $this->Lang('moddescription'); }
  public function VisibleToAdminUser() { return FALSE; }
  public function InstallPostMessage() { return $this->Lang('postinstall'); }
  public function LazyLoadFrontend() { return TRUE; }
  public function LazyLoadAdmin() { return TRUE; }
  public function InitializeFrontend() {} //prevent calling the mailer class for this
  public function InitializeAdmin() {}
  public function UninstallPostMessage() { return $this->Lang('postuninstall'); }

  //////////////////////////////////////////////////////////////////////
  //// BEGIN API SECTION
  //////////////////////////////////////////////////////////////////////

  public function GetHost()
  {
    return $this->GetSMTPHost();
  }

  public function SetHost($txt)
  {
    return $this->SetSMTPHost($txt);
  }

  public function GetPort()
  {
    return $this->GetSMTPPort();
  }

  public function SetPort($txt)
  {
    return $this->SetSMTPPort($txt);
  }

  public function GetTimeout()
  {
    return $this->GetSMTPTimeout();
  }

  public function SetTimeout($txt)
  {
    return $this->SetSMTPTimeout($txt);
  }

  public function GetUsername()
  {
    return $this->GetSMTPUsername();
  }

  public function SetUsername($txt)
  {
    return $this->SetSMTPUsername($txt);
  }

  public function GetPassword()
  {
    return $this->GetSMTPPassword();
  }

  public function SetPassword($txt)
  {
    return $this->SetSMTPPassword($txt);
  }

  public function GetSecure()
  {
    return $this->GetSMTPSecure();
  }

  public function SetSecure($txt)
  {
    return $this->SetSMTPSecure($txt);
  }

  public function __call($method,$args)
  {
    if( method_exists($this->the_mailer,$method) ) {
      return call_user_func_array([$this->the_mailer,$method],$args);
    }
    throw new CmsException('Call to invalid method '.$method.' on '.get_class($this->the_mailer).' object');
  }
} // class

