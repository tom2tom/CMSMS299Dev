<?php
/*
Class definition and methods for the Separator content type
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace ContentManager\contenttypes;

use ContentManager\ContentBase;
use function check_permission;
use function get_userid;

/**
 * Implements the Separator content type
 *
 * A separator is used simply for navigations to provide a visual separation between menu items.  Typically
 * as a horizontal or vertical bar.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class Separator extends ContentBase
{
	public function FriendlyName() { return $this->mod->Lang('contenttype_separator'); }
	public function GetURL($rewrite = true) { return '#';  }
	public function HasSearchableContent() { return false; }
	public function HasUsableLink() { return false; }
	public function IsViewable() { return false; }
	public function RequiresAlias() { return false; }
	public function WantsChildren() { return false; }

	public function SetProperties()
	{
		parent::SetProperties([
			['accesskey',''],
			['alias',''],
			['cachable',true],
			['menutext',''],
			['page_url',''],
			['secure',false], //deprecated property since 2.0
			['tabindex',''],
			['target',''],
			['template','-1'],
			['title',''],
			['titleattribute',''],
		]);
	}

	public function GetTabNames()
	{
		$res = [$this->mod->Lang('main')];
		if( check_permission(get_userid(),'Manage All Content') ) $res[] = $this->mod->Lang('options');
		return $res;
	}

	public function EditAsArray($adding = false, $tab = 0, $showadmin = false)
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
		$this->mName = ContentBase::CMS_CONTENT_HIDDEN_NAME;
		return parent::ValidateData();
	}
} // class
