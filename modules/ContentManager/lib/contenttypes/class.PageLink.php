<?php
/*
Class definition and methods for Page Link content type
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace ContentManager\contenttypes;

use CMSMS\AdminUtils;
use CMSMS\Lone;
use ContentManager\ContentBase;
use function check_permission;
use function CMSMS\specialize;
use function get_userid;

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
	public function FriendlyName() : string
	{
		return $this->mod->Lang('contenttype_pagelink');
	}

	public function HasSearchableContent() : bool
	{
		return false;
	}

	public function IsCopyable() : bool
	{
		return true;
	}

	public function IsViewable() : bool
	{
		return false;
	}

// Robert Campbell: commented this out so that this page can be seen in cms_selflink
// but not sure what it's gonna mess up.
//	public function HasUsableLink() { return false; }

	public function SetProperties()
	{
		parent::SetProperties([
			['cachable', true],
			['secure', false], //deprecated property since 2.0
		]);
		$this->AddProperty('page', 3, parent::TAB_MAIN, true, true); //target-page id
		$this->AddProperty('params', 4, parent::TAB_OPTIONS, true, true);

		//Turn off caching
		$this->mCachable = false;
	}

	public function FillParams($params, $editing = false)
	{
		parent::FillParams($params, $editing);

		if (!empty($params)) {
			$parameters = ['page', 'params'];
			foreach ($parameters as $oneparam) {
				if (isset($params[$oneparam])) {
					$this->SetPropertyValue($oneparam, $params[$oneparam]);
				}
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
		if ($errors === false) {
			$errors = [];
		}

		$page = $this->GetPropertyValue('page');
		if ($page == '-1') {
			$errors[] = $this->mod->Lang('nofieldgiven', $this->mod->Lang('page'));
			$result = false;
		} else {
			// get the content type of page. TODO load using module-methods only
			$contentops = Lone::get('ContentOperations');
			$destcontent = $contentops->LoadEditableContentFromId($page);
			if (!is_object($destcontent)) {
				$errors[] = $this->mod->Lang('destinationnotfound');
				$result = false;
			} elseif ($destcontent->Type() == 'pagelink') {
				$errors[] = $this->mod->Lang('pagelink_circular');
				$result = false;
			} elseif ($destcontent->Alias() == $this->mAlias) {
				$errors[] = $this->mod->Lang('pagelink_circular');
				$result = false;
			}
		}
		return ($errors) ? $errors : false;
	}

	public function GetTabNames() : array
	{
		$res = [$this->mod->Lang('main')];
		if (check_permission(get_userid(), 'Manage All Content')) {
			$res[] = $this->mod->Lang('options');
		}
		return $res;
	}

	/**
	 * Return html to display an input element for modifying a property
	 * of this object.
	 *
	 * @param string $propname The property name
	 * @param bool $adding Whether we are in add or edit mode.
	 * @return array 3- or 4-members
	 * [0] = heart-of-label 'for="someid">text' | text
	 * [1] = popup-help | ''
	 * [2] = input element | text
	 * [3] = optional extra displayable content
	 * or empty
	 */
	public function ShowElement($propname, $adding)
	{
		$id = 'm1_';
		switch ($propname) {
		case 'page':
			$input = AdminUtils::CreateHierarchyDropdown($this->mId, (int)$this->GetPropertyValue('page'), 'page', true);
			if ($input) {
				return [
				'for="cms_hierdropdown1">'.$this->mod->Lang('destination_page'),
				'',
				$input
				];
			}
			return [];

		case 'params':
			$val = specialize($this->GetPropertyValue('params'));
			return [
			'for="addlparms">'.$this->mod->Lang('additional_params'),
			AdminUtils::get_help_tag($this->domain, 'help_link_params', $this->mod->Lang('help_title_link_params')),
			'<input type="text" id="addlparms" name="'.$id.'params" value="'.$val.'" />'
			];

		default:
			return parent::ShowElement($propname, $adding);
		}
	}

	public function EditAsArray($adding = false, $tab = 0, $showadmin = false)
	{
		switch ($tab) {
		case 0:
			return $this->display_attributes($adding);
		case 1:
			return $this->display_attributes($adding, 1);
		}
	}

	// Return an actionable URL which can be used to preview this content
	public function GetURL() : string
	{
		$pid = $this->GetPropertyValue('page');
		$contentops = Lone::get('ContentOperations');
		$destcontent = $contentops->LoadEditableContentFromId($pid); // extended ?
//OR	$destcontent = PageLoader::LoadContent($pid) always extended
//OR	$destcontent = TODO load content using module-methods only
		if (is_object($destcontent)) {
			$params = $this->GetPropertyValue('params');
			if ($params) {
				if (strpos($params, '%') === false) {
					$val = rawurlencode($params);
					$params = strtr($val, ['%26'=>'&', '%3D'=>'=']);
				}
				$url = $destcontent->GetURL(false);
				return $url . $params;
			}
			return $destcontent->GetURL();
		}
		return ''; // OR '&lt;page missing&gt;' ?
	}
}
