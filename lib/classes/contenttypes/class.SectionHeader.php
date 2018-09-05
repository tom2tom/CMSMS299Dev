<?php
#Class definition and methods for Section Header content type
#Copyright (C) 2004-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS\contenttypes;

use CMSMS\ContentBase;
use function check_permission;
use function get_userid;
use function lang;

/**
 * Implements the CMS Made Simple Section Header content type
 *
 * Section headers are logical ways to organize content.  They usually appear in navigations, but are not navigable.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class SectionHeader extends ContentBase
{
	public function FriendlyName() { return lang('contenttype_sectionheader'); }
	public function GetURL($rewrite = true) { return '#'; }
	public function HasSearchableContent() { return false; }
	public function HasUsableLink() { return false; }
	public function IsViewable() { return false; }
	public function RequiresAlias() { return true; }

	public function SetProperties()
	{
		parent::SetProperties();
		$this->RemoveProperty('secure',false);
		$this->RemoveProperty('accesskey','');
		$this->RemoveProperty('cachable',true);
		$this->RemoveProperty('target','');
		$this->RemoveProperty('page_url','');
		$this->SetURL(''); // url will be lost when going back to a content page.

		// Turn off caching
		$this->mCachable = false;
	}

	public function TabNames()
	{
		$res = [lang('main')];
		if( check_permission(get_userid(),'Manage All Content') ) {
			$res[] = lang('options');
		}
		return $res;
	}

	public function EditAsArray($adding = false, $tab = 0, $showadmin = false)
	{
		switch($tab) {
		case '0':
			return $this->display_attributes($adding);
			break;
		case '1':
			return $this->display_attributes($adding,1);
			break;
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

//backward-compatibility shiv
\class_alias(SectionHeader::class, 'SectionHeader', false);
