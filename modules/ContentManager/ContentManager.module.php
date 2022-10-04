<?php
/*
ContentManager - A CMSMS module to provide site-pages management.
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\AdminMenuItem;
use CMSMS\CapabilityType;
use CMSMS\HookOperations;

final class ContentManager extends CMSModule
{
	public function GetAdminDescription() { return $this->Lang('moddescription'); }
	public function GetAdminSection() { return 'content'; }
	public function GetAuthor() { return 'Robert Campbell'; }
	public function GetAuthorEmail() { return ''; }
	public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
	public function GetFriendlyName() { return $this->Lang('friendlyname'); }
	public function GetHelp() { return $this->Lang('help_module'); } // OR un-translated modhelp.htm ?
	public function GetName() { return 'ContentManager'; }
	public function GetVersion() { return '2.0'; }
	public function HasAdmin() { return true; }
	public function InstallPostMessage() { return $this->Lang('postinstall'); }
	public function IsAdminOnly() { return true; }
//	public function LazyLoadAdmin() { return true; }
//	public function LazyLoadFrontend() { return true; }
	public function MinimumCMSVersion() { return '2.999'; }
	public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
	public function UninstallPreMessage() { return $this->Lang('preuninstall'); }

	public function HasCapability($capability, $params = [])
	{
		switch ($capability) {
//abandoned			case CapabilityType::CORE_MODULE:
			case CapabilityType::SITE_SETTINGS:
				return true;
			default:
				return false;
		}
	}

	public function InitializeAdmin()
	{
		HookOperations::add_hook('ExtraSiteSettings', [$this, 'ExtraSiteSettings']);
	}

	/**
	 * Hook function to populate 'centralised' site settings UI
	 * @internal
	 * @since 2.0
	 * @return array
	 */
	public function ExtraSiteSettings()
	{
		//TODO check permission local or Site Prefs
		return [
		 'title'=> $this->Lang('settings_title'),
		 //'desc'=> 'useful text goes here', // optional useful text
		 'url'=> $this->create_action_url('', 'settings'), // if permitted
		 //optional 'text' => custom link-text | explanation e.g need permission
		];
	}

	/**
	 * Report whether the current user is authorized to edit the
	 * content page whose id is specified
	 */
	public function CanEditContent($content_id = -1)
	{
		if( $this->CheckPermission('Manage All Content') ) {
			return true;
		}
		if( $this->CheckPermission('Modify Any Page') ) {
			return true;
		}
		$pages = author_pages(get_userid(false));
		if( !$pages ) {
			return false;
		}
		// user has some edit-authority ...
		if ($content_id <= 0) {
			return true; // and so may add or clone
		}
		return in_array($content_id, $pages);
	}

	public function GetHeaderHTML()
	{
		$out = '';
		$fmt = '<link rel="stylesheet" href="%s/%s">';
		$urlpath = $this->GetModuleURLPath();
		$cssfiles = [
			'css/module.min.css'
		];
		foreach( $cssfiles as $one ) {
			$out .= sprintf($fmt, $urlpath, $one).PHP_EOL;
		}
		add_page_headtext($out, false);
	}

	public function GetAdminMenuItems()
	{
		$out = [];

		if( $this->CheckPermission('Add Pages') || $this->CheckPermission('Remove Pages') || $this->CanEditContent() ) {
			// user is entitled to see the main page in the admin navigation
			$obj = AdminMenuItem::from_module($this);
			$obj->title = $this->Lang('title_settingsmenu');
			$out[] = $obj;
		}

		if( $this->CheckPermission('Modify Site Preferences') ) {
			$obj = new AdminMenuItem();
			$obj->module = $this->GetName();
			$obj->section = 'siteadmin';
			$obj->title = $this->Lang('title_module_settings');
			$obj->description = $this->Lang('desc_module_settings');
			$obj->icon = false;
			$obj->action = 'settings';
			$obj->name = 'set';
			$out[] = $obj;
		}
		return $out;
	}
} // class
