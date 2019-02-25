<?php
#Class for CMS Made Simple ErrorPage content type
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

namespace CMSMS\contenttypes;

use function cmsms;
use function lang;

/**
 * Implements the ErrorPage content type
 *
 * @package CMS
 * @version $Revision$
 * @license GPL
 */
class ErrorPage extends Content
{
	public $doAliasCheck;
	public $error_types;

	public function __construct()
	{
		parent::__construct();

		global $CMS_ADMIN_PAGE;
		if( isset($CMS_ADMIN_PAGE) ) {
			$this->error_types = ['404' => lang('404description'),
								  '403' => lang('403description'),
								  '503' => lang('503description') ];
		}
		$this->doAliasCheck = false;
		$this->doAutoAliasIfEnabled = false;
		$this->mType = strtolower(get_class()) ;
	}

	public function HandlesAlias()
	{
		return true;
	}

	public function FriendlyName()
	{
		return lang('contenttype_errorpage');
	}

	public function SetProperties()
	{
		parent::SetProperties([
			['accesskey',''],
			['active',true],
			['alias',''], //this one is a replacement
			['cachable',false],
			['extra1',''],
			['extra2',''],
			['extra3',''],
			['image',''],
			['menutext',''],
			['page_url',''],
			['parent',-1],
//			['searchable',false],
			['secure',false],
			['showinmenu',false],
			['target',''],
			['thumbnail',''],
			['titleattribute',''],
		]);
		$this->AddProperty('alias',10,parent::TAB_MAIN,true);

		#Turn on preview
		$this->mPreview = true;
	}

	public function IsCopyable()
	{
		return false;
	}

	public function IsDefaultPossible()
	{
		return false;
	}

	public function HasUsableLink()
	{
		return false;
	}

	public function WantsChildren()
	{
		return false;
	}

	public function IsSystemPage()
	{
		return true;
	}

	public function FillParams($params, $editing = false)
	{
		parent::FillParams($params,$editing);
		$this->mParentId = -1;
		$this->mShowInMenu = false;
		$this->mCachable = false;
		$this->mActive = true;
	}

	public function display_single_element($one, $adding)
	{
		switch($one) {
		case 'alias':
			$dropdownopts = '';
			//$dropdownopts = '<option value="">'.lang('none').'</option>';
			foreach ($this->error_types as $code=>$name) {
				$dropdownopts .= '<option value="error' . $code . '"';
				if ('error'.$code == $this->mAlias) {
					$dropdownopts .= ' selected="selected" ';
				}
				$dropdownopts .= ">{$name} ({$code})</option>";
			}
			return [lang('error_type').':', '<select name="alias">'.$dropdownopts.'</select>'];
			break;

		default:
			return parent::display_single_element($one,$adding);
		}
	}

	public function TemplateResource() : string
	{
		return ''; //TODO
	}

	public function ValidateData()
	{
		// $this->SetPropertyValue('searchable',0);
		// force not searchable.

		$errors = parent::ValidateData();
		if ($errors == false) {
			$errors = [];
		}

		//Do our own alias check
		if ($this->mAlias == '') {
			$errors[] = lang('nofieldgiven', lang('error_type'));
		}
		else if (in_array($this->mAlias, $this->error_types)) {
			$errors[] = lang('nofieldgiven', lang('error_type'));
		}
		else if ($this->mAlias != $this->mOldAlias) {
			$gCms = cmsms();
			$contentops =& $gCms->GetContentOperations();
			$error = $contentops->CheckAliasError($this->mAlias, $this->mId);
			if ($error !== false) {
				if ($error == lang('aliasalreadyused')) {
					$errors[] = lang('errorpagealreadyinuse');
				}
				else {
					$errors[] = $error;
				}
			}
		}

		return (count($errors) > 0 ? $errors : false);
	}
}

//backward-compatibility shiv
\class_alias(ErrorPage::class, 'ErrorPage', false);
