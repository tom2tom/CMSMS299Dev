<?php
/*
Class for CMS Made Simple ErrorPage content type
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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
namespace ContentManager\contenttypes;

use CMSMS\AppState;
use CMSMS\FormUtils;
use CMSMS\Lone;
use ContentManager\ContentBase;

/**
 * Implements the ErrorPage content type
 *
 * @package CMS
 * @version $Revision$
 * @license GPL
 */
class ErrorPage extends ContentBase
{
	public $doAliasCheck = false;
	public $error_types = [];

	public function __construct()
	{
		parent::__construct();

		if (AppState::test(AppState::ADMIN_PAGE)) {
			$this->error_types = [
				'403' => $this->mod->Lang('403description'),
				'404' => $this->mod->Lang('404description'),
				'503' => $this->mod->Lang('503description'),
			];
		}
//		$this->doAliasCheck = false;
		$this->doAutoAliasIfEnabled = false;
		$this->mType = strtolower(get_class($this)); //TODO BAD namespace
	}

	public function FriendlyName(): string
	{
		return $this->mod->Lang('contenttype_errorpage');
	}

	public function IsDefaultPossible(): bool
	{
		return false;
	}

	public function IsSystemPage(): bool
	{
		return true;
	}

	public function HandlesAlias(): bool
	{
		return true;
	}

	public function HasUsableLink(): bool
	{
		return false;
	}

	public function WantsChildren(): bool
	{
		return false;
	}

	public function SetProperties()
	{
		parent::SetProperties([
			['accesskey', ''],
			['active', true],
			['alias', ''], //this one is a replacement
			['cachable', false],
			['extra1', ''],
			['extra2', ''],
			['extra3', ''],
			['image', ''],
			['menutext', ''],
			['page_url', ''],
			['parent', -1],
//			['searchable',false],
			['secure', false], //deprecated property since 2.0
			['showinmenu', false],
			['target', ''],
			['thumbnail', ''],
			['titleattribute', ''],
		]);
		$this->AddProperty('alias', 10, parent::TAB_MAIN, true);

		//Turn on preview
		$this->mPreview = true;
	}

	public function FillParams($params, $editing = false)
	{
		parent::FillParams($params, $editing);
		$this->mParentId = -1;
		$this->mShowInMenu = false;
		$this->mCachable = false;
		$this->mActive = true;
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
		case 'alias': // replacement property
/*//		$dropdownopts = '<option value="">'.$this->mod->Lang('none').'</option>';
			$dropdownopts = '';
			foreach ($this->error_types as $code=>$name) {
				$dropdownopts .= '<option value="error' . $code . '"';
				if ('error'.$code == $this->mAlias) {
					$dropdownopts .= ' selected="selected" ';
				}
				$dropdownopts .= ">{$name} ({$code})</option>";
			}
			$id = 'm1_';
			$outold = '<select name="'.$id.'alias">'.$dropdownopts.'</select>';
*/
//			$opts = [$this->mod->Lang('none') => ''];
			$opts = [];
			foreach ($this->error_types as $code => $name) {
				$opts["$name ($code)"] = 'error'.$code;
			}
			$sel = $this->mAlias;

			$input = FormUtils::create_select([
				'type' => 'drop',
				'name' => 'alias',
				'getid' => 'm1_',
				'htmlid' => 'errtype',
				'multiple' => false,
				'options' => $opts,
				'selectedvalue' => $sel,
			]);
			return [
			'for="errtype">'.$this->mod->Lang('error_type'),
			'',
			$input
			];

		default:
			return parent::ShowElement($propname, $adding);
		}
	}

	public function TemplateResource(): string
	{
		return ''; //TODO
	}

	public function ValidateData()
	{
//		$this->SetPropertyValue('searchable',0);
		// force not searchable.

		$errors = parent::ValidateData();

		//Do our own alias check
		if ($this->mAlias == '') {
			$errors[] = $this->mod->Lang('nofieldgiven', $this->mod->Lang('error_type'));
		} elseif (in_array($this->mAlias, $this->error_types)) {
			$errors[] = $this->mod->Lang('nofieldgiven', $this->mod->Lang('error_type'));
		} elseif ($this->mAlias != $this->mOldAlias) {
			//TODO use module-methods
			$contentops = Lone::get('ContentOperations');
			$error = $contentops->CheckAliasError($this->mAlias, $this->mId);
			if ($error) {
				if ($error == $this->mod->Lang('aliasalreadyused')) {
					$errors[] = $this->mod->Lang('errorpagealreadyinuse');
				} else {
					$errors[] = $error;
				}
			}
		}
		return $errors;
	}
}
