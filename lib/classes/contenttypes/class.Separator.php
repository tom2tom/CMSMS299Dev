<?php
#Class definition and methods for the Separator content type
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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

use const CMS_CONTENT_HIDDEN_NAME;
use function check_permission;
use function get_userid;
use function lang;

/**
 * Implements the CMS Made Simple Separator content type
 *
 * A separator is used simply for navigations to provide a visual separation between menu items.  Typically
 * as a horizontal or vertical bar.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class Separator extends \CMSMS\ContentBase
{
	public function SetProperties()
	{
		parent::SetProperties();
		$this->RemoveProperty('secure',0);
		$this->RemoveProperty('template','-1');
		$this->RemoveProperty('alias','');
		$this->RemoveProperty('title','');
		$this->RemoveProperty('menutext','');
		$this->RemoveProperty('target','');
		$this->RemoveProperty('accesskey','');
		$this->RemoveProperty('titleattribute','');
		$this->RemoveProperty('cachable',true);
		$this->RemoveProperty('page_url','');
		$this->RemoveProperty('tabindex','');
	}

	public function GetURL($rewrite = true) { return '#';  }
	public function IsViewable() { return false; }
	public function FriendlyName() { return lang('contenttype_separator'); }
	public function HasUsableLink() { return false; }
	public function WantsChildren() { return false; }
	public function RequiresAlias() { return false; }
	public function HasSearchableContent() { return false; }

	public function TabNames()
	{
		$res = [lang('main')];
		if( check_permission(get_userid(),'Manage All Content') ) $res[] = lang('options');
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

	public function ValidateData()
	{
		$this->mName = CMS_CONTENT_HIDDEN_NAME;
		return parent::ValidateData();
	}
} // class

//backward-compatibility shiv
\class_alias(Separator::class, 'Separator', false);
