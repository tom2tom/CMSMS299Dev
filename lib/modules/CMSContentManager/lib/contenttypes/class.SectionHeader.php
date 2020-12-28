<?php
#Class definition and methods for Section Header content type
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

namespace CMSContentManager\contenttypes;

use CMSContentManager\ContentBase;
use function check_permission;
use function get_userid;

/**
 * Implements the Section Header content type
 *
 * Section headers are logical ways to organize content.  They usually appear in navigations, but are not navigable.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class SectionHeader extends ContentBase
{
	public function FriendlyName() { return $this->mod->Lang('contenttype_sectionheader'); }
	public function GetURL($rewrite = true) { return '#'; }
	public function HasSearchableContent() { return false; }
	public function HasUsableLink() { return false; }
	public function IsViewable() { return false; }
	public function RequiresAlias() { return true; }

	public function SetProperties()
	{
		parent::SetProperties([
			['accesskey',''],
			['cachable',true],
			['page_url',''],
			['secure',false], //deprecated property since 2.99
			['target',''],
		]);

		// Turn off caching
		$this->mCachable = false;
		$this->SetURL(''); // url will be lost when going back to a content page.
	}

	public function GetTabNames() : array
	{
		$res = [$this->mod->Lang('main')];
		if( check_permission(get_userid(),'Manage All Content') ) {
			$res[] = $this->mod->Lang('options');
		}
		return $res;
	}

	public function EditAsArray(bool $adding = false, $tab = 0, bool $showadmin = false)
	{
		switch($tab) {
		case '0':
			return $this->display_attributes($adding);
		case '1':
			return $this->display_attributes($adding,1);
		}
	}

	public function TemplateResource() : string
	{
		return ''; //TODO
	}

	public function ValidateData()
	{
		$res = parent::ValidateData();
		if( is_array($res) && $this->mId < 1 ) {
			// some error occurred..
			// reset the menu text
			// and the alias
			$this->mName = '';
			$this->mMenuText = '';
		}
		$this->mTemplateId = -1;
		return $res;
	}
}
