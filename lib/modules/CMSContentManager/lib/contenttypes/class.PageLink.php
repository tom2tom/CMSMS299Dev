<?php
#Class definition and methods for Page Link content type
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CMSContentManager\Utils;
use CMSMS\ContentOperations;
use function check_permission;
use function cms_htmlentities;
use function get_userid;
use function lang;

/**
 * Implements the PageLink content type.
 *
 * This content type simply provides a way to manage additional links to internal content pages
 * that may be in another place in the page hierarchy.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class PageLink extends ContentBase
{
	public function FriendlyName() { return $this->mod->Lang('contenttype_pagelink'); }
	public function HasSearchableContent() { return false; }
	public function IsCopyable() { return true; }
	public function IsViewable() { return false; }

// calguy1000: commented this out so that this page can be seen in cms_selflink
// but not sure what it's gonna mess up.
//	public function HasUsableLink() { return false; }

	public function SetProperties()
	{
		parent::SetProperties([
			['cachable',true],
			['secure',false], //deprecated property since 2.3
		]);
		$this->AddProperty('page',3, parent::TAB_MAIN,true,true);
		$this->AddProperty('params',4, parent::TAB_OPTIONS,true,true);

		//Turn off caching
		$this->mCachable = false;
	}

	public function FillParams($params, $editing = false)
	{
		parent::FillParams($params,$editing);

		if (!empty($params)) {
			$parameters = ['page', 'params' ];
			foreach ($parameters as $oneparam) {
				if (isset($params[$oneparam])) $this->SetPropertyValue($oneparam, $params[$oneparam]);
			}
		}
	}

	public function TemplateResource() : string
	{
		return ''; //TODO
	}

	public function ValidateData()
	{
		$errors = parent::ValidateData();
		if( $errors === false ) $errors = [];

		$page = $this->GetPropertyValue('page');
		if ($page == '-1') {
			$errors[]= $this->mod->Lang('nofieldgiven', $this->mod->Lang('page'));
			$result = false;
		}

		// get the content type of page.
		else {
			$contentops = ContentOperations::get_instance();
			$destobj = $contentops->LoadContentFromID($page); //TODO ensure relevant content-object?
			if( !is_object($destobj) ) {
				$errors[] = $this->mod->Lang('destinationnotfound');
				$result = false;
			}
			else if( $destobj->Type() == 'pagelink' ) {
				$errors[] = $this->mod->Lang('pagelink_circular');
				$result = false;
			}
			else if( $destobj->Alias() == $this->mAlias ) {
				$errors[] = $this->mod->Lang('pagelink_circular');
				$result = false;
			}
		}
		return (count($errors) > 0?$errors:false);
	}

	public function GetTabNames()
	{
		$res = [lang('main')];
		if( check_permission(get_userid(),'Manage All Content') ) $res[] = lang('options');
		return $res;
	}

	public function ShowElement($propname, $adding)
	{
		$id = 'm1_';
		switch($propname) {
		case 'page':
			$tmp = Utils::CreateHierarchyDropdown($this->mId, $this->GetPropertyValue('page'), 'page', true);
			if( !$tmp ) return [$this->mod->Lang('destination_page').':',$tmp];
			break;

		case 'params':
			$val = cms_htmlentities($this->GetPropertyValue('params'));
			return [$this->mod->Lang('additional_params').':','<input type="text" name="'.$id.'params" value="'.$val.'" />'];

		default:
			return parent::ShowElement($propname,$adding);
		}
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

	public function GetURL($rewrite = true)
	{
		$page = $this->GetPropertyValue('page');
		$params = $this->GetPropertyValue('params');

		$contentops = ContentOperations::get_instance();
		$destcontent = $contentops->LoadContentFromId($page); //TODO ensure relevant content-object?
		if( is_object( $destcontent ) ) {
			$url = $destcontent->GetURL();
			return $url . $params;
		}
	}
}