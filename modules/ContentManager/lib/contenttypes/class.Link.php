<?php
/*
CMS Made Simple link content type
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

use ContentManager\ContentBase;
use function check_permission;
use function CMSMS\urlSpecialize;
use function get_userid;

/**
 * Implements the Link content type
 *
 * Links are content objects that appear in navigations and implement
 * a link to an external page or site.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class Link extends ContentBase
{
	public function FriendlyName() : string
	{
		return $this->mod->Lang('contenttype_link');
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

	public function SetProperties()
	{
		parent::SetProperties([
			['cachable', true],
			['secure', false], //deprecated property since 2.0
		]);
		$this->AddProperty('url', 3, self::TAB_MAIN, true, true);
	}

	public function FillParams($params, $editing = false)
	{
		parent::FillParams($params, $editing);

		if (isset($params)) {
			$parameters = ['url'];
			foreach ($parameters as $oneparam) {
				if (isset($params[$oneparam])) {
					$this->SetPropertyValue($oneparam, $params[$oneparam]);
				}
			}

			if (isset($params['file_url'])) {
				$this->SetPropertyValue('url', $params['file_url']);
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

		if ($this->GetPropertyValue('url') == '') {
			$errors[] = $this->mod->Lang('nofieldgiven', $this->mod->Lang('url'));
			$result = false;
		}
		return $errors ? $errors : false;
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
		switch ($propname) {
		case 'url':
			$u = urlSpecialize(''.$this->GetPropertyValue('url'));
			return [
			'for="pageurl">'.$this->mod->Lang('url'),
			'',
			'<input type="text" id="pageurl" name="m1_url" size="50" maxlength="255" value="'.$u.'" />'
			];

		default:
			return parent::ShowElement($propname, $adding);
		}
	}

	public function EditAsArray($adding = false, $tab = 0, $showadmin = false)
	{
		switch ($tab) {
		case '0':
			return $this->display_attributes($adding);
			break;
		case '1':
			return $this->display_attributes($adding, 1);
			break;
		}
	}

	public function URL() : string
	{
		return $this->GetPropertyValue('url');
	}
}
